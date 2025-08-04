<?php
header('Content-Type: application/json');
require_once '../config/database.php';

try {
    if (!isset($_GET['id'])) {
        throw new Exception('ID is required');
    }

    $db = new Database();
    $conn = $db->getConnection();
    
    // Start transaction
    $conn->beginTransaction();
    
    // Delete associated images first
    $stmt = $conn->prepare("DELETE FROM news_images WHERE news_id = ?");
    $stmt->execute([$_GET['id']]);
    
    // Then delete the news/event record
    $stmt = $conn->prepare("DELETE FROM news_events WHERE id = ?");
    $success = $stmt->execute([$_GET['id']]);
    
    if ($success) {
        $conn->commit();
        echo json_encode(['success' => true]);
    } else {
        throw new Exception('Failed to delete record');
    }
    
} catch (Exception $e) {
    if (isset($conn)) {
        $conn->rollBack();
    }
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}