<?php
include 'db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
  header("Location: SignIn_SignUp.html");
  exit();
}

$user_id = $_SESSION['user_id'];
$isAdmin = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>Lost Items | CvSU Naic Lost & Found</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
<style>
  body {
    background: linear-gradient(180deg, #f9fafc 0%, #eef1f5 100%);
    font-family: 'Inter', sans-serif;
    color: #333;
  }

  .page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    margin-bottom: 20px;
  }

  .items-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
    gap: 24px;
  }

  .item-card {
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.08);
    padding: 12px;
    text-align: center;
    position: relative;
    transition: all 0.3s ease;
    cursor: pointer;
  }

  .item-card:hover {
    transform: translateY(-6px);
    box-shadow: 0 8px 20px rgba(0,0,0,0.15);
  }

  .item-card img {
    width: 100%;
    height: 180px;
    object-fit: cover;
    border-radius: 8px;
    margin-bottom: 10px;
  }

  .status-badge {
    position: absolute;
    top: 12px;
    right: 12px;
    color: #fff;
    padding: 4px 10px;
    font-size: 13px;
    font-weight: 600;
    border-radius: 8px;
    box-shadow: 0 2px 6px rgba(0,0,0,0.2);
  }
  .status-unclaimed { background: #28a745; }
  .status-pending { background: #ffc107; color: #222; }
  .status-claimed { background: #dc3545; }

  .suggestions {
    margin-top: 10px;
    text-align: left;
    border-top: 1px solid #eee;
    padding-top: 6px;
  }

  .match-item {
    cursor: pointer;
    padding: 4px 6px;
    border-radius: 6px;
    transition: background 0.2s ease;
  }
  .match-item:hover { background: #f1f1f1; }

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
</style>
</head>
<body>

<div class="container py-4">
  <div class="page-header">
    <a href="feeds.php" class="btn btn-outline-dark"><i class="fa fa-arrow-left"></i> Back</a>
  </div>

  <div class="items-grid" id="itemsGrid"></div>
</div>

<!-- Item Modal -->
<div class="modal-custom" id="itemModal">
  <div class="modal-content-custom">
    <button class="btn-close-custom" onclick="closeModal()">&times;</button>
    <h5 id="modalItemName" class="fw-bold mb-2"></h5>
    <img id="modalItemImage" src="" alt="Item Image">
    <p><i class="fa fa-calendar"></i> <strong>Date:</strong> <span id="modalItemDate"></span></p>
    <p><i class="fa fa-map-marker-alt"></i> <strong>Location:</strong> <span id="modalItemLocation"></span></p>
    <p><i class="fa fa-align-left"></i> <strong>Description:</strong> <span id="modalItemDescription"></span></p>
    <p><i class="fa fa-user"></i> <strong>Posted by:</strong> <span id="modalItemPoster"></span></p>

    <button class="btn btn-primary btn-action" id="claimButton" style="display:none;" onclick="openProofModal()">Claim This Item</button>
    <button class="btn btn-secondary btn-action" onclick="closeModal()">Close</button>
  </div>
</div>

<!-- Proof Modal -->
<div class="modal-custom" id="proofModal">
  <div class="modal-content-custom">
    <button class="btn-close-custom" onclick="closeProofModal()">&times;</button>
    <h5 class="fw-bold mb-3 text-center">Submit Proof of Ownership</h5>
    <form id="proofForm" enctype="multipart/form-data">
      <input type="hidden" name="item_id" id="proofItemId">
      <div class="mb-3">
        <label class="form-label">Upload Image Evidence</label>
        <input type="file" name="proof_image" class="form-control" accept="image/*" required onchange="previewProof(event)">
        <img id="proofPreview" style="display:none; width:100%; border-radius:10px; margin-top:10px;">
      </div>
      <button type="submit" class="btn btn-success w-100">Submit Proof</button>
    </form>
  </div>
</div>

<script>
let selectedItemId = null;

function fetchItems() {
  fetch('get_items.php')
    .then(res => res.json())
    .then(data => {
      const grid = document.getElementById('itemsGrid');
      grid.innerHTML = '';

      data.forEach(item => {
        let statusClass = 'status-unclaimed', statusText = 'Unclaimed';
        if (item.claim_status === 'pending') {
          statusClass = 'status-pending';
          statusText = 'Pending';
        } else if (item.claimed == 1) {
          statusClass = 'status-claimed';
          statusText = 'Claimed';
        }

        const card = document.createElement('div');
        card.className = 'item-card';
        card.onclick = () => showItemDetails(item.id);

        card.innerHTML = `
          <div class="status-badge ${statusClass}">${statusText}</div>
          <img src="${item.image_path}" alt="${item.name}">
          <p>${item.name}</p>
          <div class="suggestions" id="suggestions-${item.id}"></div>
        `;
        grid.appendChild(card);

        // SUGGESTIONS — Show "No similar found items" if none
        fetch(`suggest_matches.php?lost_id=${item.id}`)
          .then(res => res.json())
          .then(matches => {
            const container = document.getElementById(`suggestions-${item.id}`);
            const filtered = matches.filter(m => m.claimed != 1);

            if (filtered.length > 0) {
              container.innerHTML = `
                <small class="text-muted fw-bold">Similar Found Items:</small>
                ${filtered.map(m => `
                  <div class="match-item mt-1"
                       onclick="event.stopPropagation(); showItemDetails(${m.id}, true)">
                    <small><i class="fa fa-caret-right"></i> ${m.item_name}</small>
                    <small class="text-secondary">${m.location}</small>
                  </div>
                `).join('')}
              `;
            } else {
              container.innerHTML = `<small class="text-muted fst-italic">No similar found items.</small>`;
            }
          });
      });
    });
}

function showItemDetails(id, isFound = false) {
  fetch(`get_item_details.php?id=${id}&type=${isFound ? 'found' : 'lost'}`)
    .then(res => res.json())
    .then(item => {
      if (item.error) return alert(item.error);

      document.getElementById('modalItemName').innerText = item.name || item.item_name;
      document.getElementById('modalItemImage').src = item.image_path;
      document.getElementById('modalItemDate').innerText = item.date_found || item.created_at || '';
      document.getElementById('modalItemLocation').innerText = item.location;
      document.getElementById('modalItemDescription').innerText = item.description;
      document.getElementById('modalItemPoster').innerText =
        item.anonymous == 1 ? "Anonymous" : (item.uploader_name || "Unknown");

      selectedItemId = id;
      document.getElementById('claimButton').style.display = isFound ? 'block' : 'none';
      document.getElementById('itemModal').style.display = 'flex';
    });
}

function openProofModal() {
  closeModal();
  document.getElementById('proofItemId').value = selectedItemId;
  document.getElementById('proofModal').style.display = 'flex';
}

function closeModal() { document.getElementById('itemModal').style.display = 'none'; }
function closeProofModal() { document.getElementById('proofModal').style.display = 'none'; }

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
    });
});

window.addEventListener('click', e => {
  if (e.target === document.getElementById('itemModal')) closeModal();
  if (e.target === document.getElementById('proofModal')) closeProofModal();
});

document.addEventListener('DOMContentLoaded', fetchItems);
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
