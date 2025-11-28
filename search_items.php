<?php
include 'db.php';

$q = trim($_GET['q'] ?? '');
if ($q === '') {
    exit('<div class="card"><h3>Please enter a search term.</h3></div>');
}

$q = "%{$q}%";

// Search both lost_items and found_items
$sql = "
  (SELECT 'lost' AS type, id, description AS name, image_path, 0 AS claimed
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

while ($item = $result->fetch_assoc()):
    $img = $item['image_path'];
    if (!str_starts_with($img, 'uploads/')) $img = 'uploads/' . $img;
    if (!file_exists($img)) $img = 'images/default-placeholder.png';
    $page = $item['type'] === 'lost' ? 'lost_items.php' : 'found_items.php';

    $is_claimed = $item['type'] === 'found' && $item['claimed'];
?>
<div class="card-wrapper" style="position: relative; display: inline-block;">
    <?php if ($item['type'] === 'found'): ?>
        <span class="badge <?php echo $is_claimed ? 'claimed-badge' : 'found-available-badge'; ?>"
              style="position: absolute; top: -12px; right: -12px; z-index: 10; padding: 6px 10px; border-radius: 8px; font-weight: 700; font-size: 0.8rem; color: #fff; background: <?php echo $is_claimed ? '#ff4757' : '#10b981'; ?>;">
            <?php echo $is_claimed ? 'Claimed' : 'Available'; ?>
        </span>
    <?php endif; ?>

    <div class="card">
        <div class="image-container">
            <img src="<?php echo htmlspecialchars($img); ?>" alt="Item">
            <span class="label <?php echo $item['type'] === 'lost' ? 'lost' : 'found'; ?>">
                <?php echo ucfirst($item['type']); ?>
            </span>
        </div>
        <h3><?php echo htmlspecialchars($item['name']); ?></h3>
        <?php if ($item['type'] === 'lost'): ?>
            <p class="status missing">Missing</p>
        <?php endif; ?>
        <button 
            onclick="<?php if (!$is_claimed) { echo "window.location.href='$page?id=".$item['id']."'"; } ?>" 
            <?php if ($is_claimed) echo 'disabled style="cursor:not-allowed; opacity:0.6;"'; ?>
        >
            View Details
        </button>
    </div>
</div>
<?php endwhile; ?>
