<?php
require_once __DIR__ . '/../config/database.php';
require_once BASE_PATH . 'includes/functions.php';

session_start();

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Please login to continue']);
    exit;
}

$propertyId = $_POST['property_id'] ?? 0;
$userId = getCurrentUserId();

if (!$propertyId) {
    echo json_encode(['success' => false, 'message' => 'Invalid property']);
    exit;
}

// Check if already in wishlist
$stmt = $conn->prepare("SELECT id FROM wishlist WHERE user_id = ? AND property_id = ?");
$stmt->execute([$userId, $propertyId]);

if ($stmt->fetch()) {
    // Remove from wishlist
    $stmt = $conn->prepare("DELETE FROM wishlist WHERE user_id = ? AND property_id = ?");
    $stmt->execute([$userId, $propertyId]);
    echo json_encode(['success' => true, 'message' => 'Removed from wishlist']);
} else {
    // Add to wishlist
    try {
        $stmt = $conn->prepare("INSERT INTO wishlist (user_id, property_id) VALUES (?, ?)");
        $stmt->execute([$userId, $propertyId]);
        echo json_encode(['success' => true, 'message' => 'Added to wishlist']);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Error adding to wishlist']);
    }
}
?>
