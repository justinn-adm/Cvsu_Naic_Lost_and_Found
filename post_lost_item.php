<?php
// Include the database connection file
include 'db.php';

// Start the session
session_start();

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    // If not logged in, redirect to the sign-in page
    header("Location: SignIn_SignUp.html");
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Check if the required fields are set and the image is uploaded
    if (isset($_FILES['image']) && !empty($_POST['itemName']) && !empty($_POST['itemLocation'])) {
        $itemName = mysqli_real_escape_string($conn, $_POST['itemName']);
        $itemDate = $_POST['itemDate'];
        $itemLocation = mysqli_real_escape_string($conn, $_POST['itemLocation']);
        $itemDescription = mysqli_real_escape_string($conn, $_POST['itemDescription']);
        $anonymous = isset($_POST['anonymous']) ? 1 : 0;
        
        // Handle image upload
        $imageTmp = $_FILES['image']['tmp_name'];
        $imageName = $_FILES['image']['name'];
        $imageExtension = pathinfo($imageName, PATHINFO_EXTENSION);
        $newImageName = uniqid() . '.' . $imageExtension;
        $imagePath = 'uploads/' . $newImageName;

        if (move_uploaded_file($imageTmp, $imagePath)) {
            // Prepare the SQL query
            $userId = $_SESSION['user_id'];
            $query = "INSERT INTO lost_items (user_id, name, date, location, description, anonymous, image) VALUES ('$userId', '$itemName', '$itemDate', '$itemLocation', '$itemDescription', '$anonymous', '$imagePath')";

            if (mysqli_query($conn, $query)) {
                echo "<script>alert('Item posted successfully!'); window.location.href = 'lost.php';</script>";
            } else {
                echo "<script>alert('Failed to post the item. Please try again later.');</script>";
            }
        } else {
            echo "<script>alert('Failed to upload the image.');</script>";
        }
    } else {
        echo "<script>alert('Please fill in all fields and upload an image.');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Post Lost Item - CvSU Naic</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;700&family=Poppins:wght@600&display=swap" rel="stylesheet">
  <style>
    /* Your existing styles */
  </style>
</head>
<body>

  <div class="header">
    <h2>Post a Lost Item</h2>
    <p>Fill in the details to help someone find their lost belongings.</p>
  </div>

  <div class="post-card">
    <form action="post_lost_item.php" method="POST" enctype="multipart/form-data">
      <div class="file-upload">
        <input type="file" name="image" id="imageInput" accept="image/*" hidden>
        <button type="button" id="customFileButton">Choose Image</button>
        <span id="fileName">No file chosen</span>
      </div>

      <div class="form-group">
        <span class="icon">🏷️</span>
        <input type="text" name="itemName" id="itemLabel" placeholder="Item Name (e.g. Umbrella)" required>
      </div>

      <div class="form-group date-picker">
        <span class="icon">📅</span>
        <input type="date" name="itemDate" id="itemDate">
      </div>

      <div class="form-group">
        <span class="icon">📍</span>
        <input type="text" name="itemLocation" id="itemLocation" placeholder="Location (e.g. Building 2)" required>
      </div>

      <div class="form-group">
        <span class="icon">📝</span>
        <textarea name="itemDescription" id="itemDescription" placeholder="Short description..."></textarea>
      </div>

      <label class="anon-option">
        <input type="checkbox" name="anonymous" id="anonymousCheckbox">
        Post Anonymously
      </label>

      <button class="post-btn" type="submit">POST</button>
    </form>
  </div>

  <script>
    const realFileInput = document.getElementById('imageInput');
    const customButton = document.getElementById('customFileButton');
    const fileName = document.getElementById('fileName');

    customButton.addEventListener('click', () => {
      realFileInput.click();
    });

    realFileInput.addEventListener('change', () => {
      if (realFileInput.files[0]) {
        fileName.textContent = realFileInput.files[0].name;
      } else {
        fileName.textContent = "No file chosen";
      }
    });
  </script>

</body>
</html>
