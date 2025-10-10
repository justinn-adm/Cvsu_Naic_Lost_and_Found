<?php
include 'db.php';
session_start();

// Protect admin route
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// Handle delete request
if (isset($_POST['delete_id'])) {
    $id = intval($_POST['delete_id']);

    // Get the image path to delete the file
    $result = $conn->query("SELECT image_path FROM lost_items WHERE id = $id");
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $image_path = $row['image_path'];
        if (file_exists($image_path)) {
            unlink($image_path); // delete image file
        }
    }

    // Delete related claims first (avoid foreign key constraint error)
    $conn->query("DELETE FROM claims WHERE item_id = $id");

    // Then delete the item itself
    $conn->query("DELETE FROM lost_items WHERE id = $id");

    echo "<script>alert('Item deleted successfully!'); window.location.href='admin_items.php';</script>";
    exit();
}

// Fetch all items
$sql = "SELECT id, name, description, image_path, date_found, location, uploader_name, anonymous, claimed 
        FROM lost_items ORDER BY date_found DESC";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Manage Items | Admin</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
<style>
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
        font-family: 'Inter', sans-serif;
    }
    body {
        background: #f6f8fc;
        color: #2d3436;
        padding: 30px;
    }
    h1 {
        text-align: center;
        margin-bottom: 25px;
        color: #2d3436;
    }
    table {
        width: 100%;
        border-collapse: collapse;
        background: #fff;
        box-shadow: 0 4px 10px rgba(0,0,0,0.05);
        border-radius: 12px;
        overflow: hidden;
    }
    thead {
        background: #0984e3;
        color: white;
    }
    th, td {
        padding: 14px 12px;
        text-align: center;
        border-bottom: 1px solid #eee;
    }
    tr:hover {
        background: #f1f3f6;
    }
    img {
        width: 60px;
        height: 60px;
        border-radius: 8px;
        object-fit: cover;
    }
    .delete-btn {
        background: #d63031;
        color: white;
        border: none;
        padding: 8px 12px;
        border-radius: 6px;
        cursor: pointer;
        transition: background 0.3s ease;
    }
    .delete-btn:hover {
        background: #e17055;
    }
    @media (max-width: 768px) {
        table, thead, tbody, th, td, tr {
            display: block;
        }
        thead {
            display: none;
        }
        tr {
            margin-bottom: 15px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }
        td {
            display: flex;
            justify-content: space-between;
            padding: 10px;
            border-bottom: 1px solid #eee;
        }
        td:last-child {
            border-bottom: none;
        }
        td::before {
            content: attr(data-label);
            font-weight: 600;
            color: #0984e3;
        }
    }
</style>
</head>
<body>

<h1>🗂️ Manage Lost & Found Items</h1>

<table>
    <thead>
        <tr>
            <th>Image</th>
            <th>Item Name</th>
            <th>Description</th>
            <th>Date Found</th>
            <th>Location</th>
            <th>Uploader</th>
            <th>Anonymous</th>
            <th>Claimed</th>
            <th>Action</th>
        </tr>
    </thead>
    <tbody>
        <?php if ($result && $result->num_rows > 0): ?>
            <?php while ($row = $result->fetch_assoc()): ?>
                <tr>
                    <td data-label="Image"><img src="<?php echo htmlspecialchars($row['image_path']); ?>" alt=""></td>
                    <td data-label="Name"><?php echo htmlspecialchars($row['name']); ?></td>
                    <td data-label="Description"><?php echo htmlspecialchars($row['description']); ?></td>
                    <td data-label="Date Found"><?php echo htmlspecialchars($row['date_found']); ?></td>
                    <td data-label="Location"><?php echo htmlspecialchars($row['location']); ?></td>
                    <td data-label="Uploader"><?php echo htmlspecialchars($row['uploader_name']); ?></td>
                    <td data-label="Anonymous"><?php echo $row['anonymous'] ? 'Yes' : 'No'; ?></td>
                    <td data-label="Claimed"><?php echo $row['claimed'] ? '✅' : '❌'; ?></td>
                    <td data-label="Action">
                        <form method="POST" onsubmit="return confirmDelete()">
                            <input type="hidden" name="delete_id" value="<?php echo $row['id']; ?>">
                            <button type="submit" class="delete-btn">Delete</button>
                        </form>
                    </td>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr><td colspan="9">No items found.</td></tr>
        <?php endif; ?>
    </tbody>
</table>

<script>
function confirmDelete() {
    return confirm("Are you sure you want to delete this item? Related claims will also be removed.");
}
</script>

</body>
</html>
