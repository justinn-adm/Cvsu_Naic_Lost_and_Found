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
  * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', sans-serif; }

  body {
    background: linear-gradient(135deg, #fdfbff, #f2efff);
    padding: 40px 20px;
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
    margin-bottom: 20px;
  }

  .back-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(122, 66, 255, 0.3);
  }

  h2 {
    font-weight: 800;
    color: #7a42ff;
    margin-bottom: 25px;
    text-align: center;
    letter-spacing: 1px;
  }

  .claims-container {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
    gap: 20px;
    width: 100%;
    max-width: 1000px;
  }

  .claim-card {
    background: #ffffff;
    border-radius: 18px;
    box-shadow: 0 4px 10px rgba(140, 82, 255, 0.15);
    padding: 15px;
    text-align: center;
    transition: all 0.3s ease;
    border-top: 4px solid #b388ff;
  }

  .claim-card:hover {
    transform: translateY(-6px);
    box-shadow: 0 8px 20px rgba(140, 82, 255, 0.25);
  }

  .claim-card img {
    width: 100%;
    height: 140px;
    object-fit: cover;
    border-radius: 12px;
    margin-bottom: 10px;
  }

  .claim-card h3 {
    font-size: 1rem;
    color: #3b3b98;
    margin-bottom: 5px;
  }

  .claim-card p {
    font-size: 0.85rem;
    color: #636e72;
    margin-bottom: 6px;
  }

  .status {
    display: inline-block;
    padding: 4px 10px;
    border-radius: 15px;
    font-weight: 600;
    font-size: 0.75rem;
    margin-bottom: 8px;
  }

  .status.pending { background: #fff3b0; color: #e1b12c; }
  .status.approved { background: #b9fbc0; color: #00b894; }
  .status.rejected { background: #ffccd5; color: #d63031; }

  .proof-img {
    width: 100%;
    max-width: 120px;
    border-radius: 10px;
    border: 2px solid #f1efff;
    margin-top: 6px;
  }

  .no-claims {
    text-align: center;
    color: #6c5ce7;
    background: #fff;
    padding: 25px;
    border-radius: 15px;
    box-shadow: 0 4px 10px rgba(140,82,255,0.15);
    max-width: 400px;
    margin-top: 40px;
  }

  @media (max-width: 600px) {
    .claim-card { padding: 12px; }
    .claim-card img { height: 120px; }
  }
</style>
</head>
<body>

  <!-- 🔙 Back Button -->
  <a href="feeds.php" class="back-btn">← Back</a>
  <!-- You can change "dashboard.php" to whatever page you want users to return to -->

  <h2>My Claimed Items</h2>

  <div class="claims-container">
    <?php if ($result->num_rows > 0): ?>
      <?php while ($row = $result->fetch_assoc()):
        $statusClass = strtolower($row['status']); 
      ?>
        <div class="claim-card">
          <img src="<?= htmlspecialchars($row['image_path']) ?>" alt="Item Image">
          <h3><?= htmlspecialchars($row['item_name']) ?></h3>
          <span class="status <?= $statusClass ?>"><?= htmlspecialchars($row['status']) ?></span>
          <p><b>Date:</b> <?= htmlspecialchars($row['claim_date']) ?></p>
          <p><b>Msg:</b> <?= htmlspecialchars($row['message']) ?></p>
          <?php if (!empty($row['proof_image'])): ?>
            <img src="<?= htmlspecialchars($row['proof_image']) ?>" class="proof-img" alt="Proof Image">
          <?php endif; ?>
        </div>
      <?php endwhile; ?>
    <?php else: ?>
      <div class="no-claims">
        <p>No claims found yet 💭</p>
      </div>
    <?php endif; ?>
  </div>

</body>
</html>
<?php
$stmt->close();
$conn->close();
?>
