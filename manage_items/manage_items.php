<?php
include 'db.php';
session_start();

// ✅ Admin check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// Handle approve/reject actions
if (isset($_POST['action'], $_POST['item_type'], $_POST['item_id'])) {
    $action = $_POST['action']; // approve / reject
    $item_type = $_POST['item_type']; // lost / found
    $item_id = intval($_POST['item_id']);

    if (in_array($action, ['approve', 'reject']) && in_array($item_type, ['lost', 'found'])) {
        $table = ($item_type === 'lost') ? 'lost_items' : 'found_items';

        // For found items, check claimed status before approving
        if ($item_type === 'found') {
            $check = $conn->prepare("SELECT claimed FROM found_items WHERE id = ?");
            $check->bind_param("i", $item_id);
            $check->execute();
            $result = $check->get_result()->fetch_assoc();
            $check->close();

            if (!$result) {
                echo json_encode(['success' => false, 'message' => 'Item not found.']);
                exit;
            }

            if ($result['claimed'] == 1) {
                echo json_encode(['success' => false, 'message' => 'Cannot approve. This item is already claimed.']);
                exit;
            }
        }

        // Update status
        $status = ($action === 'approve') ? 'Approved' : 'Rejected';
        $stmt = $conn->prepare("UPDATE $table SET status = ? WHERE id = ?");
        $stmt->bind_param("si", $status, $item_id);
        $stmt->execute();
        $stmt->close();

        echo json_encode(['success' => true, 'message' => ucfirst($action) . "d successfully."]);
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Manage Items | Admin</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
body { background: #f4f6f9; font-family: 'Inter', sans-serif; }
h4 { font-weight: 600; margin-bottom: 20px; }
.tab-content { margin-top: 20px; }
.item-card { background: #fff; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.08); overflow: hidden; transition: transform 0.3s, box-shadow 0.3s; }
.item-card:hover { transform: translateY(-4px); box-shadow: 0 8px 25px rgba(0,0,0,0.12); }
.item-card img { width: 100%; height: 180px; object-fit: cover; }
.item-body { padding: 15px; }
.item-title { font-size: 1.1rem; font-weight: 600; margin-bottom: 5px; }
.item-desc { font-size: 0.9rem; color: #555; margin-bottom: 10px; }
.item-meta { font-size: 0.85rem; color: #777; margin-bottom: 10px; }
.status-badge { padding: 4px 10px; border-radius: 12px; font-size: 0.8rem; font-weight: 600; }
.status-pending { background: #ffc107; color: #fff; }
.status-approved { background: #28a745; color: #fff; }
.status-rejected { background: #e74c3c; color: #fff; }
.status-claimed { background: #17a2b8; color: #fff; }
.action-btn { font-size: 0.8rem; padding: 5px 10px; margin-right: 5px; }
</style>
</head>
<body class="p-4">

<h4>Manage Lost & Found Items</h4>

<ul class="nav nav-tabs" id="itemTabs">
  <li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#lostTab">Lost Items</a></li>
  <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#foundTab">Found Items</a></li>
</ul>

<div class="tab-content">
  <!-- LOST ITEMS -->
  <div class="tab-pane fade show active" id="lostTab">
    <div class="row g-3">
      <?php
      $lost_items = $conn->query("SELECT l.id, l.name, l.image_path, l.description, l.date_found, l.location, l.uploader_name, l.status FROM lost_items l ORDER BY l.date_found DESC");
      if ($lost_items && $lost_items->num_rows > 0):
        while ($row = $lost_items->fetch_assoc()):
      ?>
      <div class="col-md-4">
        <div class="item-card">
          <img src="<?= htmlspecialchars($row['image_path']) ?>" alt="Item Image">
          <div class="item-body">
            <div class="item-title"><?= htmlspecialchars($row['name']) ?></div>
            <div class="item-desc"><?= htmlspecialchars($row['description']) ?></div>
            <div class="item-meta">
              <div><strong>Date:</strong> <?= htmlspecialchars($row['date_found']) ?></div>
              <div><strong>Location:</strong> <?= htmlspecialchars($row['location']) ?></div>
              <div><strong>Uploader:</strong> <?= htmlspecialchars($row['uploader_name']) ?></div>
            </div>
            <div class="d-flex justify-content-between align-items-center">
              <span class="status-badge status-<?= strtolower($row['status']) ?>"><?= htmlspecialchars($row['status']) ?></span>
              <?php if ($row['status'] === 'Pending'): ?>
                <div>
                  <button class="btn btn-sm btn-success action-btn approve-btn" data-id="<?= $row['id'] ?>" data-type="lost">Approve</button>
                  <button class="btn btn-sm btn-danger action-btn reject-btn" data-id="<?= $row['id'] ?>" data-type="lost">Reject</button>
                </div>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </div>
      <?php endwhile; else: ?>
        <div class="col-12 text-center text-muted">No lost items found.</div>
      <?php endif; ?>
    </div>
  </div>

  <!-- FOUND ITEMS -->
  <div class="tab-pane fade" id="foundTab">
    <div class="row g-3">
      <?php
      $found_items = $conn->query("
        SELECT f.id, f.item_name, f.image_path, f.description, f.date_found, f.location, f.claimed, u.username AS uploader_name, f.status
        FROM found_items f
        LEFT JOIN users u ON f.user_id = u.id
        ORDER BY f.date_found DESC
      ");
      if ($found_items && $found_items->num_rows > 0):
        while ($row = $found_items->fetch_assoc()):
      ?>
      <div class="col-md-4">
        <div class="item-card">
          <img src="<?= htmlspecialchars($row['image_path']) ?>" alt="Item Image">
          <div class="item-body">
            <div class="item-title"><?= htmlspecialchars($row['item_name']) ?></div>
            <div class="item-desc"><?= htmlspecialchars($row['description']) ?></div>
            <div class="item-meta">
              <div><strong>Date:</strong> <?= htmlspecialchars($row['date_found']) ?></div>
              <div><strong>Location:</strong> <?= htmlspecialchars($row['location']) ?></div>
              <div><strong>Uploader:</strong> <?= htmlspecialchars($row['uploader_name']) ?></div>
            </div>
            <div class="d-flex justify-content-between align-items-center">
              <?php
                if ($row['claimed'] == 1) $status_class = 'claimed';
                else $status_class = strtolower($row['status']);
              ?>
              <span class="status-badge status-<?= $status_class ?>"><?= ($row['claimed']==1?'Claimed':$row['status']) ?></span>
              <?php if ($row['claimed'] != 1 && $row['status'] === 'Pending'): ?>
                <div>
                  <button class="btn btn-sm btn-success action-btn approve-btn" data-id="<?= $row['id'] ?>" data-type="found">Approve</button>
                  <button class="btn btn-sm btn-danger action-btn reject-btn" data-id="<?= $row['id'] ?>" data-type="found">Reject</button>
                </div>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </div>
      <?php endwhile; else: ?>
        <div class="col-12 text-center text-muted">No found items found.</div>
      <?php endif; ?>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Approve / Reject logic
document.querySelectorAll('.approve-btn, .reject-btn').forEach(btn => {
  btn.addEventListener('click', function() {
    const item_id = this.dataset.id;
    const item_type = this.dataset.type;
    const action = this.classList.contains('approve-btn') ? 'approve' : 'reject';

    if (!confirm(`Are you sure you want to ${action} this ${item_type} item?`)) return;

    fetch('', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: `item_id=${item_id}&item_type=${item_type}&action=${action}`
    })
    .then(res => res.json())
    .then(data => {
      alert(data.message);
      location.reload();
    })
    .catch(err => console.error(err));
  });
});
</script>

</body>
</html>
