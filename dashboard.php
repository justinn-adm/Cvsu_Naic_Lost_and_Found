<?php
include 'db.php';
session_start();
if(!isset($_SESSION['user_id'])) {
    header("Location: SignIn_SignUp.html");
    exit();
}

// ======================
// DASHBOARD STATISTICS
// ======================

// Total users
$stmt = $conn->prepare("SELECT id FROM users WHERE role='user'");
$stmt->execute();
$stmt->store_result();
$users = $stmt->num_rows;

// Total lost items
$stmt = $conn->prepare("SELECT id FROM lost_items");
$stmt->execute();
$stmt->store_result();
$total_lost = $stmt->num_rows;

// Total found items
$stmt = $conn->prepare("SELECT id FROM found_items");
$stmt->execute();
$stmt->store_result();
$total_found = $stmt->num_rows;

// Pending claims (only for existing items)
$stmt = $conn->prepare("
    SELECT c.id 
    FROM claims c
    LEFT JOIN found_items f ON c.item_id = f.id
    LEFT JOIN lost_items l ON c.item_id = l.id
    WHERE c.status = 'pending' AND (f.id IS NOT NULL OR l.id IS NOT NULL)
");
$stmt->execute();
$stmt->store_result();
$pending_claims = $stmt->num_rows;

// Approved claims
$stmt = $conn->prepare("
    SELECT c.id 
    FROM claims c
    LEFT JOIN found_items f ON c.item_id = f.id
    LEFT JOIN lost_items l ON c.item_id = l.id
    WHERE c.status = 'approved' AND (f.id IS NOT NULL OR l.id IS NOT NULL)
");
$stmt->execute();
$stmt->store_result();
$approved_claims = $stmt->num_rows;

// Rejected claims
$stmt = $conn->prepare("
    SELECT c.id 
    FROM claims c
    LEFT JOIN found_items f ON c.item_id = f.id
    LEFT JOIN lost_items l ON c.item_id = l.id
    WHERE c.status = 'rejected' AND (f.id IS NOT NULL OR l.id IS NOT NULL)
");
$stmt->execute();
$stmt->store_result();
$rejected_claims = $stmt->num_rows;

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
    opacity:0;
    transform: translateY(40px);
    transition: opacity 1.2s ease, transform 1.2s ease;
}

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

.charts {
    display:flex;
    flex-wrap:nowrap;
    gap:15px;
    justify-content:center;
    align-items:flex-start;
}

.bar-chart-container {
    flex:2;
    background:#fff;
    padding:20px;
    border-radius:12px;
    box-shadow:0 4px 15px rgba(0,0,0,0.08);
    height:430px;
    opacity:0;
    transform: translateY(60px);
    transition: opacity 1.5s ease, transform 1.5s ease;
}
.doughnuts {
    flex:1;
    display:flex;
    flex-direction:column;
    gap:15px;
    justify-content:flex-end;
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
    opacity:0;
    transform: translateY(60px);
    transition: opacity 1.5s ease, transform 1.5s ease;
}
.bar-chart-container.show,
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
        <div class="bar-chart-container" id="barChartContainer">
            <canvas id="itemsPostedChart"></canvas>
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

    // Animate charts from below
    setTimeout(() => {
        document.getElementById('barChartContainer').classList.add('show');
    }, 300);
    setTimeout(() => {
        document.getElementById('totalItemsContainer').classList.add('show');
    }, 600);
    setTimeout(() => {
        document.getElementById('claimsStatusContainer').classList.add('show');
    }, 900);
});

// ============================
// BAR CHART (Items per weekday)
// ============================
const ctxBar = document.getElementById('itemsPostedChart').getContext('2d');
const gradient = ctxBar.createLinearGradient(0, 0, 0, 400);
gradient.addColorStop(0, '#3498db');
gradient.addColorStop(1, '#85c1e9');

new Chart(ctxBar, {
    type:'bar',
    data:{
        labels:['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'],
        datasets:[{
            label:'Items Posted',
            data:[<?php echo implode(',', $item_posted_per_day); ?>],
            backgroundColor: gradient,
            borderRadius: 12,
            barPercentage: 0.6,
            categoryPercentage: 0.6,
            hoverBackgroundColor:'#2980b9',
        }]
    },
    options:{
        responsive:true,
        animation:{
            duration:1500,
            easing:'easeOutCubic'
        },
        plugins:{
            legend:{ display:false },
            tooltip:{
                padding:10,
                titleFont:{ weight:'600' },
                bodyFont:{ size:13 },
                backgroundColor:'#2c3e50',
                titleColor:'#fff',
                bodyColor:'#fff',
            }
        },
        scales:{
            x:{
                grid:{ display:false },
                ticks:{ color:'#555', font:{ size:12 } }
            },
            y:{
                beginAtZero:true,
                grid:{
                    color:'rgba(0,0,0,0.05)',
                    borderDash:[3,3],
                    drawBorder:false
                },
                ticks:{ stepSize:1, color:'#555', font:{ size:12 } }
            }
        }
    }
});

// ============================
// DOUGHNUT CHARTS
// ============================

// Members / Lost / Found
new Chart(document.getElementById('totalItemsChart'), {
    type:'doughnut',
    data:{
        labels:['Members','Lost Items','Found Items'],
        datasets:[{
            data:[<?php echo $users; ?>, <?php echo $total_lost; ?>, <?php echo $total_found; ?>],
            backgroundColor:['#17a2b8','#3498db','#2ecc71'],
            borderColor:'#fff',
            borderWidth:2,
            hoverOffset:10
        }]
    },
    options:{
        animation:{
            animateScale:true,
            animateRotate:true,
            duration:1800,
            easing:'easeOutBack'
        },
        responsive:true,
        maintainAspectRatio:true,
        cutout:'65%',
        plugins:{
            legend:{ position:'bottom' },
            tooltip:{ padding:8, titleFont:{ weight:'600' }, bodyFont:{ size:12 } }
        }
    }
});

// Claims (Approved / Rejected / Pending)
new Chart(document.getElementById('claimsStatusChart'), {
    type:'doughnut',
    data:{
        labels:['Approved','Rejected','Pending'],
        datasets:[{
            data:[<?php echo $approved_claims; ?>, <?php echo $rejected_claims; ?>, <?php echo $pending_claims; ?>],
            backgroundColor:['#28a745','#e74c3c','#ffc107'],
            borderColor:'#fff',
            borderWidth:2,
            hoverOffset:10
        }]
    },
    options:{
        animation:{
            animateScale:true,
            animateRotate:true,
            duration:1800,
            easing:'easeOutBack'
        },
        responsive:true,
        maintainAspectRatio:true,
        cutout:'65%',
        plugins:{
            legend:{ position:'bottom' },
            tooltip:{ padding:8, titleFont:{ weight:'600' }, bodyFont:{ size:12 } }
        }
    }
});
</script>
</body>
</html>
