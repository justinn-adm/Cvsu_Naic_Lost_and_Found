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

/* 🔔 Fetch total unread notifications (claims + posts) */
$notif_sql = "
  SELECT
    (
      SELECT COUNT(*) 
      FROM claims 
      WHERE user_id = ? 
        AND status IN ('Approved','Rejected') 
        AND is_read = 0
    ) +
    (
      SELECT COUNT(*) 
      FROM found_items 
      WHERE user_id = ? 
        AND status IN ('Approved','Rejected') 
        AND is_read = 0
    ) +
    (
      SELECT COUNT(*) 
      FROM lost_items 
      WHERE user_id = ? 
        AND status IN ('Approved','Rejected') 
        AND is_read = 0
    ) AS unread_count
";
$stmt_notif = $conn->prepare($notif_sql);
$stmt_notif->bind_param("iii", $user_id, $user_id, $user_id);
$stmt_notif->execute();
$result_notif = $stmt_notif->get_result();
$row_notif = $result_notif->fetch_assoc();
$unread_count = $row_notif['unread_count'] ?? 0;
$stmt_notif->close();

/* Fetch recent lost and found items */
$lost_query = "SELECT id, description, image_path, claimed FROM lost_items ORDER BY id DESC LIMIT 2";
$recent_lost = $conn->query($lost_query);

$found_query = "SELECT id, description, image_path FROM found_items ORDER BY id DESC LIMIT 2";
$recent_found = $conn->query($found_query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Lost & Found | User Dashboard</title>

<!-- Fonts & Icons -->
<link href="https://fonts.googleapis.com/css2?family=Agrandir:wght@400;600;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<style>
* { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Agrandir', sans-serif; }

body {
  background: url('images/purple.png') no-repeat center center fixed;
  background-size: cover;
  color: #333;
  min-height: 100vh;
  overflow-x: hidden;
  animation: fadeInBody 1.2s ease-in-out forwards;
}

/* NAVBAR */
nav {
  width: 100%;
  display: flex;
  justify-content: center;
  gap: 80px;
  position: fixed;
  top: 0; left: 0;
  z-index: 100;
  backdrop-filter: blur(10px);
  border-bottom: 1px solid rgba(255,255,255,0.2);
  padding: 20px 0;
  animation: navbarDrop 1.2s ease-in-out forwards;
}
.nav-links { display: flex; align-items: center; gap: 120px; }
.nav-item { position: relative; }
.nav-links a {
  text-decoration: none;
  color: #000;
  font-weight: 800;
  font-size: 1.05rem;
  transition: all 0.3s ease;
  text-transform: uppercase;
}
.nav-links a:hover { color: #d9b3ff; }
.nav-links a::after {
  content: '';
  position: absolute;
  left: 0; bottom: -6px;
  width: 0%;
  height: 2px;
  background: #9333ea;
  transition: width 0.3s;
}
.nav-links a:hover::after { width: 100%; }

/* DROPDOWN */
.dropdown-content {
  visibility: hidden;
  opacity: 0;
  transform: translateY(8px);
  position: absolute;
  top: 120%; left: 50%;
  transform: translateX(-50%) translateY(8px);
  background: rgba(255,255,255,0.98);
  box-shadow: 0 8px 20px rgba(147,51,234,0.25);
  border-radius: 8px;
  overflow: hidden;
  min-width: 180px;
  transition: all 0.25s ease-in-out;
  pointer-events: none;
  text-align: left;
}
.dropdown-content a {
  display: block;
  padding: 10px 15px;
  color: #4b0082;
  font-weight: 600;
  text-decoration: none;
  font-size: 0.9rem;
}
.dropdown-content a:hover {
  background: linear-gradient(90deg,#9333ea,#a855f7);
  color: #fff;
}
.dropdown.show .dropdown-content {
  visibility: visible;
  opacity: 1;
  transform: translateX(-50%) translateY(0);
  pointer-events: auto;
  z-index: 999;
}

/* Notification Bell */
.notification-bell { position: relative; font-size: 1.3rem; color: #6a2aff; cursor: pointer; transition: transform 0.3s; }
.notification-bell:hover { transform: scale(1.2); }
.bell-badge {
  position: absolute;
  top: -6px; right: -8px;
  background: #ff4757;
  color: #fff;
  font-size: .7rem;
  font-weight: 700;
  padding: 3px 6px;
  border-radius: 50%;
  animation: pulse 1.3s infinite;
}
@keyframes pulse { 0%,100% { transform: scale(1); } 50% { transform: scale(1.3); } }

/* Logout */
.logout {
  background: linear-gradient(135deg,#9333ea,#a855f7);
  color: #fff;
  padding: 10px 22px;
  border-radius: 30px;
  font-weight: 600;
  display: flex;
  align-items: center;
  gap: 8px;
  cursor: pointer;
  transition: all 0.3s;
}
.logout:hover {
  transform: scale(1.05);
  box-shadow: 0 8px 20px rgba(147,51,234,0.4);
}

/* MAIN CONTAINER */
.container {
  max-width: 1200px;
  margin: 140px auto 60px auto;
  background: rgba(255,255,255,0.92);
  border-radius: 25px;
  padding: 50px;
  box-shadow: 0 8px 25px rgba(140,82,255,0.25);
}

/* Sections */
.section { margin-top: 40px; }
.section h2 { text-align: center; font-weight: 800; font-size: 1.2rem; margin-bottom: 20px; }
.section-divider {
  width: 60px; height: 4px;
  background: linear-gradient(90deg,#9333ea,#a855f7);
  margin: 0 auto 25px auto;
  border-radius: 4px;
}

/* Top Bar */
.top-bar { display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #eee; padding-bottom: 15px; }
.top-bar h2 { font-weight: 800; font-size: 1.7rem; color: #6a2aff; }
.top-bar .user img { width: 55px; height: 55px; border-radius: 50%; object-fit: cover; border: 2px solid #9333ea; cursor: pointer; transition: .3s; }
.top-bar .user img:hover { transform: scale(1.08); }

/* Search Section */
.search-section { margin-top: 30px; text-align: center; }
.search-bar { display: flex; justify-content: center; gap: 10px; flex-wrap: wrap; }
.search-bar input { width: 60%; min-width: 180px; padding: 10px 15px; border-radius: 25px; border: 2px solid #9333ea; outline: none; }
.search-bar button { background: linear-gradient(90deg,#9333ea,#a855f7); border: none; padding: 10px 20px; border-radius: 25px; color: #fff; font-weight: 600; cursor: pointer; transition: .3s; }
.search-bar button:hover { transform: scale(1.05); }

/* Cards */
.cards {
  display: grid;
  grid-template-columns: repeat(auto-fit,minmax(180px,1fr));
  gap: 15px;
  margin-top: 25px;
}
.card {
  background: linear-gradient(145deg,#ffffff,#f2eaff);
  border-radius: 18px;
  padding: 15px;
  text-align: center;
  box-shadow: 0 6px 15px rgba(140,82,255,0.1);
  transition: .3s;
}
.card:hover { transform: translateY(-6px); box-shadow: 0 12px 25px rgba(140,82,255,0.35); }
.image-container { position: relative; display: inline-block; }
.image-container img { width: 100%; height: 140px; border-radius: 12px; object-fit: cover; margin-bottom: 8px; }
.label { position: absolute; top: 10px; left: 10px; color: #fff; font-size: 0.7rem; font-weight: 700; padding: 4px 8px; border-radius: 8px; text-transform: uppercase; }
.label.lost { background: linear-gradient(90deg, #9333ea, #c084fc); }
.label.found { background: linear-gradient(90deg, #10b981, #34d399); }
.card h3 { font-size: 0.95rem; font-weight: 600; color: #4b0082; margin-bottom: 4px; }
.status { display: inline-flex; align-items: center; justify-content: center; gap: 8px; font-weight: 600; border-radius: 20px; padding: 4px 10px; font-size: .75rem; }
.status.missing { color: #b94cff; background: rgba(185,76,255,0.1); }
.status.claimed { color: #2ecc71; background: rgba(46,204,113,0.1); }
.card button { background: linear-gradient(90deg,#9333ea,#a855f7); color: #fff; border: none; border-radius: 8px; padding: 6px 10px; margin-top: 6px; cursor: pointer; font-weight: 600; font-size: 0.85rem; transition: .3s; }
.card button:hover { transform: scale(1.05); background: linear-gradient(90deg,#9244ff,#b971ff); }

/* Back To Top */
#backToTop { position: fixed; bottom: 25px; right: 25px; background: linear-gradient(135deg,#9333ea,#a855f7); color: #fff; border: none; border-radius: 50%; width: 45px; height: 45px; display: none; justify-content: center; align-items: center; cursor: pointer; box-shadow: 0 6px 12px rgba(140,82,255,0.3); transition: .3s; }
#backToTop:hover { transform: scale(1.15); }

@keyframes fadeInBody { from { opacity: 0; } to { opacity: 1; } }
@keyframes navbarDrop { 0% { opacity: 0; transform: translateY(-60px); } 100% { opacity: 1; transform: translateY(0); } }
</style>
</head>

<body>
<nav>
  <div class="nav-links">
    <div class="nav-item"><a href="feeds.php">Home</a></div>
    <div class="nav-item dropdown">
      <a href="#">View Items ▾</a>
      <div class="dropdown-content">
        <a href="lost_items.php">View My Lost Items</a>
        <a href="found_items.php">View Found Items</a>
      </div>
    </div>
    <div class="nav-item dropdown">
      <a href="#">Post Item ▾</a>
      <div class="dropdown-content">
        <a href="post_lost_item.php">Post Lost Item</a>
        <a href="post_found_item.php">Post Found Item</a>
      </div>
    </div>
    <div class="nav-item">
      <a href="my_claims.php" class="notification-bell" title="My Claims & Posts">
        <i class="fa-solid fa-bell"></i>
        <?php if ($unread_count > 0): ?>
          <span class="bell-badge"><?php echo $unread_count; ?></span>
        <?php endif; ?>
      </a>
    </div>
    <div class="logout" id="logoutBtn">
      <i class="fa-solid fa-arrow-right-from-bracket"></i> Logout
    </div>
  </div>
</nav>

<div class="container">
  <!-- Top Bar -->
  <div class="top-bar">
    <h2>Welcome, <?php echo htmlspecialchars($username); ?> 👋</h2>
    <div class="user">
      <img src="images/<?php echo htmlspecialchars($profile_img); ?>" alt="Profile" onclick="window.location.href='my_account.php'">
    </div>
  </div>

  <!-- Search Section -->
  <div class="search-section">
    <div class="search-bar">
      <input type="text" id="searchInput" placeholder="Search for items...">
      <button onclick="searchItems()">Search</button>
    </div>
    <div id="searchResults" class="cards"></div>
  </div>

  <!-- LOST ITEMS SECTION -->
  <div id="recentLostSection" class="section">
    <h2 style="color:#9333ea;">Recently Reported Lost Items</h2>
    <div class="section-divider"></div>
    <div class="cards">
      <?php while ($lost = $recent_lost->fetch_assoc()): 
        $lost_img = $lost['image_path'];
        if (!str_starts_with($lost_img, 'uploads/')) $lost_img = 'uploads/' . $lost_img;
        if (!file_exists($lost_img)) $lost_img = 'images/default-placeholder.png';
      ?>
        <div class="card">
          <div class="image-container">
            <img src="<?php echo htmlspecialchars($lost_img); ?>" alt="Lost Item">
            <span class="label lost">Lost</span>
          </div>
          <h3><?php echo htmlspecialchars($lost['description']); ?></h3>
          <p class="status <?php echo $lost['claimed'] ? 'claimed' : 'missing'; ?>">
            <?php echo $lost['claimed'] ? 'Claimed' : 'Missing'; ?>
          </p>
          <button onclick="window.location.href='lost_items.php?id=<?php echo $lost['id']; ?>'">View Details</button>
        </div>
      <?php endwhile; ?>
    </div>
  </div>

  <!-- FOUND ITEMS SECTION -->
  <div id="recentFoundSection" class="section">
    <h2 style="color:#10b981;">Recently Found Items</h2>
    <div class="section-divider" style="background:linear-gradient(90deg,#10b981,#34d399);"></div>
    <div class="cards">
      <?php while ($found = $recent_found->fetch_assoc()): 
        $found_img = $found['image_path'];
        if (!str_starts_with($found_img, 'uploads/')) $found_img = 'uploads/' . $found_img;
        if (!file_exists($found_img)) $found_img = 'images/default-placeholder.png';
      ?>
        <div class="card">
          <div class="image-container">
            <img src="<?php echo htmlspecialchars($found_img); ?>" alt="Found Item">
            <span class="label found">Found</span>
          </div>
          <h3><?php echo htmlspecialchars($found['description']); ?></h3>
          <button onclick="window.location.href='found_items.php?id=<?php echo $found['id']; ?>'">View Details</button>
        </div>
      <?php endwhile; ?>
    </div>
  </div>
</div>

<button id="backToTop"><i class="fa-solid fa-chevron-up"></i></button>

<script>
/* Dropdown Hover */
document.querySelectorAll('.dropdown').forEach(drop => {
  let timeout;
  drop.addEventListener('mouseenter', () => { clearTimeout(timeout); drop.classList.add('show'); });
  drop.addEventListener('mouseleave', () => { timeout = setTimeout(() => drop.classList.remove('show'), 250); });
});

/* Search Function */
function searchItems() {
  const query = document.getElementById('searchInput').value.trim();
  const results = document.getElementById('searchResults');
  const recentLost = document.getElementById('recentLostSection');
  const recentFound = document.getElementById('recentFoundSection');

  if (query === '') {
    results.style.display = 'none';
    recentLost.style.display = 'block';
    recentFound.style.display = 'block';
    return;
  }

  fetch('search_items.php?q=' + encodeURIComponent(query))
    .then(r => r.text())
    .then(data => {
      results.innerHTML = data || "<p style='text-align:center;color:#9333ea;'>No items found 😕</p>";
      results.style.display = 'grid';
      recentLost.style.display = 'none';
      recentFound.style.display = 'none';
    });
}

/* Logout */
document.getElementById('logoutBtn').addEventListener('click', () => {
  if (confirm("Are you sure you want to log out?")) window.location.href = 'logout.php';
});

/* Back To Top */
const backBtn = document.getElementById("backToTop");
window.addEventListener("scroll", () => {
  backBtn.style.display = window.scrollY > 200 ? "flex" : "none";
});
backBtn.addEventListener("click", () => window.scrollTo({ top: 0, behavior: "smooth" }));
</script>
</body>
</html>
