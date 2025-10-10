<?php
include 'db.php';

$q = trim($_GET['q'] ?? '');
if ($q === '') exit('<div class="card"><h3>Please enter a search term.</h3></div>');

$q = "%{$q}%";

// Search both lost_items and found_items
$sql = "
  (SELECT 'lost' AS type, id, description AS name, image_path, claimed
   FROM lost_items
   WHERE description LIKE ?)
  UNION
  (SELECT 'found' AS type, id, item_name AS name, image_path, claimed
   FROM found_items
   WHERE item_name LIKE ?)
  ORDER BY id DESC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param('ss', $q, $q);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
  echo '<div class="card"><h3>No items found matching your search.</h3></div>';
  exit();
}

while ($item = $result->fetch_assoc()) {
  $img = $item['image_path'];
  if (!str_starts_with($img, 'uploads/')) $img = 'uploads/' . $img;
  if (!file_exists($img)) $img = 'images/default-placeholder.png';
  $page = $item['type'] === 'lost' ? 'items.php' : 'found_items.php';
  echo '
  <div class="card">
    <img src="'.htmlspecialchars($img).'" alt="Item">
    <h3>'.htmlspecialchars($item['name']).'</h3>
    <p class="status '.($item['claimed'] ? 'claimed' : 'missing').'">'
    .($item['claimed'] ? 'Claimed' : 'Available').'</p>
    <button onclick="window.location.href=\''.$page.'?id='.$item['id'].'\'">View Details</button>
  </div>';
}
