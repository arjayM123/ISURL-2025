
<?php
// library_publication.php - Newsletter Detail Page
include 'config/database.php';
include '_layout.php';

$db = new Database();
$conn = $db->getConnection();

// Get the publication ID from URL parameter
$publication_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($publication_id <= 0) {
    die("Invalid publication ID");
}

// Fetch publication details
$stmt = $conn->prepare("SELECT * FROM publications WHERE id = ?");
$stmt->execute([$publication_id]);
$publication = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$publication) {
    die("Publication not found");
}

// Fetch associated images for this publication ordered by position
$stmt = $conn->prepare("
    SELECT id, publication_id, file_mime as mime_type, position, 
           created_at, original_filename as original_name, file_path
    FROM publication_images 
    WHERE publication_id = ? 
    ORDER BY position ASC
");
$stmt->execute([$publication_id]);
$images = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($publication['title']); ?> - Newsletter</title>
    <!-- Your existing CSS here -->
</head>
<body>
    <div class="container">
        <a href="javascript:history.back()" class="back-button">← Back to Newsletter</a>
        <div class="header">
            <h1><?php echo htmlspecialchars($publication['title']); ?></h1>
            <div class="meta">
                <?php echo date('F j, Y', strtotime($publication['created_at'])); ?>
                <?php if (count($images) > 0): ?>
                    • <?php echo count($images); ?> Image<?php echo count($images) > 1 ? 's' : ''; ?>
                <?php endif; ?>
            </div>
        </div>
        <?php if (!empty($images)): ?>
            <div class="images-container">
                <?php foreach ($images as $index => $image): ?>
                    <div class="image-item">
                        <img src="<?php echo htmlspecialchars($image['file_path']); ?>"
                             alt="<?php echo htmlspecialchars($image['original_name']); ?>"
                             onclick="openModal(<?php echo $index; ?>)"
                             data-index="<?php echo $index; ?>">
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="no-images">
                <h3>No Images Available</h3>
                <p>This newsletter issue doesn't have any images to display.</p>
            </div>
        <?php endif; ?>
    </div>

    <!-- Modal for full-size image viewing -->
    <?php if (!empty($images)): ?>
    <div id="imageModal" class="modal">
        <span class="close" onclick="closeModal()">&times;</span>
        <?php if (count($images) > 1): ?>
            <button class="modal-nav prev" onclick="changeImage(-1)">❮</button>
            <button class="modal-nav next" onclick="changeImage(1)">❯</button>
        <?php endif; ?>
        <img class="modal-content" id="modalImage">
        <div class="modal-info" style="text-align:center;color:#fff;margin-top:10px;">
            <span id="modalImageName"></span>
        </div>
    </div>
    <script>
        const images = <?php echo json_encode($images); ?>;
        let currentImageIndex = 0;

        function openModal(index) {
            currentImageIndex = index;
            const modal = document.getElementById('imageModal');
            const modalImg = document.getElementById('modalImage');
            const modalName = document.getElementById('modalImageName');
            modal.style.display = 'block';
            modalImg.src = images[index].file_path;
            modalName.textContent = images[index].original_name;
        }

        function closeModal() {
            document.getElementById('imageModal').style.display = 'none';
        }

        function changeImage(direction) {
            currentImageIndex += direction;
            if (currentImageIndex < 0) currentImageIndex = images.length - 1;
            if (currentImageIndex >= images.length) currentImageIndex = 0;
            const modalImg = document.getElementById('modalImage');
            const modalName = document.getElementById('modalImageName');
            modalImg.src = images[currentImageIndex].file_path;
            modalName.textContent = images[currentImageIndex].original_name;
        }

        window.onclick = function(event) {
            const modal = document.getElementById('imageModal');
            if (event.target == modal) {
                closeModal();
            }
        }
    </script>
    <?php endif; ?>
</body>
</html>


 <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            background-color: #f5f5f5;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background: linear-gradient(135deg, #226c2a, #2d8f37);
            color: white;
            padding: 2rem;
            margin-bottom: 2rem;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            text-align: center;
        }
        .header h1 {
            font-size: 2.5rem;
            margin-bottom: 0.5rem;
        }
        .header .meta {
            font-size: 1.1rem;
            opacity: 0.9;
        }
        .back-button {
            display: inline-block;
            background-color: #226c2a;
            color: white;
            padding: 12px 24px;
            text-decoration: none;
            border-radius: 8px;
            margin-bottom: 2rem;
            transition: background-color 0.3s;
            font-weight: 500;
        }
        .back-button:hover {
            background-color: #1a5520;
        }
        .images-container {
            display: flex;
            flex-direction: column;
            gap: 2rem;
        }
        .image-item {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            transition: transform 0.3s, box-shadow 0.3s;
            position: relative;
        }
        .image-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.15);
        }
        .image-item img {
            width: 100%;
            height: auto;
            display: block;
            cursor: pointer;
        }
        .image-order-label {
            position: absolute;
            top: 8px;
            left: 8px;
            background: rgba(33,108,42,0.8);
            color: #fff;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 12px;
        }
        .image-name {
            position: absolute;
            bottom: 8px;
            left: 8px;
            right: 8px;
            background: rgba(0,0,0,0.7);
            color: #fff;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 11px;
            word-break: break-all;
        }
        .no-images {
            text-align: center;
            color: #666;
            font-style: italic;
            padding: 4rem 2rem;
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        .no-images h3 {
            margin-bottom: 1rem;
            color: #999;
        }
        /* Modal for full-size image viewing */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.9);
        }
        .modal-content {
            margin: auto;
            display: block;
            width: 90%;
            max-width: 1200px;
            max-height: 90%;
            object-fit: contain;
            margin-top: 2%;
        }
        .close {
            position: absolute;
            top: 20px;
            right: 40px;
            color: #f1f1f1;
            font-size: 40px;
            font-weight: bold;
            transition: 0.3s;
            cursor: pointer;
            z-index: 1001;
        }
        .close:hover {
            color: #bbb;
        }
        .modal-nav {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            background: rgba(0,0,0,0.5);
            color: white;
            border: none;
            font-size: 30px;
            padding: 10px 15px;
            cursor: pointer;
            border-radius: 5px;
            transition: background 0.3s;
        }
        .modal-nav:hover {
            background: rgba(0,0,0,0.8);
        }
        .prev {
            left: 20px;
        }
        .next {
            right: 20px;
        }
        @media (max-width: 768px) {
            .container {
                padding: 15px;
            }
            .header {
                padding: 1.5rem;
            }
            .header h1 {
                font-size: 2rem;
            }
            .image-info {
                padding: 1rem;
            }
            .modal-content {
                width: 95%;
                margin-top: 5%;
            }
            .close {
                right: 20px;
                font-size: 30px;
            }
        }
    </style>