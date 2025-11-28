<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.html");
    exit();
}

$user_id = $_SESSION['user_id'];

// Ensure user exists
$result = $conn->query("SELECT id FROM users WHERE id = $user_id");
if ($result->num_rows == 0) die("Error: Logged-in user does not exist.");

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $item_name   = trim($_POST['item_name']);
    $description = trim($_POST['description']);
    $location    = trim($_POST['location']);
    $anonymous   = isset($_POST['anonymous']) ? 1 : 0;

    // Handle image upload
    $image_path = "";
    if (!empty($_FILES["item_image"]["name"])) {
        $target_dir = "uploads/";
        if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);

        $image_path = $target_dir . time() . "_" . basename($_FILES["item_image"]["name"]);

        if (!move_uploaded_file($_FILES["item_image"]["tmp_name"], $image_path)) {
            die("Error uploading file.");
        }
    }

    // Insert WITHOUT date_found (MySQL auto timestamp)
    $sql = "INSERT INTO found_items (user_id, item_name, description, location, image_path, anonymous, status) 
            VALUES (?, ?, ?, ?, ?, ?, 'Pending')";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("issssi", $user_id, $item_name, $description, $location, $image_path, $anonymous);

    if ($stmt->execute()) {
        echo "<script>alert('Found item submitted successfully! Pending admin approval.'); window.location.href='found_items.php';</script>";
        exit();
    } else {
        die('Error: ' . $stmt->error);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Report Found Item</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">

<style>
body {
    font-family: 'Poppins', sans-serif;
    background: #e4e7eb;
    display: flex;
    justify-content: center;
    align-items: center;
    min-height: 100vh;
    margin: 0;
    padding: 15px;
}

.form-container {
    background: #fdfdfd;
    border-radius: 14px;
    padding: 28px 22px;
    width: 100%;
    max-width: 400px;
    box-shadow: 0 6px 18px rgba(0,0,0,0.08);
}

.form-container h2 {
    text-align: center;
    margin-bottom: 24px;
    font-weight: 600;
    font-size: 1.6rem;
    color: #1f2937;
}

.form-group {
    margin-bottom: 14px;
    display: flex;
    flex-direction: column;
}

.form-group label {
    font-size: 0.88rem;
    color: #4b5563;
    margin-bottom: 4px;
}

.form-group input,
.form-group textarea {
    padding: 10px 12px;
    border: 1px solid #cbd5e1;
    border-radius: 8px;
    font-size: 0.95rem;
    color: #1f2937;
    background: #f9fafc;
    transition: 0.2s ease;
}

.form-group input:focus,
.form-group textarea:focus {
    border-color: #3b82f6;
    box-shadow: 0 0 4px rgba(59,130,246,0.3);
    outline: none;
}

.form-group textarea {
    resize: none;
}

.form-check {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 18px;
}

.form-check input {
    width: 16px;
    height: 16px;
}

.form-check label {
    font-size: 0.88rem;
    color: #4b5563;
}

.submit-btn {
    width: 100%;
    padding: 11px;
    border: none;
    border-radius: 10px;
    background: #3b82f6;
    color: #fff;
    font-size: 0.96rem;
    font-weight: 500;
    cursor: pointer;
    transition: 0.2s ease;
}

.submit-btn:hover {
    background: #2563eb;
}

@media (max-width: 400px) {
    .form-container {
        padding: 22px 16px;
    }
}
</style>
</head>
<body>
<div class="form-container">
    <h2>Report Found Item</h2>

    <form method="POST" enctype="multipart/form-data">
        <div class="form-group">
            <label for="item_name">Item Name</label>
            <input type="text" id="item_name" name="item_name" required>
        </div>

        <div class="form-group">
            <label for="description">Description</label>
            <textarea id="description" name="description" rows="2" required></textarea>
        </div>

        <div class="form-group">
            <label for="location">Location Found</label>
            <input type="text" id="location" name="location" required>
        </div>

        <div class="form-group">
            <label for="item_image">Upload Photo</label>
            <input type="file" id="item_image" name="item_image" accept="image/*">
        </div>

        <div class="form-check">
            <input type="checkbox" id="anonymous" name="anonymous">
            <label for="anonymous">Post as Anonymous</label>
        </div>

        <button type="submit" class="submit-btn">Submit</button>
    </form>
</div>
</body>
</html>
