<?php
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

$cityId = $_GET['city_id'] ?? 0;

if (!$cityId) {
    echo json_encode([]);
    exit;
}

$stmt = $conn->prepare("SELECT id, name FROM areas WHERE city_id = ? AND status = 'active' ORDER BY name");
$stmt->execute([$cityId]);
$areas = $stmt->fetchAll();

echo json_encode($areas);
?>
