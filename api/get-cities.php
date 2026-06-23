<?php
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

$stateId = $_GET['state_id'] ?? 0;

if (!$stateId) {
    echo json_encode([]);
    exit;
}

$stmt = $conn->prepare("SELECT id, name FROM cities WHERE state_id = ? AND status = 'active' ORDER BY name");
$stmt->execute([$stateId]);
$cities = $stmt->fetchAll();

echo json_encode($cities);
?>
