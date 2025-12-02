<?php
session_start();
require 'db.php';

// Ensure user is logged in
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

// Logged-in user info
$userId          = $_SESSION['user']['user_id'];
$orgCode         = $_SESSION['user']['org_code'];
$brCode          = $_SESSION['user']['br_code'] ?? null;
$userTypeSession = $_SESSION['user']['user_type_id'] ?? 0;
$canInsert       = $_SESSION['user']['can_insert'] ?? 1;
$canEdit         = $_SESSION['user']['can_edit'] ?? 1;
$canDelete       = $_SESSION['user']['can_delete'] ?? 1;

$message = '';

// --- Fetch user types ---
if ($userTypeSession == 1) {
    $userTypes = $pdo->query("SELECT USER_TYPE_ID, USER_TYPE_NAME FROM user_type_info ORDER BY USER_TYPE_NAME")
                     ->fetchAll(PDO::FETCH_ASSOC);
} else {
    $stmt = $pdo->prepare("SELECT USER_TYPE_ID, USER_TYPE_NAME FROM user_type_info WHERE USER_TYPE_ID = :uid");
    $stmt->execute(['uid' => $userTypeSession]);
    $userTypes = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$selectedUserTypeId = $_POST['user_type_id'] ?? '';

// --- Fetch all menus ---
$menus = $pdo->query("SELECT MENU_ID, MENU_NAME FROM menu_info ORDER BY MENU_NAME")->fetchAll(PDO::FETCH_ASSOC);

// --- Ensure a permission row exists for each menu for this user type ---
if ($selectedUserTypeId && $menus) {
    foreach ($menus as $menu) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM user_menu_view_permission WHERE USER_TYPE_ID = :uid AND MENU_ID = :mid");
        $stmt->execute([
            'uid' => $selectedUserTypeId,
            'mid' => $menu['MENU_ID']
        ]);
        $exists = $stmt->fetchColumn();

        if (!$exists) {
            $stmt = $pdo->prepare("INSERT INTO user_menu_view_permission
                (USER_TYPE_ID, MENU_ID, CAN_VIEW, ENTRY_USER, ENTRY_DATE, ORG_CODE, BR_CODE)
                VALUES (:uid, :mid, 0, :eu, CURDATE(), :org, :br)");
            $stmt->execute([
                'uid' => $selectedUserTypeId,
                'mid' => $menu['MENU_ID'],
                'eu'  => $userId,
                'org' => $orgCode,
                'br'  => $brCode
            ]);
        }
    }
}

// --- Handle permission update ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_permission']) && $selectedUserTypeId) {
    $selectedMenus = $_POST['menus'] ?? [];

    foreach ($menus as $menu) {
        $canView = in_array($menu['MENU_ID'], $selectedMenus) ? 1 : 0;

        $stmt = $pdo->prepare("UPDATE user_menu_view_permission
                               SET CAN_VIEW = :cv, EDIT_USER = :eu, EDIT_DATE = CURDATE()
                               WHERE USER_TYPE_ID = :uid AND MENU_ID = :mid");
        $stmt->execute([
            'cv'  => $canView,
            'eu'  => $userId,
            'uid' => $selectedUserTypeId,
            'mid' => $menu['MENU_ID']
        ]);
    }

    $message = "<div class='alert alert-success text-center'>✅ Permissions updated successfully!</div>";
}

// --- Fetch current permissions for checkboxes ---
$currentPermissions = [];
if ($selectedUserTypeId) {
    $stmt = $pdo->prepare("SELECT MENU_ID FROM user_menu_view_permission WHERE USER_TYPE_ID = :uid AND CAN_VIEW = 1");
    $stmt->execute(['uid' => $selectedUserTypeId]);
    $currentPermissions = $stmt->fetchAll(PDO::FETCH_COLUMN);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>User Menu Permission</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="d-flex flex-column min-vh-100">
<?php include 'header.php'; ?>

<main class="container py-4 flex-grow-1">
    <h3 class="mb-4">User Menu Permission Management</h3>
    <?= $message ?>

    <!-- User Type Selection -->
    <form method="post">
        <div class="mb-3">
            <label class="form-label">Select User Type</label>
            <select name="user_type_id" class="form-select" onchange="this.form.submit()" required>
                <option value="">-- Select User Type --</option>
                <?php foreach ($userTypes as $ut): ?>
                    <option value="<?= $ut['USER_TYPE_ID'] ?>" <?= ($selectedUserTypeId == $ut['USER_TYPE_ID']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($ut['USER_TYPE_NAME']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
    </form>

    <!-- Menu Permission Table -->
    <?php if ($selectedUserTypeId && $menus): ?>
    <form method="post">
        <input type="hidden" name="user_type_id" value="<?= htmlspecialchars($selectedUserTypeId) ?>">

        <table class="table table-bordered table-striped">
            <thead class="table-dark text-center">
                <tr>
                    <th>#</th>
                    <th>Menu Name</th>
                    <th>Can View</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($menus as $i => $menu): ?>
                <tr>
                    <td><?= htmlspecialchars($menu['MENU_ID']) ?></td>
                    <td><?= htmlspecialchars($menu['MENU_NAME']) ?></td>
                    <td class="text-center">
                        <input type="checkbox" name="menus[]" value="<?= $menu['MENU_ID'] ?>"
                            <?= in_array($menu['MENU_ID'], $currentPermissions) ? 'checked' : '' ?>>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div class="text-center mt-3">
            <button type="submit" name="save_permission" class="btn btn-primary">💾 Save Permissions</button>
            <a href="user_permission.php" class="btn btn-secondary">🔄 Cancel</a>
        </div>
    </form>
    <?php endif; ?>
</main>

<?php include 'footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
