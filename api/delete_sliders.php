
<?php
header('Content-Type: application/json');
require_once '../config/database.php';

try {
    if (!isset($_GET['id'])) {
        throw new Exception('ID is required');
    }

    $db = new Database();
    $conn = $db->getConnection();
    
    // Delete the slider record
    $stmt = $conn->prepare("DELETE FROM sliders WHERE id = ?");
    $success = $stmt->execute([$_GET['id']]);
    
    if ($success) {
        echo json_encode(['success' => true]);
    } else {
        throw new Exception('Failed to delete record');
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}