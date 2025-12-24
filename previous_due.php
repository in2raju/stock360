<?php
session_start();
require 'db.php';

// Logged-in user info
$user      = $_SESSION['user'] ?? null;
$userId    = $user['user_id'] ?? '';
$brCode    = $user['br_code'] ?? '';
$orgCode   = $user['org_code'] ?? '';
$canInsert = $user['can_insert'] ?? 1;

$message = "";

// Handle Previous Due Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $canInsert) {
    $customer_id = $_POST['customer_id'] ?? '';
    $due_amount  = $_POST['due_amount'] ?? 0;

    // Validation
    if (!$customer_id || !is_numeric($due_amount) || $due_amount <= 0) {
        $message = "<div class='alert alert-danger'>Invalid customer or due amount.</div>";
    } else {
        // Insert previous due
        $prevDueId = "PDU-" . $brCode . "-" . date('ymdHis') . rand(10,99);
        $stmt = $pdo->prepare("INSERT INTO customer_previous_due 
            (prev_due_id, customer_id, due_amount, entry_user, entry_date, org_code, br_code)
            VALUES (:id, :customer, :amount, :user, NOW(), :org, :br)
        ");
        $stmt->execute([
            'id' => $prevDueId,
            'customer' => $customer_id,
            'amount' => $due_amount,
            'user' => $userId,
            'org' => $orgCode,
            'br' => $brCode
        ]);
        $message = "<div class='alert alert-success'>Previous Due Added Successfully: $prevDueId</div>";
    }
}

// Fetch Customers (Branch-wise)
$stmt = $pdo->prepare("SELECT customer_id, customer_name FROM customer_info 
    WHERE br_code = :br AND org_code = :org AND (delete_status != 'Y' OR delete_status IS NULL)
    ORDER BY customer_name ASC
");
$stmt->execute(['br' => $brCode, 'org' => $orgCode]);
$customers = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Previous Due Entry</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</head>
<body class="d-flex flex-column min-vh-100 bg-light">

<?php include 'header.php'; ?>

<main class="container py-4 flex-grow-1">
<h3><i class="bi bi-cash-stack"></i> Previous Due Entry</h3>
<?= $message ?>

<div class="card shadow-sm">
    <div class="card-body">
        <form method="post">
            <div class="row g-3 align-items-end">
                <div class="col-md-6">
                    <label class="form-label">Customer</label>
                    <select name="customer_id" class="form-select" required>
                        <option value="">-- Select Customer --</option>
                        <?php foreach ($customers as $c): ?>
                            <option value="<?= $c['customer_id'] ?>"><?= htmlspecialchars($c['customer_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Due Amount</label>
                    <input type="number" step="0.01" name="due_amount" class="form-control" placeholder="0.00" required>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-success w-100">
                        <i class="bi bi-save"></i> Save
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
</main>

<?php include 'footer.php'; ?>

</body>
</html>
