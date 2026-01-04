<?php
include 'db.php';
session_start();

// ========== AUTHENTICATION CHECK ==========
if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'] ?? 'user';

// ========== INPUT VALIDATION ==========
if (!isset($_GET['id']) || !isset($_GET['type'])) {
    echo json_encode(['error' => 'Missing item ID or type']);
    exit();
}

$id = intval($_GET['id']);
$type = $_GET['type']; // 'lost' or 'found'

// =======================================
// CONFIGURE TABLE AND DATE FIELD
// =======================================
if ($type === 'lost') {
    $table = 'lost_items';
    $name_field = 'name';
    $date_field = 'created_at';
} 
elseif ($type === 'found') {
    $table = 'found_items';
    $name_field = 'item_name';
    $date_field = 'date_found';
} 
else {
    echo json_encode(['error' => 'Invalid item type']);
    exit();
}

// =======================================
// PERMISSIONS
// =======================================
// ADMIN → can view everything
// USER → can only view their own lost items, but can view ALL found items
if ($role === 'admin') {

    $sql = "SELECT 
                $table.*,
                users.username AS uploader_name,
                users.role AS uploader_role
            FROM $table 
            LEFT JOIN users ON $table.user_id = users.id
            WHERE $table.id = ?";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);

} else {

    if ($type === 'lost') {

        $sql = "SELECT 
                    $table.*,
                    users.username AS uploader_name,
                    users.role AS uploader_role
                FROM $table
                LEFT JOIN users ON $table.user_id = users.id
                WHERE $table.id = ? AND $table.user_id = ?";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $id, $user_id);

    } else {

        $sql = "SELECT 
                    $table.*,
                    users.username AS uploader_name,
                    users.role AS uploader_role
                FROM $table
                LEFT JOIN users ON $table.user_id = users.id
                WHERE $table.id = ?";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id);
    }
}

// =======================================
// EXECUTE QUERY
// =======================================
$stmt->execute();
$result = $stmt->get_result();

if ($item = $result->fetch_assoc()) {

    // Fix naming difference
    $final_name = $item[$name_field] ?? '';

    // Format dates consistently
    $created_at = isset($item['created_at'])
        ? date("Y-m-d H:i:s", strtotime($item['created_at']))
        : null;

    $date_found = isset($item['date_found'])
        ? date("Y-m-d", strtotime($item['date_found']))
        : null;

    // ===== ADMIN NAME OVERRIDE ONLY =====
    if (($item['uploader_role'] ?? '') === 'admin') {
        $uploader_name = 'Admin';
    } else {
        $uploader_name = $item['uploader_name'] ?? 'Unknown';
    }

    echo json_encode([
        'id'            => $item['id'],
        'name'          => $final_name,
        'description'   => $item['description'],
        'location'      => $item['location'],
        'image_path'    => $item['image_path'],
        'anonymous'     => $item['anonymous'] ?? 0,
        'uploader_name' => $uploader_name,
        'created_at'    => $created_at,
        'date_found'    => $date_found
    ]);

} else {
    echo json_encode(['error' => 'Item not found or access denied']);
}
?>
