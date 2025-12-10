<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
  header("Location: SignIn_SignUp.html");
  exit();
}

$user_id = $_SESSION['user_id'];

// Get user info
$sql = "SELECT profile_img, username FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($profile_img, $username);
$stmt->fetch();
$stmt->close();

// Fetch ONLY approved found items
$found_items = $conn->prepare("
    SELECT id, item_name, image_path, date_found
    FROM found_items
    WHERE user_id = ?
      AND status = 'Approved'
    ORDER BY id DESC
");
$found_items->bind_param("i", $user_id);
$found_items->execute();
$found_result = $found_items->get_result();

// Image helper
function getImagePath($path) {
  if (!$path || trim($path) == '') return 'images/no-image.png';
  if (str_starts_with($path, 'uploads/') || str_starts_with($path, 'images/')) return $path;
  return 'uploads/' . $path;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>My Account - Found Items</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">

<style>
body {
  font-family: 'Inter', sans-serif;
  background: #f0f2f7;
  margin: 0;
  padding: 20px;
  color: #333;
}
.container {
  max-width: 800px;
  margin: auto;
  background: #fff;
  padding: 25px 30px;
  border-radius: 16px;
  box-shadow: 0 12px 30px rgba(0,0,0,0.08);
  position: relative;
}
.back-btn {
  display: inline-block;
  margin-bottom: 25px;
  padding: 10px 18px;
  background: #6a2aff;
  color: #fff;
  border-radius: 8px;
  text-decoration: none;
  font-weight: 600;
}
.profile {
  position: absolute;
  top: 25px;
  right: 30px;
  text-align: center;
}
.profile img {
  width: 70px;
  height: 70px;
  border-radius: 50%;
  object-fit: cover;
  border: 3px solid #9333ea;
}
.profile h1 {
  font-size: 16px;
  color: #4b0082;
  margin-top: 6px;
  font-weight: 700;
}
.section { margin-top: 80px; }
.section h2 {
  font-size: 20px;
  color: #6a2aff;
  margin-bottom: 15px;
}
.cards {
  display: flex;
  flex-direction: column;
  gap: 15px;
}
.card {
  display: flex;
  gap: 15px;
  background: #fff;
  border-radius: 12px;
  padding: 12px;
  box-shadow: 0 4px 12px rgba(0,0,0,0.08);
  border-left: 5px solid #6a2aff;
}
.card img {
  width: 120px;
  height: 120px;
  object-fit: cover;
  border-radius: 8px;
}
.card-body h3 {
  font-size: 16px;
  margin: 0 0 6px 0;
  color: #4b0082;
}
.info-text {
  font-size: 13px;
  color: #555;
}
.view-all-btn {
  margin-top: 7px;
  display: inline-block;
  padding: 6px 12px;
  background: #6a2aff;
  color: #fff;
  border-radius: 6px;
  font-size: 12px;
  text-decoration: none;
}
.empty {
  text-align: center;
  color: #777;
  font-style: italic;
}
</style>

</head>
<body>

<div class="container">

  <a href="feeds.php" class="back-btn">← Back</a>

  <div class="profile">
    <img src="images/<?php echo htmlspecialchars($profile_img); ?>">
    <h1><?php echo htmlspecialchars($username); ?></h1>
  </div>

  <!-- FOUND ITEMS ONLY -->
  <div class="section">
    <h2>My Posted Found Items (Approved)</h2>

    <div class="cards">
      <?php if ($found_result->num_rows > 0): ?>

        <?php while ($found = $found_result->fetch_assoc()): ?>
          <div class="card">
            <img src="<?php echo htmlspecialchars(getImagePath($found['image_path'])); ?>" alt="Found Item">

            <div class="card-body">
              <h3><?php echo htmlspecialchars($found['item_name']); ?></h3>

              <p class="info-text">
                Approved on:
                <strong><?php echo htmlspecialchars($found['date_found']); ?></strong>
              </p>

              <a href="found_items.php?id=<?php echo $found['id']; ?>" class="view-all-btn">
                View Details
              </a>
            </div>
          </div>
        <?php endwhile; ?>

      <?php else: ?>
        <p class="empty">You have no approved found items yet.</p>
      <?php endif; ?>
    </div>

  </div>

</div>

</body>
</html>
