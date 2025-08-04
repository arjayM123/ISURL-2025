<?php
require_once '../config/database.php';

if (isset($_GET['id'])) {
    try {
        $db = new Database();
        $conn = $db->getConnection();
        
        $stmt = $conn->prepare("SELECT file_content, file_mime FROM sliders WHERE id = ? AND deleted_at IS NULL");
        $stmt->execute([$_GET['id']]);
        $file = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($file && $file['file_content']) {
            // Clear any output buffers
            while (ob_get_level()) {
                ob_end_clean();
            }
            
            // Set proper content type and cache headers
            header("Content-Type: " . $file['file_mime']);
            header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
            header("Cache-Control: post-check=0, pre-check=0", false);
            header("Pragma: no-cache");
            
            // Output the file content
            echo $file['file_content'];
            exit;
        }
    } catch (Exception $e) {
        error_log("Error serving slider file: " . $e->getMessage());
    }
}

// If no file found or error occurred
header("HTTP/1.0 404 Not Found");
header("Content-Type: text/plain");
echo "File not found";