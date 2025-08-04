<?php
session_start();
require_once 'config/database.php';

$db = new Database();
$conn = $db->getConnection();

// Check if ID is provided
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$id) {
    $_SESSION['message'] = "Invalid publication ID";
    $_SESSION['message_type'] = 'error';
    header('Location: manage_tables.php?tab=publication');
    exit;
}

// Fetch publication details
$stmt = $conn->prepare("SELECT * FROM publications WHERE id = ?");
$stmt->execute([$id]);
$publication = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$publication) {
    $_SESSION['message'] = "Publication not found";
    $_SESSION['message_type'] = 'error';
    header('Location: manage_tables.php?tab=publication');
    exit;
}

// Fetch existing images
$stmt = $conn->prepare("SELECT * FROM publication_images WHERE publication_id = ? ORDER BY position ASC");
$stmt->execute([$id]);
$existingImages = $stmt->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $conn->beginTransaction();
        
        // Update publication title
        $title = trim($_POST['title']);
        if (empty($title)) {
            throw new Exception("Publication title cannot be empty");
        }
        
        $stmt = $conn->prepare("UPDATE publications SET title = ? WHERE id = ?");
        $stmt->execute([$title, $id]);

        // Handle image deletions first
        if (!empty($_POST['delete_images'])) {
            $deleteImages = json_decode($_POST['delete_images'], true);
            if (is_array($deleteImages)) {
                foreach ($deleteImages as $imageId) {
                    // Get file path before deleting record
                    $stmt = $conn->prepare("SELECT file_path FROM publication_images WHERE id = ? AND publication_id = ?");
                    $stmt->execute([$imageId, $id]);
                    $image = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($image && file_exists($image['file_path'])) {
                        unlink($image['file_path']); // Delete physical file
                    }
                    
                    // Delete database record
                    $stmt = $conn->prepare("DELETE FROM publication_images WHERE id = ? AND publication_id = ?");
                    $stmt->execute([$imageId, $id]);
                }
            }
        }

        // Update positions of all remaining images
        if (!empty($_POST['positions'])) {
            $positions = json_decode($_POST['positions'], true);
            if (is_array($positions)) {
                foreach ($positions as $imageId => $position) {
                    $stmt = $conn->prepare("UPDATE publication_images 
                                        SET position = ? 
                                        WHERE id = ? AND publication_id = ?");
                    $stmt->execute([(int)$position, (int)$imageId, $id]);
                }
            }
        }

        // Handle new image uploads - Only process if files were actually uploaded
        if (isset($_FILES['images']) && !empty($_FILES['images']['name'][0])) {
            $upload_dir = 'assets/publication_uploads/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }

            // Get the current maximum position after deletions and updates
            $stmt = $conn->prepare("SELECT COALESCE(MAX(position), -1) as max_pos 
                                FROM publication_images 
                                WHERE publication_id = ?");
            $stmt->execute([$id]);
            $currentMaxPosition = (int)$stmt->fetch(PDO::FETCH_ASSOC)['max_pos'];
            
            $uploadedCount = 0;
            foreach ($_FILES['images']['tmp_name'] as $key => $tmp_name) {
                if ($_FILES['images']['error'][$key] === UPLOAD_ERR_OK && !empty($tmp_name)) {
                    $file_name = $_FILES['images']['name'][$key];
                    $file_type = $_FILES['images']['type'][$key];
                    
                    // Validate file type
                    if (!in_array($file_type, ['image/jpeg', 'image/png', 'image/gif'])) {
                        throw new Exception("Invalid file type. Only JPEG, PNG and GIF are allowed.");
                    }
                    
                    // Generate unique filename
                    $extension = pathinfo($file_name, PATHINFO_EXTENSION);
                    $unique_filename = uniqid('pub_' . $id . '_') . '.' . $extension;
                    $file_path = $upload_dir . $unique_filename;

                    if (move_uploaded_file($tmp_name, $file_path)) {
                        $newPosition = $currentMaxPosition + 1 + $uploadedCount;
                        
                        $stmt = $conn->prepare("INSERT INTO publication_images 
                            (publication_id, file_path, file_mime, position, original_filename) 
                            VALUES (?, ?, ?, ?, ?)");
                        $stmt->execute([$id, $file_path, $file_type, $newPosition, $file_name]);
                        $uploadedCount++;
                    } else {
                        throw new Exception("Failed to upload file: " . $file_name);
                    }
                }
            }
        }

        $conn->commit();
        $_SESSION['message'] = "Publication updated successfully!";
        $_SESSION['message_type'] = 'success';

        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
            strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            echo json_encode(['success' => true]);
            exit;
        }

        header('Location: manage_tables.php?tab=publication');
        exit;

    } catch (Exception $e) {
        $conn->rollBack();
        $error = $e->getMessage();
        
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
            strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => $error]);
            exit;
        }
        
        $_SESSION['message'] = $error;
        $_SESSION['message_type'] = 'error';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Publication - ISU Roxas Library</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
</head>
<body>
    <div class="container">
        <div class="header-container">
            <a href="manage_tables.php?tab=publication" class="back-btn">
                <i class="fas fa-arrow-left"></i> Back to Publications
            </a>
            <h1>Edit Publication</h1>
        </div>

        <?php if (isset($error)): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>

        <form id="editForm" method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label for="title">Publication Title</label>
                <input type="text" id="title" name="title" value="<?php echo htmlspecialchars($publication['title']); ?>" required>
            </div>

            <div class="form-group">
                <label>Publication Images</label>
                <div id="file-drop-area">
                    <i class="fas fa-cloud-upload-alt"></i>
                    <p>Drag and drop images here or click to select files</p>
                    <input type="file" id="fileInput" name="images[]" multiple accept="image/*">
                </div>
            </div>

            <div id="existingImages" class="images-grid">
                <?php foreach ($existingImages as $image): ?>
                   <div class="image-wrapper" data-id="<?php echo $image['id']; ?>">
    <div class="image-container">
        <img src="<?php echo htmlspecialchars($image['file_path']); ?>" alt="Publication page">
        <button type="button" class="delete-btn" onclick="deleteImage(<?php echo $image['id']; ?>)">
            <i class="fas fa-trash"></i>
        </button>
    </div>
    <div class="image-info">
        <span class="page-number">Page <?php echo $image['position'] + 1; ?></span>
        <span class="filename"><?php echo htmlspecialchars($image['original_filename']); ?></span>
    </div>
</div>
                <?php endforeach; ?>
            </div>

            <input type="hidden" name="delete_images" id="deleteImages" value="[]">
            <input type="hidden" name="positions" id="positions" value="{}">

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Update Publication</button>
            </div>
        </form>
    </div>

    <script>
        // Initialize Sortable
        new Sortable(document.getElementById('existingImages'), {
            animation: 150,
            onEnd: function() {
                updatePositions();
            }
        });

        // Handle image deletion
        const deletedImages = [];
        let newImageCounter = 0; // Track new images separately
        
        function deleteImage(imageId) {
            deletedImages.push(imageId);
            document.getElementById('deleteImages').value = JSON.stringify(deletedImages);
            const imageWrapper = document.querySelector(`.image-wrapper[data-id="${imageId}"]`);
            imageWrapper.remove();
            updatePositions();
        }

        // Update image positions - Fixed to only handle existing images
        function updatePositions() {
            const positions = {};
            document.querySelectorAll('.image-wrapper').forEach((wrapper, index) => {
                const imageId = wrapper.dataset.id;
                if (imageId && !wrapper.classList.contains('new-image')) { // Only existing images
                    positions[imageId] = index;
                    wrapper.querySelector('.page-number').textContent = `Page ${index + 1}`;
                } else if (wrapper.classList.contains('new-image')) {
                    // For new images, just update the display
                    wrapper.querySelector('.page-number').textContent = `New Page ${index + 1}`;
                }
            });
            document.getElementById('positions').value = JSON.stringify(positions);
        }

        // Initialize positions on page load
        updatePositions();

        // Add drag and drop functionality
        const fileDropArea = document.getElementById('file-drop-area');
        const fileInput = document.querySelector('#fileInput');

        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            fileDropArea.addEventListener(eventName, preventDefaults, false);
            document.body.addEventListener(eventName, preventDefaults, false);
        });

        ['dragenter', 'dragover'].forEach(eventName => {
            fileDropArea.addEventListener(eventName, highlight, false);
        });

        ['dragleave', 'drop'].forEach(eventName => {
            fileDropArea.addEventListener(eventName, unhighlight, false);
        });

        // Handle dropped files
        fileDropArea.addEventListener('drop', handleDrop, false);

        // Handle selected files
        fileInput.addEventListener('change', handleFiles);

        function preventDefaults(e) {
            e.preventDefault();
            e.stopPropagation();
        }

        function highlight(e) {
            fileDropArea.classList.add('highlight');
        }

        function unhighlight(e) {
            fileDropArea.classList.remove('highlight');
        }

        function handleDrop(e) {
            const dt = e.dataTransfer;
            const files = dt.files;
            // Clear the file input to avoid duplication
            fileInput.value = '';
            // Set the files to the input
            fileInput.files = files;
            handleFiles({target: {files: files}});
        }

        function handleFiles(e) {
            // Clear existing new image previews to avoid duplication
            document.querySelectorAll('.new-image').forEach(img => img.remove());
            
            const files = [...e.target.files];
            files.forEach(previewFile);
        }

        // Fixed preview function to avoid duplication
        function previewFile(file) {
            // Check if file is an image
            if (!file.type.match('image.*')) {
                alert('Only image files are allowed!');
                return;
            }

            const reader = new FileReader();
            reader.onload = function(e) {
                const wrapper = document.createElement('div');
                wrapper.className = 'image-wrapper new-image';
                wrapper.innerHTML = `
                    <img src="${e.target.result}" alt="Publication page">
                    <div class="image-info">
                        <span class="page-number">New Page</span>
                        <span class="filename">${file.name}</span>
                    </div>
                    <button type="button" class="delete-btn" onclick="removeNewImage(this)">
                        <i class="fas fa-trash"></i>
                    </button>
                `;
                document.getElementById('existingImages').appendChild(wrapper);
                updatePositions();
            }
            reader.readAsDataURL(file);
        }

        // Remove new image preview
        function removeNewImage(button) {
            const wrapper = button.closest('.image-wrapper');
            wrapper.remove();
            updatePositions();
            
            // Clear the file input if no new images remain
            if (!document.querySelector('.new-image')) {
                fileInput.value = '';
            }
        }

        // Fixed form submission
     document.getElementById('editForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData();
    
    // Add form fields
    formData.append('title', document.getElementById('title').value);
    formData.append('delete_images', document.getElementById('deleteImages').value);
    
    // Get positions of ALL images, including new ones
    const positions = {};
    document.querySelectorAll('.image-wrapper').forEach((wrapper, index) => {
        if (wrapper.classList.contains('new-image')) {
            // For new images, store their position with a temporary ID
            positions['new_' + index] = index;
        } else {
            // For existing images, store their actual ID
            const imageId = wrapper.dataset.id;
            positions[imageId] = index;
        }
    });
    
    formData.append('positions', JSON.stringify(positions));
    
    // Add new files in the correct order
    const newImageWrappers = document.querySelectorAll('.image-wrapper.new-image');
    const fileInput = document.getElementById('fileInput');
    if (fileInput.files && fileInput.files.length > 0) {
        const files = Array.from(fileInput.files);
        newImageWrappers.forEach((wrapper, index) => {
            const filename = wrapper.querySelector('.filename').textContent;
            const matchingFile = files.find(file => file.name === filename);
            if (matchingFile) {
                formData.append(`images[${index}]`, matchingFile);
            }
        });
    }

    fetch('', {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: formData
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            window.location.href = 'manage_tables.php?tab=publication';
        } else if (data.error) {
            alert(data.error);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while updating the publication');
    });
});
async function deleteImage(imageId) {
    try {
        const response = await fetch('./api/delete_image.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({ image_id: imageId })
        });

        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        const result = await response.json();
        
        if (result.success) {
            const imageWrapper = document.querySelector(`.image-wrapper[data-id="${imageId}"]`);
            if (imageWrapper) {
                imageWrapper.remove();
                updatePositions();
            }
        } else {
            throw new Error(result.error || 'Failed to delete image');
        }
    } catch (error) {
        console.error('Error deleting image:', error);
        alert('Failed to delete image: ' + error.message);
    }
}
    </script>
<style>
    :root {
        --primary-color: #216c2a;
        --secondary-color: #2c8436;
        --border-color: #e2e8f0;
        --danger-color: #dc2626;
        --shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    }

    .container {
        max-width: 1200px;
        margin: 2rem auto;
        padding: 2rem;
        background: #ffffff;
        box-shadow: 0 0 20px rgba(0, 0, 0, 0.05);
        border-radius: 12px;
    }

    .header-container {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 3rem;
        padding-bottom: 1rem;
        border-bottom: 2px solid var(--border-color);
    }

    .header-container h1 {
        color: var(--primary-color);
        font-size: 1.8rem;
        font-weight: 600;
        margin: 0;
    }

    .back-btn {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        text-decoration: none;
        color: var(--primary-color);
        font-weight: 500;
        padding: 0.5rem 1rem;
        border-radius: 6px;
        transition: all 0.3s ease;
    }

    .back-btn:hover {
        background: rgba(33, 108, 42, 0.1);
    }

    .form-group {
        margin-bottom: 2rem;
    }

    .form-group label {
        display: block;
        margin-bottom: 0.75rem;
        font-weight: 600;
        color: #374151;
    }

    input[type="text"] {
        width: 100%;
        padding: 0.75rem;
        border: 2px solid var(--border-color);
        border-radius: 6px;
        font-size: 1rem;
        transition: border-color 0.3s ease;
    }

    input[type="text"]:focus {
        outline: none;
        border-color: var(--primary-color);
        box-shadow: 0 0 0 3px rgba(33, 108, 42, 0.1);
    }

    #file-drop-area {
        border: 2px dashed var(--border-color);
        border-radius: 8px;
        padding: 2rem;
        text-align: center;
        background: #f8fafc;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    #file-drop-area:hover {
        border-color: var(--primary-color);
        background: #f0fdf4;
    }

    #file-drop-area i {
        font-size: 2rem;
        color: var(--primary-color);
        margin-bottom: 1rem;
    }

    #file-drop-area p {
        color: #64748b;
        margin: 0;
    }

    .images-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
        gap: 1.5rem;
        margin-top: 2rem;
    }

    .image-wrapper {
        position: relative;
        border: 1px solid var(--border-color);
        border-radius: 10px;
        overflow: hidden;
        box-shadow: var(--shadow);
        transition: transform 0.3s ease;
    }

    .image-wrapper:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    }

    .image-wrapper img {
        width: 100%;
        height: 200px;
        object-fit: cover;
        display: block;
    }

    .image-info {
        padding: 1rem;
        background: white;
        border-top: 1px solid var(--border-color);
    }

    .page-number {
        display: block;
        font-weight: 600;
        color: var(--primary-color);
        margin-bottom: 0.25rem;
    }

    .filename {
        display: block;
        font-size: 0.875rem;
        color: #64748b;
        word-break: break-all;
    }

    .delete-btn {
        position: absolute;
        top: 0.75rem;
        right: 0.75rem;
        background: var(--danger-color);
        color: white;
        border: none;
        border-radius: 50%;
        width: 32px;
        height: 32px;
        cursor: pointer;
        opacity: 0;
        transform: scale(0.9);
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .image-wrapper:hover .delete-btn {
        opacity: 1;
        transform: scale(1);
    }

    .delete-btn:hover {
        background: #b91c1c;
        transform: scale(1.1);
    }

    .form-actions {
        margin-top: 3rem;
        padding-top: 2rem;
        border-top: 2px solid var(--border-color);
        text-align: right;
    }

    .btn-primary {
        background: var(--primary-color);
        color: white;
        border: none;
        padding: 0.75rem 2rem;
        border-radius: 6px;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .btn-primary:hover {
        background: var(--secondary-color);
        transform: translateY(-1px);
    }

    .alert {
        padding: 1rem;
        border-radius: 6px;
        margin-bottom: 2rem;
    }

    .alert-error {
        background: #fee2e2;
        border: 1px solid #fecaca;
        color: #991b1b;
    }

    @media (max-width: 768px) {
        .container {
            padding: 1rem;
            margin: 1rem;
        }

        .header-container {
            flex-direction: column;
            gap: 1rem;
            align-items: flex-start;
        }

        .images-grid {
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
        }
    }
    
    #file-drop-area {
        border: 2px dashed var(--border-color);
        border-radius: 8px;
        padding: 2rem;
        text-align: center;
        background: #f8fafc;
        cursor: pointer;
        transition: all 0.3s ease;
        position: relative;
    }

    #file-drop-area.highlight {
        border-color: var(--primary-color);
        background: #f0fdf4;
    }

    #file-drop-area input[type="file"] {
        position: absolute;
        width: 100%;
        height: 100%;
        top: 0;
        left: 0;
        opacity: 0;
        cursor: pointer;
    }

    #file-drop-area i {
        font-size: 2.5rem;
        color: var(--primary-color);
        margin-bottom: 1rem;
        display: block;
    }

    #file-drop-area p {
        color: #64748b;
        margin: 0;
        font-size: 1rem;
    }
    .image-wrapper.new-image {
    border: 2px solid var(--primary-color);
}

.image-wrapper.new-image .delete-btn {
    opacity: 1;
    transform: scale(1);
    background: var(--danger-color);
}

.image-wrapper.new-image:hover .delete-btn {
    background: #b91c1c;
}
</style>


</body>
</html>