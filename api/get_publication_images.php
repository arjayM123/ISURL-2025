<?php
require_once '../config/database.php';

$db = new Database();
$conn = $db->getConnection();

$publication_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($publication_id > 0) {
    // First get the publication title
    $pubQuery = "SELECT title FROM publications WHERE id = ?";
    $pubStmt = $conn->prepare($pubQuery);
    $pubStmt->execute([$publication_id]);
    $publication = $pubStmt->fetch(PDO::FETCH_ASSOC);

    // Then get the images
    $query = "SELECT * FROM publication_images 
              WHERE publication_id = ? 
              ORDER BY position ASC";
    
    $stmt = $conn->prepare($query);
    $stmt->execute([$publication_id]);
    $images = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    header('Content-Type: application/json');
    echo json_encode([
        'title' => $publication['title'],
        'images' => $images
    ]);
} else {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid publication ID']);
}