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
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(145deg, #ffffffff, #b5bbb5ff);
            min-height: 100vh;
        }
        .container {
            background: #fff;
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 12px 40px rgba(0,0,0,0.1);
            margin-top: 50px;
            margin-bottom: 50px;
        }
        h1 {
            font-weight: 700;
            color: #212eedff;
            margin-bottom: 30px;
            text-align: center;
        }
        .table {
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 5px 20px rgba(0,0,0,0.05);
        }
        .table th {
            background: #3947e2ff;
            color: #fff;
            font-weight: 600;
            text-align: center;
        }
        .table td, .table th {
            vertical-align: middle;
        }
        .table tbody tr {
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        .table tbody tr:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        }
        .badge {
            font-size: 0.9rem;
            padding: 0.5em 0.8em;
            border-radius: 12px;
        }
        .btn {
            border-radius: 8px;
        }
        .btn-sm {
            padding: 0.35rem 0.7rem;
        }
        img.proof-img {
            max-width: 80px;
            max-height: 80px;
            border-radius: 10px;
            object-fit: cover;
            border: 1px solid #ddd;
            transition: transform 0.2s ease;
        }
        img.proof-img:hover {
            transform: scale(1.05);
        }
        td.text-start {
            max-width: 250px;
        }
        td.text-start p {
            margin: 0;
        }
        @media (max-width: 992px) {
            .table-responsive {
                font-size: 0.95rem;
            }
            img.proof-img {
                max-width: 60px;
                max-height: 60px;
            }
        }
    </style>
</head>
<body>

<div class="container">
    <h1>📋 Manage Claims</h1>

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
                                        <img src="<?= htmlspecialchars($row['proof_image']); ?>" class="proof-img" alt="Proof Image">
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
