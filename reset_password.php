<?php
session_start();
require 'db.php';

// -----------------------------
// Check login
// -----------------------------
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

// -----------------------------
// Session user info
// -----------------------------
$userSession = $_SESSION['user'];
$userId      = $userSession['user_id'];
$userType    = $userSession['user_type_id']; // 1=Super Admin, 2=Admin, etc
$userOrg     = $userSession['org_code'];
$userBr      = $userSession['br_code'];

$canReset    = ($userType <= 2); // only Super Admin or Admin can reset passwords
$message     = '';

// -----------------------------
// Handle Reset Password
// -----------------------------
if (isset($_POST['reset_password_btn']) && $canReset) {
    $resetUser = $_POST['reset_user_id'] ?? null;
    $newPassword = trim($_POST['new_password'] ?? '');

    if ($resetUser && $newPassword) {
        $hash = password_hash($newPassword, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE user_login_info SET USER_PASSWORD=:hash WHERE USER_ID=:uid");
        $stmt->execute(['hash' => $hash, 'uid' => $resetUser]);

        $stmt = $pdo->prepare("SELECT USER_NAME FROM user_login_info WHERE USER_ID=:uid");
        $stmt->execute(['uid' => $resetUser]);
        $userRow = $stmt->fetch(PDO::FETCH_ASSOC);

        $message = "<div class='alert alert-success text-center'>
                        Password for <strong>{$userRow['USER_NAME']}</strong> has been reset successfully.
                    </div>";
    } else {
        $message = "<div class='alert alert-danger text-center'>Please select a user and enter a new password.</div>";
    }
}

// -----------------------------
// Fetch users for dropdown
// -----------------------------
$sql = "SELECT u.USER_ID, u.USER_NAME, u.USER_ID AS LOGIN_ID, u.PHONE,
               o.ORGANIZATION_NAME, b.BRANCH_NAME, u.USER_TYPE_ID
        FROM user_login_info u
        LEFT JOIN organization_info o ON u.ORG_CODE = o.ORG_CODE
        LEFT JOIN branch_info b ON u.BR_CODE = b.BR_CODE
        WHERE 1=1";

$params = [];
if ($userType == 2) { // Admin: only users of same organization
    $sql .= " AND u.ORG_CODE = :org";
    $params['org'] = $userOrg;
} elseif ($userType > 2) { // lower users cannot reset anyone
    $sql .= " AND u.USER_ID = :uid";
    $params['uid'] = $userId;
}

$sql .= " ORDER BY o.ORGANIZATION_NAME, b.BRANCH_NAME, u.USER_NAME";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Reset User Password</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<?php include 'header.php'; ?>

<main class="container py-4">
    <h3 class="mb-4">Reset User Password</h3>

    <?= $message ?>

    <?php if($canReset): ?>
    <div class="card shadow-sm">
        <div class="card-body">
            <form method="post">
                <div class="row mb-3">
                    <div class="col-md-8">
                        <label for="reset_user_id" class="form-label">Select User</label>
                        <select name="reset_user_id" id="reset_user_id" class="form-select" required>
                            <option value="">-- Select User --</option>
                            <?php foreach($users as $u): ?>
                                <?php if($u['USER_TYPE_ID'] > $userType): ?>
                                    <option value="<?= $u['USER_ID'] ?>">
                                        <?= htmlspecialchars($u['ORGANIZATION_NAME'] . " → " . $u['BRANCH_NAME'] . " → " . $u['USER_NAME'] . " → " . $u['LOGIN_ID'] . " → " . $u['PHONE']) ?>
                                    </option>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label for="new_password" class="form-label">New Password</label>
                        <input type="text" class="form-control" id="new_password" name="new_password" placeholder="Enter new password" required>
                    </div>
                </div>

                <button type="submit" name="reset_password_btn" class="btn btn-danger">
                    Reset Password
                </button>
            </form>
        </div>
    </div>
    <?php else: ?>
        <div class="alert alert-warning text-center">You do not have permission to reset passwords.</div>
    <?php endif; ?>
</main>

<?php include 'footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
