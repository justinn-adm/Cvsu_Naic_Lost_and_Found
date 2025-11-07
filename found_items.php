<?php
include 'db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: SignIn_SignUp.html");
    exit();
}

$user_id = $_SESSION['user_id'];

// Get total found items
$query = "SELECT COUNT(*) AS total FROM found_items";
$result = $conn->query($query);
$total_items = ($result && $row = $result->fetch_assoc()) ? $row['total'] : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<title>Found Items | CvSU Naic Lost & Found</title>

<!-- Bootstrap & Fonts -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">

<style>
  body {
    background: linear-gradient(180deg, #f7f9ff 0%, #eef1f7 100%);
    font-family: 'Inter', sans-serif;
    color: #333;
    padding-bottom: 40px;
  }

  .page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin: 25px auto;
    flex-wrap: wrap;
    gap: 12px;
  }

  .page-header h4 {
    font-weight: 700;
    color: #4b4b4b;
  }

  .btn-back {
    background: #7a42ff;
    color: white;
    border-radius: 10px;
    font-weight: 600;
    padding: 8px 16px;
    transition: 0.3s;
  }

  .btn-back:hover {
    background: #6933e3;
    color: #fff;
  }

  .items-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
    gap: 24px;
  }

  .item-card {
    background: #fff;
    border-radius: 14px;
    box-shadow: 0 4px 14px rgba(0,0,0,0.08);
    padding: 12px;
    text-align: center;
    position: relative;
    transition: all 0.3s ease;
    cursor: pointer;
    overflow: hidden;
  }

  .item-card:hover {
    transform: translateY(-6px);
    box-shadow: 0 8px 22px rgba(0,0,0,0.15);
  }

  .item-card img {
    width: 100%;
    height: 180px;
    object-fit: cover;
    border-radius: 10px;
    margin-bottom: 10px;
  }

  .item-card p {
    margin: 0;
    font-weight: 600;
    color: #2d2d2d;
  }

  .poster-info {
    font-size: 0.85rem;
    color: #555;
    margin-top: 4px;
  }

  /* Status badge - on top of image */
  .status-badge {
    position: absolute;
    top: 12px;
    left: 12px;
    padding: 6px 12px;
    border-radius: 8px;
    font-size: 0.8rem;
    font-weight: 700;
    color: #fff;
    z-index: 2;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    box-shadow: 0 2px 5px rgba(0,0,0,0.3);
  }

  .status-claimed { background: #dc3545; }
  .status-unclaimed { background: #28a745; }

  /* Modal */
  .modal-custom {
    display: none;
    position: fixed;
    top: 0; left: 0;
    width: 100%; height: 100%;
    background: rgba(0,0,0,0.6);
    justify-content: center;
    align-items: center;
    z-index: 1050;
    padding: 20px;
  }

  .modal-content-custom {
    background: #fff;
    padding: 25px;
    width: 100%;
    max-width: 550px;
    border-radius: 16px;
    position: relative;
    animation: slideDown 0.35s ease;
  }

  @keyframes slideDown {
    from { transform: translateY(-40px); opacity: 0; }
    to { transform: translateY(0); opacity: 1; }
  }

  .modal-content-custom img {
    width: 100%;
    max-height: 260px;
    object-fit: cover;
    border-radius: 10px;
    margin-bottom: 15px;
  }

  .btn-close-custom {
    position: absolute;
    top: 12px;
    right: 12px;
    background: transparent;
    border: none;
    font-size: 1.3rem;
    cursor: pointer;
  }

  .btn-action {
    margin: 4px 0;
    width: 100%;
  }

  #proofPreview {
    width: 100%;
    border-radius: 8px;
    margin-top: 10px;
    display: none;
  }

  footer {
    margin-top: 40px;
    text-align: center;
    font-size: 0.9rem;
    color: #777;
  }
</style>
</head>
<body>

<div class="container py-4">
  <div class="page-header">
    <a href="feeds.php" class="btn btn-back"><i class="fa fa-arrow-left"></i> Back</a>
    <h4>Total Found Items: <span class="text-primary fw-bold"><?= $total_items; ?></span></h4>
  </div>

  <div class="items-grid">
    <?php
    $sql = "
      SELECT 
        f.id,
        f.item_name,
        f.description,
        f.location,
        f.date_found,
        f.image_path,
        f.anonymous,
        f.user_id AS poster_id,
        u.username
      FROM found_items f
      LEFT JOIN users u ON f.user_id = u.id
      ORDER BY f.date_found DESC
    ";
    $result = $conn->query($sql);

    if ($result && $result->num_rows > 0):
      while ($row = $result->fetch_assoc()):
        $poster_name = ($row['anonymous'] == 1) ? "Anonymous" : htmlspecialchars($row['username']);

        $claim_check = $conn->prepare("SELECT status FROM claims WHERE item_id = ? AND status = 'Approved' LIMIT 1");
        $claim_check->bind_param("i", $row['id']);
        $claim_check->execute();
        $claim_result = $claim_check->get_result();
        $is_approved = ($claim_result && $claim_result->num_rows > 0);
        $claim_check->close();

        if ($is_approved) {
          $badge_class = "status-claimed";
          $badge_text = "Claimed";
          $can_claim = false;
        } else {
          $badge_class = "status-unclaimed";
          $badge_text = "Unclaimed";
          $can_claim = ($row['poster_id'] != $user_id);
        }
    ?>
    <div class="item-card" onclick="showItemDetails(
        '<?= htmlspecialchars($row['item_name']); ?>',
        '<?= htmlspecialchars($row['description']); ?>',
        '<?= htmlspecialchars($row['location']); ?>',
        '<?= htmlspecialchars($row['date_found']); ?>',
        '<?= htmlspecialchars($row['image_path']); ?>',
        '<?= htmlspecialchars($poster_name); ?>',
        '<?= htmlspecialchars($badge_text); ?>',
        <?= $can_claim ? 'true' : 'false'; ?>,
        <?= $row['id']; ?>
    )">
      <span class="status-badge <?= $badge_class; ?>"><?= $badge_text; ?></span>
      <img src="<?= htmlspecialchars($row['image_path']); ?>" alt="Item Image">
      <p><?= htmlspecialchars($row['item_name']); ?></p>
      <div class="poster-info">
        <i class="fa fa-user"></i> Posted by: <strong><?= $poster_name; ?></strong>
      </div>
    </div>
    <?php endwhile; else: ?>
      <p class="text-center">No found items posted yet.</p>
    <?php endif; ?>
  </div>
</div>

<!-- Item Modal -->
<div class="modal-custom" id="itemModal">
  <div class="modal-content-custom">
    <button class="btn-close-custom" onclick="closeModal()">&times;</button>
    <h5 id="modalItemName" class="fw-bold mb-2"></h5>
    <img id="modalItemImage" src="" alt="Item Image">
    <p><i class="fa fa-calendar"></i> <strong>Date Found:</strong> <span id="modalItemDate"></span></p>
    <p><i class="fa fa-map-marker-alt"></i> <strong>Location:</strong> <span id="modalItemLocation"></span></p>
    <p><i class="fa fa-align-left"></i> <strong>Description:</strong> <span id="modalItemDescription"></span></p>
    <p><i class="fa fa-user"></i> <strong>Posted by:</strong> <span id="modalItemPoster"></span></p>
    <p><i class="fa fa-tag"></i> <strong>Status:</strong> <span id="modalItemStatus"></span></p>

    <button class="btn btn-primary btn-action" id="claimButton" style="display:none;" onclick="openProofModal()">Claim This Item</button>
    <button class="btn btn-secondary btn-action" onclick="closeModal()">Close</button>
  </div>
</div>

<!-- Proof Upload Modal -->
<div class="modal-custom" id="proofModal">
  <div class="modal-content-custom">
    <button class="btn-close-custom" onclick="closeProofModal()">&times;</button>
    <h5 class="fw-bold mb-3 text-center">Submit Proof of Ownership</h5>
    <form id="proofForm" enctype="multipart/form-data">
      <input type="hidden" name="item_id" id="proofItemId">
      <div class="mb-3">
        <label class="form-label">Upload Image Evidence</label>
        <input type="file" name="proof_image" class="form-control" accept="image/*" required onchange="previewProof(event)">
        <img id="proofPreview" alt="Preview">
      </div>
      <button type="submit" class="btn btn-success w-100">Submit Proof</button>
    </form>
  </div>
</div>

<footer>
  <p>&copy; <?= date("Y"); ?> CvSU Naic Lost & Found. All Rights Reserved.</p>
</footer>

<script>
let selectedItemId = null;

function showItemDetails(name, description, location, date, image, poster, status, canClaim, id) {
  document.getElementById('modalItemName').innerText = name;
  document.getElementById('modalItemDescription').innerText = description;
  document.getElementById('modalItemLocation').innerText = location;
  document.getElementById('modalItemDate').innerText = date;
  document.getElementById('modalItemImage').src = image;
  document.getElementById('modalItemPoster').innerText = poster;
  document.getElementById('modalItemStatus').innerText = status;
  selectedItemId = id;
  document.getElementById('claimButton').style.display = canClaim ? 'block' : 'none';
  document.getElementById('itemModal').style.display = 'flex';
}

function closeModal() { document.getElementById('itemModal').style.display = 'none'; }
function closeProofModal() { document.getElementById('proofModal').style.display = 'none'; }

function openProofModal() {
  closeModal();
  document.getElementById('proofItemId').value = selectedItemId;
  document.getElementById('proofModal').style.display = 'flex';
}

function previewProof(event) {
  const file = event.target.files[0];
  if (file) {
    const img = document.getElementById('proofPreview');
    img.src = URL.createObjectURL(file);
    img.style.display = 'block';
  }
}

document.getElementById('proofForm').addEventListener('submit', function(e) {
  e.preventDefault();
  const formData = new FormData(this);

  fetch('claim_item.php', { method: 'POST', body: formData })
    .then(res => res.text())
    .then(data => {
      alert(data.trim());
      closeProofModal();
      setTimeout(() => location.reload(), 1000);
    })
    .catch(err => {
      alert("Something went wrong. Please try again.");
      console.error(err);
    });
});

window.addEventListener('click', e => {
  if (e.target === document.getElementById('itemModal')) closeModal();
  if (e.target === document.getElementById('proofModal')) closeProofModal();
});
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
