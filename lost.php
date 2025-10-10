<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
  header("Location: SignIn_SignUp.html");
  exit();
}

$user_id = $_SESSION['user_id'];

// Fetch user info
$sql = "SELECT profile_img, username FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($profile_img, $username);
$stmt->fetch();
$stmt->close();

/* ✅ Fetch recent lost items */
$lost_query = "SELECT id, description, image_path, claimed FROM lost_items ORDER BY id DESC LIMIT 2";
$recent_lost = $conn->query($lost_query);

/* ✅ Fetch recent claimed item */
$claimed_query = "SELECT id, item_name, image_path FROM found_items WHERE claimed = 1 ORDER BY id DESC LIMIT 1";
$recent_claimed = $conn->query($claimed_query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Lost & Found Dashboard</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
* {
  margin: 0; padding: 0; box-sizing: border-box;
  font-family: 'Inter', sans-serif;
}
body {
  background: linear-gradient(135deg, #f9f6ff, #f0eaff);  
  color: #333;
  height: 100vh;
  overflow: hidden;
}

/* HEADER */
nav {
  width: 100%;
  background: linear-gradient(90deg, #a55bff, #b46aff, #c48aff);
  padding: 18px 60px;
  box-shadow: 0 3px 15px rgba(0, 0, 0, 0.15);
  display: flex;
  align-items: center;
  justify-content: space-between;
  position: fixed;
  top: 0; left: 0;
  z-index: 100;
  border-bottom-left-radius: 25px;
  border-bottom-right-radius: 25px;
}

nav .logo {
  font-size: 1.7rem;
  font-weight: 700;
  color: #fff;
  display: flex;
  align-items: center;
  gap: 10px;
}

nav ul {
  display: flex; list-style: none; gap: 25px; align-items: center;
}

nav ul li {
  position: relative;
  padding-bottom: 15px; /* smooth hover area */
}
<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
  header("Location: SignIn_SignUp.html");
  exit();
}

$user_id = $_SESSION['user_id'];

// Fetch user info
$sql = "SELECT profile_img, username FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($profile_img, $username);
$stmt->fetch();
$stmt->close();

/* ✅ Fetch recent lost items */
$lost_query = "SELECT id, description, image_path, claimed FROM lost_items ORDER BY id DESC LIMIT 2";
$recent_lost = $conn->query($lost_query);

/* ✅ Fetch recent claimed item */
$claimed_query = "SELECT id, item_name, image_path FROM found_items WHERE claimed = 1 ORDER BY id DESC LIMIT 1";
$recent_claimed = $conn->query($claimed_query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Lost & Found Dashboard</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
* {
  margin: 0; padding: 0; box-sizing: border-box;
  font-family: 'Inter', sans-serif;
}
body {
  background: linear-gradient(135deg, #f9f6ff, #f0eaff);  
  color: #333;
  height: 100vh;
  overflow: hidden;
}

/* HEADER */
nav {
  width: 100%;
  background: linear-gradient(90deg, #a55bff, #b46aff, #c48aff);
  padding: 15px 60px;
  box-shadow: 0 3px 15px rgba(0, 0, 0, 0.15);
  display: flex;
  align-items: center;
  justify-content: space-between;
  position: fixed;
  top: 0; left: 0;
  z-index: 100;
  border-bottom-left-radius: 25px;
  border-bottom-right-radius: 25px;
}

/* Logo styling */
nav .logo {
  font-size: 1.7rem;
  font-weight: 700;
  color: #fff;
  display: flex;
  align-items: center;
  gap: 10px;
}

/* Adjust nav list to appear slightly lower for balance */
nav ul {
  display: flex;
  list-style: none;
  gap: 30px;
  align-items: center;
  position: relative;
  top: 3px; /* moves links a little lower visually */
}

nav ul li {
  position: relative;
  padding-bottom: 15px; /* smooth hover area */
}

nav ul li a {
  text-decoration: none; 
  color: #fff; 
  font-weight: 700;
  font-size: 1rem; 
  padding: 6px 17px; 
  border-radius: 8px;
  transition: 0.3s ease;
}
nav ul li a:hover {
  background: rgba(255, 255, 255, 0.25);
}

/* ======= DROPDOWN (hover fixed + better position) ======= */
.dropdown-content {
  display: flex;
  flex-direction: row;
  position: absolute;
  top: 100%;
  left: 50%;
  transform: translateX(-50%) translateY(10px);
  opacity: 0;
  background: rgba(255, 255, 255, 0.97);
  border-radius: 40px;
  padding: 8px 12px;
  box-shadow: 0 6px 20px rgba(140, 82, 255, 0.25);
  pointer-events: none;
  transition: all 0.35s ease;
  backdrop-filter: blur(8px);
  min-width: max-content;
}

.dropdown-content a {
  color: #6a2aff;
  text-decoration: none;
  font-weight: 600;
  padding: 10px 18px;
  border-radius: 25px;
  transition: all 0.3s ease;
  white-space: nowrap;
}

.dropdown-content a:hover {
  background: linear-gradient(90deg, #a55bff, #c48aff);
  color: #fff;
  box-shadow: 0 3px 10px rgba(140, 82, 255, 0.25);
}

/* Hover stays active */
nav ul li:hover .dropdown-content,
.dropdown-content:hover {
  opacity: 1;
  transform: translateX(-50%) translateY(0);
  pointer-events: auto;
}

/* Logout button */
nav .logout {
  background: #fff;
  color: #a55bff;
  padding: 10px 22px;
  border-radius: 30px;
  font-weight: 600;
  border: 2px solid transparent;
  transition: 0.3s;
  display: flex;
  align-items: center;
  gap: 8px;
  cursor: pointer;
}
nav .logout:hover {
  background: transparent;
  color: #fff;
  border: 2px solid #fff;
  box-shadow: 0 0 10px rgba(255, 255, 255, 0.3);
}

/* CONTAINER */
.container {
  max-width: 1200px;
  margin: 130px auto 0 auto;
  background: #ffffff;
  border-radius: 25px;
  padding: 40px;
  box-shadow: 0 8px 25px rgba(140, 82, 255, 0.15);
  height: calc(100vh - 150px);
  overflow-y: auto;
}

.container::-webkit-scrollbar {
  width: 6px;
}
.container::-webkit-scrollbar-thumb {
  background: rgba(140, 82, 255, 0.4);
  border-radius: 10px;
}

/* TOP BAR */
.top-bar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  border-bottom: 2px solid #eee;
  padding-bottom: 15px;
}

.top-bar h2 {
  font-weight: 700;
  font-size: 1.7rem;
  color: #6a2aff;
}

.top-bar .user {
  display: flex; align-items: center; gap: 12px;
}

.top-bar .user img {
  width: 55px; height: 55px;
  border-radius: 50%;
  object-fit: cover;
  border: 2px solid #a55bff;
  box-shadow: 0 0 10px rgba(140, 82, 255, 0.3);
}

/* Search Bar */
.search-bar {
  margin-top: 20px;
  display: flex;
  justify-content: center;
  align-items: center;
  gap: 10px;
}
.search-bar input {
  width: 60%;
  padding: 10px 15px;
  border-radius: 20px;
  border: 2px solid #a55bff;
  outline: none;
}
.search-bar button {
  background: linear-gradient(90deg, #a55bff, #c48aff);
  border: none;
  padding: 10px 20px;
  border-radius: 20px;
  color: #fff;
  font-weight: 600;
  cursor: pointer;
}
.search-bar button:hover {
  transform: scale(1.05);
}

/* CARDS */
.cards {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
  gap: 25px;
  margin-top: 30px;
}

.card {
  background: linear-gradient(145deg, #ffffff, #f2eaff);
  border-radius: 18px;
  padding: 20px;
  text-align: center;
  box-shadow: 0 6px 15px rgba(140,82,255,0.1);
  transition: all 0.3s ease;
}

.card:hover {
  transform: translateY(-6px);
  box-shadow: 0 12px 25px rgba(140,82,255,0.2);
}

.card img {
  width: 100%;
  height: 160px;
  border-radius: 12px;
  object-fit: cover;
  background: #ddd;
  margin-bottom: 12px;
}

.card h3 {
  font-size: 1rem;
  font-weight: 600;
  color: #4b0082;
  margin-bottom: 6px;
}

.status {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  font-weight: 600;
  border-radius: 30px;
  padding: 6px 12px;
  font-size: 0.8rem;
}
.status.missing {
  color: #b94cff;
  background: rgba(185, 76, 255, 0.1);
}
.status.claimed {
  color: #2ecc71;
  background: rgba(46, 204, 113, 0.1);
}

.card button {
  background: linear-gradient(90deg, #a55bff, #c48aff);
  color: #fff;
  border: none;
  border-radius: 8px;
  padding: 8px 14px;
  margin-top: 8px;
  cursor: pointer;
  font-weight: 600;
  transition: 0.3s;
}
.card button:hover {
  transform: scale(1.05);
  background: linear-gradient(90deg, #9244ff, #b971ff);
}
</style>
</head>

<body>
<!-- NAV -->
<nav>
  <div class="logo">Lost & Found</div>
  <ul>
    <li><a href="home.php">Home</a></li>

    <li class="dropdown">
      <a href="#">Claim Items ▾</a>
      <div class="dropdown-content">
        <a href="items.php">Lost Items</a>
        <a href="found_items.php">Found Items</a>
      </div>
    </li>

    <li class="dropdown">
      <a href="#">Post Items ▾</a>
      <div class="dropdown-content">
        <a href="post_lost_item.php">Post Lost Item</a>
        <a href="post_found_item.php">Post Found Item</a>
      </div>
    </li>

    <li><a href="my_claims.php">My Claims</a></li>
    <li><a href="notifications.php">Notifications</a></li>
  </ul>

  <div class="logout" id="logoutBtn">
    <i class="fa-solid fa-arrow-right-from-bracket"></i> Logout
  </div>
</nav>

<!-- MAIN CONTENT -->
<div class="container">
  <div class="top-bar">
    <h2>Welcome, <?php echo htmlspecialchars($username); ?> 👋</h2>
    <div class="user">
      <img src="images/<?php echo htmlspecialchars($profile_img); ?>" alt="Profile">
    </div>
  </div>

  <!-- SEARCH BAR -->
  <div class="search-bar">
    <input type="text" id="searchInput" placeholder="Search for items...">
    <button onclick="searchItems()">Search</button>
  </div>

  <div id="searchResults" class="cards" style="display:none;"></div>

  <div class="cards" id="defaultCards">
    <?php while ($lost = $recent_lost->fetch_assoc()): ?>
      <div class="card">
        <?php 
          $lost_img = $lost['image_path'];
          if (!str_starts_with($lost_img, 'uploads/')) {
              $lost_img = 'uploads/' . $lost_img;
          }
          if (!file_exists($lost_img)) {
              $lost_img = 'images/default-placeholder.png';
          }
        ?>
        <img src="<?php echo htmlspecialchars($lost_img); ?>" alt="Lost Item">
        <h3><?php echo htmlspecialchars($lost['description']); ?></h3>
        <p class="status <?php echo $lost['claimed'] ? 'claimed' : 'missing'; ?>">
          <?php echo $lost['claimed'] ? 'Claimed' : 'Missing'; ?>
        </p>
        <button onclick="window.location.href='items.php?id=<?php echo $lost['id']; ?>'">View Details</button>
      </div>
    <?php endwhile; ?>

    <?php if ($recent_claimed->num_rows > 0): ?>
      <?php while ($found = $recent_claimed->fetch_assoc()): ?>
        <div class="card">
          <?php 
            $found_img = $found['image_path'];
            if (!str_starts_with($found_img, 'uploads/')) {
                $found_img = 'uploads/' . $found_img;
            }
            if (!file_exists($found_img)) {
                $found_img = 'images/default-placeholder.png';
            }
          ?>
          <p>Recently Claimed Item</p>
          <img src="<?php echo htmlspecialchars($found_img); ?>" alt="Found Item">
          <h3><?php echo htmlspecialchars($found['item_name']); ?></h3>
          <p class="status claimed"><i class="fa-solid fa-check-circle"></i> Claimed</p>
          <button onclick="window.location.href='found_items.php?id=<?php echo $found['id']; ?>'">View Details</button>
        </div>
      <?php endwhile; ?>
    <?php else: ?>
      <div class="card"><h3>No recent claimed items found.</h3></div>
    <?php endif; ?>
  </div>
</div>

<script>
function searchItems() {
  const query = document.getElementById('searchInput').value.trim();
  if (query === '') {
    document.getElementById('defaultCards').style.display = 'grid';
    document.getElementById('searchResults').style.display = 'none';
    return;
  }

  fetch('search_items.php?q=' + encodeURIComponent(query))
    .then(response => response.text())
    .then(data => {
      document.getElementById('defaultCards').style.display = 'none';
      const results = document.getElementById('searchResults');
      results.innerHTML = data;
      results.style.display = 'grid';
    });
}

const logoutBtn = document.getElementById('logoutBtn');
logoutBtn.addEventListener('click', () => {
  if (confirm("Are you sure you want to log out?")) {
    window.location.href = 'logout.php';
  }
});
</script>
</body>
</html>
