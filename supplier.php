<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

$userId  = $_SESSION['user']['user_id'];
$brCode  = $_SESSION['user']['br_code'];
$canInsert = $_SESSION['user']['can_insert'] ?? 0;
$canEdit   = $_SESSION['user']['can_edit'] ?? 0;
$canDelete = $_SESSION['user']['can_delete'] ?? 0;

$editSupplier = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $supplierId = $_POST['supplier_id'] ?? '';
    $name       = trim($_POST['supplier_name']);
    $address    = trim($_POST['supplier_address']);
    $contact    = trim($_POST['supplier_contact']);

    if ($supplierId) {
        if ($canEdit) {
            $stmt = $pdo->prepare("
                UPDATE supplier
                SET supplier_name = :name,
                    supplier_address = :address,
                    supplier_contact = :contact,
                    edit_user = :user,
                    edit_date = NOW()
                WHERE supplier_id = :id
                  AND br_code = :br
            ");
            $stmt->execute([
                'name'    => $name,
                'address' => $address,
                'contact' => $contact,
                'user'    => $userId,
                'id'      => $supplierId,
                'br'      => $brCode,
            ]);
        }
    } else {
        if ($canInsert) {
            $stmt = $pdo->prepare("
                INSERT INTO supplier
                    (supplier_name, supplier_address, supplier_contact,
                     entry_user, entry_date, br_code, org_code)
                VALUES
                    (:name, :address, :contact, :user, NOW(), :br, :org)
            ");
            $stmt->execute([
                'name'     => $name,
                'address'  => $address,
                'contact'  => $contact,
                'user'     => $userId,
                'br'       => $brCode,
                'org'      => $_SESSION['user']['org_code'],
            ]);
        }
    }
    header("Location: supplier.php");
    exit();
}

if (isset($_GET['edit']) && $canEdit) {
    $stmt = $pdo->prepare("SELECT * FROM supplier WHERE supplier_id = :id AND br_code = :br order by supplier_id");
    $stmt->execute(['id' => $_GET['edit'], 'br' => $brCode]);
    $editSupplier = $stmt->fetch();
}

if (isset($_GET['delete']) && $canDelete) {
    $stmt = $pdo->prepare("DELETE FROM supplier WHERE supplier_id = :id AND br_code = :br");
    $stmt->execute(['id' => $_GET['delete'], 'br' => $brCode]);
    header("Location: supplier.php");
    exit();
}

// --- Search filters ---
$searchName    = trim($_GET['search_name'] ?? '');

$sql = "SELECT * FROM supplier WHERE br_code = :br_code";
$params = ['br_code' => $brCode];

if ($searchName !== '') {
    $sql .= " AND supplier_name LIKE :sname";
    $params['sname'] = '%' . $searchName . '%';
}

$sql .= " ORDER BY supplier_id";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$suppliers = $stmt->fetchAll();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Supplier - Stock3600</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

</head>
<body class="d-flex flex-column min-vh-100">
    <?php include 'header.php'; ?>
    <main class="flex-grow-1 container py-4">
        <h3>Supplier Management</h3>

        <?php if ($canInsert || $editSupplier): ?>
            <div class="card mb-4">
                <div class="card-body">
                    <form method="post">
                        <input type="hidden" name="supplier_id" 
                               value="<?= htmlspecialchars($editSupplier['supplier_id'] ?? '') ?>">
                        <div class="mb-3">
                            <label>Supplier Name</label>
                            <input type="text" name="supplier_name" class="form-control" required
                                   value="<?= htmlspecialchars($editSupplier['supplier_name'] ?? '') ?>">
                        </div>
                        <div class="mb-3">
                            <label>Address</label>
                            <textarea name="supplier_address" class="form-control"><?= htmlspecialchars($editSupplier['supplier_address'] ?? '') ?></textarea>
                        </div>
                        <div class="mb-3">
                            <label>Contact</label>
                            <input type="text" name="supplier_contact" class="form-control"
                                   value="<?= htmlspecialchars($editSupplier['supplier_contact'] ?? '') ?>">
                        </div>
                        <button type="submit" class="btn btn-primary"><?= $editSupplier ? 'Update' : 'Add' ?></button>
                        <?php if ($editSupplier): ?>
                            <a href="supplier.php" class="btn btn-secondary">Cancel</a>
                        <?php endif; ?>
                    </form>
                </div>
            </div>
        <?php endif; ?>

        <!-- Search Filter Form -->
        <form method="get" class="mb-3 row g-2 align-items-end">
            <div class="col-auto">
                <label for="search_name" class="form-label">Search by Name</label>
                <input type="text" name="search_name" id="search_name"
                       class="form-control"
                       value="<?= htmlspecialchars($_GET['search_name'] ?? '', ENT_QUOTES) ?>">
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-primary">Search</button>
                <a href="supplier.php" class="btn btn-secondary">Reset</a>
            </div>
        </form>

        <div class="table-responsive">
            <table class="table table-bordered table-striped">
                <thead class="table-dark">
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Address</th>
                        <th>Contact</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($suppliers as $sup): ?>
                    <tr>
                        <td><?= htmlspecialchars($sup['supplier_id']) ?></td>
                        <td><?= htmlspecialchars($sup['supplier_name']) ?></td>
                        <td><?= htmlspecialchars($sup['supplier_address']) ?></td>
                        <td><?= htmlspecialchars($sup['supplier_contact']) ?></td>
                      <td>
    <?php if ($canEdit): ?>
        <a href="?edit=<?= urlencode($sup['supplier_id']) ?>" class="btn btn-sm btn-warning">
            <i class="bi bi-pencil-square"></i> Edit
        </a>
    <?php endif; ?>

    <?php if ($canDelete): ?>
        <a href="?delete=<?= urlencode($sup['supplier_id']) ?>" 
           class="btn btn-sm btn-danger" 
           onclick="return confirm('Are you sure?')">
            <i class="bi bi-trash"></i> Delete
        </a>
    <?php endif; ?>
</td>

                    </tr>
                <?php endforeach; ?>
                <?php if (empty($suppliers)): ?>
                    <tr><td colspan="5" class="text-center">No records found.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>
    <?php include 'footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
