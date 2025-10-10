<?php
include 'db.php';
session_start();

// ✅ Check if admin is logged in
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// ✅ Fetch all claims joined with users and found_items
$sql = "
    SELECT 
        c.id AS claim_id,
        c.item_id,
        c.user_id,
        c.message,
        c.status,
        c.proof_image,
        u.username AS claimant_name,
        f.item_name,
        f.description,
        f.location,
        f.date_found,
        f.image_path
    FROM claims c
    JOIN users u ON c.user_id = u.id
    JOIN found_items f ON c.item_id = f.id
    ORDER BY c.id DESC
";

$result = $conn->query($sql);

if (!$result) {
    die('SQL Error: ' . $conn->error);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Claims</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #89f7fe, #66a6ff);
            font-family: 'Poppins', sans-serif;
        }
        .container {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        .table th {
            background: #007bff;
            color: white;
        }
        .btn {
            border-radius: 8px;
        }
        h1 {
            font-weight: 600;
        }
    </style>
</head>
<body>

<div class="container my-5">
    <h1 class="text-center text-primary mb-4">📋 Manage Claims</h1>

    <div class="table-responsive">
        <table class="table table-bordered table-hover align-middle">
            <thead class="text-center">
                <tr>
                    <th>Claim ID</th>
                    <th>Item Name</th>
                    <th>Claimant</th>
                    <th>Message</th>
                    <th>Proof Image</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result->num_rows > 0): ?>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr class="text-center">
                            <td><?= $row['claim_id']; ?></td>
                            <td>
                                <strong><?= htmlspecialchars($row['item_name']); ?></strong><br>
                                <small class="text-muted">
                                    <?= htmlspecialchars($row['location']); ?> — <?= htmlspecialchars($row['date_found']); ?>
                                </small>
                            </td>
                            <td><?= htmlspecialchars($row['claimant_name']); ?></td>
                            <td class="text-start"><?= nl2br(htmlspecialchars($row['message'])); ?></td>
                            <td>
                                <?php if (!empty($row['proof_image'])): ?>
                                    <a href="<?= htmlspecialchars($row['proof_image']); ?>" target="_blank">
                                        <img src="<?= htmlspecialchars($row['proof_image']); ?>" style="max-width: 100px; max-height: 100px; border-radius: 8px;" alt="Proof Image">
                                    </a>
                                <?php else: ?>
                                    <em>No image</em>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php
                                    $status = strtolower($row['status']);
                                    $badge = match ($status) {
                                        'approved' => 'success',
                                        'rejected' => 'danger',
                                        'pending' => 'warning',
                                        default => 'secondary'
                                    };
                                ?>
                                <span class="badge bg-<?= $badge; ?>"><?= ucfirst($status); ?></span>
                            </td>
                            <td>
                                <?php if ($status === 'pending'): ?>
                                    <form method="POST" action="approve_claim.php" class="d-inline">
                                        <input type="hidden" name="claim_id" value="<?= $row['claim_id']; ?>">
                                        <button type="submit" class="btn btn-sm btn-success">Approve</button>
                                    </form>
                                    <form method="POST" action="reject_claim.php" class="d-inline">
                                        <input type="hidden" name="claim_id" value="<?= $row['claim_id']; ?>">
                                        <button type="submit" class="btn btn-sm btn-danger">Reject</button>
                                    </form>
                                <?php else: ?>
                                    <em><?= ucfirst($row['status']); ?></em>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="7" class="text-center">No claims found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

</body>
</html>
