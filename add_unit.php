<?php
session_start();
require 'db.php';

$userId     = $_SESSION['user']['user_id'];
$brCode     = $_SESSION['user']['br_code'];
$orgCode    = $_SESSION['user']['org_code'];
$canInsert  = $_SESSION['user']['can_insert'] ?? 1;
$canEdit    = $_SESSION['user']['can_edit'] ?? 0;
$canDelete  = $_SESSION['user']['can_delete'] ?? 0;
$canApprove = $_SESSION['user']['can_approve'] ?? 0;

$message    = '';
$editUnit   = null;

// Edit / Delete / Approve
if (isset($_GET['edit']) && $canEdit) {
    $stmt = $pdo->prepare("SELECT * FROM product_unit WHERE unit_id=:id AND br_code=:br");
    $stmt->execute(['id'=>$_GET['edit'], 'br'=>$brCode]);
    $editUnit = $stmt->fetch(PDO::FETCH_ASSOC);
}

if (isset($_GET['delete']) && $canDelete) {
    $stmt = $pdo->prepare("DELETE FROM product_unit WHERE unit_id=:id AND br_code=:br AND authorized_status='N'");
    $stmt->execute(['id'=>$_GET['delete'], 'br'=>$brCode]);
    $message = "<div class='alert alert-danger py-2'>Unit deleted!</div>";
}

if (isset($_GET['approve']) && $canApprove) {
    $stmt = $pdo->prepare("
        UPDATE product_unit 
        SET authorized_status='Y', authorized_user=:user, authorized_date=NOW() 
        WHERE unit_id=:id AND br_code=:br
    ");
    $stmt->execute(['user'=>$userId, 'id'=>$_GET['approve'], 'br'=>$brCode]);
    $message = "<div class='alert alert-success py-2'>Unit approved!</div>";
}

// Insert / Update
if ($_SERVER['REQUEST_METHOD']==='POST') {
    $unitId    = $_POST['unit_id'] ?? '';
    $unitName  = trim($_POST['unit_name']);
    $unitShort = trim($_POST['unit_short']);
    $desc      = trim($_POST['description']);

    if ($unitId && $canEdit) {
        $stmt = $pdo->prepare("
            UPDATE product_unit 
            SET unit_name=:name, unit_short=:short, description=:desc, edit_user=:user, edit_date=NOW() 
            WHERE unit_id=:id AND br_code=:br AND authorized_status='N'
        ");
        $stmt->execute([
            'name'=>$unitName,
            'short'=>$unitShort,
            'desc'=>$desc,
            'user'=>$userId,
            'id'=>$unitId,
            'br'=>$brCode
        ]);
        $message = "<div class='alert alert-warning py-2'>Unit updated!</div>";
    } elseif (!$unitId && $canInsert) {
        $stmt = $pdo->prepare("
            INSERT INTO product_unit 
            (unit_name, unit_short, description, entry_user, entry_date, org_code, br_code, authorized_status) 
            VALUES (:name,:short,:desc,:user,NOW(),:org,:br,'N')
        ");
        $stmt->execute([
            'name'=>$unitName,
            'short'=>$unitShort,
            'desc'=>$desc,
            'user'=>$userId,
            'org'=>$orgCode,
            'br'=>$brCode
        ]);
        $message = "<div class='alert alert-success py-2'>Unit added!</div>";
    }
    header("Location: add_unit.php");
    exit();
}

// Fetch units
$stmt = $pdo->prepare("SELECT * FROM product_unit WHERE br_code=:br ORDER BY unit_name");
$stmt->execute(['br'=>$brCode]);
$units = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Product Units - Stock360</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<style>
.table-vcenter td { vertical-align: middle; }
.btn-action-group { display:flex; gap:5px; justify-content:center; flex-wrap:nowrap; }
.btn-action { display:inline-flex; align-items:center; gap:4px; white-space:nowrap; padding:0.25rem 0.5rem; font-size:0.8rem; }
.is-editing { border:2px solid #0d6efd !important; background-color:#f0f7ff !important; }
</style>
</head>
<body class="bg-light d-flex flex-column min-vh-100">
<?php include 'header.php'; ?>

<main class="container py-4 flex-grow-1">
<h3><i class="bi bi-box-seam"></i> Product Unit Management</h3>
<?= $message ?>

<!-- Form -->
<div class="card mb-4" id="form_card">
<div class="card-body">
<?php if ($canInsert || $editUnit): ?>
<form method="post">
<input type="hidden" name="unit_id" value="<?= htmlspecialchars($editUnit['unit_id'] ?? '') ?>">

<div class="row g-3">
    <div class="col-md-4">
        <label>Unit Name</label>
        <input type="text" name="unit_name" class="form-control" required value="<?= htmlspecialchars($editUnit['unit_name'] ?? '') ?>">
    </div>
    <div class="col-md-3">
        <label>Short Name</label>
        <input type="text" name="unit_short" class="form-control" required value="<?= htmlspecialchars($editUnit['unit_short'] ?? '') ?>">
    </div>
    <div class="col-md-5">
        <label>Description</label>
        <input type="text" name="description" class="form-control" value="<?= htmlspecialchars($editUnit['description'] ?? '') ?>">
    </div>
</div>
<div class="mt-3">
    <button class="btn btn-primary"><?= $editUnit ? 'Update' : 'Add' ?></button>
    <?php if ($editUnit): ?>
    <a href="add_unit.php" class="btn btn-secondary">Cancel</a>
    <?php endif; ?>
</div>
</form>
<?php else: ?>
<p class="text-muted">You do not have permission to add units.</p>
<?php endif; ?>
</div>
</div>

<!-- Units Table -->
<div class="card">
<div class="card-body">
<div class="table-responsive">
<table class="table table-bordered table-hover border-3">
<thead class="table-secondary">
<tr>
<th>ID</th>
<th>Unit Name</th>
<th>Short</th>
<th>Description</th>
<th width="250">Actions</th>
</tr>
</thead>
<tbody>
<?php foreach($units as $u): ?>
<tr class="text-center">
<td><?= $u['unit_id'] ?></td>
<td><?= htmlspecialchars($u['unit_name']) ?></td>
<td><?= htmlspecialchars($u['unit_short']) ?></td>
<td><?= htmlspecialchars($u['description']) ?></td>
<td>
<div class="btn-action-group">
<?php if($u['authorized_status']!=='Y'): ?>
    <?php if($canEdit): ?>
    <a href="?edit=<?= $u['unit_id'] ?>" class="btn btn-sm btn-warning btn-action"><i class="bi bi-pencil-square"></i> Edit</a>
    <?php endif; ?>
    <?php if($canDelete): ?>
    <a href="?delete=<?= $u['unit_id'] ?>" class="btn btn-sm btn-danger btn-action" onclick="return confirm('Delete this unit?')"><i class="bi bi-trash"></i> Delete</a>
    <?php endif; ?>
    <?php if($canApprove): ?>
    <a href="?approve=<?= $u['unit_id'] ?>" class="btn btn-sm btn-success btn-action"><i class="bi bi-check-circle"></i> Approve</a>
    <?php endif; ?>
<?php else: ?>
    <span class="badge bg-success"><i class="bi bi-shield-lock"></i> Authorized</span>
<?php endif; ?>
</div>
</td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>
</div>
</div>

</main>
<?php include 'footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
