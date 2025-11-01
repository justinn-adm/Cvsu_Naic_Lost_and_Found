<?php
// ===========================
// POST LOST ITEM PAGE
// ===========================

// Include database connection
include 'db.php';

// Start session
session_start();

// Redirect if user is not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: SignIn_SignUp.html");
    exit();
}

// ===========================
// Handle Form Submission
// ===========================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (
        isset($_FILES['image']) &&
        !empty($_POST['itemName']) &&
        !empty($_POST['itemLocation'])
    ) {

        // Sanitize and prepare input
        $itemName        = trim($_POST['itemName']);
        $itemDate        = $_POST['itemDate'];
        $itemLocation    = trim($_POST['itemLocation']);
        $itemDescription = trim($_POST['itemDescription']);
        $anonymous       = isset($_POST['anonymous']) ? 1 : 0;

        // Handle image upload
        $imageTmp      = $_FILES['image']['tmp_name'];
        $imageName     = $_FILES['image']['name'];
        $imageExt      = pathinfo($imageName, PATHINFO_EXTENSION);
        $newImageName  = uniqid() . '.' . $imageExt;
        $imagePath     = 'uploads/' . $newImageName;

        if (move_uploaded_file($imageTmp, $imagePath)) {

            $userId = intval($_SESSION['user_id']);

            // Get uploader's username
            $stmtUser = $conn->prepare("SELECT username FROM users WHERE id = ?");
            $stmtUser->bind_param("i", $userId);
            $stmtUser->execute();
            $resultUser = $stmtUser->get_result();
            $userData = $resultUser->fetch_assoc();
            $uploaderName = $userData ? $userData['username'] : 'Anonymous';
            $stmtUser->close();

            // Insert with prepared statement
            $stmt = $conn->prepare("
                INSERT INTO lost_items 
                (user_id, name, date_found, location, description, anonymous, image_path, uploader_name)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");

            $stmt->bind_param(
                "issssiss",
                $userId,
                $itemName,
                $itemDate,
                $itemLocation,
                $itemDescription,
                $anonymous,
                $imagePath,
                $uploaderName
            );

            if ($stmt->execute()) {
                echo "<script>alert('Item posted successfully!'); window.location.href='feeds.php';</script>";
            } else {
                echo "<script>alert('Database error: " . addslashes($stmt->error) . "');</script>";
            }

            $stmt->close();

        } else {
            echo "<script>alert('Failed to upload the image.');</script>";
        }

    } else {
        echo "<script>alert('Please fill in all required fields and upload an image.');</script>";
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Post Lost Item - CvSU Naic</title>

<!-- Google Fonts -->
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;700&family=Poppins:wght@600&display=swap" rel="stylesheet">

<!-- Styles -->
<style>
/* ===========================
   Global Styles
=========================== */
body {
    font-family: 'Inter', sans-serif;
    background: #f3f6fb;
    margin: 0;
    padding: 0;
    display: flex;
    flex-direction: column;
    align-items: center;
    min-height: 100vh;
}

h2, p {
    text-align: center;
    margin: 0;
}

.header {
    margin-top: 50px;
}

.header h2 {
    font-family: 'Poppins', sans-serif;
    color: #2c3e50;
    font-size: 28px;
}

.header p {
    color: #555;
    margin-top: 8px;
    font-size: 15px;
}

/* ===========================
   Form Card
=========================== */
.post-card {
    background: #fff;
    box-shadow: 0 6px 20px rgba(0,0,0,0.1);
    border-radius: 15px;
    padding: 30px 40px;
    margin-top: 30px;
    width: 400px;
}

@media (max-width: 480px) {
    .post-card { width: 90%; padding: 25px; }
}

/* ===========================
   File Upload
=========================== */
.file-upload {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 20px;
}

.file-upload button {
    background: #0078d7;
    color: #fff;
    border: none;
    padding: 8px 14px;
    border-radius: 8px;
    cursor: pointer;
    font-size: 14px;
    transition: 0.3s ease;
}

.file-upload button:hover {
    background: #005bb5;
}

.file-upload span {
    font-size: 14px;
    color: #333;
}

/* ===========================
   Form Groups
=========================== */
.form-group {
    position: relative;
    margin-bottom: 20px;
}

.form-group .icon {
    position: absolute;
    top: 12px;
    left: 12px;
    font-size: 16px;
}

.form-group input,
.form-group textarea {
    width: 100%;
    padding: 10px 12px 10px 35px;
    border-radius: 8px;
    border: 1px solid #ccc;
    font-size: 14px;
    transition: all 0.3s ease;
}

.form-group input:focus,
.form-group textarea:focus {
    border-color: #0078d7;
    box-shadow: 0 0 4px rgba(0,120,215,0.3);
    outline: none;
}

textarea {
    resize: none;
    height: 70px;
}

/* ===========================
   Anonymous Checkbox
=========================== */
.anon-option {
    display: flex;
    align-items: center;
    font-size: 14px;
    margin-bottom: 20px;
    color: #333;
}

.anon-option input {
    margin-right: 8px;
}

/* ===========================
   Submit Button
=========================== */
.post-btn {
    width: 100%;
    background: linear-gradient(135deg, #0078d7, #00b4d8);
    color: #fff;
    border: none;
    padding: 12px;
    border-radius: 10px;
    font-size: 16px;
    font-weight: 600;
    cursor: pointer;
    transition: 0.3s ease;
}

.post-btn:hover {
    background: linear-gradient(135deg, #005bb5, #0096c7);
}
</style>
</head>
<body>

<!-- Header -->
<div class="header">
    <h2>Post a Lost Item</h2>
    <p>Fill in the details to help someone find their lost belongings.</p>
</div>

<!-- Form Card -->
<div class="post-card">
    <form action="post_lost_item.php" method="POST" enctype="multipart/form-data">

        <!-- File Upload -->
        <div class="file-upload">
            <input type="file" name="image" id="imageInput" accept="image/*" hidden>
            <button type="button" id="customFileButton">Choose Image</button>
            <span id="fileName">No file chosen</span>
        </div>

        <!-- Item Name -->
        <div class="form-group">
            <span class="icon">🏷️</span>
            <input type="text" name="itemName" placeholder="Item Name (e.g. Umbrella)" required>
        </div>

        <!-- Date -->
        <div class="form-group">
            <span class="icon">📅</span>
            <input type="date" name="itemDate" required>
        </div>

        <!-- Location -->
        <div class="form-group">
            <span class="icon">📍</span>
            <input type="text" name="itemLocation" placeholder="Location (e.g. Building 2)" required>
        </div>

        <!-- Description -->
        <div class="form-group">
            <span class="icon">📝</span>
            <textarea name="itemDescription" placeholder="Short description..."></textarea>
        </div>

        <!-- Anonymous Checkbox -->
        <label class="anon-option">
            <input type="checkbox" name="anonymous"> Post Anonymously
        </label>

        <!-- Submit Button -->
        <button class="post-btn" type="submit">POST</button>
    </form>
</div>

<!-- Scripts -->
<script>
const realFileInput = document.getElementById('imageInput');
const customButton = document.getElementById('customFileButton');
const fileName = document.getElementById('fileName');

// Open file selector on button click
customButton.addEventListener('click', () => realFileInput.click());

// Display selected file name
realFileInput.addEventListener('change', () => {
    fileName.textContent = realFileInput.files[0] ? realFileInput.files[0].name : "No file chosen";
});
</script>

</body>
</html>
