<?php
require_once __DIR__ . '/../config/database.php';
require_once BASE_PATH . 'includes/functions.php';

session_start();

header('Content-Type: application/json');

if (!isLoggedIn() || !isSeller()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$propertyId = $_POST['property_id'] ?? 0;
$userId = getCurrentUserId();

if (!$propertyId) {
    echo json_encode(['success' => false, 'message' => 'Invalid property']);
    exit;
}

// Check if property belongs to user
$stmt = $conn->prepare("SELECT id FROM properties WHERE id = ? AND user_id = ?");
$stmt->execute([$propertyId, $userId]);

if (!$stmt->fetch()) {
    echo json_encode(['success' => false, 'message' => 'Property not found']);
    exit;
}

try {
    // Delete property images
    $stmt = $conn->prepare("SELECT image_path FROM property_images WHERE property_id = ?");
    $stmt->execute([$propertyId]);
    $images = $stmt->fetchAll();
    
    foreach ($images as $image) {
        deleteFile($image['image_path']);
    }
    
    // Delete property (cascade will handle related records)
    $stmt = $conn->prepare("DELETE FROM properties WHERE id = ?");
    $stmt->execute([$propertyId]);
    
    logActivity($userId, 'delete', 'property', 'Property deleted: ' . $propertyId);
    
    echo json_encode(['success' => true, 'message' => 'Property deleted successfully']);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Error deleting property']);
}
?>
