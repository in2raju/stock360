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

    $distributor_code = $_POST['distributor_code'] ?? '';
    $due_amount       = $_POST['due_amount'] ?? 0;

    if (!$distributor_code || !is_numeric($due_amount) || $due_amount <= 0) {
        $message = "<div class='alert alert-danger'>Invalid distributor or due amount.</div>";
    } else {

        // Check existing due
        $chk = $pdo->prepare("
            SELECT distributor_due_id
            FROM distributor_previous_due
            WHERE distributor_code=? AND br_code=? AND org_code=?
        ");
        $chk->execute([$distributor_code, $brCode, $orgCode]);
        $exist = $chk->fetch(PDO::FETCH_ASSOC);

        if ($exist) {
            // UPDATE
            $stmt = $pdo->prepare("
                UPDATE distributor_previous_due
                SET due_amount=:amt,
                    edit_user=:usr,
                    edit_date=NOW()
                WHERE distributor_code=:dcode
                  AND br_code=:br
                  AND org_code=:org
            ");
            $stmt->execute([
                'amt'   => $due_amount,
                'usr'   => $userId,
                'dcode' => $distributor_code,
                'br'    => $brCode,
                'org'   => $orgCode
            ]);

            $message = "<div class='alert alert-success'>Distributor Previous Due Updated Successfully</div>";
        } else {
            // INSERT
            $dueId = "DPD-" . $brCode . "-" . date('ymdHis') . rand(10, 99);

            $stmt = $pdo->prepare("
                INSERT INTO distributor_previous_due
                (distributor_due_id, distributor_code, due_amount,
                 entry_user, entry_date, br_code, org_code, due_date)
                VALUES
                (:id,:dcode,:amt,:usr,NOW(),:br,:org,CURDATE())
            ");
            $stmt->execute([
                'id'    => $dueId,
                'dcode' => $distributor_code,
                'amt'   => $due_amount,
                'usr'   => $userId,
                'br'    => $brCode,
                'org'   => $orgCode
            ]);

            $message = "<div class='alert alert-success'>
                Distributor Previous Due Added Successfully: <b>$dueId</b>
            </div>";
        }
    }
}

/* ==========================
   FETCH DISTRIBUTORS
========================== */
$stmt = $pdo->prepare("
    SELECT distributor_code, distributor_name
    FROM distributor
    WHERE br_code=:br AND org_code=:org
    ORDER BY distributor_name
");
$stmt->execute(['br' => $brCode, 'org' => $orgCode]);
$distributors = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* ==========================
   FETCH PREVIOUS DUE LIST
========================== */
$stmt = $pdo->prepare("
    SELECT 
        d.distributor_due_id,
        d.due_amount,
        d.entry_date,
        m.distributor_name,
        m.distributor_contact
    FROM distributor_previous_due d
    JOIN distributor m ON d.distributor_code = m.distributor_code
    WHERE d.br_code=:br AND d.org_code=:org
    ORDER BY d.entry_date DESC
");
$stmt->execute(['br' => $brCode, 'org' => $orgCode]);
$dueList = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
<title>Distributor Previous Due Entry</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>

<body class="bg-light">
<?php include 'header.php'; ?>

<div class="container py-4">

<h3><i class="bi bi-cash-stack"></i> Distributor Previous Due Entry</h3>
<?= $message ?>

<!-- ENTRY FORM -->
<div class="card shadow-sm mb-4">
<div class="card-body">
<form method="post">
<div class="row g-3">

<div class="col-md-6">
<label class="form-label">Distributor</label>
<select name="distributor_code" class="form-select" required>
<option value="">-- Select Distributor --</option>
<?php foreach ($distributors as $d): ?>
<option value="<?= $d['distributor_code'] ?>">
    <?= htmlspecialchars($d['distributor_name']) ?>
</option>
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
placeholder="Search by Distributor / Phone / Due ID">
</div>

<!-- DUE TABLE -->
<div class="card shadow-sm">
<div class="card-body">
<h5>Distributor Previous Due List</h5>

<table class="table table-bordered table-striped">
<thead class="table-dark">
<tr>
<th>Due ID</th>
<th>Distributor</th>
<th>Phone</th>
<th class="text-end">Due Amount</th>
<th>Date</th>
</tr>
</thead>
<tbody id="dueTable">
<?php foreach ($dueList as $row): ?>
<tr>
<td><?= $row['distributor_due_id'] ?></td>
<td><?= htmlspecialchars($row['distributor_name']) ?></td>
<td><?= htmlspecialchars($row['distributor_contact']) ?></td>
<td class="text-end"><?= number_format($row['due_amount'], 2) ?></td>
<td><?= date('d-m-Y', strtotime($row['entry_date'])) ?></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>
</div>

</div>

<script>
$("#search").on("keyup", function () {
    let value = $(this).val().toLowerCase();
    $("#dueTable tr").filter(function () {
        $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
    });
});
</script>

<?php include 'footer.php'; ?>
</body>
</html>
