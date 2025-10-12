<?php
include 'db.php';

$id = intval($_GET['id']); // sanitize input

$query = "SELECT * FROM users WHERE id = $id";
$result = mysqli_query($conn, $query);
$user = mysqli_fetch_assoc($result);

if (!$user) {
    echo "User not found.";
    exit();
}

if (isset($_POST['update'])) {
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    
    $query = "UPDATE users SET username='$username', email='$email' WHERE id=$id";
    mysqli_query($conn, $query);
    header("Location: user_management.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Edit User</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
  <style>
    body {
      font-family: 'Inter', sans-serif;
      background-color: #f4f6f9;
      display: flex;
      justify-content: center;
      align-items: flex-start;
      padding: 50px 20px;
      margin: 0;
    }

    .edit-container {
      background: #fff;
      padding: 30px 40px;
      border-radius: 12px;
      box-shadow: 0 4px 15px rgba(0,0,0,0.08);
      width: 100%;
      max-width: 450px;
    }

    .edit-container h2 {
      margin-bottom: 25px;
      font-weight: 600;
      color: #2d3436;
      text-align: center;
    }

    form {
      display: flex;
      flex-direction: column;
      gap: 18px;
    }

    label {
      font-weight: 500;
      margin-bottom: 6px;
      color: #333;
    }

    input[type="text"],
    input[type="email"] {
      padding: 10px 14px;
      border-radius: 8px;
      border: 1px solid #ccc;
      font-size: 1rem;
      transition: all 0.3s ease;
    }

    input[type="text"]:focus,
    input[type="email"]:focus {
      border-color: #007bff;
      box-shadow: 0 0 5px rgba(0,123,255,0.3);
      outline: none;
    }

    button {
      padding: 12px;
      background-color: #007bff;
      color: #fff;
      font-size: 1rem;
      font-weight: 500;
      border: none;
      border-radius: 8px;
      cursor: pointer;
      transition: background-color 0.3s ease, transform 0.2s ease;
    }

    button:hover {
      background-color: #0056b3;
      transform: translateY(-2px);
    }

    .back-link {
      display: inline-block;
      margin-top: 15px;
      text-decoration: none;
      color: #007bff;
      font-weight: 500;
      transition: color 0.3s;
    }

    .back-link:hover {
      color: #0056b3;
    }
  </style>
</head>
<body>

  <div class="edit-container">
    <h2>Edit User</h2>
    <form method="post">
      <div>
        <label for="username">Username</label>
        <input type="text" id="username" name="username" value="<?= htmlspecialchars($user['username']); ?>" required>
      </div>
      
      <div>
        <label for="email">Email</label>
        <input type="email" id="email" name="email" value="<?= htmlspecialchars($user['email']); ?>" required>
      </div>

      <button type="submit" name="update">Update User</button>
    </form>
    <a href="user_management.php" class="back-link">&larr; Back to User Management</a>
  </div>

</body>
</html>
