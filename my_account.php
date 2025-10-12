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

// Fetch lost items (by uploader_name)
$lost_items = $conn->prepare("SELECT id, description, image_path, claimed FROM lost_items WHERE uploader_name = ? ORDER BY id DESC");
$lost_items->bind_param("s", $username);
$lost_items->execute();
$lost_result = $lost_items->get_result();

// Fetch found items (by user_id)
$found_items = $conn->prepare("SELECT id, item_name, image_path, claimed FROM found_items WHERE user_id = ? ORDER BY id DESC");
$found_items->bind_param("i", $user_id);
$found_items->execute();
$found_result = $found_items->get_result();

// Function to handle image path
function getImagePath($path) {
  if (!$path || trim($path) == '') {
    return 'images/no-image.png'; // fallback
  }
  if (str_starts_with($path, 'uploads/') || str_starts_with($path, 'images/')) {
    return $path;
  }
  return 'uploads/' . $path;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>My Account - Lost & Found</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;700&display=swap" rel="stylesheet">
<style>
body {
  font-family: 'Inter', sans-serif;
  background: linear-gradient(135deg, #f8f6ff, #e8dfff);
  margin: 0;
  padding: 40px;
}
.container {
  max-width: 950px;
  margin: auto;
  background: #fff;
  padding: 35px;
  border-radius: 20px;
  box-shadow: 0 10px 25px rgba(140, 82, 255, 0.15);
}
h1 {
  text-align: center;
  color: #6a2aff;
}
.profile {
  text-align: center;
  margin-bottom: 30px;
}
.profile img {
  width: 120px;
  height: 120px;
  border-radius: 50%;
  object-fit: cover;
  border: 3px solid #9333ea;
}
.section {
  margin-top: 30px;
}
.section h2 {
  color: #9333ea;
  border-bottom: 2px solid #eee;
  padding-bottom: 10px;
}
.cards {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
  gap: 15px;
  margin-top: 20px;
}
.card {
  background: #faf8ff;
  border-radius: 12px;
  padding: 10px;
  box-shadow: 0 4px 10px rgba(140,82,255,0.1);
  text-align: center;
  transition: transform 0.2s ease, box-shadow 0.2s ease;
}
.card:hover {
  transform: translateY(-4px);
  box-shadow: 0 8px 15px rgba(140,82,255,0.2);
}
.card img {
  width: 100%;
  height: 120px;
  border-radius: 8px;
  object-fit: cover;
  margin-bottom: 8px;
}
.card h3 {
  color: #4b0082;
  font-size: 15px;
  margin: 4px 0;
}
.status {
  font-weight: 600;
  color: #6a2aff;
  font-size: 13px;
}
.back-btn {
  display: inline-block;
  margin-bottom: 20px;
  padding: 10px 20px;
  background: #6a2aff;
  color: white;
  border-radius: 8px;
  text-decoration: none;
  font-weight: bold;
  transition: 0.3s;
}
.back-btn:hover {
  background: #4b00b0;
}
.empty {
  text-align: center;
  color: #555;
  font-style: italic;
  margin-top: 10px;
}
</style>
</head>
<body>

<div class="container">
  <a href="lost.php" class="back-btn">← Back</a>

  <div class="profile">
    <img src="images/<?php echo htmlspecialchars($profile_img); ?>" alt="Profile">
    <h1><?php echo htmlspecialchars($username); ?>’s Account</h1>
  </div>

  <div class="section">
    <h2>My Lost Items</h2>
    <div class="cards">
      <?php if ($lost_result->num_rows > 0): ?>
        <?php while ($lost = $lost_result->fetch_assoc()): ?>
          <div class="card">
            <img src="<?php echo htmlspecialchars(getImagePath($lost['image_path'])); ?>" alt="Lost Item">
            <h3><?php echo htmlspecialchars($lost['description']); ?></h3>
            <p class="status"><?php echo $lost['claimed'] ? 'Claimed' : 'Unclaimed'; ?></p>
          </div>
        <?php endwhile; ?>
      <?php else: ?>
        <p class="empty">You haven’t posted any lost items yet.</p>
      <?php endif; ?>
    </div>
  </div>

  <div class="section">
    <h2>My Found Items</h2>
    <div class="cards">
      <?php if ($found_result->num_rows > 0): ?>
        <?php while ($found = $found_result->fetch_assoc()): ?>
          <div class="card">
            <img src="<?php echo htmlspecialchars(getImagePath($found['image_path'])); ?>" alt="Found Item">
            <h3><?php echo htmlspecialchars($found['item_name']); ?></h3>
            <p class="status"><?php echo $found['claimed'] ? 'Claimed' : 'Unclaimed'; ?></p>
          </div>
        <?php endwhile; ?>
      <?php else: ?>
        <p class="empty">You haven’t posted any found items yet.</p>
      <?php endif; ?>
    </div>
  </div>
</div>

</body>
</html>
