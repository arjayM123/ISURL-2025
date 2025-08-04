
<?php
session_start();
require_once 'config/database.php';
$db = new Database();
$conn = $db->getConnection();

// Get news ID from URL
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$id) {
    $_SESSION['message'] = "Invalid news ID";
    $_SESSION['message_type'] = "error";
    header('Location: manage_tables.php?tab=news');
    exit;
}

// Fetch news details
$stmt = $conn->prepare("SELECT * FROM news_events WHERE id = ?");
$stmt->execute([$id]);
$news = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$news) {
    $_SESSION['message'] = "News not found";
    $_SESSION['message_type'] = "error";
    header('Location: manage_tables.php?tab=news');
    exit;
}

// Fetch existing images
$stmt = $conn->prepare("SELECT * FROM news_images WHERE news_id = ? ORDER BY order_position ASC");
$stmt->execute([$id]);
$existingImages = $stmt->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $conn->beginTransaction();

        // Update news details
        $title = $_POST['title'];
        $content = isset($_POST['content']) ? $_POST['content'] : '';
        $type = $_POST['type'];
        $tags_links = isset($_POST['tags_links']) ? trim($_POST['tags_links']) : '';

        $stmt = $conn->prepare("UPDATE news_events SET title = ?, content = ?, type = ?, tags_links = ? WHERE id = ?");
        $stmt->execute([$title, $content, $type, $tags_links, $id]);

        // Handle image deletions
        if (!empty($_POST['delete_images'])) {
            $deleteImages = json_decode($_POST['delete_images'], true);
            foreach ($deleteImages as $imageId) {
                $stmt = $conn->prepare("DELETE FROM news_images WHERE id = ? AND news_id = ?");
                $stmt->execute([$imageId, $id]);
            }
        }

        // Handle new image uploads
        if (isset($_FILES['images']) && !empty($_FILES['images']['name'][0])) {
            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
            
            foreach ($_FILES['images']['tmp_name'] as $key => $tmp_name) {
                if ($_FILES['images']['error'][$key] === UPLOAD_ERR_OK) {
                    $file_type = $_FILES['images']['type'][$key];
                    
                    if (!in_array($file_type, $allowedTypes)) {
                        throw new Exception("Invalid file type. Allowed types: JPEG, PNG, GIF");
                    }

                    $file_content = file_get_contents($tmp_name);
                    if ($file_content === false) {
                        throw new Exception("Error reading file");
                    }

                    // Get max position
                    $stmt = $conn->prepare("SELECT MAX(order_position) as max_pos FROM news_images WHERE news_id = ?");
                    $stmt->execute([$id]);
                    $result = $stmt->fetch();
                    $position = ($result['max_pos'] !== null ? $result['max_pos'] : -1) + 1;

                    $stmt = $conn->prepare("INSERT INTO news_images (news_id, file_content, file_mime, order_position) VALUES (?, ?, ?, ?)");
                    $stmt->execute([$id, $file_content, $file_type, $position]);
                }
            }
        }

        $conn->commit();
        $_SESSION['message'] = "News updated successfully!";
        $_SESSION['message_type'] = "success";
        
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            echo json_encode(['success' => true]);
            exit;
        }
        
        header('Location: manage_tables.php?tab=news');
        exit;

    } catch (Exception $e) {
        $conn->rollBack();
        $_SESSION['message'] = "Error: " . $e->getMessage();
        $_SESSION['message_type'] = "error";
        
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
            exit;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit News - ISU Roxas Library</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="container">
        <div class="page-header">
            <a href="manage_tables.php?tab=news" class="back-btn">
                <i class="fas fa-arrow-left"></i>
                <span>Back to News List</span>
            </a>
            <h2>Edit News</h2>
        </div>

        <div class="form-container">
            <form action="" method="POST" enctype="multipart/form-data" class="upload-form" id="editForm">
                <div class="form-grid">
                    <div class="form-left">
                        <div class="form-group">
                            <label>Title:</label>
                            <input type="text" name="title" value="<?php echo htmlspecialchars($news['title']); ?>" required>
                        </div>

                        <div class="form-group">
                            <label>Type:</label>
                            <select name="type" required>
                                <option value="news" <?php echo $news['type'] === 'news' ? 'selected' : ''; ?>>News</option>
                                <option value="event" <?php echo $news['type'] === 'event' ? 'selected' : ''; ?>>Event</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Content:</label>
                            <textarea name="content"><?php echo htmlspecialchars($news['content']); ?></textarea>
                        </div>

                        <div class="form-group">
                            <label>Hashtags & Links (optional):</label>
                            <textarea name="tags_links" placeholder="#https://example.com/|example"><?php echo htmlspecialchars($news['tags_links']); ?></textarea>
                            <small style="color:#888;">
                                Enter hashtags (start with #) and/or links, separated by spaces or new lines.<br>
                                For custom link names, use: <b>#https://link.com/|Link Name</b>
                            </small>
                        </div>
                    </div>

                    <div class="form-right">
                        <div class="form-group">
                            <label>Add New Images:</label>
                            <div class="file-drop-area" id="dropZone">
                                <div class="file-drop-icon">
                                    <i class="fas fa-cloud-upload-alt"></i>
                                </div>
                                <div class="file-drop-content">
                                    <div class="fake-btn">Choose Images</div>
                                    <span class="file-msg">or drag and drop here</span>
                                    <span class="file-support">Supports: JPG, PNG, GIF</span>
                                </div>
                                <input type="file" name="images[]" id="fileInput" class="file-input" accept="image/*" multiple>
                            </div>
                        </div>

                        <div class="existing-images">
                            <h3>Existing Images</h3>
                            <div id="existingPreview" class="images-preview">
                                <?php foreach ($existingImages as $image): ?>
                                    <div class="image-wrapper" data-id="<?php echo $image['id']; ?>">
                                        <img src="data:<?php echo $image['file_mime']; ?>;base64,<?php echo base64_encode($image['file_content']); ?>"
                                             alt="News image">
                                        <span class="image-order"><?php echo $image['order_position'] + 1; ?></span>
                                        <button type="button" class="delete-btn" onclick="deleteImage(<?php echo $image['id']; ?>)">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <h3>New Images Preview</h3>
                        <div id="preview" class="images-preview"></div>
                        <input type="hidden" name="delete_images" id="deleteImages" value="">
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Save Changes
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

        const deletedImages = new Set();

        function deleteImage(imageId) {
            if (confirm('Are you sure you want to delete this image?')) {
                const wrapper = document.querySelector(`.image-wrapper[data-id="${imageId}"]`);
                if (wrapper) {
                    wrapper.remove();
                    deletedImages.add(imageId);
                    document.getElementById('deleteImages').value = JSON.stringify(Array.from(deletedImages));
                }
            }
        }

        // Initialize sortable for existing images
        new Sortable(document.getElementById('existingPreview'), {
            animation: 150,
            onEnd: function() {
                updateImageOrder();
            }
        });

        // Initialize sortable for new images
        new Sortable(document.getElementById('preview'), {
            animation: 150,
            onEnd: function() {
                updateImageOrder();
            }
        });
    </script>

    <style>
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
        .delete-btn {
            position: absolute;
            top: 5px;
            right: 5px;
            background: rgba(220, 53, 69, 0.8);
            color: white;
            border: none;
            border-radius: 50%;
            width: 25px;
            height: 25px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
        }

        .delete-btn:hover {
            background: #dc3545;
            transform: scale(1.1);
        }

        .existing-images {
            margin-bottom: 2rem;
        }

        .existing-images h3 {
            color: #216c2a;
            margin-bottom: 1rem;
        }
    </style>
</body>
</html>