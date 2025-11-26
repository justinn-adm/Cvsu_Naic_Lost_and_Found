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

// Fetch lost items
$lost_items = $conn->prepare("SELECT id, description, image_path, claimed FROM lost_items WHERE uploader_name = ? ORDER BY id DESC");
$lost_items->bind_param("s", $username);
$lost_items->execute();
$lost_result = $lost_items->get_result();

// Fetch found items
$found_items = $conn->prepare("SELECT id, item_name, image_path, claimed FROM found_items WHERE user_id = ? ORDER BY id DESC");
$found_items->bind_param("i", $user_id);
$found_items->execute();
$found_result = $found_items->get_result();

// Image path helper
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
<title>My Account - Lost & Found</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
<style>
body {
  font-family: 'Inter', sans-serif;
  background: #f0f2f7;
  margin: 0;
  padding: 20px;
  color: #333;
}

/* Container */
.container {
  max-width: 800px;
  margin: auto;
  background: #fff;
  padding: 25px 30px;
  border-radius: 16px;
  box-shadow: 0 12px 30px rgba(0,0,0,0.08);
  position: relative;
}

/* Back button */
.back-btn {
  display: inline-block;
  margin-bottom: 25px;
  padding: 10px 18px;
  background: #6a2aff;
  color: #fff;
  border-radius: 8px;
  text-decoration: none;
  font-weight: 600;
  transition: 0.3s;
}
.back-btn:hover { background: #4b00b0; }

/* Profile (top-right) */
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

/* Section titles */
.section {
  margin-top: 80px; /* space to avoid overlap with profile */
}
.section h2 {
  font-size: 18px;
  color: #6a2aff;
  margin-bottom: 15px;
  border-bottom: 1px solid #e0e0e0;
  padding-bottom: 6px;
}

/* Vertical cards */
.cards {
  display: flex;
  flex-direction: column;
  gap: 15px;
}

/* Card styles */
.card {
  display: flex;
  gap: 15px;
  background: #fff;
  border-radius: 12px;
  overflow: hidden;
  box-shadow: 0 4px 12px rgba(0,0,0,0.08);
  transition: transform 0.2s ease, box-shadow 0.2s ease;
  cursor: pointer;
  border-left: 5px solid #6a2aff;
}
.card:hover {
  transform: translateY(-4px);
  box-shadow: 0 8px 20px rgba(0,0,0,0.15);
}
.card img {
  width: 120px;
  height: 120px;
  object-fit: cover;
  border-radius: 8px;
  flex-shrink: 0;
}
.card-body {
  padding: 12px 10px;
  display: flex;
  flex-direction: column;
  justify-content: center;
}
.card-body h3 {
  font-size: 16px;
  margin: 0 0 6px 0;
  font-weight: 600;
  color: #4b0082;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}
.card-body p {
  font-size: 14px;
  color: #555;
  margin: 4px 0 8px 0;
}
.status-badge {
  display: inline-block;
  padding: 3px 10px;
  font-size: 12px;
  font-weight: 600;
  border-radius: 12px;
  color: #fff;
}
.status-unclaimed { background: #28a745; }
.status-claimed { background: #dc3545; }

/* View all button */
.view-all-btn {
  margin-top: 5px;
  display: inline-block;
  padding: 6px 12px;
  background: #6a2aff;
  color: #fff;
  font-size: 12px;
  font-weight: 600;
  border-radius: 6px;
  text-decoration: none;
  transition: 0.2s;
}
.view-all-btn:hover { background: #4b00b0; }

/* Empty state */
.empty {
  text-align: center;
  color: #777;
  font-style: italic;
  margin-top: 8px;
}
</style>
</head>
<body>

<div class="container">
  <a href="feeds.php" class="back-btn">← Back</a>

  <div class="profile">
    <img src="images/<?php echo htmlspecialchars($profile_img); ?>" alt="Profile">
    <h1><?php echo htmlspecialchars($username); ?></h1>
  </div>

  <div class="section">
    <h2>My Lost Items</h2>
    <div class="cards">
      <?php if ($lost_result->num_rows > 0): ?>
        <?php while ($lost = $lost_result->fetch_assoc()): ?>
          <div class="card">
            <img src="<?php echo htmlspecialchars(getImagePath($lost['image_path'])); ?>" alt="Lost Item">
            <div class="card-body">
              <h3 title="<?php echo htmlspecialchars($lost['description']); ?>">
                <?php echo htmlspecialchars($lost['description']); ?>
              </h3>
              <p>Reported recently</p>
              <span class="status-badge <?php echo $lost['claimed'] ? 'status-claimed' : 'status-unclaimed'; ?>">
                <?php echo $lost['claimed'] ? 'Claimed' : 'Unclaimed'; ?>
              </span>
              <a href="lost_items.php?id=<?php echo $lost['id']; ?>" class="view-all-btn">View Details</a>
            </div>
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
            <div class="card-body">
              <h3 title="<?php echo htmlspecialchars($found['item_name']); ?>">
                <?php echo htmlspecialchars($found['item_name']); ?>
              </h3>
              <p>Found recently</p>
              <span class="status-badge <?php echo $found['claimed'] ? 'status-claimed' : 'status-unclaimed'; ?>">
                <?php echo $found['claimed'] ? 'Claimed' : 'Unclaimed'; ?>
              </span>
              <a href="found_items.php?id=<?php echo $found['id']; ?>" class="view-all-btn">View Details</a>
            </div>
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
