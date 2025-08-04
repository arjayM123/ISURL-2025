
<?php
// Include database connection
include 'config/database.php';
include '_layout.php';

$db = new Database();
$conn = $db->getConnection();

// Get the news/event ID from the query string
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Fetch the news/event details
$stmt = $conn->prepare("
    SELECT * FROM news_events 
    WHERE id = :id AND deleted_at IS NULL
    LIMIT 1
");
$stmt->execute(['id' => $id]);
$item = $stmt->fetch(PDO::FETCH_ASSOC);

// Fetch images for this news/event
$stmtImg = $conn->prepare("
    SELECT id, file_mime 
    FROM news_images 
    WHERE news_id = :id 
    ORDER BY order_position ASC
");
$stmtImg->execute(['id' => $id]);
$images = $stmtImg->fetchAll(PDO::FETCH_ASSOC);

$idListStmt = $conn->query("SELECT id FROM news_events WHERE deleted_at IS NULL ORDER BY created_at DESC");
$idList = $idListStmt->fetchAll(PDO::FETCH_COLUMN);

// Find current index
$currentIndex = array_search($id, $idList);

function renderTagsLinks($tags_links) {
    $out = '';
    $items = preg_split('/[\s\r\n]+/', $tags_links);
    foreach ($items as $item) {
        $item = trim($item);
        if (!$item) continue;
        if (strpos($item, '#') === 0) {
            $value = substr($item, 1);
            // Custom link name with |
            if (strpos($value, '|') !== false) {
                list($url, $name) = explode('|', $value, 2);
                if (filter_var($url, FILTER_VALIDATE_URL)) {
                    $out .= '<div class="tag-link-row"><a href="' . htmlspecialchars($url) . '" target="_blank" rel="noopener" class="tag-link">' . htmlspecialchars($name) . '</a></div>';
                }
            } elseif (filter_var($value, FILTER_VALIDATE_URL)) {
                // # followed by a URL: display as a link with # in front, URL as text
                $out .= '<div class="tag-link-row"><a href="' . htmlspecialchars($value) . '" target="_blank" rel="noopener" class="tag-link">' . htmlspecialchars($value) . '</a></div>';
            } else {
                // # followed by text: display as plain hashtag
                $out .= '<div class="tag-link-row hashtag">' . htmlspecialchars($value) . '</div>';
            }
        } elseif (filter_var($item, FILTER_VALIDATE_URL)) {
            // Plain link, show as URL
            $out .= '<div class="tag-link-row"><a href="' . htmlspecialchars($item) . '" target="_blank" rel="noopener" class="tag-link">' . htmlspecialchars($item) . '</a></div>';
        }
    }
    return $out;
}
?>

<div class="container">
    <?php if ($item): ?>
        <div class="news-view">
            <h2><?php echo htmlspecialchars($item['title']); ?></h2>
            <div class="news-meta">
                <span class="news-type"><?php echo ucfirst($item['type']); ?></span>
                <span class="news-date"><?php echo date('M d, Y', strtotime($item['created_at'])); ?></span>
            </div>
            <?php if ($images): ?>
                <div class="news-images">
                    <?php foreach ($images as $img): ?>
                        <img src="./api/get_news_image.php?id=<?php echo $img['id']; ?>"
                             alt="News Image"
                             style="max-width: 100%; margin-bottom: 1rem;"
                             onerror="this.src='assets/img/default.jpg'">
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            <div class="news-content">
                <?php echo nl2br(htmlspecialchars($item['content'])); ?>
            </div>
            <?php if (!empty($item['tags_links'])): ?>
            <div class="tags-links-bar" style="margin-top:2rem;">
                <?php echo renderTagsLinks($item['tags_links']); ?>
            </div>
            <?php endif; ?>
            <div style="margin-top:2rem;">
                <a href="index.php#news-events" class="back-link">&larr; Back to News & Updates</a>
            </div>
        </div>
    <?php else: ?>
        <div class="news-view">
            <h2>Post Not Found</h2>
            <p>The news or event you are looking for does not exist.</p>
            <div style="margin-top:2rem;">
                <a href="index.php#news-events" class="back-link">&larr; Back to News & Updates</a>
            </div>
        </div>
    <?php endif; ?>
</div>
<?php include '_footer.php'; ?>
<style>
.container {
    max-width: 800px;
    margin: 0rem auto;
    padding: 2rem;
    background: #fff;
    border-radius: 10px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
}
.news-view h2 {
    margin-bottom: 0.5rem;
}
.news-meta {
    color: #888;
    font-size: 0.95rem;
    margin-bottom: 1.5rem;
}
.news-type {
    background: #e3f2fd;
    color: #0d47a1;
    border-radius: 20px;
    padding: 0.2rem 0.8rem;
    margin-right: 1rem;
}
.news-date {
    color: #999;
}
.news-images img {
    display: block;
    margin-bottom: 1rem;
    border-radius: 6px;
    box-shadow: 0 1px 4px rgba(0,0,0,0.07);
}
.news-content {
    margin-top: 1.5rem;
    font-size: 1.1rem;
    color: #333;
    line-height: 1.7;
}
.back-link {
    display: inline-block;
    margin-top: 2rem;
    color: #216c2a;
    text-decoration: none;
    font-weight: bold;
    transition: color 0.2s;
}
.back-link:hover {
    color: #1a5621;
}
.tag-link-row {
    margin-bottom: 0.5em;
    font-size: 1.08em;
    font-family: 'Segoe UI', 'Arial', sans-serif;
    letter-spacing: 0.01em;
    display: flex;
    align-items: center;
    gap: 0.3em;
}
.tag-link {
    color: #216c2a;
    font-weight: 600;
    text-decoration: none;
    border-radius: 16px;
    padding: 0.18em 0.9em 0.18em 0.7em;
    margin-left: 0.2em;
    margin-right: 0.2em;
    display: inline-block;
    transition: background 0.2s, color 0.2s;
    position: relative;
}
.tag-link::before {
    content: "#";
    color: #216c2a;
    font-weight: 700;
    margin-right: 0.2em;
    font-size: 1.05em;
    position: relative;
    top: 0;
}
.tag-link:hover {
    color: #acb9aeff;

}
.hashtag {
    color: #216c2a;
    font-weight: 700;
    font-family: 'Segoe UI Semibold', 'Arial', sans-serif;
    letter-spacing: 0.02em;
    border-radius: 16px;
    padding: 0.18em 0.9em 0.18em 0.7em;
    margin-left: 0.2em;
    margin-right: 0.2em;
    display: inline-block;
    position: relative;
}
.hashtag::before {
    content: "#";
    color: #216c2a;
    font-weight: 700;
    margin-right: 0.2em;
    font-size: 1.05em;
    position: relative;
    top: 0;
}
</style>