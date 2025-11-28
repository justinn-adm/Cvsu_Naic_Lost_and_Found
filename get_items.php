<?php
include 'db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'] ?? 'user';

if ($role === 'admin') {
    // Admin sees all items
    $sql = "SELECT id, name, image_path, claimed, status FROM lost_items ORDER BY id DESC";
    $stmt = $conn->prepare($sql);
} else {
    // Regular users only see approved items
    $sql = "SELECT id, name, image_path, claimed, status FROM lost_items WHERE status='Approved' ORDER BY id DESC";
    $stmt = $conn->prepare($sql);
}

$stmt->execute();
$result = $stmt->get_result();

$items = [];
while ($row = $result->fetch_assoc()) {
    $items[] = $row;
}

echo json_encode($items);
?>
