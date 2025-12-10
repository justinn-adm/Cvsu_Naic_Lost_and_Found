<?php 
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: SignIn_SignUp.html");
    exit();
}

$user_id = $_SESSION['user_id'];

/* Fetch user info */
$sql = "SELECT profile_img, username FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($profile_img, $username);
$stmt->fetch();
$stmt->close();

/* Fetch unread notifications */
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
$unread_count = $stmt_notif->get_result()->fetch_assoc()['unread_count'] ?? 0;
$stmt_notif->close();

/* Fetch recent FOUND items only (LOST removed) */
$found_query = "SELECT id, description, image_path FROM found_items WHERE status = 'approved' ORDER BY id DESC LIMIT 2";
$recent_found = $conn->query($found_query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>Lost & Found | User Dashboard</title>

<!-- Fonts & Icons -->
<link href="https://fonts.googleapis.com/css2?family=Agrandir:wght@400;600;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<style>
/* ===== GENERAL ===== */
* { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Agrandir', sans-serif; }

body {
  background: url('images/purple.png') no-repeat center center fixed;
  background-size: cover;
  min-height: 100vh;
  animation: fadeInBody 1.2s ease-in-out forwards;
  overflow-x: hidden;
}

/* ===== NAVBAR ===== */
nav {
  width: 100%;
  display: flex;
  justify-content: center;
  position: fixed;
  top: 0;
  padding: 20px 0;
  backdrop-filter: blur(10px);
  border-bottom: 1px solid rgba(255,255,255,0.2);
  z-index: 100;
}
.nav-links {
  display: flex;
  align-items: center;
  gap: 150px; /* reduced gap to be more robust */
  position: relative;
}

/* ensure each dropdown parent is the positioning anchor */
.nav-item {
  position: relative;
}

/* main nav links */
.nav-links a, .nav-links > .nav-item > a {
  color: #000; text-decoration: none; font-weight: 800; font-size: 1.05rem;
  text-transform: uppercase; transition: 0.3s;
  position: relative;
  display: inline-block;
  padding: 6px 2px;
}
.nav-links a:hover { color: #d9b3ff; }
.nav-links a::after {
  content: ''; position: absolute; left: 0; bottom: -6px;
  width: 0%; height: 2px; background: #9333ea; transition: 0.3s;
}
.nav-links a:hover::after { width: 100%; }

/* Dropdown content - absolute, centered relative to .nav-item */
.dropdown-content {
  visibility: hidden;
  opacity: 0;
  pointer-events: none;

  position: absolute;
  top: calc(100% + 8px); /* sit a little below the parent link */
  left: 50%;
  transform: translateX(-50%) translateY(8px); /* center horizontally, slight Y offset */
  background: rgba(255,255,255,0.98);
  box-shadow: 0 8px 20px rgba(147,51,234,0.25);
  border-radius: 8px;
  overflow: hidden;
  min-width: 180px;
  transition: opacity 0.22s ease, transform 0.22s ease, visibility 0.22s;
  z-index: 999;
}
.dropdown-content a {
  display: block; padding: 10px 15px; color: #4b0082;
  font-weight: 600; text-decoration: none; font-size: 0.95rem;
  white-space: nowrap;
}
.dropdown-content a:hover {
  background: linear-gradient(90deg,#9333ea,#a855f7);
  color: #fff;
}

/* show dropdown on hover of the parent .nav-item */
.nav-item.show .dropdown-content,
.nav-item:hover .dropdown-content {
  visibility: visible;
  opacity: 1;
  transform: translateX(-50%) translateY(0);
  pointer-events: auto;
}

/* Notifications */
.notification-bell { font-size: 1.3rem; color: #6a2aff; position: relative; cursor: pointer; }
.bell-badge {
  position: absolute; top: -6px; right: -8px;
  background: #ff4757; color: #fff; padding: 3px 6px;
  font-size: .7rem; border-radius: 50%; animation: pulse 1.3s infinite;
}

/* Logout Button */
.logout {
  background: linear-gradient(135deg,#9333ea,#a855f7);
  padding: 10px 22px; border-radius: 30px; color: #fff; font-weight: 600;
  display: flex; gap: 8px; align-items:center; cursor: pointer; transition: 0.3s;
}
.logout:hover { transform: scale(1.05); }

/* ===== MAIN CONTENT ===== */
.container {
  max-width: 1000px; margin: 140px auto 40px auto;
  background: rgba(255,255,255,0.92);
  border-radius: 25px; padding: 25px 40px;
  box-shadow: 0 8px 25px rgba(140,82,255,0.25);
}

/* Top Bar */
.top-bar { display: flex; justify-content: space-between; align-items: center; padding-bottom: 10px; border-bottom: 2px solid #eee; }
.top-bar h2 { color: #6a2aff; font-size: 1.7rem; font-weight: 800; }
.top-bar img {
  width: 55px; height: 55px; border-radius: 50%; border: 2px solid #9333ea;
  object-fit: cover; cursor: pointer; transition: 0.3s;
}

/* Search */
.search-section { margin-top: 20px; text-align: center; }
.search-bar input {
  width: 60%; min-width: 180px; padding: 8px 12px;
  border-radius: 25px; border: 2px solid #9333ea;
}
.search-bar button {
  padding: 8px 18px; border-radius: 25px;
  background: linear-gradient(90deg,#9333ea,#a855f7); color: #fff;
  border: none; font-weight: 600; cursor: pointer; transition: 0.3s;
}

/* Cards */
.cards {
  display: grid; grid-template-columns: repeat(auto-fit,minmax(180px,1fr));
  gap: 12px; margin-top: 20px;
}
.card {
  padding: 12px; border-radius: 18px;
  background: linear-gradient(145deg,#ffffff,#f2eaff);
  text-align: center; transition: 0.3s;
  box-shadow: 0 6px 15px rgba(140,82,255,0.1);
}
.card:hover {
  transform: translateY(-6px);
  box-shadow: 0 12px 25px rgba(140,82,255,0.35);
}
.card img { width: 100%; height: 120px; object-fit: cover; border-radius: 12px; margin-bottom: 8px; }
.card button {
  padding: 8px 16px; border-radius: 12px; color: #fff; font-weight: 600;
  background: linear-gradient(90deg,#9333ea,#a855f7);
  border: none; cursor: pointer; transition: 0.3s;
}

/* Back To Top */
#backToTop {
  position: fixed; bottom: 25px; right: 25px;
  width: 45px; height: 45px; display: none;
  border-radius: 50%; background: linear-gradient(135deg,#9333ea,#a855f7);
  color: #fff; border: none; cursor: pointer;
}

/* Animations */
@keyframes fadeInBody { from { opacity: 0; } to { opacity: 1; } }
@keyframes pulse { 0%,100% { transform: scale(1); } 50% { transform: scale(1.3); } }

/* ===== Responsive tweaks ===== */
@media (max-width: 900px) {
  .nav-links { gap: 24px; }
  .search-bar input { width: 72%; }
}
@media (max-width: 560px) {
  .nav-links { gap: 12px; font-size: 0.95rem; }
  .search-bar input { width: 68%; }
  .nav-links a { font-size: 0.95rem; }
  /* Make dropdown full width on tiny screens */
  .dropdown-content { left: 50%; min-width: 160px; }
}
</style>
</head>

<body>

<nav>
  <div class="nav-links">
    <a href="feeds.php">Home</a>

    <div class="nav-item dropdown" id="viewItemsDropdown">
      <a href="javascript:void(0)" class="dropdown-toggle">View Items ▾</a>
      <div class="dropdown-content" role="menu" aria-labelledby="viewItemsDropdown">
        <a href="lost_items.php">View My Lost Items</a>
        <a href="found_items.php">View Found Items</a>
      </div>
    </div>

    <div class="nav-item dropdown" id="postItemDropdown">
      <a href="javascript:void(0)" class="dropdown-toggle">Post Item ▾</a>
      <div class="dropdown-content" role="menu" aria-labelledby="postItemDropdown">
        <a href="post_lost_item.php">Post Lost Item</a>
        <a href="post_found_item.php">Post Found Item</a>
      </div>
    </div>

    <a href="my_claims.php" class="notification-bell" title="Notifications">
      <i class="fa-solid fa-bell"></i>
      <?php if ($unread_count > 0): ?>
        <span class="bell-badge"><?php echo $unread_count; ?></span>
      <?php endif; ?>
    </a>

    <div class="logout" id="logoutBtn" title="Logout">
      <i class="fa-solid fa-arrow-right-from-bracket"></i> Logout
    </div>
  </div>
</nav>

<div class="container">

  <!-- Top Bar -->
  <div class="top-bar">
    <h2>Welcome, <?php echo htmlspecialchars($username); ?> 👋</h2>
    <img src="images/<?php echo htmlspecialchars($profile_img); ?>" onclick="window.location.href='my_account.php'" alt="Profile image">
  </div>

  <!-- Search -->
  <div class="search-section">
    <div class="search-bar">
      <input type="text" id="searchInput" placeholder="Search for items...">
      <button onclick="searchItems()">Search</button>
    </div>
    <div id="searchResults" class="cards" style="display:none;"></div>
  </div>

  <!-- FOUND ITEMS ONLY (LOST REMOVED) -->
  <div class="section">
    <h2 style="color:#10b981; text-align:center;">Recently Posted Found Items</h2>
    <div class="cards">
      <?php while ($found = $recent_found->fetch_assoc()): 
        $img = $found['image_path'];
        if (!str_starts_with($img, 'uploads/')) $img = 'uploads/' . $img;
      ?>
      <div class="card">
        <img src="<?php echo htmlspecialchars($img); ?>" alt="Found item image">
        <h3><?php echo htmlspecialchars($found['description']); ?></h3>
        <button onclick="window.location.href='found_items.php?id=<?php echo $found['id']; ?>'">View Details</button>
      </div>
      <?php endwhile; ?>
    </div>
  </div>

</div>

<button id="backToTop" title="Back to top"><i class="fa-solid fa-chevron-up"></i></button>

<script>
/* Dropdown: hover for desktop, click/tap for mobile-friendly behavior */
/* Add small debounce to avoid flicker when moving between elements */
document.querySelectorAll('.nav-item.dropdown').forEach(drop => {
  let timeout;

  drop.addEventListener('mouseenter', () => {
    clearTimeout(timeout);
    drop.classList.add('show');
  });
  drop.addEventListener('mouseleave', () => {
    timeout = setTimeout(() => drop.classList.remove('show'), 180);
  });

  // allow click to toggle (useful for touch devices)
  const toggle = drop.querySelector('.dropdown-toggle');
  toggle.addEventListener('click', (e) => {
    e.preventDefault();
    // close other dropdowns
    document.querySelectorAll('.nav-item.dropdown').forEach(d => {
      if (d !== drop) d.classList.remove('show');
    });
    drop.classList.toggle('show');
  });
});

// close dropdown if clicking outside
document.addEventListener('click', (e) => {
  if (!e.target.closest('.nav-item.dropdown')) {
    document.querySelectorAll('.nav-item.dropdown').forEach(d => d.classList.remove('show'));
  }
});

/* Search */
function searchItems() {
  const query = document.getElementById('searchInput').value.trim();
  const results = document.getElementById('searchResults');

  if (query === '') {
    results.innerHTML = '';
    results.style.display = 'none';
    return;
  }

  fetch('search_items.php?q=' + encodeURIComponent(query))
    .then(r => r.text())
    .then(data => {
      results.innerHTML = data || "<p style='text-align:center;color:#9333ea;'>No items found 😕</p>";
      results.style.display = 'grid';
    }).catch(err => {
      results.innerHTML = "<p style='text-align:center;color:#9333ea;'>Search failed. Try again.</p>";
      results.style.display = 'grid';
    });
}

/* Logout */
document.getElementById('logoutBtn').addEventListener('click', () => {
  if (confirm("Are you sure you want to log out?")) {
    window.location.href = 'logout.php';
  }
});

/* Back to Top */
const backBtn = document.getElementById("backToTop");
window.addEventListener("scroll", () => {
  backBtn.style.display = window.scrollY > 200 ? "flex" : "none";
});
backBtn.addEventListener("click", () => window.scrollTo({ top: 0, behavior: "smooth" }));

/* keyboard accessibility: close dropdowns on ESC */
document.addEventListener('keydown', (e) => {
  if (e.key === 'Escape' || e.key === 'Esc') {
    document.querySelectorAll('.nav-item.dropdown').forEach(d => d.classList.remove('show'));
  }
});
</script>

</body>
</html>
