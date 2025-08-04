<?php
require_once '../config/database.php';

if (isset($_GET['id'])) {
    try {
        $db = new Database();
        $conn = $db->getConnection();
        
        $stmt = $conn->prepare("SELECT file_content, file_mime FROM news_images WHERE id = ?");
        $stmt->execute([$_GET['id']]);
        $image = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($image && $image['file_content']) {
            // Clear any output buffers
            while (ob_get_level()) {
                ob_end_clean();
            }
            
            header("Content-Type: " . $image['file_mime']);
            header("Cache-Control: public, max-age=31536000");
            header("Expires: " . gmdate("D, d M Y H:i:s", time() + 31536000) . " GMT");
            echo $image['file_content'];
            exit;
        }
    } catch (Exception $e) {
        error_log("Error serving news image: " . $e->getMessage());
    }
}

// If no image found or error occurred
header("HTTP/1.0 404 Not Found");
header("Content-Type: image/jpeg");
readfile("assets/img/default.jpg");