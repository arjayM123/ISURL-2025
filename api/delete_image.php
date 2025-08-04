<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

try {
    $data = json_decode(file_get_contents('php://input'), true);
    $image_id = isset($data['image_id']) ? intval($data['image_id']) : 0;

    if (!$image_id) {
        throw new Exception('Invalid image ID');
    }

    $db = new Database();
    $conn = $db->getConnection();

    // First get the file path
    $stmt = $conn->prepare("SELECT file_path FROM publication_images WHERE id = ?");
    $stmt->execute([$image_id]);
    $image = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$image) {
        throw new Exception('Image not found');
    }

    // Delete the physical file
    if (file_exists($image['file_path'])) {
        unlink($image['file_path']);
    }

    // Delete from database
    $stmt = $conn->prepare("DELETE FROM publication_images WHERE id = ?");
    $result = $stmt->execute([$image_id]);

    if ($result) {
        echo json_encode(['success' => true]);
    } else {
        throw new Exception('Failed to delete image from database');
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}