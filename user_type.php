<?php
session_start();
require 'db.php';

// Ensure user is logged in
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

// Logged-in user info
$userId      = $_SESSION['user']['user_id'];
$brCode      = $_SESSION['user']['br_code'];
$orgCode     = $_SESSION['user']['org_code'];
$userTypeId  = $_SESSION['user']['user_type_id'];
$canInsert   = $_SESSION['user']['can_insert'] ?? 0;
$canEdit     = $_SESSION['user']['can_edit'] ?? 0;
$canDelete   = $_SESSION['user']['can_delete'] ?? 0;

$editUserType = null;

// ----------------------
// Handle Add / Update
// ----------------------
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $typeId   = $_POST['USER_TYPE_ID'] ?? '';
    $typeName = trim($_POST['USER_TYPE_NAME']);
    $typeCode = trim($_POST['USER_TYPE_CODE']);

    if ($typeId) {
        // Update
        if ($canEdit) {
            $stmt = $pdo->prepare("UPDATE user_type_info 
                                   SET USER_TYPE_NAME = :name, 
                                       USER_TYPE_CODE = :code 
                                   WHERE USER_TYPE_ID = :id");
            $stmt->execute([
                'name' => $typeName,
                'code' => $typeCode,
                'id'   => $typeId
            ]);
        }
    } else {
        // Insert
        if ($canInsert) {
            $stmt = $pdo->prepare("INSERT INTO user_type_info (USER_TYPE_NAME, USER_TYPE_CODE) 
                                   VALUES (:name, :code)");
            $stmt->execute([
                'name' => $typeName,
                'code' => $typeCode
            ]);
        }
    }

    header("Location: user_type.php");
    exit();
}

// ----------------------
// Handle Edit Request
// ----------------------
if (isset($_GET['edit']) && $canEdit) {
    $stmt = $pdo->prepare("SELECT * FROM user_type_info WHERE USER_TYPE_ID = :id");
    $stmt->execute(['id' => $_GET['edit']]);
    $editUserType = $stmt->fetch();
}

// ----------------------
// Handle Delete Request
// ----------------------
if (isset($_GET['delete']) && $canDelete) {
    $stmt = $pdo->prepare("DELETE FROM user_type_info WHERE USER_TYPE_ID = :id");
    $stmt->execute(['id' => $_GET['delete']]);
    header("Location: user_type.php");
    exit();
}

// ----------------------
// Fetch All User Types
// ----------------------
$stmt = $pdo->query("SELECT * FROM user_type_info ORDER BY USER_TYPE_ID");
$userTypes = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>User Type Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="d-flex flex-column min-vh-100">
<?php include 'header.php'; ?>

<main class="container flex-grow-1 py-4">
    <h3>User Type Management</h3>

    <!-- Add / Edit Form -->
    <?php if ($canInsert || $editUserType): ?>
    <div class="card mb-4">
        <div class="card-body">
            <form method="post">
                <input type="hidden" name="USER_TYPE_ID" value="<?= htmlspecialchars($editUserType['USER_TYPE_ID'] ?? '') ?>">
                
                <div class="mb-3">
                    <label>User Type Name</label>
                    <input type="text" name="USER_TYPE_NAME" class="form-control" required 
                           value="<?= htmlspecialchars($editUserType['USER_TYPE_NAME'] ?? '') ?>">
                </div>
                <div class="mb-3">
                    <label>User Type Code</label>
                    <input type="text" name="USER_TYPE_CODE" class="form-control" required 
                           value="<?= htmlspecialchars($editUserType['USER_TYPE_CODE'] ?? '') ?>">
                </div>

                <button type="submit" class="btn btn-primary">
                    <?= $editUserType ? 'Update' : 'Add' ?>
                </button>
                <?php if ($editUserType): ?>
                    <a href="user_type.php" class="btn btn-secondary">Cancel</a>
                <?php endif; ?>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <!-- User Type Table -->
    <div class="table-responsive">
        <table class="table table-bordered table-striped align-middle">
            <thead class="table-dark">
                <tr>
                    <th>ID</th>
                    <th>User Type Name</th>
                    <th>User Type Code</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($userTypes as $ut): ?>
                    <tr>
                        <td><?= htmlspecialchars($ut['USER_TYPE_ID']) ?></td>
                        <td><?= htmlspecialchars($ut['USER_TYPE_NAME']) ?></td>
                        <td><?= htmlspecialchars($ut['USER_TYPE_CODE']) ?></td>
                        <td>
                            <?php if ($canEdit): ?>
                                <a href="?edit=<?= urlencode($ut['USER_TYPE_ID']) ?>" class="btn btn-sm btn-warning">Edit</a>
                            <?php endif; ?>
                            <?php if ($canDelete): ?>
                                <a href="?delete=<?= urlencode($ut['USER_TYPE_ID']) ?>" 
                                   onclick="return confirm('Are you sure you want to delete this user type?');" 
                                   class="btn btn-sm btn-danger">Delete</a>
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
