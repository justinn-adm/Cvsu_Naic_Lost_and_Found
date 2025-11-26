<?php
include 'db.php';
session_start();
if(!isset($_SESSION['user_id'])) {
    header("Location: SignIn_SignUp.html");
    exit();
}

// Dashboard Stats
$stmt = $conn->prepare("SELECT id FROM users WHERE role='user'");
$stmt->execute(); $stmt->store_result(); $users = $stmt->num_rows;

$stmt = $conn->prepare("SELECT id FROM lost_items");
$stmt->execute(); $stmt->store_result(); $total_lost = $stmt->num_rows;

$stmt = $conn->prepare("SELECT id FROM found_items");
$stmt->execute(); $stmt->store_result(); $total_found = $stmt->num_rows;

$stmt = $conn->prepare("
    SELECT c.id FROM claims c
    LEFT JOIN found_items f ON c.item_id=f.id
    LEFT JOIN lost_items l ON c.item_id=l.id
    WHERE c.status='pending' AND (f.id IS NOT NULL OR l.id IS NOT NULL)
");
$stmt->execute(); $stmt->store_result(); $pending_claims = $stmt->num_rows;

$stmt = $conn->prepare("
    SELECT c.id FROM claims c
    LEFT JOIN found_items f ON c.item_id=f.id
    LEFT JOIN lost_items l ON c.item_id=l.id
    WHERE c.status='approved' AND (f.id IS NOT NULL OR l.id IS NOT NULL)
");
$stmt->execute(); $stmt->store_result(); $approved_claims = $stmt->num_rows;

$stmt = $conn->prepare("
    SELECT c.id FROM claims c
    LEFT JOIN found_items f ON c.item_id=f.id
    LEFT JOIN lost_items l ON c.item_id=l.id
    WHERE c.status='rejected' AND (f.id IS NOT NULL OR l.id IS NOT NULL)
");
$stmt->execute(); $stmt->store_result(); $rejected_claims = $stmt->num_rows;

// Items posted per weekday
$item_posted_per_day = array_fill(1,7,0);
$result = $conn->query("
    SELECT DAYOFWEEK(created_at) AS weekday, COUNT(*) AS count 
    FROM (
        SELECT created_at FROM lost_items
        UNION ALL
        SELECT created_at FROM found_items
    ) AS combined 
    GROUP BY DAYOFWEEK(created_at)
");
while($row = $result->fetch_assoc()){
    $day = $row['weekday'] - 1;
    if($day == 0) $day = 7;
    $item_posted_per_day[$day] = $row['count'];
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
body {
    font-family: 'Inter', sans-serif;
    background-color: #f7f9fc;
    margin: 0;
    padding: 20px;
}

#dashboard-content {
    opacity: 0;
    transform: translateY(40px);
    transition: opacity 1.2s ease, transform 1.2s ease;
}

/* Dashboard Cards */
.dashboard {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 15px;
    margin-bottom: 30px;
}

.card {
    position: relative;
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    padding: 15px 20px;
    min-height: 120px;
    border-radius: 12px;
    background: #fff;
    box-shadow: 0 4px 12px rgba(0,0,0,0.08);
    cursor: pointer;
    transition: transform 0.3s, box-shadow 0.3s;
    text-align: center;
}
.card:hover {
    transform: translateY(-3px);
    box-shadow: 0 6px 20px rgba(0,0,0,0.12);
}
.card::before {
    content:'';
    position:absolute;
    left:0;
    top:0;
    width:10px;
    height:100%;
    border-radius:12px 0 0 12px;
}
.members::before { background-color: #17a2b8; }
.lost::before { background-color: #007bff; }
.found::before { background-color: #28a745; }
.pending::before { background-color: #ffc107; }

.card p { margin:0; font-size:1rem; color:#555; font-weight:500; }
.card h2 { font-family:'Luckiest Guy', cursive; font-size:2rem; margin-top:10px; color:#333; }

/* Charts Section */
.charts {
    display:flex;
    flex-wrap:nowrap;
    gap:15px;
    justify-content:center;
    align-items:flex-start;
}

.bar-charts {
    flex:2;
    display:flex;
    flex-direction:column;
    gap:15px;
}

/* First Bar Chart */
.bar-chart-container {
    background:#fff;
    padding:15px;
    border-radius:12px;
    box-shadow:0 4px 15px rgba(0,0,0,0.08);
    height:230px;
    opacity:0;
    transform: translateY(60px);
    transition: opacity 1.5s ease, transform 1.5s ease;
}
.bar-chart-container.show {
    opacity:1;
    transform:translateY(0);
}

/* Smaller Second Bar Chart */
.bar-chart-container.small {
    height:170px;
}

.bar-chart-container canvas {
    width:100%;
    height:100%;
}

/* Doughnut Charts */
.doughnuts {
    flex:1;
    display:flex;
    flex-direction:column;
    gap:15px;
    justify-content:flex-start;
    align-items:center;
    margin-left:20px;
}

.doughnut-container {
    background:#fff;
    padding:15px;
    border-radius:12px;
    box-shadow:0 4px 15px rgba(0,0,0,0.08);
    width:100%;
    max-width:200px;
    height:230px;
    opacity:0;
    transform: translateY(60px);
    transition: opacity 1.5s ease, transform 1.5s ease;
}
.doughnut-container.show {
    opacity:1;
    transform:translateY(0);
}
</style>
</head>
<body>

<div id="dashboard-content">
    <div class="dashboard">
        <div class="card members" onclick="loadPage('user_management.php','user_management')">
            <p>Total Members</p>
            <h2><?php echo $users; ?></h2>
        </div>
        <div class="card lost">
            <p>Total Lost Items</p>
            <h2><?php echo $total_lost; ?></h2>
        </div>
        <div class="card found">
            <p>Total Found Items</p>
            <h2><?php echo $total_found; ?></h2>
        </div>
        <div class="card pending" onclick="loadPage('admin_claims.php?filter=pending','manage_claims')">
            <p>Pending Claims</p>
            <h2><?php echo $pending_claims; ?></h2>
        </div>
    </div>

    <div class="charts">
        <div class="bar-charts">
            <div class="bar-chart-container" id="barChartContainer1">
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

// === BAR CHART 1 ===
const ctxBar1 = document.getElementById('itemsPostedChart').getContext('2d');
const gradient1 = ctxBar1.createLinearGradient(0, 0, 0, 200);
gradient1.addColorStop(0, '#3498db');
gradient1.addColorStop(1, '#85c1e9');

new Chart(ctxBar1, {
    type:'bar',
    data:{
        labels:['Mon','Tue','Wed','Thu','Fri','Sat','Sun'],
        datasets:[{
            label:'Items Posted',
            data:[<?php echo implode(',', $item_posted_per_day); ?>],
            backgroundColor: gradient1,
            borderRadius: 10,
            barPercentage: 0.6,
            categoryPercentage: 0.6,
            hoverBackgroundColor:'#2980b9',
        }]
    },
    options:{
        responsive:true,
        maintainAspectRatio:false,
        plugins:{ legend:{ display:false } },
        scales:{
            x:{ grid:{ display:false }, ticks:{ color:'#555' } },
            y:{ beginAtZero:true, grid:{ color:'rgba(0,0,0,0.05)' }, ticks:{ color:'#555' } }
        }
    }
});

// === BAR CHART 2 (smaller) ===
const ctxBar2 = document.getElementById('itemsClaimedChart').getContext('2d');
const gradient2 = ctxBar2.createLinearGradient(0, 0, 0, 200);
gradient2.addColorStop(0, '#2ecc71');
gradient2.addColorStop(1, '#82e0aa');

new Chart(ctxBar2, {
    type:'bar',
    data:{
        labels:['Approved','Rejected','Pending'],
        datasets:[{
            label:'Claims Status',
            data:[<?php echo $approved_claims; ?>, <?php echo $rejected_claims; ?>, <?php echo $pending_claims; ?>],
            backgroundColor: gradient2,
            borderRadius: 8,
            barPercentage: 0.5,
            categoryPercentage: 0.6,
            hoverBackgroundColor:'#27ae60',
        }]
    },
    options:{
        responsive:true,
        maintainAspectRatio:false,
        plugins:{ legend:{ display:false } },
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
            borderColor:'#fff', borderWidth:2, hoverOffset:10
        }]
    },
    options:{ cutout:'65%', plugins:{ legend:{ position:'bottom' } } }
});

new Chart(document.getElementById('claimsStatusChart'), {
    type:'doughnut',
    data:{
        labels:['Approved','Rejected','Pending'],
        datasets:[{
            data:[<?php echo $approved_claims; ?>, <?php echo $rejected_claims; ?>, <?php echo $pending_claims; ?>],
            backgroundColor:['#28a745','#e74c3c','#ffc107'],
            borderColor:'#fff', borderWidth:2, hoverOffset:10
        }]
    },
    options:{ cutout:'65%', plugins:{ legend:{ position:'bottom' } } }
});
</script>
</body>
</html>

