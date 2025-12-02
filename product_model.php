<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

$userId    = $_SESSION['user']['user_id'];
$brCode    = $_SESSION['user']['br_code'];
$orgCode   = $_SESSION['user']['org_code'];
$canInsert = $_SESSION['user']['can_insert'] ?? 0;
$canEdit   = $_SESSION['user']['can_edit'] ?? 0;
$canDelete = $_SESSION['user']['can_delete'] ?? 0;

$editModel = null;

// ---------- Fetch dropdown data ----------
$stmtSup = $pdo->prepare("SELECT supplier_id, supplier_name FROM supplier WHERE br_code = :br ORDER BY supplier_name");
$stmtSup->execute(['br' => $brCode]);
$suppliers = $stmtSup->fetchAll(PDO::FETCH_ASSOC);

// --- Get supplier filter if editing or posting ---
$selectedSupplier = $_POST['supplier_id'] ?? $_GET['supplier_id'] ?? null;
$categories = [];

if ($selectedSupplier) {
    $stmtCat = $pdo->prepare("SELECT product_category_id, product_category_name FROM product_category WHERE supplier_id = :sup AND br_code = :br ORDER BY product_category_name");
    $stmtCat->execute(['sup' => $selectedSupplier, 'br' => $brCode]);
    $categories = $stmtCat->fetchAll(PDO::FETCH_ASSOC);
}

// ---------- AJAX Category Fetch ----------
if (isset($_GET['fetch_cat']) && isset($_GET['supplier_id'])) {
    header('Content-Type: application/json');
    $stmt = $pdo->prepare("SELECT product_category_id, product_category_name FROM product_category WHERE supplier_id = :sup AND br_code = :br ORDER BY product_category_name");
    $stmt->execute(['sup' => $_GET['supplier_id'], 'br' => $brCode]);
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    exit();
}

// ---------- Save (Insert / Update) ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $modelId    = $_POST['model_id'] ?? '';
    $supplierId = $_POST['supplier_id'] ?? '';
    $categoryId = $_POST['product_category_id'] ?? '';
    $modelName  = trim($_POST['model_name'] ?? '');
    $price      = trim($_POST['price'] ?? 0);

    if ($modelId) {
        if ($canEdit) {
            $stmt = $pdo->prepare("
                UPDATE product_model
                SET supplier_id = :sup,
                    product_category_id = :cat,
                    model_name = :mname,
                    price = :price,
                    edit_user = :user,
                    edit_date = NOW()
                WHERE model_id = :id AND br_code = :br
            ");
            $stmt->execute([
                'sup' => $supplierId,
                'cat' => $categoryId,
                'mname' => $modelName,
                'price' => $price,
                'user' => $userId,
                'id'   => $modelId,
                'br'   => $brCode
            ]);
        }
    } else {
        if ($canInsert) {
            $stmt = $pdo->prepare("
                INSERT INTO product_model
                    (supplier_id, product_category_id, model_name, price, entry_user, entry_date, br_code, org_code)
                VALUES
                    (:sup, :cat, :mname, :price, :user, NOW(), :br, :org)
            ");
            $stmt->execute([
                'sup'   => $supplierId,
                'cat'   => $categoryId,
                'mname' => $modelName,
                'price' => $price,
                'user'  => $userId,
                'br'    => $brCode,
                'org'   => $orgCode
            ]);
        }
    }

    header("Location: product_model.php");
    exit();
}

// ---------- Edit ----------
if (isset($_GET['edit']) && $canEdit) {
    $stmt = $pdo->prepare("SELECT * FROM product_model WHERE model_id = :id AND br_code = :br");
    $stmt->execute(['id' => $_GET['edit'], 'br' => $brCode]);
    $editModel = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($editModel && isset($editModel['supplier_id'])) {
        $selectedSupplier = $editModel['supplier_id'];

        $stmtCat = $pdo->prepare("
            SELECT product_category_id, product_category_name
            FROM product_category
            WHERE supplier_id = :sup AND br_code = :br
            ORDER BY product_category_name
        ");
        $stmtCat->execute(['sup' => $selectedSupplier, 'br' => $brCode]);
        $categories = $stmtCat->fetchAll(PDO::FETCH_ASSOC);
    } else {
        $editModel = null;
        $selectedSupplier = null;
        $categories = [];
    }
}

// ---------- Delete ----------
if (isset($_GET['delete']) && $canDelete) {
    $stmt = $pdo->prepare("DELETE FROM product_model WHERE model_id = :id AND br_code = :br");
    $stmt->execute(['id' => $_GET['delete'], 'br' => $brCode]);
    header("Location: product_model.php");
    exit();
}

// ---------- Search ----------
$searchName = trim($_GET['search_name'] ?? '');
$sql = "
    SELECT 
        pm.model_id,
        pm.model_name,
        pm.price,
        s.supplier_name,
        c.product_category_name
    FROM product_model pm
    LEFT JOIN supplier s ON pm.supplier_id = s.supplier_id
    LEFT JOIN product_category c ON pm.product_category_id = c.product_category_id
    WHERE pm.br_code = :br
";
$params = ['br' => $brCode];
if ($searchName !== '') {
    $sql .= " AND pm.model_name LIKE :sname";
    $params['sname'] = '%' . $searchName . '%';
}
$sql .= " ORDER BY pm.model_id";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$models = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Product Model - Stock3600</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
</head>
<body class="d-flex flex-column min-vh-100">
<?php include 'header.php'; ?>

<main class="flex-grow-1 container py-4">
    <h3>Product Model Management</h3>

    <?php if ($canInsert || $editModel): ?>
        <div class="card mb-4">
            <div class="card-body">
                <form method="post">
                    <input type="hidden" name="model_id" value="<?= htmlspecialchars($editModel['model_id'] ?? '') ?>">

                    <div class="mb-3">
                        <label>Supplier</label>
                        <select name="supplier_id" id="supplier_id" class="form-select select2" required>
                            <option value="">Select Supplier</option>
                            <?php foreach ($suppliers as $sup): ?>
                                <option value="<?= $sup['supplier_id'] ?>"
                                    <?= ($selectedSupplier == $sup['supplier_id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($sup['supplier_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3" id="category-wrapper" style="<?= $selectedSupplier ? '' : 'display:none;' ?>">
                        <label>Product Category</label>
                        <select name="product_category_id" id="product_category_id" class="form-select select2" required>
                            <option value="">Select Category</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= $cat['product_category_id'] ?>"
                                    <?= (isset($editModel['product_category_id']) && $editModel['product_category_id'] == $cat['product_category_id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($cat['product_category_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label>Model Name</label>
                        <input type="text" name="model_name" class="form-control" required
                               value="<?= htmlspecialchars($editModel['model_name'] ?? '') ?>">
                    </div>

                    <div class="mb-3">
                        <label>Price</label>
                        <input type="number" step="0.01" name="price" class="form-control"
                               value="<?= htmlspecialchars($editModel['price'] ?? '') ?>">
                    </div>

                    <button type="submit" class="btn btn-primary"><?= $editModel ? 'Update' : 'Add' ?></button>
                    <?php if ($editModel): ?>
                        <a href="product_model.php" class="btn btn-secondary">Cancel</a>
                    <?php endif; ?>
                </form>
            </div>
        </div>
    <?php endif; ?>

    <!-- Search Filter Form -->
    <form method="get" class="mb-3 row g-2 align-items-end">
        <div class="col-auto">
            <label for="search_name" class="form-label">Search by Model Name</label>
            <input type="text" name="search_name" id="search_name"
                   class="form-control"
                   value="<?= htmlspecialchars($_GET['search_name'] ?? '', ENT_QUOTES) ?>">
        </div>
        <div class="col-auto">
            <button type="submit" class="btn btn-primary">Search</button>
            <a href="product_model.php" class="btn btn-secondary">Reset</a>
        </div>
    </form>

    <!-- Table -->
    <div class="table-responsive">
        <table class="table table-bordered table-striped">
            <thead class="table-dark">
                <tr>
                    <th>ID</th>
                    <th>Model Name</th>
                    <th>Supplier</th>
                    <th>Category</th>
                    <th>Price</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($models as $m): ?>
                    <tr>
                        <td><?= htmlspecialchars($m['model_id'] ?? '') ?></td>
                        <td><?= htmlspecialchars($m['model_name'] ?? '') ?></td>
                        <td><?= htmlspecialchars($m['supplier_name'] ?? '') ?></td>
                        <td><?= htmlspecialchars($m['product_category_name'] ?? '') ?></td>
                        <td><?= htmlspecialchars(number_format((float)($m['price'] ?? 0), 2)) ?></td>
                        <td>
                            <?php if ($canEdit): ?>
                                <a href="?edit=<?= urlencode($m['model_id'] ?? '') ?>" class="btn btn-sm btn-warning">Edit</a>
                            <?php endif; ?>
                            <?php if ($canDelete): ?>
                                <a href="?delete=<?= urlencode($m['model_id'] ?? '') ?>" class="btn btn-sm btn-danger"
                                   onclick="return confirm('Are you sure?')">Delete</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($models)): ?>
                    <tr><td colspan="6" class="text-center">No records found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</main>

<?php include 'footer.php'; ?>

<script>
$(document).ready(function() {
    $('.select2').select2({ width: '100%', placeholder: 'Select an option', allowClear: true });

    $('#supplier_id').on('change', function() {
        const supId = $(this).val();
        const $wrapper = $('#category-wrapper');
        const $cat = $('#product_category_id');

        $cat.empty().append('<option>Loading...</option>');
        if (supId) {
            $wrapper.show();
            $.get('product_model.php', { supplier_id: supId, fetch_cat: 1 }, function(data) {
                $cat.empty().append('<option value="">Select Category</option>');
                data.forEach(d => $cat.append(new Option(d.product_category_name, d.product_category_id)));
                $cat.trigger('change.select2');
            }, 'json');
        } else {
            $wrapper.hide();
            $cat.empty().append('<option value="">Select Category</option>').trigger('change.select2');
        }
    });
});

</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
