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
$brCode    = $user['br_code'];
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
            SET DISTRIBUTOR_NAME = :name,
                DISTRIBUTOR_ADDRESS = :address,
                DISTRIBUTOR_CONTACT = :contact,
                AUTHORIZED_STATUS = :auth,
                EDIT_USER = :user,
                EDIT_DATE = NOW()
            WHERE DISTRIBUTOR_CODE = :id
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
        $distributorCode = $brCode . '-' . date('Ymd-His'); // unique code
        $stmt = $pdo->prepare("
            INSERT INTO distributor 
            (DISTRIBUTOR_CODE, DISTRIBUTOR_NAME, DISTRIBUTOR_ADDRESS, DISTRIBUTOR_CONTACT, AUTHORIZED_STATUS, ENTRY_USER, ENTRY_DATE, ORG_CODE, BR_CODE)
            VALUES (:code, :name, :address, :contact, :auth, :user, NOW(), :org, :br)
        ");
        $stmt->execute([
            'code' => $distributorCode,
            'name' => $name,
            'address' => $address,
            'contact' => $contact,
            'auth' => $authorized,
            'user' => $userId,
            'org' => $orgCode,
            'br' => $brCode
        ]);
        $message = "<div class='alert alert-success text-center'>Distributor added successfully!</div>";
    }

    // Clear form fields
    $editDistributor = null;
}

// ----------------------
// Handle Edit
// ----------------------
if (isset($_GET['edit']) && $canEdit) {
    $stmt = $pdo->prepare("SELECT * FROM distributor WHERE DISTRIBUTOR_CODE = :id");
    $stmt->execute(['id' => $_GET['edit']]);
    $editDistributor = $stmt->fetch();
}

// ----------------------
// Handle Delete (Delete)
if (isset($_GET['delete']) && $canDelete) {
    $stmt = $pdo->prepare("DELETE FROM distributor WHERE DISTRIBUTOR_CODE = :id");
    $stmt->execute(['id' => $_GET['delete']]);
    header("Location: distributor.php");
    exit();
}

// ----------------------
// Fetch Distributors
// ----------------------
if ($userType == 1) { // Super admin sees all
    $stmt = $pdo->query("SELECT * FROM distributor ORDER BY DISTRIBUTOR_CODE DESC");
    $distributors = $stmt->fetchAll();
} else {
    $stmt = $pdo->prepare("
        SELECT * FROM distributor 
        WHERE ORG_CODE = :org AND BR_CODE = :br 
        ORDER BY DISTRIBUTOR_CODE DESC
    ");
    $stmt->execute(['org' => $orgCode, 'br' => $brCode]);
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
                <input type="hidden" name="distributor_code" value="<?= htmlspecialchars($editDistributor['DISTRIBUTOR_CODE'] ?? '') ?>">

                <div class="mb-3">
                    <label>Distributor Name</label>
                    <input type="text" name="distributor_name" class="form-control" required
                           value="<?= htmlspecialchars($editDistributor['DISTRIBUTOR_NAME'] ?? '') ?>">
                </div>

                <div class="mb-3">
                    <label>Address</label>
                    <textarea name="distributor_address" class="form-control"><?= htmlspecialchars($editDistributor['DISTRIBUTOR_ADDRESS'] ?? '') ?></textarea>
                </div>

                <div class="mb-3">
                    <label>Contact</label>
                    <input type="text" name="distributor_contact" class="form-control"
                           value="<?= htmlspecialchars($editDistributor['DISTRIBUTOR_CONTACT'] ?? '') ?>">
                </div>

                <div class="mb-3">
                    <label>Authorized Status</label>
                    <select name="authorized_status" class="form-select">
                        <option value="N" <?= (isset($editDistributor['AUTHORIZED_STATUS']) && $editDistributor['AUTHORIZED_STATUS']=='N') ? 'selected' : '' ?>>No</option>
                        <option value="Y" <?= (isset($editDistributor['AUTHORIZED_STATUS']) && $editDistributor['AUTHORIZED_STATUS']=='Y') ? 'selected' : '' ?>>Yes</option>
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
                    <td><?= htmlspecialchars($d['DISTRIBUTOR_CODE']) ?></td>
                    <td><?= htmlspecialchars($d['DISTRIBUTOR_NAME']) ?></td>
                    <td><?= htmlspecialchars($d['DISTRIBUTOR_ADDRESS']) ?></td>
                    <td><?= htmlspecialchars($d['DISTRIBUTOR_CONTACT']) ?></td>
                    <td><?= htmlspecialchars($d['AUTHORIZED_STATUS']) ?></td>

                    <td>
                        <?php if ($canEdit): ?>
                            <a href="?edit=<?= urlencode($d['DISTRIBUTOR_CODE']) ?>" class="btn btn-sm btn-warning">Edit</a>
                        <?php endif; ?>
                        <?php if ($canDelete): ?>
                            <a href="?delete=<?= urlencode($d['DISTRIBUTOR_CODE']) ?>" class="btn btn-sm btn-danger"
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
