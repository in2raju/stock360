<?php
declare(strict_types=1);
session_start();

require_once 'db.php';  // Make sure this file sets up $pdo (PDO instance) securely

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate & sanitize input
    $userId = isset($_POST['user_id']) ? trim($_POST['user_id']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';

    if ($userId === '' || $password === '') {
        $error = 'Please provide both User ID and Password.';
    } else {
        // Fetch user info and branch
        $sql = "
           SELECT 
        u.USER_ID, 
        u.USER_PASSWORD, 
        u.BR_CODE, 
        u.ORG_CODE,
        u.USER_TYPE_ID, 
        b.BRANCH_NAME
    FROM user_login_info u
    LEFT JOIN branch_info b ON u.BR_CODE = b.BR_CODE
    WHERE u.USER_ID = :user_id
      AND u.AUTHORIZED_STATUS = 'Y'
    LIMIT 1
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['user_id' => $userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['USER_PASSWORD'])) {
            // Derive org code from branch
            $orgCode = $user['ORG_CODE'];
            // Fetch organization name
            $stmtOrg = $pdo->prepare("
                SELECT ORGANIZATION_NAME
                FROM organization_info
                WHERE ORG_CODE = :org_code
                LIMIT 1
            ");
            $stmtOrg->execute(['org_code' => $orgCode]);
            $org = $stmtOrg->fetch(PDO::FETCH_ASSOC);
            $orgName = $org['ORGANIZATION_NAME'] ?? '';

            // Fetch user permissions
            $stmtPerm = $pdo->prepare("
                SELECT can_insert, can_edit, can_delete,can_approve
                FROM user_action_permission
                WHERE user_type_id = :type_id
                LIMIT 1
            ");
            $stmtPerm->execute(['type_id' => $user['USER_TYPE_ID']]);
            $perm = $stmtPerm->fetch(PDO::FETCH_ASSOC);

            $canInsert = $canEdit = $canDelete = 0;
            if ($perm) {
                $canInsert  = (int)$perm['can_insert'];
                $canEdit    = (int)$perm['can_edit'];
                $canDelete  = (int)$perm['can_delete'];
                $canApprove = (int)$perm['can_approve'];
            }

            // Store session securely
            $_SESSION['user'] = [
                'user_id'      => $user['USER_ID'],
                'user_type_id' => $user['USER_TYPE_ID'],
                'br_code'      => $user['BR_CODE'],
                'branch_name'  => $user['BRANCH_NAME'],
                'org_code'     => $orgCode,
                'org_name'     => $orgName,
                'can_insert'   => $canInsert,
                'can_edit'     => $canEdit,
                'can_delete'   => $canDelete,
                'can_approve'  => $canApprove,
            ];

            // Regenerate session ID for security
            session_regenerate_id(true);

            header('Location: home.php');
            exit;
        } else {
            $error = 'Invalid User ID or Password.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login - Stock3600</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- Use a local copy or integrity check in production -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" 
          rel="stylesheet" 
          integrity="sha384-…your-integrity-hash…" 
          crossorigin="anonymous">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .login-card {
            max-width: 380px;
            margin: auto;
            margin-top: 8%;
        }
    </style>
</head>
<body>
    <div class="login-card card shadow-sm">
        <div class="card-body p-4">
            <h4 class="card-title text-center mb-4">Stock360 Login</h4>

            <?php if ($error): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error, ENT_QUOTES) ?></div>
            <?php endif; ?>

            <form method="post" novalidate>
                <div class="mb-3">
                    <label for="user_id" class="form-label">User ID</label>
                    <input type="text" name="user_id" id="user_id"
                        class="form-control" required maxlength="50"
                        value="<?= isset($_POST['user_id']) ? htmlspecialchars($_POST['user_id'], ENT_QUOTES) : '' ?>">
                </div>
                <div class="mb-3">
                    <label for="password" class="form-label">Password</label>
                    <input type="password" name="password" id="password"
                        class="form-control" required>
                </div>
                <button type="submit" class="btn btn-primary w-100">Login</button>
            </form>
        </div>
    </div>
</body>
</html>
