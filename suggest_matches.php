<?php
include 'db.php';
session_start();

if (!isset($_GET['lost_id'])) {
    echo json_encode([]);
    exit();
}

$lost_id = intval($_GET['lost_id']);
$user_id = $_SESSION['user_id'];

// Fetch the lost item details
$lost = $conn->prepare("SELECT name, description, location FROM lost_items WHERE id=?");
$lost->bind_param("i", $lost_id);
$lost->execute();
$result = $lost->get_result();
$lost_item = $result->fetch_assoc();

if (!$lost_item) {
    echo json_encode([]);
    exit();
}

// Prepare wildcard search terms
$name = '%' . $lost_item['name'] . '%';
$desc = '%' . $lost_item['description'] . '%';
$loc  = '%' . $lost_item['location'] . '%';

// FIXED QUERY → Exclude claimed + pending
$query = $conn->prepare("
    SELECT id, item_name, location, image_path, claimed, claim_status
    FROM found_items
    WHERE (
        LOWER(item_name) LIKE LOWER(?) 
        OR LOWER(description) LIKE LOWER(?) 
        OR LOWER(location) LIKE LOWER(?)
    )
    AND user_id != ?
    AND claimed = 0
    AND claim_status != 'pending'
    LIMIT 3
");

$query->bind_param("sssi", $name, $desc, $loc, $user_id);
$query->execute();
$matches = $query->get_result()->fetch_all(MYSQLI_ASSOC);

echo json_encode($matches);
?>
