

<?php
session_start();
require_once 'config/database.php';

// Add this function at the top
function sendJsonResponse($success, $message) {
    header('Content-Type: application/json');
    echo json_encode(['success' => $success, 'message' => $message]);
    exit;
}

$db = new Database();
$conn = $db->getConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                if (isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
                    $fileTmpPath = $_FILES['file']['tmp_name'];
                    $fileMime = mime_content_type($fileTmpPath);
                    $fileContent = file_get_contents($fileTmpPath);

                    try {
                        $stmt = $conn->prepare("INSERT INTO sliders (file_content, file_mime) VALUES (?, ?)");
                        $success = $stmt->execute([$fileContent, $fileMime]);

                        // Check if this is an AJAX request
                        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
                            strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
                            $_SESSION['message'] = 'Slider added successfully!';
                            $_SESSION['message_type'] = 'success';
                            sendJsonResponse(true, "Slider added successfully!");
                        } else {
                            // Traditional form submit
                            $_SESSION['message'] = 'Slider added successfully!';
                            $_SESSION['message_type'] = 'success';
                            header('Location: manage_tables.php?tab=sliders');
                            exit;
                        }
                    } catch (Exception $e) {
                        sendJsonResponse(false, "Error: " . $e->getMessage());
                    }
                } else {
                    sendJsonResponse(false, "No file uploaded or invalid file");
                }
                break;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Slider - ISU Roxas Library</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>

<body>
  <div class="container">


    <div class="form-container">
            <div class="page-header">
        <a href="dashboard.php" class="back-btn">
            <i class="fas fa-arrow-left"></i>
            <span>Back to Dashboard</span>
        </a>
        <h2>Manage Slider</h2>
    </div>
    <form action="" method="POST" enctype="multipart/form-data" class="upload-form">
        <div class="form-grid">
                <div class="form-group">
                    <label>File:</label>
                    <div class="file-drop-area" id="dropZone">
                        <div class="file-drop-icon">
                            <i class="fas fa-cloud-upload-alt"></i>
                        </div>
                        <div class="file-drop-content">
                            <div class="fake-btn">Browse Files</div>
                            <span class="file-msg">or drag and drop here</span>
                            <span class="file-support">Supports: Images, Videos</span>
                        </div>
                        <input type="file" name="file" id="fileInput" class="file-input" accept="image/*,video/*" required>
                    </div>
                </div>
            
                <div class="form-group">
                    <div id="preview" class="file-preview"></div>
                </div>
        </div>
        
        <div class="form-actions">
            <input type="hidden" name="action" value="add">
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-plus"></i> Upload
            </button>
        </div>
    </form>
</div>
</div>

    <style>

    /* Reset and Base Styles */
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    body {
        font-family: Arial, sans-serif;
        background: #f4f6f9;
        min-height: 100vh;
    }

    .container {
        max-width: 900px;
        margin: 20px auto;
        background:white;
    }

    /* Header Styles */
    .page-header {
        padding: 1.5rem;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    .back-btn {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        text-decoration: none;
        color: #216c2a;
        padding: 0.5rem 1rem;
        border-radius: 4px;
        transition: all 0.3s ease;
    }

    /* Form Styles */
        .form-container {
        margin-bottom: 1rem;
    }
    .upload-form {
        background: white;
        padding: 1rem;
        border-radius: 10px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        width: 100%;
        max-width: 1000px;
        margin: 0 auto;
    }
        .form-grid {
        display: grid;
        grid-template-columns: 1fr;
        gap: 2rem;
    }

    .form-left,
    .form-right {
        display: flex;
        flex-direction: column;
        gap: 1.5rem;
    }


    .form-group {
        margin-bottom: 1.5rem;
    }

    .form-group label {
        display: block;
        margin-bottom: 0.5rem;
        color: #216c2a;
        font-weight: bold;
    }

    .form-group input[type="text"],
    .form-group select {
        width: 100%;
        padding: 0.75rem;
        border: 1px solid #dee2e6;
        border-radius: 4px;
        transition: all 0.3s ease;
    }
    .form-group input[type="text"]:focus,
    .form-group select:focus {
        outline: none;
        border-color: #216c2a;
        box-shadow: 0 0 0 3px rgba(33, 108, 42, 0.1);
    }
    /* File Drop Area Styles */
    .file-drop-area {
        position: relative;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-direction: column;
        width: 100%;
        padding: 2rem;
        border: 2px dashed #ccd2d6;
        border-radius: 12px;
        transition: all 0.3s ease;
        background-color: #f8f9fa;
        cursor: pointer;
        text-align: center;
    }


    .file-drop-icon {
        font-size: 2.5rem;
        color: #216c2a;
        margin-bottom: 1rem;
        transition: transform 0.3s ease;
    }

    .file-drop-content {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 0.5rem;
    }

    .fake-btn {
        background-color: #216c2a;
        color: white;
        padding: 0.75rem 1.5rem;
        border-radius: 6px;
        font-weight: bold;
        transition: all 0.3s ease;
    }

    .file-msg {
        color: #666;
        font-size: 0.95rem;
    }

    .file-support {
        color: #999;
        font-size: 0.8rem;
    }

    .file-input {
        position: absolute;
        left: 0;
        top: 0;
        height: 100%;
        width: 100%;
        cursor: pointer;
        opacity: 0;
    }

    .file-drop-area:hover {
        border-color: #216c2a;
        background-color: rgba(33, 108, 42, 0.02);
    }

    .file-drop-area:hover .file-drop-icon {
        transform: translateY(-5px);
    }

    /* Preview Styles */
    .file-preview {
        margin-top: 1.5rem;
    }

    .file-preview img,
    .file-preview video {
        max-width: 100%;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }

    /* Button Styles */
    .btn {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.75rem 1.5rem;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        font-weight: bold;
        transition: all 0.3s ease;
    }

    .btn-primary {
        background: #216c2a;
        color: white;
    }

    .btn-primary:hover {
        background: #1a5621;
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    }

    /* Slider Grid */
    .slider-items {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
        gap: 1.5rem;
        margin-top: 2rem;
    }

    .slider-item {
        background: white;
        border-radius: 10px;
        overflow: hidden;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        transition: transform 0.3s ease;
    }

    .slider-item:hover {
        transform: translateY(-5px);
        box-shadow: 0 6px 12px rgba(0,0,0,0.15);
    }

    .item-title {
        padding: 1rem;
        border-bottom: 1px solid #dee2e6;
    }

    .item-controls {
        padding: 1rem;
        background: #f8f9fa;
        border-top: 1px solid #dee2e6;
        text-align: right;
    }

    /* Responsive Styles */
    @media (max-width: 768px) {
        .container {
            padding: 1rem;
        }
        
        .slider-items {
            grid-template-columns: 1fr;
        }
    }

    </style>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const dropZone = document.getElementById('dropZone');
    const fileInput = document.getElementById('fileInput');
    const preview = document.getElementById('preview');
    const fileMsg = document.querySelector('.file-msg');

    function preventDefaults(e) {
        e.preventDefault();
        e.stopPropagation();
    }

    ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
        dropZone.addEventListener(eventName, preventDefaults, false);
    });

    function highlight() {
        dropZone.classList.add('highlight');
    }

    function unhighlight() {
        dropZone.classList.remove('highlight');
    }

    ['dragenter', 'dragover'].forEach(eventName => {
        dropZone.addEventListener(eventName, highlight, false);
    });

    ['dragleave', 'drop'].forEach(eventName => {
        dropZone.addEventListener(eventName, unhighlight, false);
    });

    dropZone.addEventListener('drop', handleDrop, false);
    fileInput.addEventListener('change', handleFiles);

    function handleDrop(e) {
        const dt = e.dataTransfer;
        const files = dt.files;
        fileInput.files = files; // Set files to input for form submission
        handleFiles({ target: { files: files } });
    }

    function handleFiles(e) {
        const files = e.target.files;
        if (files.length > 0) {
            const file = files[0];
            fileMsg.textContent = file.name;
            showPreview(file);
        }
    }

    function showPreview(file) {
        preview.innerHTML = '';
        if (file.type.startsWith('image/')) {
            const img = document.createElement('img');
            img.src = URL.createObjectURL(file);
            preview.appendChild(img);
        } else if (file.type.startsWith('video/')) {
            const video = document.createElement('video');
            video.src = URL.createObjectURL(file);
            video.controls = true;
            preview.appendChild(video);
        }
    }
});
document.querySelector('.upload-form').addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    
    fetch('', {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Redirect with session message intact
            window.location.href = 'manage_tables.php?tab=sliders&msg=1';
        }
    })
    .catch(error => {
        console.error('Error:', error);
        window.location.href = 'manage_tables.php?tab=sliders';
    });
});
</script>
</body>

</html>