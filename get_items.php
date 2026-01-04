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
    // Admin sees all lost items
    $sql = "SELECT id, name, image_path, claimed, status 
        FROM lost_items 
        ORDER BY id DESC";
    $stmt = $conn->prepare($sql);
} else {
    // Regular users see only THEIR lost items
    $sql = "SELECT id, name, image_path, claimed, status 
            FROM lost_items 
            WHERE user_id = ? 
            ORDER BY id DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
}

$stmt->execute();
$result = $stmt->get_result();

$items = [];
while ($row = $result->fetch_assoc()) {
    $items[] = $row;
}

echo json_encode($items);
?>
