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

if (!isset($_GET['id']) || !isset($_GET['type'])) {
    echo json_encode(['error' => 'Missing item ID or type']);
    exit();
}

$id = intval($_GET['id']);
$type = $_GET['type']; // 'lost' or 'found'

// Determine table and timestamp field
if ($type === 'lost') {
    $table = 'lost_items';
    $date_field = 'created_at'; // Should be TIMESTAMP DEFAULT CURRENT_TIMESTAMP
} elseif ($type === 'found') {
    $table = 'found_items';
    $date_field = 'date_found'; // TIMESTAMP DEFAULT CURRENT_TIMESTAMP
} else {
    echo json_encode(['error' => 'Invalid item type']);
    exit();
}

// ADMIN → can view everything
// USER → can only view their own lost items, but can view any found item
if ($role === 'admin') {
    $sql = "SELECT $table.*, users.username AS uploader_name 
            FROM $table
            LEFT JOIN users ON $table.user_id = users.id
            WHERE $table.id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);

} else {
    if ($type === 'lost') {
        // Users can view *only their own* lost item
        $sql = "SELECT $table.*, users.username AS uploader_name 
                FROM $table
                LEFT JOIN users ON $table.user_id = users.id
                WHERE $table.id = ? AND $table.user_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $id, $user_id);

    } else {
        // For found items → everyone can view
        $sql = "SELECT $table.*, users.username AS uploader_name 
                FROM $table
                LEFT JOIN users ON $table.user_id = users.id
                WHERE $table.id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id);
    }
}

$stmt->execute();
$result = $stmt->get_result();

if ($item = $result->fetch_assoc()) {

    // Fix naming inconsistency:
    // lost_items uses "name"
    // found_items uses "item_name"
    $final_name = $item['name'] ?? $item['item_name'] ?? '';

    echo json_encode([
        'id' => $item['id'],
        'name' => $final_name,
        'description' => $item['description'],
        'location' => $item['location'],
        'image_path' => $item['image_path'],
        'anonymous' => $item['anonymous'] ?? 0,
        'uploader_name' => $item['uploader_name'] ?? 'Unknown',

        // Automatic timestamps (these fields are filled automatically by MySQL)
        'created_at' => $item['created_at'] ?? null,
        'date_found' => $item['date_found'] ?? null,
    ]);

} else {
    echo json_encode(['error' => 'Item not found or access denied']);
}
?>
