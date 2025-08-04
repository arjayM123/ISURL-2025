<?php
session_start();
require_once 'config/database.php';
$db = new Database();
$conn = $db->getConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                $type = $_POST['type'];
                $title = $_POST['title'];
                // If content is not set, default to empty string
                $content = isset($_POST['content']) ? $_POST['content'] : '';

                try {
                    $conn->beginTransaction();

                    // Insert news/event (content can be empty)
                    $tags_links = isset($_POST['tags_links']) ? trim($_POST['tags_links']) : '';
                    $stmt = $conn->prepare("INSERT INTO news_events (title, content, type, tags_links) VALUES (?, ?, ?, ?)");
                    $success = $stmt->execute([$title, $content, $type, $tags_links]);
                    $news_id = $conn->lastInsertId();

                    if ($success) {
                        // Only process images if any are uploaded
                        if (isset($_FILES['images']) && !empty($_FILES['images']['name'][0])) {
                            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
                            $orderPositions = isset($_POST['image_order']) ? $_POST['image_order'] : [];

                            foreach ($_FILES['images']['tmp_name'] as $key => $tmp_name) {
                                $file_type = $_FILES['images']['type'][$key];

                                if (!in_array($file_type, $allowedTypes)) {
                                    throw new Exception("Invalid file type. Allowed types: JPEG, PNG, GIF");
                                }

                                $file_content = file_get_contents($tmp_name);
                                if ($file_content === false) {
                                    throw new Exception("Error reading file");
                                }

                                $position = isset($orderPositions[$key]) ? $orderPositions[$key] : $key;

                                $stmt = $conn->prepare("INSERT INTO news_images (news_id, file_content, file_mime, order_position) VALUES (?, ?, ?, ?)");
                                $stmt->execute([$news_id, $file_content, $file_type, $position]);
                            }
                        }
                        $conn->commit();
$_SESSION['message'] = "News/Event uploaded successfully!";
$_SESSION['message_type'] = "success";
header('Location: manage_tables.php?tab=news');
exit;
                    }
                } catch (Exception $e) {
                    $conn->rollBack();
                    $_SESSION['message'] = "Error: " . $e->getMessage();
                    $_SESSION['message_type'] = "error";
                }

        }
    }
}

// Fetch active news and events
$stmt = $conn->query("SELECT * FROM news_events WHERE deleted_at IS NULL ORDER BY created_at DESC");
$news_events = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage News & Events - ISU Roxas Library</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>

<body>
    <div class="container">
        <div class="page-header">
            <a href="dashboard.php" class="back-btn">
                <i class="fas fa-arrow-left"></i>
                <span>Back to Dashboard</span>
            </a>
            <h2>Manage News & Events</h2>
        </div>

        <div class="form-container">
            <form action="" method="POST" enctype="multipart/form-data" class="upload-form">
                <div class="form-grid">
                    <div class="form-left">
                        <div class="form-group">
                            <label>Title:</label>
                            <input type="text" name="title" required>
                        </div>

                        <div class="form-group">
                            <label>Type:</label>
                            <select name="type" required>
                                <option value="news">News</option>
                                <option value="event">Event</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Content:</label>
                            <textarea name="content"></textarea>
                        </div>
                        
                    <div class="form-group">
                        <label>Hashtags & Links (optional):</label>
                        <textarea name="tags_links"
                            placeholder="#https://example.com/|example"><?php echo isset($news['tags_links']) ? htmlspecialchars($news['tags_links']) : ''; ?></textarea>
                        <small style="color:#888;">
                            Enter hashtags (start with #) and/or links, separated by spaces or new lines.<br>
                            For custom link names, use: <b>#https://link.com/|Link Name</b>
                        </small>
                    </div>
                    </div>


                    <div class="form-right">
                        <div class="form-group">
                            <label>Images:</label>
                            <div class="file-drop-area" id="dropZone">
                                <div class="file-drop-icon">
                                    <i class="fas fa-cloud-upload-alt"></i>
                                </div>
                                <div class="file-drop-content">
                                    <div class="fake-btn">Choose Images</div>
                                    <span class="file-msg">or drag and drop here</span>
                                    <span class="file-support">Supports: JPG, PNG, GIF</span>
                                </div>
                                <input type="file" name="images[]" id="fileInput" class="file-input" accept="image/*"
                                    multiple>
                            </div>

                        </div>

                                    <h2>Uploaded Images Preview</h2>
            <div id="preview" class="images-preview"></div>
            <div id="imageOrderInputs"></div>
                    </div>
                </div>

                <div class="form-actions">
                    <input type="hidden" name="action" value="add">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Create Post
                    </button>
                </div>
            </form>

        </div>
    </div>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Sortable/1.14.0/Sortable.min.js"></script>
    <script>
        const dropZone = document.getElementById('dropZone');
        const fileInput = document.getElementById('fileInput');
        const preview = document.getElementById('preview');
        const fileMsg = document.querySelector('.file-msg');

        let fileList = new DataTransfer();


        function updateFileList() {
            const newFileList = new DataTransfer();
            const wrappers = Array.from(preview.getElementsByClassName('image-wrapper'));
            const orderInputs = document.getElementById('imageOrderInputs');
            orderInputs.innerHTML = ''; // Clear existing order inputs

            wrappers.forEach((wrapper, index) => {
                const originalIndex = wrapper.querySelector('img').dataset.originalIndex;
                if (originalIndex !== undefined && fileList.files[originalIndex]) {
                    newFileList.items.add(fileList.files[originalIndex]);

                    // Add hidden input for order
                    const orderInput = document.createElement('input');
                    orderInput.type = 'hidden';
                    orderInput.name = `image_order[${originalIndex}]`;
                    orderInput.value = index;
                    orderInputs.appendChild(orderInput);
                }
            });

            fileList = newFileList;
            fileInput.files = fileList.files;
        }
        function updateImageOrder() {
            const wrappers = Array.from(preview.getElementsByClassName('image-wrapper'));
            wrappers.forEach((wrapper, index) => {
                wrapper.querySelector('.image-order').textContent = index + 1;
            });
            updateFileList();
        }

        function updateFileList() {
            const newFileList = new DataTransfer();
            const wrappers = Array.from(preview.getElementsByClassName('image-wrapper'));

            wrappers.forEach(wrapper => {
                const originalIndex = wrapper.querySelector('img').dataset.originalIndex;
                if (originalIndex !== undefined && fileList.files[originalIndex]) {
                    newFileList.items.add(fileList.files[originalIndex]);
                }
            });

            fileList = newFileList;
            fileInput.files = fileList.files;
        }

       function handleFiles(e) {
    const files = e.target.files;
    preview.innerHTML = '';
    fileList = new DataTransfer();

    [...files].forEach((file, index) => {
        if (file.type.startsWith('image/')) {
            const wrapper = document.createElement('div');
            wrapper.className = 'image-wrapper';

            const img = document.createElement('img');
            img.src = URL.createObjectURL(file);
            img.dataset.index = index;
            img.dataset.originalIndex = index;
            img.dataset.filename = file.name;

            const orderBadge = document.createElement('span');
            orderBadge.className = 'image-order';
            orderBadge.textContent = index + 1;

            const nameLabel = document.createElement('span');
            nameLabel.className = 'image-name';
            nameLabel.textContent = file.name;

            wrapper.appendChild(orderBadge);
            wrapper.appendChild(img);
            wrapper.appendChild(nameLabel);
            preview.appendChild(wrapper);

            fileList.items.add(file);
        }
    });

    fileInput.files = fileList.files;
    fileMsg.textContent = `${fileList.files.length} files selected`;
}


        function handleDrop(e) {
            preventDefaults(e);
            unhighlight(e);

            const dt = e.dataTransfer;
            const files = dt.files;

            handleFiles({ target: { files } });
        }

        function preventDefaults(e) {
            e.preventDefault();
            e.stopPropagation();
        }

        function highlight(e) {
            dropZone.classList.add('highlight');
        }

        function unhighlight(e) {
            dropZone.classList.remove('highlight');
        }

        // Event Listeners
        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            dropZone.addEventListener(eventName, preventDefaults, false);
        });

        ['dragenter', 'dragover'].forEach(eventName => {
            dropZone.addEventListener(eventName, highlight, false);
        });

        ['dragleave', 'drop'].forEach(eventName => {
            dropZone.addEventListener(eventName, unhighlight, false);
        });

        dropZone.addEventListener('drop', handleDrop, false);
        fileInput.addEventListener('change', handleFiles);
    </script>

</body>

</html>
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
        max-width: 1000px;
        margin: 20px auto;
        background: white;
        padding: 20px;
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
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        width: 100%;
        max-width: 1300px;
        margin: 0 auto;
    }

    .form-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
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

    .form-actions {
        text-align: right;
        padding-top: 1rem;
        border-top: 1px solid #dee2e6;
    }

    .form-group input[type="text"],
    .form-group select,
    .form-group textarea {
        width: 100%;
        padding: 0.75rem;
        border: 1px solid #dee2e6;
        border-radius: 4px;
        transition: all 0.3s ease;
    }

    .form-group textarea {
        height: 200px;
        resize: vertical;
    }

    .form-group input[type="text"]:focus,
    .form-group select:focus,
    .form-group textarea:focus {
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

    /* Images Preview */
    .images-preview {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
        gap: 1rem;
        margin-top: 1rem;
    }

    .images-preview img {
        width: 100%;
        height: 150px;
        object-fit: cover;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
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
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    }

    .btn-danger {
        background: #dc3545;
        color: white;
    }

    .btn-danger:hover {
        background: #c82333;
        transform: translateY(-2px);
    }

    /* News Grid */
    .news-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
        gap: 1.5rem;
    }

    .news-item {
        background: white;
        border-radius: 10px;
        overflow: hidden;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        transition: transform 0.3s ease;
    }

    .news-item:hover {
        transform: translateY(-5px);
        box-shadow: 0 6px 12px rgba(0, 0, 0, 0.15);
    }

    .news-header {
        padding: 1rem;
        border-bottom: 1px solid #dee2e6;
    }

    .news-type {
        display: inline-block;
        padding: 0.25rem 0.75rem;
        border-radius: 20px;
        font-size: 0.875rem;
        font-weight: bold;
        margin-bottom: 0.5rem;
    }

    .news-type.news {
        background: #e3f2fd;
        color: #0d47a1;
    }

    .news-type.event {
        background: #f3e5f5;
        color: #7b1fa2;
    }

    .news-content {
        padding: 1rem;
    }

    .news-images {
        display: flex;
        gap: 0.5rem;
        padding: 1rem;
        overflow-x: auto;
    }

    .news-images img {
        height: 100px;
        width: 100px;
        object-fit: cover;
        border-radius: 4px;
    }

    .item-controls {
        padding: 1rem;
        background: #f8f9fa;
        border-top: 1px solid #dee2e6;
        text-align: right;
    }

    /* Responsive Styles */
    @media (max-width: 768px) {
        .upload-form {
            padding: 1.5rem;
        }

        .news-grid {
            grid-template-columns: 1fr;
        }

        .form-grid {
            grid-template-columns: 1fr;
        }

        .form-right {
            margin-top: 1rem;
        }
    }

    .image-wrapper {
        position: relative;
        cursor: move;
    }

    .image-order {
        position: absolute;
        top: 5px;
        left: 5px;
        background: rgba(33, 108, 42, 0.8);
        color: white;
        padding: 2px 8px;
        border-radius: 12px;
        font-size: 12px;
    }

    .sortable-ghost {
        opacity: 0.4;
    }

    .images-preview {
        min-height: 100px;
        padding: 10px;
        border: 1px solid #dee2e6;
        border-radius: 8px;
        margin-top: 1rem;
    }

    .images-preview.sortable:empty {
        border: 2px dashed #dee2e6;
    }

    .highlight {
        border-color: #216c2a;
        background-color: rgba(33, 108, 42, 0.05);
    }

    .sort-controls {
        margin-top: 1rem;
        margin-bottom: 1rem;
        text-align: left;
    }

    .btn-secondary {
        background: #6c757d;
        color: white;
        margin-right: 0.5rem;
    }

    .btn-secondary:hover {
        background: #5a6268;
    }

    .image-name {
        position: absolute;
        bottom: 5px;
        left: 5px;
        right: 5px;
        background: rgba(0, 0, 0, 0.7);
        color: white;
        padding: 4px 8px;
        border-radius: 4px;
        font-size: 12px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .image-wrapper {
        position: relative;
        margin-bottom: 1rem;
    }

    .images-preview img {
        width: 100%;
        height: 150px;
        object-fit: cover;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }
</style>