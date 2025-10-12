<?php
include 'db.php';
session_start();
if(!isset($_SESSION['user_id'])) { header("Location: SignIn_SignUp.html"); exit(); }

// Total users
$users = $total_lost = $total_found = $pending_claims = 0;

// Users
$stmt = $conn->prepare("SELECT id FROM users WHERE role='user'");
$stmt->execute(); $stmt->store_result(); $users = $stmt->num_rows;

// Lost items
$stmt = $conn->prepare("SELECT id FROM lost_items");
$stmt->execute(); $stmt->store_result(); $total_lost = $stmt->num_rows;

// Found items
$stmt = $conn->prepare("SELECT id FROM found_items");
$stmt->execute(); $stmt->store_result(); $total_found = $stmt->num_rows;

// Pending claims
$stmt = $conn->prepare("SELECT id FROM claims WHERE status='pending'");
$stmt->execute(); $stmt->store_result(); $pending_claims = $stmt->num_rows;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Dashboard Cards</title>
<!-- Restore original fonts -->
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&family=Poppins:wght@400;500&family=Luckiest+Guy&display=swap" rel="stylesheet">
<style>
body {
  font-family: 'Inter', sans-serif;
  background-color: #f7f9fc;
  margin: 0;
}

.dashboard {
  padding:30px;
  display:grid;
  grid-template-columns: repeat(auto-fit, minmax(220px,1fr));
  gap:20px;
}

.card {
  padding:20px;
  border-radius:12px;
  color:white;
  text-align:center;
  font-family: 'Poppins', sans-serif;
  font-weight:500; /* lighter than before */
  cursor:pointer;
  transition: transform 0.3s, box-shadow 0.3s;
}

.card:hover {
  transform:translateY(-5px);
  box-shadow:0 8px 20px rgba(0,0,0,0.12);
}

.card h2{
  font-family: 'Luckiest Guy', cursive;
  font-size:2rem;
  font-weight:500; /* less bold */
  margin-bottom:8px;
}

.members{ background:#17a2b8; }
.lost{ background:#007bff; }
.found{ background:#28a745; }
.pending{ background:#ffc107; color:#333; }

</style>
</head>
<body>
<div class="dashboard">
  <div class="card members" onclick="loadPage('user_management.php','user_management')">
    <h2><?php echo $users; ?></h2>
    <p>Total Members</p>
  </div>
  <div class="card lost" >
    <h2><?php echo $total_lost; ?></h2>
    <p>Total Lost Items</p>
  </div>
  <div class="card found">
    <h2><?php echo $total_found; ?></h2>
    <p>Total Found Items</p>
  </div>
  <div class="card pending" onclick="loadPage('admin_claims.php?filter=pending','manage_claims')">
    <h2><?php echo $pending_claims; ?></h2>
    <p>Pending Claims</p>
  </div>
</div>

<script>
function loadPage(url, sidebarId){
  const iframe = window.parent.document.getElementById('main-content');
  iframe.classList.remove('fade'); void iframe.offsetWidth; iframe.src = url; iframe.classList.add('fade');
  window.parent.postMessage({ activeSidebar: sidebarId }, '*');
}
</script>
</body>
</html>
