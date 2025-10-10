
<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.html");
    exit();
}

$user_id = $_SESSION['user_id'];

$sql = "
    SELECT 
        c.id AS claim_id,
        c.status,
        c.message,
        c.claim_date,
        c.proof_image,
        f.id AS item_id,
        f.item_name,
        f.description,
        f.image_path
    FROM claims c
    JOIN found_items f ON c.item_id = f.id
    WHERE c.user_id = ?
    ORDER BY c.claim_date DESC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    echo "<h2>My Claims</h2>";
    while ($row = $result->fetch_assoc()) {
        echo "<div style='border:1px solid #ccc; padding:10px; margin:10px; border-radius:10px; background:#f7f7f7'>";
        echo "<h3>" . htmlspecialchars($row['item_name']) . "</h3>";
        echo "<p><b>Description:</b> " . htmlspecialchars($row['description']) . "</p>";
        echo "<p><b>Status:</b> " . htmlspecialchars($row['status']) . "</p>";
        echo "<p><b>Message:</b> " . htmlspecialchars($row['message']) . "</p>";
        echo "<p><b>Claimed on:</b> " . htmlspecialchars($row['claim_date']) . "</p>";
        echo "<img src='" . htmlspecialchars($row['image_path']) . "' width='150' style='border-radius:10px'>";
        echo "<p><b>Proof:</b></p>";
        echo "<img src='" . htmlspecialchars($row['proof_image']) . "' width='150' style='border-radius:10px'>";
        echo "</div>";
    }
} else {
    echo "<p>No claims found.</p>";
}

$stmt->close();
$conn->close();
?>
 