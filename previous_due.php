<?php
session_start();
require 'db.php';

/* ==========================
   USER INFO
========================== */
$user      = $_SESSION['user'] ?? null;
$userId    = $user['user_id'] ?? '';
$brCode    = $user['br_code'] ?? '';
$orgCode   = $user['org_code'] ?? '';
$canInsert = $user['can_insert'] ?? 1;

$message = "";

/* ==========================
   HANDLE PREVIOUS DUE ENTRY
========================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $canInsert) {

    $customer_id = $_POST['customer_id'] ?? '';
    $due_amount  = $_POST['due_amount'] ?? 0;

    if (!$customer_id || !is_numeric($due_amount) || $due_amount <= 0) {
        $message = "<div class='alert alert-danger'>Invalid customer or due amount.</div>";
    } else {

        // Check existing previous due
        $chk = $pdo->prepare("
            SELECT prev_due_id
            FROM customer_previous_due
            WHERE customer_id=? AND br_code=? AND org_code=?
        ");
        $chk->execute([$customer_id, $brCode, $orgCode]);
        $exist = $chk->fetch();

        if ($exist) {
            // Update
            $stmt = $pdo->prepare("
                UPDATE customer_previous_due
                SET previous_due_amount=:amt,
                    edit_user=:usr,
                    edit_date=NOW()
                WHERE customer_id=:cid
                  AND br_code=:br
                  AND org_code=:org
            ");
            $stmt->execute([
                'amt' => $due_amount,
                'usr' => $userId,
                'cid' => $customer_id,
                'br'  => $brCode,
                'org' => $orgCode
            ]);

            $message = "<div class='alert alert-success'>Previous Due Updated Successfully</div>";
        } else {
            // Insert
            $prevDueId = "CPD-" . $brCode . "-" . date('ymdHis') . rand(10,99);

            $stmt = $pdo->prepare("
                INSERT INTO customer_previous_due
                (prev_due_id, customer_id, previous_due_amount,
                 entry_user, entry_date, org_code, br_code)
                VALUES
                (:id,:cid,:amt,:usr,NOW(),:org,:br)
            ");
            $stmt->execute([
                'id'  => $prevDueId,
                'cid' => $customer_id,
                'amt' => $due_amount,
                'usr' => $userId,
                'org' => $orgCode,
                'br'  => $brCode
            ]);

            $message = "<div class='alert alert-success'>
                Previous Due Added Successfully: <b>$prevDueId</b>
            </div>";
        }
    }
}

/* ==========================
   FETCH CUSTOMERS
========================== */
$stmt = $pdo->prepare("
    SELECT customer_id, customer_name
    FROM customer_info
    WHERE br_code=:br AND org_code=:org
      AND (delete_status!='Y' OR delete_status IS NULL)
    ORDER BY customer_name
");
$stmt->execute(['br'=>$brCode,'org'=>$orgCode]);
$customers = $stmt->fetchAll();

/* ==========================
   FETCH PREVIOUS DUE LIST
========================== */
$stmt = $pdo->prepare("
    SELECT 
        p.prev_due_id,
        p.previous_due_amount,
        p.entry_date,
        c.customer_name,
        c.customer_phone
    FROM customer_previous_due p
    JOIN customer_info c ON p.customer_id=c.customer_id
    WHERE p.br_code=:br AND p.org_code=:org
    ORDER BY p.entry_date DESC
");
$stmt->execute(['br'=>$brCode,'org'=>$orgCode]);
$prevDueList = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html>
<head>
<title>Previous Due Entry</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>

<body class="bg-light">
<?php include 'header.php'; ?>

<div class="container py-4">

<h3><i class="bi bi-cash-stack"></i> Previous Due Entry</h3>
<?= $message ?>

<!-- ENTRY FORM -->
<div class="card shadow-sm mb-4">
<div class="card-body">
<form method="post">
<div class="row g-3">
<div class="col-md-6">
<label class="form-label">Customer</label>
<select name="customer_id" class="form-select" required>
<option value="">-- Select Customer --</option>
<?php foreach($customers as $c): ?>
<option value="<?= $c['customer_id'] ?>"><?= htmlspecialchars($c['customer_name']) ?></option>
<?php endforeach; ?>
</select>
</div>

<div class="col-md-4">
<label class="form-label">Previous Due Amount</label>
<input type="number" step="0.01" name="due_amount" class="form-control" required>
</div>

<div class="col-md-2 d-grid">
<button class="btn btn-success mt-4">
<i class="bi bi-save"></i> Save
</button>
</div>
</div>
</form>
</div>
</div>

<!-- SEARCH -->
<div class="mb-3">
<input type="text" id="search" class="form-control"
placeholder="Search by Customer Name / Phone / Previous Due ID">
</div>

<!-- PREVIOUS DUE TABLE -->
<div class="card shadow-sm">
<div class="card-body">
<h5>Previous Due List</h5>

<table class="table table-bordered table-striped">
<thead class="table-dark">
<tr>
<th>Prev Due ID</th>
<th>Customer</th>
<th>Phone</th>
<th>Due Amount</th>
<th>Date</th>
</tr>
</thead>
<tbody id="dueTable">
<?php foreach($prevDueList as $d): ?>
<tr>
<td><?= $d['prev_due_id'] ?></td>
<td><?= htmlspecialchars($d['customer_name']) ?></td>
<td><?= htmlspecialchars($d['customer_phone']) ?></td>
<td class="text-end"><?= number_format($d['previous_due_amount'],2) ?></td>
<td><?= date('d-m-Y',strtotime($d['entry_date'])) ?></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>
</div>

</div>

<script>
$("#search").on("keyup", function() {
    let value = $(this).val().toLowerCase();
    $("#dueTable tr").filter(function() {
        $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
    });
});
</script>

<?php include 'footer.php'; ?>
</body>
</html>
