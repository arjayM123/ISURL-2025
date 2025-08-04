
<?php
require_once 'config/database.php';
$db = new Database();
$conn = $db->getConnection();

$stmt = $conn->prepare("
    SELECT 
        ne.*,
        ni.id as image_id,
        ni.file_mime
    FROM news_events ne 
    LEFT JOIN news_images ni ON ne.id = ni.news_id 
    WHERE ne.deleted_at IS NULL 
    AND (ni.order_position = 0 OR ni.order_position IS NULL)
    ORDER BY ne.created_at DESC 
    LIMIT 3
");
$stmt->execute();
$news_events = $stmt->fetchAll(PDO::FETCH_ASSOC);

include '_layout.php';
?>

<style>
.container-flex {
    display: flex;
    gap: 30px;
    max-width: 1200px;
    margin: 30px auto;
    align-items: flex-start;
}
.responsive-pdf-container {
    width: 90vw;
    max-width: 900px;
    height: 100vh;
    min-height: 400px;
    border: 1px solid #ccc;
    box-sizing: border-box;
    background: #fff;
    flex: 2;
}
.responsive-pdf-container iframe {
    width: 100%;
    height: 100%;
    border: none;
    display: block;
}
.news-list {
    flex: 1;
    min-width: 220px;
    max-width: 320px;
    border-radius: 10px;
    padding: 0 12px;
    display: flex;
    flex-direction: column;
    gap: 18px;
    height: fit-content;
}
.news-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    border-radius: 8px;
    overflow: hidden;
    padding: 8px;
    margin-bottom: 8px;
}
.news-item img {
    width: 100%;
    max-width: 400px;
    height: 200px;
    object-fit: cover;
    margin-bottom: 8px;
}
.news-title {
    font-size: 1rem;
    font-weight: 600;
    color: #1e293b;
    text-align: center;
    margin-bottom: 4px;
}
@media screen and (max-width: 900px) {
    .container-flex {
        flex-direction: column;
        gap: 0;
    }
    .responsive-pdf-container {
        width: 98vw;
        height: 70vh;
        min-height: 300px;
        border-width: 1px;
    }
    .news-list {
        max-width: 100%;
        width: 100%;
        margin-top: 0;
        flex-direction: row;
        flex-wrap: wrap;
        gap: 12px;
        padding: 10px 0;
    }
    .news-item {
        flex: 1 1 45%;
        max-width: 48%;
        margin: 0 auto;
    }
}
@media screen and (max-width: 600px) {
    .news-list {
        flex-direction: column;
        gap: 10px;
        padding: 0;
    }
    .news-item {
        max-width: 100%;
    }
    .responsive-pdf-container {
        width: 98vw;
        height: 70vh;
        min-height: 300px;
        border-width: 1px;
    }
}
</style>

<div class="container-flex">
    <div class="responsive-pdf-container">
        <iframe 
            src="assets/file/LIBRARY POLICIES.pdf#toolbar=0&navpanes=0&scrollbar=1"
            allowfullscreen>
            This browser does not support PDFs. Please download the PDF to view it: 
            <a href="assets/file/LIBRARY POLICIES.pdf">Download PDF</a>.
        </iframe>
    </div>
    <div class="news-list">
        <h2>News and updates</h2>
        <?php foreach ($news_events as $news): ?>
        <a href="reports_view.php?id=<?= $news['id'] ?>" class="news-item" style="text-decoration:none;">
            <?php if (!empty($news['image_id'])): ?>
                <img src="./api/get_news_image.php?id=<?= $news['image_id'] ?>" alt="News Image">
            <?php endif; ?>
            <div class="news-title"><?= htmlspecialchars($news['title']) ?></div>
        </a>
        <?php endforeach; ?>
    </div>
</div>
<?php
include '_footer.php';