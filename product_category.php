<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require 'db.php';

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

// Logged-in user details
$loginUserId = $_SESSION['user']['user_id'] ?? '';
$brCode      = $_SESSION['user']['br_code'] ?? '';
$brName      = $_SESSION['user']['br_name'] ?? '';
$userTypeId  = $_SESSION['user']['user_type_id'] ?? '';

// Fetch organization name
$orgCode = substr($brCode, 0, 3);
$stmtOrg = $pdo->prepare("SELECT organization_name FROM organization_info WHERE org_code = :org_code");
$stmtOrg->execute(['org_code' => $orgCode]);
$orgRow  = $stmtOrg->fetch(PDO::FETCH_ASSOC);
$orgName = $orgRow['organization_name'] ?? '';

// Permissions
$canInsert = $canEdit = $canDelete = 0;
if ($userTypeId) {
    $stmtPerm = $pdo->prepare("SELECT can_insert, can_edit, can_delete 
                               FROM user_action_permission 
                               WHERE user_type_id = :user_type_id");
    $stmtPerm->execute(['user_type_id' => $userTypeId]);
    $permRow = $stmtPerm->fetch(PDO::FETCH_ASSOC);
    if ($permRow) {
        $canInsert = (int)$permRow['can_insert'];
        $canEdit   = (int)$permRow['can_edit'];
        $canDelete = (int)$permRow['can_delete'];
    }
}

// Edit variable
$editCategory = null;

// Fetch suppliers for dropdown
$stmtSup = $pdo->prepare("SELECT supplier_id, supplier_name FROM supplier WHERE br_code = :br_code ORDER BY supplier_name");
$stmtSup->execute(['br_code' => $brCode]);
$suppliers = $stmtSup->fetchAll(PDO::FETCH_ASSOC);

// Insert / Update
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $categoryId = $_POST['product_category_id'] ?? '';
    $name       = trim($_POST['product_category_name']);
    $supplierId = $_POST['supplier_id'];

    if ($categoryId) {
        // Update
        if ($canEdit) {
            $stmt = $pdo->prepare("UPDATE product_category 
                                   SET product_category_name = :name,
                                       supplier_id = :supplier_id,
                                       edit_user = :user,
                                       edit_date = NOW()
                                   WHERE product_category_id = :id AND br_code = :br_code");
            $stmt->execute([
                'name'        => $name,
                'supplier_id' => $supplierId,
                'user'        => $loginUserId,
                'id'          => $categoryId,
                'br_code'     => $brCode
            ]);
        }
    } else {
        // Insert
        if ($canInsert) {
            // Do NOT generate product_category_id in PHP; let the MySQL trigger handle it
            $stmt = $pdo->prepare("INSERT INTO product_category 
                                   (product_category_name, supplier_id, entry_user, entry_date, br_code, org_code)
                                   VALUES (:name, :supplier_id, :user, NOW(), :br_code, :org_code)");
            $stmt->execute([
                'name'        => $name,
                'supplier_id' => $supplierId,
                'user'        => $loginUserId,
                'br_code'     => $brCode,
                'org_code'    => $orgCode
            ]);
        }
    }

    header("Location: product_category.php");
    exit();
}

// Edit request
if (isset($_GET['edit']) && $canEdit) {
    $stmt = $pdo->prepare("SELECT * FROM product_category WHERE product_category_id = :id AND br_code = :br_code");
    $stmt->execute(['id' => $_GET['edit'], 'br_code' => $brCode]);
    $editCategory = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Delete request
if (isset($_GET['delete']) && $canDelete) {
    $stmt = $pdo->prepare("DELETE FROM product_category WHERE product_category_id = :id AND br_code = :br_code");
    $stmt->execute(['id' => $_GET['delete'], 'br_code' => $brCode]);
    header("Location: product_category.php");
    exit();
}

// Fetch categories
$stmt = $pdo->prepare("SELECT pc.*, s.supplier_name FROM product_category pc 
                       LEFT JOIN supplier s ON pc.supplier_id = s.supplier_id
                       WHERE pc.br_code = :br_code ORDER BY pc.product_category_name");
$stmt->execute(['br_code' => $brCode]);
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Product Category - Stock3600</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body class="d-flex flex-column min-vh-100">
<?php include 'header.php'; ?>

<main class="flex-grow-1 container py-4">
    <h3>Product Category Management</h3>

    <!-- Form -->
    <div class="card mb-4">
        <div class="card-body">
            <?php if ($canInsert || $editCategory): ?>
            <form method="post" action="product_category.php">
                <input type="hidden" name="product_category_id" value="<?= htmlspecialchars($editCategory['product_category_id'] ?? '') ?>">
                
                <div class="mb-3">
                    <label>Category Name</label>
                    <input type="text" name="product_category_name" class="form-control" required value="<?= htmlspecialchars($editCategory['product_category_name'] ?? '') ?>">
                </div>

                <div class="mb-3">
                    <label>Supplier</label>
                    <select name="supplier_id" class="form-control" required>
                        <option value="">Select Supplier</option>
                        <?php foreach ($suppliers as $sup): ?>
                            <option value="<?= $sup['supplier_id'] ?>" <?= ($editCategory['supplier_id'] ?? '') == $sup['supplier_id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($sup['supplier_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <button type="submit" class="btn btn-primary"><?= $editCategory ? 'Update' : 'Add' ?></button>
                <?php if ($editCategory): ?>
                    <a href="product_category.php" class="btn btn-secondary">Cancel</a>
                <?php endif; ?>
            </form>
            <?php else: ?>
                <p class="text-muted">You do not have permission to add categories.</p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Table -->
    <div class="table-responsive">
        <table class="table table-bordered table-striped">
            <thead class="table-dark">
                <tr>
                    <th>ID</th>
                    <th>Category Name</th>
                    <th>Supplier</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($categories as $cat): ?>
                <tr>
                    <td><?= htmlspecialchars($cat['product_category_id']) ?></td>
                    <td><?= htmlspecialchars($cat['product_category_name']) ?></td>
                    <td><?= htmlspecialchars($cat['supplier_name']) ?></td>
                    <td>
                        <?php if ($canEdit): ?>
                            <a href="?edit=<?= urlencode($cat['product_category_id']) ?>" class="btn btn-sm btn-warning">Edit</a>
                        <?php endif; ?>
                        <?php if ($canDelete): ?>
                            <a href="?delete=<?= urlencode($cat['product_category_id']) ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?')">Delete</a>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</main>

<?php include 'footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
