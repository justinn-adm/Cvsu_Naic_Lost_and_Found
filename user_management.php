<?php
include 'db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: index.html");
    exit();
}

$logged_in_id = $_SESSION['user_id'];

$sql = "SELECT * FROM users"; 
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>User Management</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Poppins:wght@500&display=swap" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta2/css/all.min.css" rel="stylesheet">
  <style>
    body {
      font-family: 'Inter', sans-serif;
      background-color: #f4f6f9;
      margin: 0;
      padding: 30px;
      color: #333;
    }

    .header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 25px;
    }

    .header h1 {
      font-family: 'Poppins', sans-serif;
      font-weight: 600;
      font-size: 1.8rem;
      color: #2d3436;
    }

    .header .btn-group a {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      padding: 8px 14px;
      border-radius: 6px;
      text-decoration: none;
      font-weight: 500;
      font-size: 0.95rem;
      transition: all 0.3s ease;
      box-shadow: 0 2px 6px rgba(0,0,0,0.1);
    }

    .add-user-btn {
      background-color: #007bff;
      color: white;
    }
    .add-user-btn:hover { background-color: #0056b3; }

    table {
      width: 100%;
      border-collapse: collapse;
      background: #fff;
      border-radius: 12px;
      overflow: hidden;
      box-shadow: 0 4px 15px rgba(0,0,0,0.08);
    }

    th, td {
      padding: 14px 16px;
      text-align: left;
    }

    th {
      background-color: #007bff;
      color: #fff;
      font-weight: 500;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }

    tr {
      border-bottom: 1px solid #e0e0e0;
      transition: background 0.3s;
    }

    tr:hover {
      background-color: #f1f3f6;
    }

    td:last-child {
      display: flex;
      gap: 10px;
    }

    .action-btn {
      padding: 6px 12px;
      font-size: 0.9rem;
      border-radius: 6px;
      font-weight: 500;
      display: inline-flex;
      align-items: center;
      gap: 6px;
      transition: all 0.3s ease;
      text-decoration: none;
    }

    .edit-btn {
      background-color: #28a745;
      color: #fff;
    }
    .edit-btn:hover {
      background-color: #218838;
    }

    .delete-btn {
      background-color: #d9534f;
      color: #fff;
    }
    .delete-btn:hover {
      background-color: #c9302c;
    }

    @media (max-width: 768px) {
      body { padding: 20px; }
      table { font-size: 0.9rem; }
      .header { flex-direction: column; gap: 12px; }
      .header h1 { font-size: 1.5rem; }
      .header .btn-group { width: 100%; display: flex; gap: 10px; justify-content: flex-start; }
    }
  </style>
</head>
<body>

  <div class="header">
    <h1>User Management</h1>
    <div class="btn-group">
      <a href="add_admin.php" class="add-user-btn"><i class="fas fa-user-plus"></i> Add Admin</a>
    </div>
  </div>

  <table>
    <thead>
      <tr>
        <th>ID</th>
        <th>Username</th>
        <th>Email</th>
        <th>Role</th>
        <th>Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php if ($result->num_rows > 0): ?>
        <?php while($row = $result->fetch_assoc()): ?>
          <tr>
            <td><?= htmlspecialchars($row['id']) ?></td>
            <td><?= htmlspecialchars($row['username']) ?></td>
            <td><?= htmlspecialchars($row['email']) ?></td>
            <td><?= htmlspecialchars($row['role']) ?></td>
            <td>
              <a href="edit_user.php?id=<?= $row['id'] ?>" class="action-btn edit-btn">
                <i class="fas fa-edit"></i> Edit
              </a>
              <?php if ($row['id'] != $logged_in_id): ?>
                <a href="delete_user.php?id=<?= $row['id'] ?>" class="action-btn delete-btn" onclick="return confirm('Are you sure you want to delete this user?');">
                  <i class="fas fa-trash-alt"></i> Delete
                </a>
              <?php endif; ?>
            </td>
          </tr>
        <?php endwhile; ?>
      <?php else: ?>
        <tr><td colspan="5" style="text-align:center; padding:20px;">No users found.</td></tr>
      <?php endif; ?>
    </tbody>
  </table>

</body>
</html>
