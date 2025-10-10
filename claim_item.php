<?php
session_start();
include 'db.php';

// ✅ 1. Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    die("❌ You must be logged in to claim an item.");
}

$user_id = intval($_SESSION['user_id']);

// ✅ 2. Validate input
if (!isset($_POST['item_id']) || empty($_POST['item_id'])) {
    die("❌ Invalid request: Missing item ID.");
}

$item_id = intval($_POST['item_id']);

// ✅ 3. Check if proof image is uploaded
if (!isset($_FILES['proof_image']) || $_FILES['proof_image']['error'] !== UPLOAD_ERR_OK) {
    die("❌ Please upload a valid proof image.");
}

$upload_dir = 'proof_uploads/';
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0755, true);
}

$file_tmp  = $_FILES['proof_image']['tmp_name'];
$file_name = time() . '_' . preg_replace("/[^a-zA-Z0-9_\.-]/", "_", basename($_FILES['proof_image']['name']));
$proof_path = $upload_dir . $file_name;

// ✅ 4. Validate file type (JPG, PNG)
$allowed_types = ['image/jpeg', 'image/png', 'image/jpg'];
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$file_mime = finfo_file($finfo, $file_tmp);
finfo_close($finfo);

if (!in_array($file_mime, $allowed_types)) {
    die("❌ Invalid file format. Only JPG or PNG allowed.");
}

if (!move_uploaded_file($file_tmp, $proof_path)) {
    die("❌ Failed to upload proof image.");
}

// ✅ 5. Start transaction
$conn->begin_transaction();
try {
    // Check if item exists and not claimed
    $stmt = $conn->prepare("SELECT id, user_id, claimed FROM found_items WHERE id = ? FOR UPDATE");
    $stmt->bind_param("i", $item_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        throw new Exception("❌ Invalid item. It may not exist anymore.");
    }

    $item = $result->fetch_assoc();
    $stmt->close();

    // Prevent claiming your own item
    if ($item['user_id'] == $user_id) {
        throw new Exception("⚠️ You cannot claim your own item.");
    }

    // Prevent claiming an already claimed item
    if ($item['claimed'] == 1) {
        throw new Exception("⚠️ This item has already been claimed.");
    }

    // ✅ 6. Insert claim record
    $status  = "Pending";
    $message = "User submitted claim with proof.";

    $stmt = $conn->prepare("INSERT INTO claims (item_id, user_id, message, proof_image, status, claim_date)
                            VALUES (?, ?, ?, ?, ?, NOW())");
    $stmt->bind_param("iisss", $item_id, $user_id, $message, $proof_path, $status);

    if (!$stmt->execute()) {
        throw new Exception("❌ Database error: " . $stmt->error);
    }
    $stmt->close();

    // ✅ 7. Mark item as claimed
    $update = $conn->prepare("UPDATE found_items SET claimed = 1 WHERE id = ?");
    $update->bind_param("i", $item_id);
    if (!$update->execute()) {
        throw new Exception("❌ Failed to update item claimed status.");
    }
    $update->close();

    // ✅ Commit transaction
    $conn->commit();
    echo "✅ Claim submitted successfully. Waiting for admin approval.";

} catch (Exception $e) {
    $conn->rollback();
    echo $e->getMessage();
}

$conn->close();
?>
