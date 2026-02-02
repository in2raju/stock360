<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require 'db.php';

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

/* -----------------------------
   Logged-in user details
----------------------------- */
$loginUserId = $_SESSION['user']['user_id'] ?? '';
$brCode      = $_SESSION['user']['br_code'] ?? '';
$userTypeId  = $_SESSION['user']['user_type_id'] ?? '';

$orgCode = substr($brCode, 0, 3);

/* -----------------------------
   Permissions
----------------------------- */
$canInsert = $canEdit = $canDelete = 0;

$stmtPerm = $pdo->prepare("
    SELECT can_insert, can_edit, can_delete
    FROM user_action_permission
    WHERE user_type_id = :ut
");
$stmtPerm->execute(['ut' => $userTypeId]);
$perm = $stmtPerm->fetch(PDO::FETCH_ASSOC);

if ($perm) {
    $canInsert = (int)$perm['can_insert'];
    $canEdit   = (int)$perm['can_edit'];
    $canDelete = (int)$perm['can_delete'];
}

/* -----------------------------
   Edit holder
----------------------------- */
$editCategory = null;

/* -----------------------------
   Suppliers dropdown
----------------------------- */
$stmtSup = $pdo->prepare("
    SELECT supplier_id, supplier_name
    FROM supplier
    WHERE br_code = :br
    ORDER BY supplier_name
");
$stmtSup->execute(['br' => $brCode]);
$suppliers = $stmtSup->fetchAll(PDO::FETCH_ASSOC);

/* -----------------------------
   Units dropdown
----------------------------- */
$stmtUnit = $pdo->prepare("
    SELECT unit_id, unit_name
    FROM product_unit
    WHERE org_code = :org
      AND br_code  = :br
      AND AUTHORIZED_STATUS = 'Y'
    ORDER BY unit_name
");
$stmtUnit->execute([
    'org' => $orgCode,
    'br'  => $brCode
]);
$units = $stmtUnit->fetchAll(PDO::FETCH_ASSOC);

/* -----------------------------
   Insert / Update
----------------------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $categoryId = $_POST['product_category_id'] ?? '';
    $name       = trim($_POST['product_category_name']);
    $supplierId = $_POST['supplier_id'];
    $unitId     = $_POST['unit_id'];

    if ($categoryId && $canEdit) {
        // UPDATE
        $stmt = $pdo->prepare("
            UPDATE product_category
            SET product_category_name = :name,
                supplier_id = :supplier,
                unit_id = :unit,
                edit_user = :user,
                edit_date = NOW()
            WHERE product_category_id = :id
              AND br_code = :br
        ");
        $stmt->execute([
            'name'     => $name,
            'supplier' => $supplierId,
            'unit'     => $unitId,
            'user'     => $loginUserId,
            'id'       => $categoryId,
            'br'       => $brCode
        ]);
    }

    if (!$categoryId && $canInsert) {
        // INSERT
        $stmt = $pdo->prepare("
            INSERT INTO product_category
            (product_category_name, supplier_id, unit_id,
             entry_user, entry_date, org_code, br_code)
            VALUES
            (:name, :supplier, :unit,
             :user, NOW(), :org, :br)
        ");
        $stmt->execute([
            'name'     => $name,
            'supplier' => $supplierId,
            'unit'     => $unitId,
            'user'     => $loginUserId,
            'org'      => $orgCode,
            'br'       => $brCode
        ]);
    }

    header("Location: product_category.php");
    exit();
}

/* -----------------------------
   Edit request
----------------------------- */
if (isset($_GET['edit']) && $canEdit) {
    $stmt = $pdo->prepare("
        SELECT *
        FROM product_category
        WHERE product_category_id = :id
          AND br_code = :br
    ");
    $stmt->execute([
        'id' => $_GET['edit'],
        'br' => $brCode
    ]);
    $editCategory = $stmt->fetch(PDO::FETCH_ASSOC);
}

/* -----------------------------
   Delete request
----------------------------- */
if (isset($_GET['delete']) && $canDelete) {
    $stmt = $pdo->prepare("
        DELETE FROM product_category
        WHERE product_category_id = :id
          AND br_code = :br
    ");
    $stmt->execute([
        'id' => $_GET['delete'],
        'br' => $brCode
    ]);
    header("Location: product_category.php");
    exit();
}

/* -----------------------------
   Fetch category list
----------------------------- */
$stmt = $pdo->prepare("
    SELECT pc.*, s.supplier_name, u.unit_name
    FROM product_category pc
    LEFT JOIN supplier s ON pc.supplier_id = s.supplier_id
    LEFT JOIN product_unit u ON pc.unit_id = u.unit_id
    WHERE pc.br_code = :br
    ORDER BY pc.product_category_name
");
$stmt->execute(['br' => $brCode]);
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Product Category - Stock360</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>
<?php include 'header.php'; ?>

<main class="container py-4">
<h3>Product Category Management</h3>

<div class="card mb-4">
<div class="card-body">

<?php if ($canInsert || $editCategory): ?>
<form method="post">
<input type="hidden" name="product_category_id"
       value="<?= htmlspecialchars($editCategory['product_category_id'] ?? '') ?>">

<div class="mb-3">
<label>Category Name</label>
<input type="text" name="product_category_name" class="form-control" required
       value="<?= htmlspecialchars($editCategory['product_category_name'] ?? '') ?>">
</div>

<div class="mb-3">
<label>Supplier</label>
<select name="supplier_id" class="form-select" required>
<option value="">Select Supplier</option>
<?php foreach ($suppliers as $s): ?>
<option value="<?= $s['supplier_id'] ?>"
<?= (($editCategory['supplier_id'] ?? '') == $s['supplier_id']) ? 'selected' : '' ?>>
<?= htmlspecialchars($s['supplier_name']) ?>
</option>
<?php endforeach; ?>
</select>
</div>

<div class="mb-3">
<label>Unit</label>
<select name="unit_id" class="form-select" required>
<option value="">Select Unit</option>
<?php foreach ($units as $u): ?>
<option value="<?= $u['unit_id'] ?>"
<?= (($editCategory['unit_id'] ?? '') == $u['unit_id']) ? 'selected' : '' ?>>
<?= htmlspecialchars($u['unit_name']) ?>
</option>
<?php endforeach; ?>
</select>
</div>

<button class="btn btn-primary"><?= $editCategory ? 'Update' : 'Add' ?></button>
<?php if ($editCategory): ?>
<a href="product_category.php" class="btn btn-secondary">Cancel</a>
<?php endif; ?>

</form>
<?php endif; ?>

</div>
</div>

<div class="table-responsive">
<table class="table table-bordered table-striped">
<thead class="table-dark">
<tr>
<th>ID</th>
<th>Category</th>
<th>Supplier</th>
<th>Unit</th>
<th>Action</th>
</tr>
</thead>
<tbody>
<?php foreach ($categories as $c): ?>
<tr>
<td><?= $c['product_category_id'] ?></td>
<td><?= htmlspecialchars($c['product_category_name']) ?></td>
<td><?= htmlspecialchars($c['supplier_name']) ?></td>
<td><?= htmlspecialchars($c['unit_name']) ?></td>
<td>
<?php if ($canEdit): ?>
<a href="?edit=<?= $c['product_category_id'] ?>" class="btn btn-sm btn-warning">Edit</a>
<?php endif; ?>
<?php if ($canDelete): ?>
<a href="?delete=<?= $c['product_category_id'] ?>" class="btn btn-sm btn-danger"
   onclick="return confirm('Delete this category?')">Delete</a>
<?php endif; ?>
</td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>

</main>

<?php include 'footer.php'; ?>
</body>
</html>
