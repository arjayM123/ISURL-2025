<?php
require_once '../config/database.php';

header('Content-Type: application/json');

// Validate input
if (!isset($_GET['id'])) {
    echo json_encode(['success' => false, 'error' => 'Missing publication ID']);
    exit;
}

$id = intval($_GET['id']);

// Validate that ID is a positive integer
if ($id <= 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid publication ID']);
    exit;
}

$db = new Database();
$conn = $db->getConnection();

try {
    // Start transaction for data integrity
    $conn->beginTransaction();
    
    // First check if the publication exists
    $checkStmt = $conn->prepare("SELECT id FROM publications WHERE id = ?");
    $checkStmt->execute([$id]);
    
    if ($checkStmt->rowCount() === 0) {
        $conn->rollback();
        echo json_encode(['success' => false, 'error' => 'Publication not found']);
        exit;
    }
    
    // Delete related images first (cascade should handle this, but explicit is safer)
    $stmtImg = $conn->prepare("DELETE FROM publication_images WHERE publication_id = ?");
    $stmtImg->execute([$id]);
    
    // Delete the publication itself - FIXED: changed from 'publication' to 'publications'
    $stmtPub = $conn->prepare("DELETE FROM publications WHERE id = ?");
    $stmtPub->execute([$id]);
    
    // Commit the transaction
    $conn->commit();
    
    echo json_encode(['success' => true, 'message' => 'Publication deleted successfully']);
    
} catch (Exception $e) {
    // Rollback transaction on error
    if ($conn->inTransaction()) {
        $conn->rollback();
    }
    
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}
?>