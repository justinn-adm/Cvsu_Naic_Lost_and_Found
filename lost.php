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

/* 🔔 Fetch unread claim notifications */
$notif_sql = "
  SELECT COUNT(*) AS unread_count 
  FROM claims 
  WHERE user_id = ? 
  AND status IN ('Approved', 'Rejected') 
  AND is_read = 0
";
$stmt_notif = $conn->prepare($notif_sql);
$stmt_notif->bind_param("i", $user_id);
$stmt_notif->execute();
$result_notif = $stmt_notif->get_result();
$row_notif = $result_notif->fetch_assoc();
$unread_count = $row_notif['unread_count'] ?? 0;
$stmt_notif->close();

/* Fetch recent lost items */
$lost_query = "SELECT id, description, image_path, claimed FROM lost_items ORDER BY id DESC LIMIT 2";
$recent_lost = $conn->query($lost_query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Lost & Found | Lost Items</title>

<!-- Fonts & Icons -->
<link href="https://fonts.googleapis.com/css2?family=Agrandir:wght@400;600;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<style>
*{margin:0;padding:0;box-sizing:border-box;font-family:'Agrandir',sans-serif;}
body{
  background: url('images/purple.png') no-repeat center center fixed;
  background-size: cover;
  color:#333;
  min-height:100vh;
  overflow-x:hidden;
}

/* === NAVBAR (simple text) === */
nav {
  width: 100%;
  display: flex;
  justify-content: center;
  gap: 80px;
  position: fixed;
  top: 30px;
  left: 0;
  z-index: 100;
  background: transparent;
  animation: fadeIn 2s ease forwards;
}

/* Nav links */
.nav-links {
  display: flex;
  align-items: center;
  gap: 100px;
  position: relative;
}

.nav-item {
  position: relative;
}

.nav-links a {
  text-decoration: none;
  color: #000000ff;
  font-weight: 800;
  font-size: 1.05rem;
  transition: all 0.3s ease;
  letter-spacing: 0.6px;
  text-transform: uppercase;
  cursor: pointer;
}

.nav-links a:hover {
  color: #d9b3ff;
}

/* === DROPDOWN === */
.dropdown-content {
  display: none;
  position: absolute;
  top: 28px;
  left: 0;
  background: rgba(255,255,255,0.95);
  box-shadow: 0 8px 20px rgba(147,51,234,0.25);
  border-radius: 8px;
  overflow: hidden;
  min-width: 180px;
  animation: dropdownFade 0.3s ease forwards;
}

.dropdown-content a {
  display: block;
  padding: 10px 15px;
  color: #4b0082;
  font-weight: 600;
  text-decoration: none;
  font-size: 0.9rem;
  text-transform: none;
}

.dropdown-content a:hover {
  background: linear-gradient(90deg,#9333ea,#a855f7);
  color: #fff;
}

.dropdown:hover .dropdown-content {
  display: block;
}

@keyframes dropdownFade {
  from {opacity: 0; transform: translateY(-5px);}
  to {opacity: 1; transform: translateY(0);}
}

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

/* Animations */
@keyframes fadeIn { 0%{opacity:0;}100%{opacity:1;} }

/* === CONTENT === */
.container {
  max-width:1200px;
  margin:180px auto 40px auto;
  background:rgba(255,255,255,0.9);
  border-radius:25px;
  padding:40px;
  box-shadow:0 8px 25px rgba(140,82,255,0.25);
}

.top-bar{
  display:flex;
  justify-content:space-between;
  align-items:center;
  border-bottom:2px solid #eee;
  padding-bottom:15px;
}
.top-bar h2{
  font-weight:800;
  font-size:1.7rem;
  color:#6a2aff;
}
.top-bar .user img{
  width:55px;
  height:55px;
  border-radius:50%;
  object-fit:cover;
  border:2px solid #9333ea;
  cursor:pointer;
  transition:.3s;
}
.top-bar .user img:hover{transform:scale(1.08);}

/* Search */
.search-bar{
  margin-top:20px;
  display:flex;
  justify-content:center;
  gap:10px;
}
.search-bar input{
  width:60%;
  padding:10px 15px;
  border-radius:25px;
  border:2px solid #9333ea;
  outline:none;
}
.search-bar button{
  background:linear-gradient(90deg,#9333ea,#a855f7);
  border:none;
  padding:10px 20px;
  border-radius:25px;
  color:#fff;
  font-weight:600;
  cursor:pointer;
  transition:.3s;
}
.search-bar button:hover{transform:scale(1.05);}

/* Cards */
.cards{
  display:grid;
  grid-template-columns:repeat(auto-fit,minmax(280px,1fr));
  gap:25px;
  margin-top:30px;
}
.card{
  background:linear-gradient(145deg,#ffffff,#f2eaff);
  border-radius:18px;
  padding:20px;
  text-align:center;
  box-shadow:0 6px 15px rgba(140,82,255,0.1);
  transition:.3s;
}
.card:hover{
  transform:translateY(-6px);
  box-shadow:0 12px 25px rgba(140,82,255,0.25);
}
.card img{
  width:100%;
  height:180px;
  border-radius:12px;
  object-fit:cover;
  margin-bottom:12px;
}
.card h3{
  font-size:1rem;
  font-weight:600;
  color:#4b0082;
  margin-bottom:6px;
}
.status{
  display:inline-flex;
  align-items:center;
  justify-content:center;
  gap:12px;
  font-weight:600;
  border-radius:30px;
  padding:6px 12px;
  font-size:.8rem;
}
.status.missing{color:#b94cff;background:rgba(185,76,255,0.1);}
.status.claimed{color:#2ecc71;background:rgba(46,204,113,0.1);}
.card button{
  background:linear-gradient(90deg,#9333ea,#a855f7);
  color:#fff;
  border:none;
  border-radius:8px;
  padding:8px 14px;
  margin-top:8px;
  cursor:pointer;
  font-weight:600;
  transition:.3s;
}
.card button:hover{
  transform:scale(1.05);
  background:linear-gradient(90deg,#9244ff,#b971ff);
}
</style>
</head>

<body>
<nav>
  <div class="nav-links">

    <div class="nav-item"><a href="home.php">Home</a></div>

    <!-- CLAIM ITEMS DROPDOWN -->
    <div class="nav-item dropdown">
      <a href="#">Claim Items ▾</a>
      <div class="dropdown-content">
        <a href="lost_items.php">Lost Items</a>
        <a href="found_items.php">Found Items</a>
      </div>
    </div>

    <!-- POST ITEMS DROPDOWN -->
    <div class="nav-item dropdown">
      <a href="#">Post Item ▾</a>
      <div class="dropdown-content">
        <a href="post_lost_item.php">Post Lost Item</a>
        <a href="post_found_item.php">Post Found Item</a>
      </div>
    </div>

    <div class="nav-item">
      <a href="my_claims.php" style="position:relative;">
        My Claims
        <?php if ($unread_count > 0): ?>
          <span style="position:absolute;top:-8px;right:-12px;background:#ff4757;color:#fff;font-size:.7rem;font-weight:700;padding:3px 7px;border-radius:50%;"><?php echo $unread_count; ?></span>
        <?php endif; ?>
      </a>
    </div>

    <div class="logout" id="logoutBtn">
      <i class="fa-solid fa-arrow-right-from-bracket"></i> Logout
    </div>
  </div>
</nav>

<div class="container">
  <div class="top-bar">
    <h2>Welcome, <?php echo htmlspecialchars($username); ?> 👋</h2>
    <div class="user">
      <img src="images/<?php echo htmlspecialchars($profile_img); ?>" alt="Profile" title="Go to My Account" onclick="window.location.href='my_account.php'">
    </div>
  </div>

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
          if (!str_starts_with($lost_img, 'uploads/')) $lost_img = 'uploads/' . $lost_img;
          if (!file_exists($lost_img)) $lost_img = 'images/default-placeholder.png';
        ?>
        <img src="<?php echo htmlspecialchars($lost_img); ?>" alt="Lost Item">
        <h3><?php echo htmlspecialchars($lost['description']); ?></h3>
        <p class="status <?php echo $lost['claimed'] ? 'claimed' : 'missing'; ?>">
          <?php echo $lost['claimed'] ? 'Claimed' : 'Missing'; ?>
        </p>
        <button onclick="window.location.href='items.php?id=<?php echo $lost['id']; ?>'">View Details</button>
      </div>
    <?php endwhile; ?>
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

document.getElementById('logoutBtn').addEventListener('click', () => {
  if (confirm("Are you sure you want to log out?")) {
    window.location.href = 'logout.php';
  }
});
</script>
</body>
</html>
