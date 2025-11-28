<?php
session_start();
include 'db.php';

// ✅ Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    die("❌ You must be logged in to claim an item.");
}

$user_id = intval($_SESSION['user_id']);

// ✅ Validate input
if (!isset($_POST['item_id']) || empty($_POST['item_id'])) {
    die("❌ Invalid request: Missing item ID.");
}

$item_id = intval($_POST['item_id']);

// ✅ Check if proof image is uploaded
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

// ✅ Validate file type (JPG, PNG only)
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

// ✅ Start transaction
$conn->begin_transaction();

try {
    $item = null;
    $table_name = "";

    // ✅ Fetch item from found_items with lock
    $stmt = $conn->prepare("SELECT id, user_id, claimed FROM found_items WHERE id = ? FOR UPDATE");
    $stmt->bind_param("i", $item_id);
    $stmt->execute();
    $found_result = $stmt->get_result();

    if ($found_result->num_rows > 0) {
        $item = $found_result->fetch_assoc();
        $table_name = "found_items";
    } else {
        // ✅ If not found, check lost_items
        $stmt = $conn->prepare("SELECT id, user_id, claimed FROM lost_items WHERE id = ? FOR UPDATE");
        $stmt->bind_param("i", $item_id);
        $stmt->execute();
        $lost_result = $stmt->get_result();

        if ($lost_result->num_rows > 0) {
            $item = $lost_result->fetch_assoc();
            $table_name = "lost_items";
        }
    }
    $stmt->close();

    if (!$item || empty($table_name)) {
        throw new Exception("❌ Invalid item. It may not exist or was removed.");
    }

    // Prevent claiming your own item
    if ($item['user_id'] == $user_id) {
        throw new Exception("⚠️ You cannot claim your own item.");
    }

    // ✅ Check if user already has a pending or approved claim for this item
    $check_claim = $conn->prepare("
        SELECT id FROM claims 
        WHERE item_id = ? AND user_id = ? AND status IN ('Pending', 'Approved')
    ");
    $check_claim->bind_param("ii", $item_id, $user_id);
    $check_claim->execute();
    $claim_result = $check_claim->get_result();

    if ($claim_result->num_rows > 0) {
        throw new Exception("⚠️ You already have a pending or approved claim for this item.");
    }
    $check_claim->close();

    // ✅ Insert new claim (status Pending)
    $status  = "Pending";
    $message = "User submitted claim with proof.";

    $stmt = $conn->prepare("
        INSERT INTO claims (item_id, user_id, message, proof_image, status, claim_date)
        VALUES (?, ?, ?, ?, ?, NOW())
    ");
    $stmt->bind_param("iisss", $item_id, $user_id, $message, $proof_path, $status);

    if (!$stmt->execute()) {
        throw new Exception("❌ Database error while saving claim: " . $stmt->error);
    }
    $stmt->close();

    // ✅ Commit transaction
    $conn->commit();
    echo "✅ Claim submitted successfully! Waiting for admin approval.";

} catch (Exception $e) {
    $conn->rollback();
    echo $e->getMessage();
}

$conn->close();
?>
