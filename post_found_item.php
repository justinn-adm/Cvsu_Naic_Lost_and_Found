<?php
session_start();
include 'db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: index.html");
    exit();
}

$user_id = $_SESSION['user_id'];

// Verify that user exists in the database
$result = $conn->query("SELECT id FROM users WHERE id = $user_id");
if ($result->num_rows == 0) {
    die("Error: Logged-in user does not exist in the database.");
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $item_name   = trim($_POST['item_name']);
    $description = trim($_POST['description']);
    $location    = trim($_POST['location']);
    $date_found  = $_POST['date_found'];
    $anonymous   = isset($_POST['anonymous']) ? 1 : 0;

    // Handle file upload
    $image_path = "";
    if (!empty($_FILES["item_image"]["name"])) {
        $target_dir = "uploads/";
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        $image_path = $target_dir . time() . "_" . basename($_FILES["item_image"]["name"]);

        if (!move_uploaded_file($_FILES["item_image"]["tmp_name"], $image_path)) {
            die("Error uploading file.");
        }
    }

    // Prepare and execute SQL safely
    $sql = "INSERT INTO found_items (user_id, item_name, description, location, date_found, image_path, anonymous) 
            VALUES (?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("isssssi", $user_id, $item_name, $description, $location, $date_found, $image_path, $anonymous);

    if ($stmt->execute()) {
        echo "<script>alert('Found item reported successfully!'); window.location.href='found_items.php';</script>";
        exit();
    } else {
        die("Error reporting found item: " . $stmt->error);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Report Found Item</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
<style>
    body {
        font-family: 'Poppins', sans-serif;
        background: linear-gradient(135deg, #00c6ff, #0072ff, #92fe9d);
        background-size: 400% 400%;
        animation: gradientBG 12s ease infinite;
        display: flex;
        justify-content: center;
        align-items: center;
        height: 100vh;
        margin: 0;
    }
    @keyframes gradientBG {
        0% { background-position: 0% 50%; }
        50% { background-position: 100% 50%; }
        100% { background-position: 0% 50%; }
    }
    .form-container {
        background: rgba(255, 255, 255, 0.1);
        backdrop-filter: blur(15px);
        border-radius: 15px;
        padding: 30px;
        width: 100%;
        max-width: 450px;
        color: #fff;
        box-shadow: 0 10px 25px rgba(0,0,0,0.3);
    }
    .form-container h2 {
        text-align: center;
        margin-bottom: 20px;
        font-weight: 600;
    }
    .form-group {
        margin-bottom: 15px;
    }
    .form-group label {
        display: block;
        margin-bottom: 6px;
        font-size: 0.9rem;
    }
    .form-group input, .form-group textarea {
        width: 100%;
        padding: 10px;
        border: none;
        border-radius: 8px;
        background: rgba(255,255,255,0.15);
        color: #fff;
        font-size: 1rem;
    }
    .form-group input[type="file"] {
        padding: 4px;
    }
    .form-group input:focus, .form-group textarea:focus {
        outline: 2px solid #00ffc6;
    }
    .form-check {
        display: flex;
        align-items: center;
        gap: 8px;
        margin-top: 10px;
    }
    .form-check input {
        transform: scale(1.2);
    }
    .submit-btn {
        width: 100%;
        padding: 12px;
        border: none;
        border-radius: 10px;
        background: linear-gradient(135deg, #00c6ff, #0072ff, #00ffc6);
        color: #fff;
        font-weight: bold;
        cursor: pointer;
        transition: 0.3s ease;
        font-size: 1rem;
        margin-top: 10px;
    }
    .submit-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(0,0,0,0.3);
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
            <textarea id="description" name="description" rows="3" required></textarea>
        </div>
        <div class="form-group">
            <label for="location">Location Found</label>
            <input type="text" id="location" name="location" required>
        </div>
        <div class="form-group">
            <label for="date_found">Date Found</label>
            <input type="date" id="date_found" name="date_found" required>
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
