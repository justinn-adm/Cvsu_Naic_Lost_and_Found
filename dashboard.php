<?php
include 'db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: SignIn_SignUp.html");
    exit();
}

// Protect admin route
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    echo("403 Forbidden");
    exit();
}

// Handle logout
if (isset($_GET['logout'])) {
    session_unset();
    session_destroy();
    header("Location: index.html");
    exit();
}

// Dashboard Stats
$stmt = $conn->prepare("SELECT id FROM users WHERE role='user'");
$stmt->execute(); $stmt->store_result(); $users = $stmt->num_rows;

$stmt = $conn->prepare("SELECT id FROM lost_items");
$stmt->execute(); $stmt->store_result(); $total_lost = $stmt->num_rows;

$stmt = $conn->prepare("SELECT id FROM found_items");
$stmt->execute(); $stmt->store_result(); $total_found = $stmt->num_rows;

// Claims counts
$stmt = $conn->prepare("SELECT COUNT(*) FROM claims WHERE status='pending'");
$stmt->execute(); $stmt->bind_result($pending_claims); $stmt->fetch(); $stmt->close();

$stmt = $conn->prepare("SELECT COUNT(*) FROM claims WHERE status='approved'");
$stmt->execute(); $stmt->bind_result($approved_claims); $stmt->fetch(); $stmt->close();

$stmt = $conn->prepare("SELECT COUNT(*) FROM claims WHERE status='rejected'");
$stmt->execute(); $stmt->bind_result($rejected_claims); $stmt->fetch(); $stmt->close();

// Total pending items (items that are pending admin approval)
$stmt = $conn->prepare("SELECT COUNT(*) FROM lost_items WHERE status='Pending'");
$stmt->execute(); $stmt->bind_result($pending_lost); $stmt->fetch(); $stmt->close();

$stmt = $conn->prepare("SELECT COUNT(*) FROM found_items WHERE status='Pending'");
$stmt->execute(); $stmt->bind_result($pending_found); $stmt->fetch(); $stmt->close();

$total_pending_items = $pending_lost + $pending_found;

// Items posted per weekday (lost and found separately)
$lost_per_day = array_fill(1,7,0);
$found_per_day = array_fill(1,7,0);

$result = $conn->query("SELECT DAYOFWEEK(created_at) AS weekday, COUNT(*) AS count FROM lost_items GROUP BY DAYOFWEEK(created_at)");
while($row = $result->fetch_assoc()){
    $day = $row['weekday'] - 1; if($day == 0) $day = 7;
    $lost_per_day[$day] = $row['count'];
}

$result = $conn->query("SELECT DAYOFWEEK(created_at) AS weekday, COUNT(*) AS count FROM found_items GROUP BY DAYOFWEEK(created_at)");
while($row = $result->fetch_assoc()){
    $day = $row['weekday'] - 1; if($day == 0) $day = 7;
    $found_per_day[$day] = $row['count'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Dashboard</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&family=Poppins:wght@400;500&family=Luckiest+Guy&display=swap" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<style>
/* General Body */
body { font-family: 'Inter', sans-serif; background-color: #f7f9fc; margin: 0; padding: 20px; }

/* Dashboard content animation */
#dashboard-content { opacity: 0; transform: translateY(40px); transition: opacity 1.2s ease, transform 1.2s ease; }

/* Dashboard Cards */
.dashboard { display:grid; grid-template-columns:repeat(auto-fit, minmax(150px, 1fr)); gap:15px; margin-bottom:30px; }
.card {
    position:relative; display:flex; flex-direction:column; justify-content:center; align-items:center;
    padding:15px 20px; min-height:120px; border-radius:12px; background:#fff; box-shadow:0 4px 12px rgba(0,0,0,0.08);
    cursor:pointer; transition: transform 0.4s ease, box-shadow 0.4s ease, background 0.3s;
    text-align:center;
}
.card:hover {
    transform:translateY(-6px) scale(1.02);
    box-shadow:0 10px 25px rgba(0,0,0,0.15);
    background:linear-gradient(135deg, #f0f9ff, #e1f5fe);
}
.card::before { content:''; position:absolute; left:0; top:0; width:10px; height:100%; border-radius:12px 0 0 12px; }
.members::before { background-color: #17a2b8; }
.lost::before { background-color: #007bff; }
.found::before { background-color: #28a745; }
.pending::before { background-color: #ffc107; }
.manage-items::before { background-color: #9b59b6; }
.card p { margin:0; font-size:1rem; color:#555; font-weight:500; transition: color 0.3s; }
.card h2 { font-family:'Luckiest Guy', cursive; font-size:2rem; margin-top:10px; color:#333; transition: transform 0.3s; }
.card:hover h2 { transform: scale(1.1); color:#1d3557; }

/* Charts */
.charts { display:flex; flex-wrap:nowrap; gap:15px; justify-content:center; align-items:flex-start; }
.bar-charts { flex:2; display:flex; flex-direction:column; gap:15px; }
.bar-chart-container { background:#fff; padding:15px; border-radius:12px; box-shadow:0 4px 15px rgba(0,0,0,0.08);
    opacity:0; transform: translateY(60px); transition: opacity 1.5s ease, transform 1.5s ease;
}
.bar-chart-container.show { opacity:1; transform:translateY(0); }
.bar-chart-container canvas { width:100%; height:100%; }
.bar-chart-container.large { height:220px; }
.bar-chart-container.small { height:180px; }

/* Doughnut Charts */
.doughnuts { flex:1; display:flex; flex-direction:column; gap:15px; justify-content:flex-start; align-items:center; margin-left:20px; }
.doughnut-container { background:#fff; padding:15px; border-radius:12px; box-shadow:0 4px 15px rgba(0,0,0,0.08);
    width:100%; max-width:220px; height:250px; opacity:0; transform: translateY(60px); transition: opacity 1.5s ease, transform 1.5s ease;
}
.doughnut-container.show { opacity:1; transform:translateY(0); }

/* Chart Hover Effects */
canvas:hover { cursor:pointer; filter: brightness(1.05); transition: filter 0.3s; }
</style>
</head>
<body>

<div id="dashboard-content">
    <div class="dashboard">
        <div class="card members" onclick="loadPage('user_management.php','user_management')">
            <p>Total Members</p>
            <h2><?php echo $users; ?></h2>
        </div>
               <div class="card lost" onclick="loadPage('manage_items.php','manage_items')">
            <p>Total Lost Items</p>
            <h2><?php echo $total_lost; ?></h2>
        </div>
        <div class="card found" onclick="loadPage('manage_items.php','manage_items')">
            <p>Total Found Items</p>
            <h2><?php echo $total_found; ?></h2>
        </div>
        <div class="card pending" onclick="loadPage('admin_claims.php?filter=pending','manage_claims')">
            <p>Pending Claims</p>
            <h2><?php echo $pending_claims; ?></h2>
        </div>
        <div class="card manage-items" onclick="loadPage('manage_items.php','manage_items')">
            <p>Pending Items</p>
            <h2><?php echo $total_pending_items; ?></h2>
        </div>
    </div>

    <div class="charts">
        <div class="bar-charts">
            <div class="bar-chart-container large" id="barChartContainer1">
                <canvas id="itemsPostedChart"></canvas>
            </div>
            <div class="bar-chart-container small" id="barChartContainer2">
                <canvas id="itemsClaimedChart"></canvas>
            </div>
        </div>

        <div class="doughnuts">
            <div class="doughnut-container" id="totalItemsContainer">
                <canvas id="totalItemsChart"></canvas>
            </div>
            <div class="doughnut-container" id="claimsStatusContainer">
                <canvas id="claimsStatusChart"></canvas>
            </div>
        </div>
    </div>
</div>

<script>
function loadPage(url, sidebarId){
    const iframe = window.parent.document.getElementById('main-content');
    iframe.classList.remove('fade');
    void iframe.offsetWidth;
    iframe.src = url;
    iframe.classList.add('fade');
    window.parent.postMessage({ activeSidebar: sidebarId }, '*');
}

window.addEventListener('DOMContentLoaded', () => {
    const content = document.getElementById('dashboard-content');
    requestAnimationFrame(() => {
        content.style.opacity=1;
        content.style.transform='translateY(0)';
    });
    setTimeout(() => document.getElementById('barChartContainer1').classList.add('show'), 300);
    setTimeout(() => document.getElementById('barChartContainer2').classList.add('show'), 600);
    setTimeout(() => document.getElementById('totalItemsContainer').classList.add('show'), 900);
    setTimeout(() => document.getElementById('claimsStatusContainer').classList.add('show'), 1200);
});

// === BAR CHART 1: Lost vs Found per day ===
const ctxBar1 = document.getElementById('itemsPostedChart').getContext('2d');
new Chart(ctxBar1, {
    type:'bar',
    data:{
        labels:['Sun','Mon','Tue','Wed','Thu','Fri','Sat'],
        datasets:[
            {
                label:'Lost Items',
                data:[<?php echo implode(',', $lost_per_day); ?>],
                backgroundColor:'#3498db',
                borderRadius: 5
            },
            {
                label:'Found Items',
                data:[<?php echo implode(',', $found_per_day); ?>],
                backgroundColor:'#2ecc71',
                borderRadius: 5
            }
        ]
    },
    options:{
        responsive:true,
        maintainAspectRatio:false,
        plugins:{ legend:{ position:'top' } },
        interaction: { mode: 'index', intersect: false },
        animation: { duration: 1200, easing: 'easeOutQuart' },
        scales:{
            x:{ grid:{ display:false }, ticks:{ color:'#555' } },
            y:{ beginAtZero:true, grid:{ color:'rgba(0,0,0,0.05)' }, ticks:{ color:'#555' } }
        }
    }
});

// === BAR CHART 2: Claims Status + Pending Items ===
const ctxBar2 = document.getElementById('itemsClaimedChart').getContext('2d');
new Chart(ctxBar2, {
    type:'bar',
    data:{
        labels:['Approved','Rejected','Pending Claims','Pending Items'],
        datasets:[{
            label:'Count',
            data:[<?php echo $approved_claims; ?>, <?php echo $rejected_claims; ?>, <?php echo $pending_claims; ?>, <?php echo $total_pending_items; ?>],
            backgroundColor:['#28a745','#e74c3c','#ffc107','#9b59b6'],
            borderRadius: 8,
            barPercentage: 0.4,
            categoryPercentage: 0.5
        }]
    },
    options:{
        responsive:true,
        maintainAspectRatio:false,
        plugins:{ legend:{ display:false } },
        animation: { duration: 1000, easing: 'easeOutBounce' },
        scales:{
            x:{ grid:{ display:false }, ticks:{ color:'#555' } },
            y:{ beginAtZero:true, grid:{ color:'rgba(0,0,0,0.05)' }, ticks:{ color:'#555' } }
        }
    }
});

// === DOUGHNUT CHARTS ===
new Chart(document.getElementById('totalItemsChart'), {
    type:'doughnut',
    data:{
        labels:['Members','Lost Items','Found Items'],
        datasets:[{
            data:[<?php echo $users; ?>, <?php echo $total_lost; ?>, <?php echo $total_found; ?>],
            backgroundColor:['#17a2b8','#3498db','#2ecc71'],
            borderColor:'#fff', borderWidth:2, hoverOffset:15
        }]
    },
    options:{ cutout:'65%', plugins:{ legend:{ position:'bottom' } }, animation: { duration:1200, easing:'easeOutCubic' } }
});

// === DOUGHNUT CHART 2: Claims + Pending Items ===
new Chart(document.getElementById('claimsStatusChart'), {
    type:'doughnut',
    data:{
        labels:['Approved','Rejected','Pending Claims','Pending Items'],
        datasets:[{
            data:[<?php echo $approved_claims; ?>, <?php echo $rejected_claims; ?>, <?php echo $pending_claims; ?>, <?php echo $total_pending_items; ?>],
            backgroundColor:['#28a745','#e74c3c','#ffc107','#9b59b6'],
            borderColor:'#fff', borderWidth:2, hoverOffset:15
        }]
    },
    options:{ cutout:'65%', plugins:{ legend:{ position:'bottom' } }, animation: { duration:1200, easing:'easeOutCubic' } }
});
</script>
</body>
</html>
