<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.html");
    exit();
}

$user_id = $_SESSION['user_id'];

// --- Mark all relevant notifications as read ---
// Claims + Found items + Lost items
$update_sql = "
  UPDATE claims c
  LEFT JOIN found_items f ON c.item_id = f.id
  LEFT JOIN lost_items l ON c.item_id = l.id
  SET c.is_read = 1
  WHERE c.user_id = ?
";
$update_stmt = $conn->prepare($update_sql);
$update_stmt->bind_param("i", $user_id);
$update_stmt->execute();
$update_stmt->close();

// Mark posts as read by adding a new column 'is_read' to found_items/lost_items if not exists
$conn->query("ALTER TABLE found_items ADD COLUMN IF NOT EXISTS is_read TINYINT(1) DEFAULT 0");
$conn->query("ALTER TABLE lost_items ADD COLUMN IF NOT EXISTS is_read TINYINT(1) DEFAULT 0");

$conn->query("UPDATE found_items SET is_read = 1 WHERE user_id = $user_id AND status IN ('Approved','Rejected')");
$conn->query("UPDATE lost_items SET is_read = 1 WHERE user_id = $user_id AND status IN ('Approved','Rejected')");

// --- Fetch notifications: claims + user posts ---
$sql = "
SELECT 
    'claim' AS type,
    c.id AS notification_id,
    c.status,
    c.message,
    c.claim_date AS date,
    f.item_name AS item_name,
    f.image_path AS image_path
FROM claims c
JOIN found_items f ON c.item_id = f.id
WHERE c.user_id = ?

UNION ALL

SELECT
    'post_found' AS type,
    f.id AS notification_id,
    f.status,
    NULL AS message,
    f.created_at AS date,
    f.item_name AS item_name,
    f.image_path AS image_path
FROM found_items f
WHERE f.user_id = ?
  AND f.status IN ('Approved','Rejected')

UNION ALL

SELECT
    'post_lost' AS type,
    l.id AS notification_id,
    l.status,
    NULL AS message,
    l.created_at AS date,
    l.name AS item_name,
    l.image_path AS image_path
FROM lost_items l
WHERE l.user_id = ?
  AND l.status IN ('Approved','Rejected')

ORDER BY date DESC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("iii", $user_id, $user_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Notifications | CvSU Naic Lost & Found</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&display=swap" rel="stylesheet">
<style>
body {
    background: linear-gradient(135deg,#fdfbff,#f2efff);
    font-family: 'Inter', sans-serif;
    padding: 40px 15px;
    color: #2d3436;
    display: flex;
    flex-direction: column;
    align-items: center;
    min-height: 100vh;
}
h2 { font-weight: 800; color: #7a42ff; margin-bottom: 25px; text-align: center; }
.back-btn { align-self: flex-start; background: linear-gradient(90deg,#7a42ff,#a678ff); color: #fff; padding: 10px 18px; border-radius: 25px; text-decoration: none; font-weight: 600; font-size: 0.9rem; box-shadow: 0 3px 10px rgba(122,66,255,0.2); transition: all 0.3s ease; margin-bottom: 25px; }
.back-btn:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(122,66,255,0.3); }
.notifications-container { width: 100%; max-width: 900px; }
.notification-card { display: flex; gap: 15px; background: #fff; padding: 15px 20px; border-radius: 12px; box-shadow: 0 4px 15px rgba(140,82,255,0.1); margin-bottom: 15px; align-items: center; transition: transform 0.2s, box-shadow 0.2s; }
.notification-card:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(140,82,255,0.15); }
.notification-card img { width: 70px; height: 70px; object-fit: cover; border-radius: 8px; }
.notification-body { flex: 1; }
.notification-title { font-weight: 600; margin-bottom: 5px; font-size: 1rem; }
.notification-message { font-size: 0.9rem; color: #555; margin-bottom: 5px; }
.notification-date { font-size: 0.8rem; color: #888; }
.status-badge { display: inline-flex; align-items: center; gap: 5px; padding: 5px 12px; border-radius: 25px; font-weight: 600; font-size: 0.85rem; text-transform: capitalize; }
.status-badge.pending { background:#fff4c4; color:#e1b12c; }
.status-badge.pending::before { content:"⏳"; }
.status-badge.approved { background:#c9ffd7; color:#00b894; }
.status-badge.approved::before { content:"✅"; }
.status-badge.rejected { background:#ffd6de; color:#d63031; }
.status-badge.rejected::before { content:"❌"; }
.no-notifications { margin-top: 40px; background: #fff; padding: 25px; border-radius: 12px; box-shadow: 0 4px 10px rgba(140,82,255,0.15); color: #6c5ce7; text-align: center; max-width: 400px; }
@media (max-width: 768px){ .notification-card { flex-direction: column; align-items: flex-start; } .notification-card img { width: 100%; height: 180px; margin-bottom: 10px; } }
</style>
</head>
<body>

<a href="feeds.php" class="back-btn">← Back</a>
<h2>Notifications</h2>

<div class="notifications-container">
<?php if ($result->num_rows > 0): ?>
    <?php while($row = $result->fetch_assoc()): 
        $statusClass = strtolower($row['status']);
        $typeText = ($row['type']=='claim') ? "Your claim for" : "Your item";
    ?>
    <div class="notification-card">
        <img src="<?= htmlspecialchars($row['image_path']) ?>" alt="Item">
        <div class="notification-body">
            <div class="notification-title">
                <?= $typeText ?> <strong><?= htmlspecialchars($row['item_name']) ?></strong> 
                <span class="status-badge <?= $statusClass ?>"><?= htmlspecialchars($row['status']) ?></span>
            </div>
            <?php if($row['message'] && $row['type']=='claim'): ?>
                <div class="notification-message"><?= htmlspecialchars($row['message']) ?></div>
            <?php endif; ?>
            <div class="notification-date"><?= date("M d, Y H:i", strtotime($row['date'])) ?></div>
        </div>
    </div>
    <?php endwhile; ?>
<?php else: ?>
    <div class="no-notifications">
        <p>No notifications yet 💭</p>
    </div>
<?php endif; ?>
</div>

</body>
</html>

<?php
$stmt->close();
$conn->close();
?>
