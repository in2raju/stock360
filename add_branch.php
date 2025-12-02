<?php
session_start();
require 'db.php';

// Ensure user is logged in
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

// Logged-in user info & permissions
$user      = $_SESSION['user'];
$userId    = $user['user_id'];
$userType  = $user['user_type_id'];
$orgCode   = $user['org_code'];
$canInsert = $user['can_insert'] ?? 1;
$canEdit   = $user['can_edit'] ?? 1;
$canDelete = $user['can_delete'] ?? 1;

$editDistributor = null;
$message = "";

// ----------------------
// Handle Add / Update
// ----------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $distributorId = $_POST['distributor_code'] ?? '';
    $name          = trim($_POST['distributor_name'] ?? '');
    $address       = trim($_POST['distributor_address'] ?? '');
    $contact       = trim($_POST['distributor_contact'] ?? '');
    $authorized    = $_POST['authorized_status'] ?? 'N';

    if ($distributorId && $canEdit) {
        // Update distributor
        $stmt = $pdo->prepare("
            UPDATE distributor
            SET distributor_name = :name,
                distributor_address = :address,
                distributor_contact = :contact,
                authorized_status = :auth,
                edit_user = :user,
                edit_date = CURDATE()
            WHERE distributor_code = :id
        ");
        $stmt->execute([
            'name' => $name,
            'address' => $address,
            'contact' => $contact,
            'auth' => $authorized,
            'user' => $userId,
            'id' => $distributorId
        ]);
        $message = "<div class='alert alert-success text-center'>Distributor updated successfully!</div>";
    } elseif ($canInsert) {
        // Insert distributor
        $distributorCode = $orgCode . '-' . date('Ymd-His'); // unique code
        $stmt = $pdo->prepare("
            INSERT INTO distributor 
            (distributor_code, distributor_name, distributor_address, distributor_contact, authorized_status, entry_user, entry_date, org_code)
            VALUES (:code, :name, :address, :contact, :auth, :user, CURDATE(), :org)
        ");
        $stmt->execute([
            'code' => $distributorCode,
            'name' => $name,
            'address' => $address,
            'contact' => $contact,
            'auth' => $authorized,
            'user' => $userId,
            'org' => $orgCode
        ]);
        $message = "<div class='alert alert-success text-center'>Distributor added successfully!</div>";
    }
}

// ----------------------
// Handle Edit
// ----------------------
if (isset($_GET['edit']) && $canEdit) {
    $stmt = $pdo->prepare("SELECT * FROM distributor WHERE distributor_code = :id");
    $stmt->execute(['id' => $_GET['edit']]);
    $editDistributor = $stmt->fetch();
}

// ----------------------
// Handle Delete
// ----------------------
if (isset($_GET['delete']) && $canDelete) {
    $stmt = $pdo->prepare("DELETE FROM distributor WHERE distributor_code = :id");
    $stmt->execute(['id' => $_GET['delete']]);
    header("Location: distributor.php");
    exit();
}

// ----------------------
// Fetch Distributors
// ----------------------
if ($userType == 1) { // super admin sees all
    $stmt = $pdo->query("SELECT * FROM distributor ORDER BY distributor_code DESC");
    $distributors = $stmt->fetchAll();
} else {
    $stmt = $pdo->prepare("SELECT * FROM distributor WHERE org_code = :org_code ORDER BY distributor_code DESC");
    $stmt->execute(['org_code' => $orgCode]);
    $distributors = $stmt->fetchAll();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Distributor Management</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="d-flex flex-column min-vh-100">
<?php include 'header.php'; ?>

<main class="container py-4 flex-grow-1">
    <h3>Distributor Management</h3>
    <?= $message ?>

    <!-- Distributor Form -->
    <?php if ($canInsert || $editDistributor): ?>
    <div class="card mb-4 shadow-sm">
        <div class="card-body">
            <form method="post">
                <input type="hidden" name="distributor_code" value="<?= htmlspecialchars($editDistributor['distributor_code'] ?? '') ?>">

                <div class="mb-3">
                    <label>Distributor Name</label>
                    <input type="text" name="distributor_name" class="form-control" required
                           value="<?= htmlspecialchars($editDistributor['distributor_name'] ?? '') ?>">
                </div>

                <div class="mb-3">
                    <label>Address</label>
                    <textarea name="distributor_address" class="form-control"><?= htmlspecialchars($editDistributor['distributor_address'] ?? '') ?></textarea>
                </div>

                <div class="mb-3">
                    <label>Contact</label>
                    <input type="text" name="distributor_contact" class="form-control"
                           value="<?= htmlspecialchars($editDistributor['distributor_contact'] ?? '') ?>">
                </div>

                <div class="mb-3">
                    <label>Authorized Status</label>
                    <select name="authorized_status" class="form-select">
                        <option value="N" <?= (isset($editDistributor['authorized_status']) && $editDistributor['authorized_status']=='N') ? 'selected' : '' ?>>No</option>
                        <option value="Y" <?= (isset($editDistributor['authorized_status']) && $editDistributor['authorized_status']=='Y') ? 'selected' : '' ?>>Yes</option>
                    </select>
                </div>

                <button type="submit" class="btn btn-primary"><?= $editDistributor ? 'Update' : 'Add' ?></button>
                <?php if ($editDistributor): ?>
                    <a href="distributor.php" class="btn btn-secondary">Cancel</a>
                <?php endif; ?>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <!-- Distributor Table -->
    <div class="table-responsive">
        <table class="table table-bordered table-striped align-middle">
            <thead class="table-dark">
                <tr>
                    <th>Distributor Code</th>
                    <th>Name</th>
                    <th>Address</th>
                    <th>Contact</th>
                    <th>Authorized</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($distributors as $d): ?>
                <tr>
                    <td><?= htmlspecialchars($d['distributor_code']) ?></td>
                    <td><?= htmlspecialchars($d['distributor_name']) ?></td>
                    <td><?= htmlspecialchars($d['distributor_address']) ?></td>
                    <td><?= htmlspecialchars($d['distributor_contact']) ?></td>
                    <td><?= htmlspecialchars($d['authorized_status']) ?></td>
                    <td>
                        <?php if ($canEdit): ?>
                            <a href="?edit=<?= urlencode($d['distributor_code']) ?>" class="btn btn-sm btn-warning">Edit</a>
                        <?php endif; ?>
                        <?php if ($canDelete): ?>
                            <a href="?delete=<?= urlencode($d['distributor_code']) ?>" class="btn btn-sm btn-danger"
                               onclick="return confirm('Are you sure?')">Delete</a>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($distributors)): ?>
                    <tr><td colspan="6" class="text-center">No records found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</main>

<?php include 'footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
