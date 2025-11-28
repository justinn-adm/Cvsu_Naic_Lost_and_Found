<?php
error_reporting(E_ALL); 
ini_set('display_errors', 1); 
include 'db.php'; 
session_start();

// Show registration success alert if set
if(!empty($_SESSION['submission'])){
    if($_SESSION['submission']){
        echo "<script>alert('Registration successful!');</script>";
    }
    unset($_SESSION['submission']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Escape input values
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = mysqli_real_escape_string($conn, $_POST['password']);
    
    // Role is always admin
    $role = "admin";

    // Hash the password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Insert into database
    $sql = "INSERT INTO users (username, email, role, password) VALUES ('$username', '$email', '$role', '$hashed_password')";

    if ($conn->query($sql) === TRUE) {
        echo "<script>alert('Admin added successfully!'); window.location.href = 'user_management.php';</script>";
    } else {
        echo "<script>alert('Error: " . $conn->error . "');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Add Admin</title>
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta2/css/all.min.css" rel="stylesheet">
<style>
    body {
        font-family: Arial, sans-serif;
        background-color: #f4f6f9;
        padding: 30px;
    }
    .form-container {
        max-width: 600px;
        margin: 0 auto;
        background: white;
        padding: 20px;
        border-radius: 8px;
        box-shadow: 0 0 10px rgba(0,0,0,0.1);
    }
    .form-container h1 {
        color: #333;
        margin-bottom: 20px;
    }
    input[type="text"], input[type="email"], input[type="password"] {
        width: 100%;
        padding: 10px;
        margin: 10px 0;
        border: 1px solid #ccc;
        border-radius: 5px;
    }
    .submit-btn {
        background-color: #007bff;
        color: white;
        padding: 10px 15px;
        border: none;
        border-radius: 5px;
        font-weight: bold;
        cursor: pointer;
        transition: background-color 0.3s ease;
    }
    .submit-btn:hover {
        background-color: #0056b3;
    }
</style>
</head>
<body>

<div class="form-container">
    <h1>Add New Admin</h1>
    <form action="add_admin.php" method="POST">
        <label for="username">Admin Name</label>
        <input type="text" id="username" name="username" required>

        <label for="email">Email</label>
        <input type="email" id="email" name="email" required>

        <label for="password">Password</label>
        <input type="password" id="password" name="password" required>

        <!-- Hidden role input is optional since PHP forces admin role -->
        <input type="hidden" name="role" value="admin">

        <button type="submit" class="submit-btn">
            <i class="fas fa-user-plus"></i> Add Admin
        </button>
    </form>
</div>

</body>
</html>
