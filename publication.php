<?php
session_start();
require_once 'config/database.php';

// Create uploads directory if it doesn't exist
$upload_dir = 'assets/news_uploads';
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

$db = new Database();
$conn = $db->getConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'];

    try {
        $conn->beginTransaction();
        
        // Insert publication
        $stmt = $conn->prepare("INSERT INTO publications (title) VALUES (?)");
        $success = $stmt->execute([$title]);
        $publication_id = $conn->lastInsertId();
        
        // Check if images were uploaded
        if (!isset($_FILES['images']) || empty($_FILES['images']['name'][0])) {
            throw new Exception("No images uploaded");
        }

        $positions = isset($_POST['positions']) ? json_decode($_POST['positions'], true) : [];
        
        foreach ($_FILES['images']['tmp_name'] as $key => $tmp_name) {
            if ($_FILES['images']['error'][$key] !== UPLOAD_ERR_OK) {
                continue;
            }

            $file_name = $_FILES['images']['name'][$key];
            $file_type = $_FILES['images']['type'][$key];
            
            // Validate file type
            if (!in_array($file_type, ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'])) {
                throw new Exception("Invalid file type for $file_name. Only JPG, PNG and GIF allowed.");
            }

            // Generate unique filename
            $extension = pathinfo($file_name, PATHINFO_EXTENSION);
            $unique_filename = uniqid('pub_' . $publication_id . '_') . '.' . $extension;
            $file_path = $upload_dir . '/' . $unique_filename;

            // Move uploaded file
            if (!move_uploaded_file($tmp_name, $file_path)) {
                throw new Exception("Failed to save image: $file_name");
            }

            // Get position
            $position = isset($positions[$key]) ? (int)$positions[$key] : $key;

            // Insert image record
            $stmt = $conn->prepare("INSERT INTO publication_images 
                (publication_id, file_path, file_mime, position, original_filename) 
                VALUES (?, ?, ?, ?, ?)");
            
            if (!$stmt->execute([$publication_id, $file_path, $file_type, $position, $file_name])) {
                // Delete uploaded file if database insert fails
                unlink($file_path);
                throw new Exception("Failed to save image record: $file_name");
            }
        }

        $conn->commit();
        $_SESSION['message'] = "Publication added successfully!";
        $_SESSION['message_type'] = "success";
        header('Location: manage_tables.php?tab=publication');
        exit;
        
    } catch (Exception $e) {
        $conn->rollBack();
        $_SESSION['message'] = "Error: " . $e->getMessage();
        $_SESSION['message_type'] = "error";
    }
    
    header("Location: publication.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Publications - ISU Roxas Library</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
</head>
<body>
    <div class="container">
        <div class="header-container">
            <a href="dashboard.php" class="back-btn"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
            <h1>Manage Publications</h1>
        </div>

        <div class="form-container">
            <form action="" method="POST" enctype="multipart/form-data" id="publicationForm">
                <div class="form-group">
                    <label for="title">Title</label>
                    <input type="text" id="title" name="title" required>
                </div>

                <div class="form-group">
                    <label>Images</label>
                    <div id="file-drop-area">
                        <i class="fas fa-cloud-upload-alt"></i>
                        <p>Drag and drop images here or click to select files</p>
                        <input type="file" id="fileInput" name="images[]" multiple accept="image/*">
                    </div>
                </div>

                <div id="preview" class="images-grid">
                    <!-- Images will be displayed here -->
                </div>

                <input type="hidden" name="positions" id="positions" value="{}">

                <div class="form-group">
                    <button type="submit" class="btn btn-primary">Add Publication</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        let selectedFiles = [];

        document.addEventListener('DOMContentLoaded', function() {
            const fileDropArea = document.getElementById('file-drop-area');
            const fileInput = document.getElementById('fileInput');
            const preview = document.getElementById('preview');
            const positionsInput = document.getElementById('positions');
            const form = document.getElementById('publicationForm');
            
            // Initialize Sortable
            new Sortable(preview, {
                animation: 150,
                ghostClass: 'sortable-ghost',
                onEnd: updatePositions
            });

            // Form submission handler
            form.addEventListener('submit', function(e) {
                if (selectedFiles.length === 0) {
                    alert('Please select at least one image');
                    e.preventDefault();
                    return false;
                }
                
                // Update the file input with selected files
                const dt = new DataTransfer();
                selectedFiles.forEach(file => dt.items.add(file));
                fileInput.files = dt.files;
                return true;
            });

            function updatePositions() {
                const positions = {};
                const images = preview.querySelectorAll('.image-wrapper');
                images.forEach((img, index) => {
                    const originalIndex = parseInt(img.dataset.key);
                    positions[originalIndex] = index;
                    img.querySelector('.image-order').textContent = `Position: ${index + 1}`;
                });
                positionsInput.value = JSON.stringify(positions);
            }

            // Remove image function (now defined in the proper scope)
            window.removeImage = function(index) {
                selectedFiles.splice(index, 1);
                // Re-render the preview with updated indices
                renderPreview();
            };

            function renderPreview() {
                preview.innerHTML = '';
                selectedFiles.forEach((file, index) => {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        const wrapper = document.createElement('div');
                        wrapper.className = 'image-wrapper';
                        wrapper.dataset.key = index;

                        wrapper.innerHTML = `
                            <div class="image-container">
                                <img src="${e.target.result}" alt="${file.name}">
                                <button type="button" class="delete-btn" onclick="removeImage(${index})">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                            <div class="image-info">
                                <div class="image-order">Position: ${index + 1}</div>
                                <div class="image-filename" title="${file.name}">
                                    ${file.name.length > 20 ? file.name.substring(0, 17) + '...' : file.name}
                                </div>
                            </div>
                        `;
                        
                        preview.appendChild(wrapper);
                        updatePositions();
                    }
                    reader.readAsDataURL(file);
                });
            }

            // Drag and drop handlers
            ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
                fileDropArea.addEventListener(eventName, preventDefaults, false);
                document.body.addEventListener(eventName, preventDefaults, false);
            });

            function preventDefaults(e) {
                e.preventDefault();
                e.stopPropagation();
            }

            ['dragenter', 'dragover'].forEach(eventName => {
                fileDropArea.addEventListener(eventName, () => fileDropArea.classList.add('dragover'), false);
            });

            ['dragleave', 'drop'].forEach(eventName => {
                fileDropArea.addEventListener(eventName, () => fileDropArea.classList.remove('dragover'), false);
            });

            fileDropArea.addEventListener('drop', handleDrop, false);
            fileDropArea.addEventListener('click', (e) => {
                if (e.target === fileDropArea || e.target.tagName === 'P' || e.target.tagName === 'I') {
                    fileInput.click();
                }
            });
            
            fileInput.addEventListener('change', handleFiles);

            function handleDrop(e) {
                handleFiles({ target: { files: e.dataTransfer.files } });
            }

            function handleFiles(e) {
                const files = Array.from(e.target.files).filter(file => file.type.startsWith('image/'));
                
                if (files.length === 0) {
                    alert('Please select only image files (JPEG, PNG, GIF)');
                    return;
                }

                selectedFiles = files;
                renderPreview();
            }
        });
    </script>
</body>
</html>

<style>
    :root {
        --primary-color: #216c2a;
        --secondary-color: #2c8436;
        --border-color: #e2e8f0;
        --danger-color: #dc2626;
        --shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    }

    /* Publication Management Styles */
.container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
}

.header-container {
    display: flex;
    align-items: center;
    margin-bottom: 30px;
    padding-bottom: 15px;
    border-bottom: 2px solid var(--border-color);
}

.back-btn {
    margin-right: 20px;
    text-decoration: none;
    color: var(--primary-color);
    padding: 8px 15px;
    border-radius: 5px;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    gap: 8px;
}

.back-btn:hover {
    background-color: var(--secondary-color);
    color: white;
    text-decoration: none;
}

.header-container h1 {
    margin: 0;
    color: var(--primary-color);
    font-size: 2rem;
    font-weight: 600;
}

.form-container {
    background: white;
    padding: 30px;
    border-radius: 10px;
    box-shadow: var(--shadow);
    border: 1px solid var(--border-color);
}

.form-group {
    margin-bottom: 25px;
}

.form-group label {
    display: block;
    margin-bottom: 8px;
    font-weight: 600;
    color: var(--primary-color);
    font-size: 1.1rem;
}

.form-group input[type="text"] {
    width: 100%;
    padding: 12px 15px;
    border: 2px solid var(--border-color);
    border-radius: 8px;
    font-size: 16px;
    transition: border-color 0.3s ease;
    box-sizing: border-box;
}

.form-group input[type="text"]:focus {
    outline: none;
    border-color: var(--primary-color);
    box-shadow: 0 0 0 3px rgba(33, 108, 42, 0.1);
}

/* File Drop Area */
#file-drop-area {
    border: 3px dashed var(--border-color);
    border-radius: 10px;
    padding: 50px 20px;
    text-align: center;
    cursor: pointer;
    transition: all 0.3s ease;
    background-color: #f8fdf9;
    position: relative;
}

#file-drop-area:hover,
#file-drop-area.dragover {
    border-color: var(--primary-color);
    background-color: rgba(33, 108, 42, 0.05);
    transform: translateY(-2px);
}

#file-drop-area i {
    font-size: 48px;
    color: var(--secondary-color);
    margin-bottom: 15px;
    display: block;
}

#file-drop-area p {
    margin: 0;
    color: var(--secondary-color);
    font-size: 16px;
    font-weight: 500;
}

#file-drop-area input[type="file"] {
    display: none;
}

/* Images Grid */
.images-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
    gap: 20px;
    margin-top: 25px;
    padding: 20px 0;
}

.image-wrapper {
    border: 2px solid var(--border-color);
    border-radius: 10px;
    overflow: hidden;
    cursor: move;
    transition: all 0.3s ease;
    background: white;
    box-shadow: var(--shadow);
}

.image-wrapper:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 25px rgba(33, 108, 42, 0.15);
    border-color: var(--primary-color);
}

.image-container {
    position: relative;
    overflow: hidden;
}

.image-container img {
    width: 100%;
    height: 180px;
    object-fit: cover;
    transition: transform 0.3s ease;
}

.image-wrapper:hover .image-container img {
    transform: scale(1.05);
}

.delete-btn {
    position: absolute;
    top: 10px;
    right: 10px;
    background: rgba(220, 38, 38, 0.9);
    color: white;
    border: none;
    border-radius: 50%;
    width: 35px;
    height: 35px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.3s ease;
    font-size: 14px;
    opacity: 0.8;
}

.delete-btn:hover {
    background: var(--danger-color);
    transform: scale(1.1);
    opacity: 1;
}

.image-info {
    padding: 15px;
    background: rgba(33, 108, 42, 0.05);
    border-top: 1px solid var(--border-color);
}

.image-order {
    font-weight: 600;
    color: var(--primary-color);
    margin-bottom: 5px;
    font-size: 14px;
}

.image-filename {
    font-size: 13px;
    color: var(--secondary-color);
    word-break: break-word;
    line-height: 1.4;
}

/* Button Styles */
.btn {
    padding: 12px 25px;
    border: none;
    border-radius: 8px;
    font-size: 16px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    text-decoration: none;
    display: inline-block;
    text-align: center;
}

.btn-primary {
    background-color: var(--primary-color);
    color: white;
    box-shadow: 0 4px 15px rgba(33, 108, 42, 0.3);
}

.btn-primary:hover {
    background-color: var(--secondary-color);
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(33, 108, 42, 0.4);
}

/* Sortable Ghost Effect */
.sortable-ghost {
    opacity: 0.5;
    transform: rotate(5deg);
}

/* Alert Messages */
.alert {
    padding: 15px 20px;
    margin-bottom: 20px;
    border-radius: 8px;
    font-weight: 500;
}

.alert-error {
    background-color: rgba(220, 38, 38, 0.1);
    color: var(--danger-color);
    border: 1px solid rgba(220, 38, 38, 0.2);
}

.alert-success {
    background-color: rgba(33, 108, 42, 0.1);
    color: var(--primary-color);
    border: 1px solid rgba(33, 108, 42, 0.2);
}

/* Responsive Design */
@media (max-width: 768px) {
    .container {
        padding: 15px;
    }
    
    .form-container {
        padding: 20px;
    }
    
    .images-grid {
        grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
        gap: 15px;
    }
    
    .header-container {
        flex-direction: column;
        align-items: flex-start;
        gap: 15px;
    }
    
    .header-container h1 {
        font-size: 1.5rem;
    }
    
    #file-drop-area {
        padding: 30px 15px;
    }
    
    #file-drop-area i {
        font-size: 36px;
    }
}

@media screen and (max-width: 480px) {
    .images-grid {
        grid-template-columns: 1fr;
    }
    
    .form-group input[type="text"] {
        font-size: 16px; /* Prevent zoom on iOS */
    }
}

</style>
