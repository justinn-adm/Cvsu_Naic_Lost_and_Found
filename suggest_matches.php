<?php
include 'db.php';
session_start();

header('Content-Type: application/json');

// Get lost item ID from query string
$lost_id = isset($_GET['lost_id']) ? intval($_GET['lost_id']) : 0;
if ($lost_id === 0) {
    echo json_encode([]);
    exit;
}

// Fetch the lost item details
$stmt = $conn->prepare("SELECT name, description FROM lost_items WHERE id = ?");
$stmt->bind_param("i", $lost_id);
$stmt->execute();
$stmt->bind_result($lost_name, $lost_desc);
if (!$stmt->fetch()) {
    echo json_encode([]);
    exit;
}
$stmt->close();

// Prepare search keywords
$keywords = explode(' ', $lost_name . ' ' . $lost_desc);
$keywords = array_filter(array_map('trim', $keywords)); // remove empty strings

if (count($keywords) === 0) {
    echo json_encode([]);
    exit;
}

// Build the SQL to find similar found items
$sql = "SELECT id, item_name, description, location, image_path, claimed 
        FROM found_items 
        WHERE claimed = 0 AND (";

$conditions = [];
$params = [];
$types = '';

foreach ($keywords as $word) {
    $conditions[] = "(item_name LIKE ? OR description LIKE ?)";
    $likeWord = "%$word%";
    $params[] = $likeWord;
    $params[] = $likeWord;
    $types .= 'ss';
}

$sql .= implode(' OR ', $conditions) . ") ORDER BY id DESC LIMIT 5";

$stmt = $conn->prepare($sql);
if ($stmt) {
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    $matches = [];

    while ($row = $result->fetch_assoc()) {
        $matches[] = [
            'id' => $row['id'],
            'item_name' => $row['item_name'],
            'description' => $row['description'],
            'location' => $row['location'],
            'image_path' => $row['image_path'],
            'claimed' => intval($row['claimed'])
        ];
    }

    echo json_encode($matches);
} else {
    echo json_encode([]);
}
