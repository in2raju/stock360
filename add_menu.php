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
$userTypeId  = $_SESSION['user']['user_type_id'] ?? 2; // 1 = Super Admin
$canInsert   = $_SESSION['user']['can_insert'] ?? 1;
$canEdit     = $_SESSION['user']['can_edit'] ?? 1;
$canDelete   = $_SESSION['user']['can_delete'] ?? 1;

$message = '';
$editMenu = null;

// ----------------------
// Handle Add / Update
// ----------------------
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $menuId    = $_POST['menu_id'] ?? '';
    $menuName  = trim($_POST['menu_name']);
    $menuLink  = trim($_POST['menu_link']);
    $parentId  = $_POST['parent_id'] ?: null;

    try {
        if ($menuId) {
            // UPDATE
            if ($canEdit) {
                // Check duplicate
                $check = $pdo->prepare("SELECT COUNT(*) FROM menu_info WHERE MENU_NAME = :name AND MENU_ID != :id");
                $check->execute(['name' => $menuName, 'id' => $menuId]);
                if ($check->fetchColumn() > 0) {
                    $message = "<div class='alert alert-danger text-center'>Error: MENU_NAME already exists.</div>";
                } else {
                    $stmt = $pdo->prepare("UPDATE menu_info 
                                           SET MENU_NAME = :name, MENU_LINK = :link, PARENT_ID = :parent 
                                           WHERE MENU_ID = :id");
                    $stmt->execute([
                        'name'   => $menuName,
                        'link'   => $menuLink ?: null,
                        'parent' => $parentId,
                        'id'     => $menuId
                    ]);
                    $message = "<div class='alert alert-success text-center'>Menu updated successfully!</div>";
                }
            }
        } else {
            // INSERT
            if ($canInsert) {
                $check = $pdo->prepare("SELECT COUNT(*) FROM menu_info WHERE MENU_NAME = :name");
                $check->execute(['name' => $menuName]);
                if ($check->fetchColumn() > 0) {
                    $message = "<div class='alert alert-danger text-center'>Error: MENU_NAME already exists.</div>";
                } else {
                    $stmt = $pdo->prepare("INSERT INTO menu_info (MENU_NAME, MENU_LINK, PARENT_ID) VALUES (:name, :link, :parent)");
                    $stmt->execute([
                        'name'   => $menuName,
                        'link'   => $menuLink ?: null,
                        'parent' => $parentId
                    ]);
                    $message = "<div class='alert alert-success text-center'>Menu added successfully!</div>";
                }
            }
        }
    } catch (PDOException $e) {
        $message = "<div class='alert alert-danger text-center'>Database error: " . $e->getMessage() . "</div>";
    }
}

// ----------------------
// Handle Edit Request
// ----------------------
if (isset($_GET['edit']) && $canEdit) {
    $stmt = $pdo->prepare("SELECT * FROM menu_info WHERE MENU_ID = :id");
    $stmt->execute(['id' => $_GET['edit']]);
    $editMenu = $stmt->fetch();
}

// ----------------------
// Handle Delete Request
// ----------------------
if (isset($_GET['delete']) && $canDelete) {
    $stmt = $pdo->prepare("DELETE FROM menu_info WHERE MENU_ID = :id");
    $stmt->execute(['id' => $_GET['delete']]);
    header("Location: menu.php");
    exit();
}

// ----------------------
// Fetch All Menus (Super Admin sees all)
// ----------------------
$stmt = $pdo->query("SELECT * FROM menu_info ORDER BY PARENT_ID, MENU_ID");
$menus = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Build parent menu mapping for display
$parentMap = [];
foreach ($menus as $m) {
    $parentMap[$m['MENU_ID']] = $m['MENU_NAME'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Super Admin Menu Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="d-flex flex-column min-vh-100">

<?php include 'header.php'; ?>

<main class="container py-4 flex-grow-1">
    <h3>Super Admin Menu Management</h3>
    <?= $message ?>

    <!-- Menu Form -->
    <?php if ($canInsert || $editMenu): ?>
    <div class="card mb-4 shadow-sm">
        <div class="card-body">
            <form method="post">
                <input type="hidden" name="menu_id" value="<?= htmlspecialchars($editMenu['MENU_ID'] ?? '') ?>">

                <div class="mb-3">
                    <label>Menu Name <span class="text-danger">*</span></label>
                    <input type="text" name="menu_name" class="form-control" required
                           value="<?= htmlspecialchars($editMenu['MENU_NAME'] ?? '') ?>">
                </div>

                <div class="mb-3">
                    <label>Menu Link</label>
                    <input type="text" name="menu_link" class="form-control"
                           value="<?= htmlspecialchars($editMenu['MENU_LINK'] ?? '') ?>"
                           placeholder="e.g., dashboard.php or #">
                </div>

                <div class="mb-3">
                    <label>Parent Menu</label>
                    <select name="parent_id" class="form-select">
                        <option value="">-- None (Top Menu) --</option>
                        <?php foreach ($menus as $m): ?>
                            <option value="<?= $m['MENU_ID'] ?>"
                                <?= isset($editMenu['PARENT_ID']) && $editMenu['PARENT_ID'] == $m['MENU_ID'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($m['MENU_NAME']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <button type="submit" class="btn btn-primary"><?= $editMenu ? 'Update' : 'Add Menu' ?></button>
                <?php if ($editMenu): ?>
                    <a href="menu.php" class="btn btn-secondary">Cancel</a>
                <?php endif; ?>
            </form>
        </div> 
    </div>
    <?php endif; ?>

    <!-- Menu Table -->
    <div class="table-responsive">
        <table class="table table-bordered table-striped align-middle">
            <thead class="table-dark">
                <tr>
                    <th>ID</th>
                    <th>Menu Name</th>
                    <th>Menu Link</th>
                    <th>Parent Menu</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($menus as $m): ?>
                    <tr>
                        <td><?= htmlspecialchars($m['MENU_ID']) ?></td>
                        <td><?= htmlspecialchars($m['MENU_NAME']) ?></td>
                        <td><?= htmlspecialchars($m['MENU_LINK']) ?></td>
                        <td>
                            <?= htmlspecialchars($m['PARENT_ID'] ? $parentMap[$m['PARENT_ID']] : '-') ?>
                        </td>
                        <td>
                            <?php if ($canEdit): ?>
                                <a href="?edit=<?= $m['MENU_ID'] ?>" class="btn btn-sm btn-warning">Edit</a>
                            <?php endif; ?>
                            <?php if ($canDelete): ?>
                                <a href="?delete=<?= $m['MENU_ID'] ?>" class="btn btn-sm btn-danger"
                                   onclick="return confirm('Are you sure to delete this menu?')">Delete</a>
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
