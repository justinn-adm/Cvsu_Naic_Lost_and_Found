<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.html");
    exit();
}

$user_id = $_SESSION['user_id'];

// Mark all approved/rejected claims as read
$update_sql = "
  UPDATE claims 
  SET is_read = 1 
  WHERE user_id = ? 
  AND status IN ('Approved', 'Rejected')
";
$update_stmt = $conn->prepare($update_sql);
$update_stmt->bind_param("i", $user_id);
$update_stmt->execute();
$update_stmt->close();

// Fetch claims
$sql = "
    SELECT 
        c.id AS claim_id,
        c.status,
        c.message,
        c.claim_date,
        c.proof_image,
        f.id AS item_id,
        f.item_name,
        f.description,
        f.image_path
    FROM claims c
    JOIN found_items f ON c.item_id = f.id
    WHERE c.user_id = ?
    ORDER BY c.claim_date DESC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>My Claims | CvSU Naic Lost & Found</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&display=swap" rel="stylesheet">
<style>
  * {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    font-family: 'Inter', sans-serif;
  }

  body {
    background: linear-gradient(135deg, #fdfbff, #f2efff);
    padding: 40px 15px;
    color: #2d3436;
    display: flex;
    flex-direction: column;
    align-items: center;
    min-height: 100vh;
  }

  .back-btn {
    align-self: flex-start;
    background: linear-gradient(90deg, #7a42ff, #a678ff);
    color: #fff;
    padding: 10px 18px;
    border-radius: 25px;
    text-decoration: none;
    font-weight: 600;
    font-size: 0.9rem;
    box-shadow: 0 3px 10px rgba(122, 66, 255, 0.2);
    transition: all 0.3s ease;
    margin-bottom: 25px;
  }

  .back-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(122, 66, 255, 0.3);
  }

  h2 {
    font-weight: 800;
    color: #7a42ff;
    margin-bottom: 20px;
    text-align: center;
    letter-spacing: 1px;
  }

  .table-container {
    width: 100%;
    max-width: 1000px;
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 4px 10px rgba(140,82,255,0.15);
    overflow-x: auto;
  }

  table {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.95rem;
  }

  thead {
    background: #7a42ff;
    color: #fff;
  }

  th, td {
    padding: 12px 15px;
    text-align: left;
    border-bottom: 1px solid #eee;
    vertical-align: middle;
  }

  th {
    font-weight: 600;
  }

  tr:hover {
    background: #f9f6ff;
  }

  img.item-img {
    width: 70px;
    height: 70px;
    border-radius: 8px;
    object-fit: cover;
  }

  img.proof-img {
    width: 60px;
    height: 60px;
    border-radius: 8px;
    object-fit: cover;
  }

  /* ✅ Status Badges */
  .status-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 6px 12px;
    border-radius: 25px;
    font-weight: 600;
    font-size: 0.85rem;
    text-transform: capitalize;
  }

  .status-badge.pending {
    background: #fff4c4;
    color: #e1b12c;
  }

  .status-badge.pending::before {
    content: "⏳";
  }

  .status-badge.approved {
    background: #c9ffd7;
    color: #00b894;
  }

  .status-badge.approved::before {
    content: "✅";
  }

  .status-badge.rejected {
    background: #ffd6de;
    color: #d63031;
  }

  .status-badge.rejected::before {
    content: "❌";
  }

  .no-claims {
    margin-top: 40px;
    background: #fff;
    padding: 25px;
    border-radius: 12px;
    box-shadow: 0 4px 10px rgba(140,82,255,0.15);
    color: #6c5ce7;
    text-align: center;
    max-width: 400px;
  }

  @media (max-width: 768px) {
    th, td {
      padding: 10px;
      font-size: 0.85rem;
    }
    img.item-img, img.proof-img {
      width: 55px;
      height: 55px;
    }
  }

</style>
</head>
<body>

  <a href="feeds.php" class="back-btn">← Back</a>
  <h2>My Claimed Items</h2>

  <?php if ($result->num_rows > 0): ?>
  <div class="table-container">
    <table>
      <thead>
        <tr>
          <th>Item</th>
          <th>Item Name</th>
          <th>Status</th>
          <th>Message</th>
          <th>Date</th>
          <th>Proof</th>
        </tr>
      </thead>
      <tbody>
        <?php while ($row = $result->fetch_assoc()): 
          $statusClass = strtolower($row['status']);
        ?>
          <tr>
            <td><img src="<?= htmlspecialchars($row['image_path']) ?>" alt="Item" class="item-img"></td>
            <td><?= htmlspecialchars($row['item_name']) ?></td>
            <td><span class="status-badge <?= $statusClass ?>"><?= htmlspecialchars($row['status']) ?></span></td>
            <td><?= htmlspecialchars($row['message']) ?></td>
            <td><?= htmlspecialchars($row['claim_date']) ?></td>
            <td>
              <?php if (!empty($row['proof_image'])): ?>
                <img src="<?= htmlspecialchars($row['proof_image']) ?>" alt="Proof" class="proof-img">
              <?php else: ?>
                —
              <?php endif; ?>
            </td>
          </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
  </div>
  <?php else: ?>
    <div class="no-claims">
      <p>No claims found yet 💭</p>
    </div>
  <?php endif; ?>

</body>
</html>
<?php
$stmt->close();
$conn->close();
?>
