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
$userType    = $userSession['user_type_id'];
$userOrg     = $userSession['org_code'];
$userBr      = $userSession['br_code'];

$canInsert   = $userSession['can_insert'] ?? 1;
$canEdit     = $userSession['can_edit'] ?? 1;
$canDelete   = $userSession['can_delete'] ?? 1;

$message     = '';
$editData    = null;

// -----------------------------
// AJAX: Load branches by organization
// -----------------------------
if (isset($_GET['ajax']) && $_GET['ajax'] == 1) {
    $org = $_GET['org'] ?? '';
    header('Content-Type: application/json; charset=utf-8');
    if (!$org) { echo json_encode([]); exit; }
    $stmt = $pdo->prepare("SELECT BR_CODE, BRANCH_NAME FROM branch_info WHERE ORG_CODE = :org ORDER BY BRANCH_NAME");
    $stmt->execute(['org' => $org]);
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    exit;
}

// -----------------------------
// Handle Delete
// -----------------------------
if (isset($_GET['delete']) && $canDelete) {
    $deleteUser = $_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM user_login_info WHERE USER_ID = :uid");
    $stmt->execute(['uid' => $deleteUser]);
    $message = "<div class='alert alert-success text-center'>User deleted successfully!</div>";
}

// -----------------------------
// Handle Edit Load
// -----------------------------
if (isset($_GET['edit']) && $canEdit) {
    $editUserId = $_GET['edit'];
    $stmt = $pdo->prepare("SELECT * FROM user_login_info WHERE USER_ID = :uid");
    $stmt->execute(['uid' => $editUserId]);
    $editData = $stmt->fetch(PDO::FETCH_ASSOC);
}

// -----------------------------
// Handle Update
// -----------------------------
if (isset($_POST['update_user']) && $canEdit) {
    $uid = $_POST['edit_user_id'];
    $uname = trim($_POST['user_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $userTypePost = $_POST['user_type_id'];
    $auth = $_POST['authorized_status'];
    $org = $_POST['org_code'];
    $br = $_POST['br_code'];

    $stmt = $pdo->prepare("UPDATE user_login_info 
                           SET USER_NAME=:uname, EMAIL=:email, PHONE=:phone,
                               USER_TYPE_ID=:utype, AUTHORIZED_STATUS=:auth, ORG_CODE=:org, BR_CODE=:br
                           WHERE USER_ID=:uid");
    $stmt->execute([
        'uname' => $uname,
        'email' => $email,
        'phone' => $phone,
        'utype' => $userTypePost,
        'auth'  => $auth,
        'org'   => $org,
        'br'    => $br,
        'uid'   => $uid
    ]);
    $message = "<div class='alert alert-info text-center'>User updated successfully!</div>";
    $editData = null;
}

// -----------------------------
// Handle Insert
// -----------------------------
if (isset($_POST['save_user']) && $canInsert) {
    $loginName = trim($_POST['login_name']);
    $password  = trim($_POST['password']);
    $userName  = trim($_POST['user_name']);
    $email     = trim($_POST['email']);
    $phone     = trim($_POST['phone']);
    $org       = $_POST['org_code'];
    $br        = $_POST['br_code'];
    $userTypePost  = $_POST['user_type_id'];
    $authStat  = $_POST['authorized_status'];

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM user_login_info WHERE USER_ID = :login");
    $stmt->execute(['login' => $loginName]);

    if ($stmt->fetchColumn() > 0) {
        $message = "<div class='alert alert-danger text-center'>Login name already exists!</div>";
    } else {
        $stmt = $pdo->prepare("INSERT INTO user_login_info 
            (USER_ID, USER_PASSWORD, USER_NAME, EMAIL, PHONE, ENTRY_DATE, AUTHORIZED_STATUS, USER_TYPE_ID, ORG_CODE, BR_CODE)
            VALUES (:login, :pass, :uname, :email, :phone, CURDATE(), :auth, :utype, :org, :br)");
        $stmt->execute([
            'login' => $loginName,
            'pass'  => password_hash($password, PASSWORD_DEFAULT),
            'uname' => $userName,
            'email' => $email,
            'phone' => $phone,
            'auth'  => $authStat,
            'utype' => $userTypePost,
            'org'   => $org,
            'br'    => $br
        ]);
        $message = "<div class='alert alert-success text-center'>User added successfully!</div>";
    }
}

// -----------------------------
// Fetch dropdown data
// -----------------------------
if ($userType == 1) {
    // Super Admin
    $orgs = $pdo->query("SELECT ORG_CODE, ORGANIZATION_NAME FROM organization_info ORDER BY ORGANIZATION_NAME")->fetchAll();
} else {
    $stmt = $pdo->prepare("SELECT ORG_CODE, ORGANIZATION_NAME FROM organization_info WHERE ORG_CODE = :org");
    $stmt->execute(['org' => $userOrg]);
    $orgs = $stmt->fetchAll();
}

$userTypes = $pdo->query("SELECT USER_TYPE_ID, USER_TYPE_NAME FROM user_type_info ORDER BY USER_TYPE_NAME")->fetchAll();

// -----------------------------
// Fetch users based on type
// -----------------------------
$sql = "
    SELECT u.*, ut.USER_TYPE_NAME, o.ORGANIZATION_NAME, b.BRANCH_NAME
    FROM user_login_info u
    LEFT JOIN user_type_info ut ON u.USER_TYPE_ID = ut.USER_TYPE_ID
    LEFT JOIN organization_info o ON u.ORG_CODE = o.ORG_CODE
    LEFT JOIN branch_info b ON u.BR_CODE = b.BR_CODE
    WHERE 1=1
";

$params = [];

if ($userType == 2) {
    $sql .= " AND u.ORG_CODE = :org";
    $params['org'] = $userOrg;
} elseif ($userType == 3) {
    $sql .= " AND u.BR_CODE = :br";
    $params['br'] = $userBr;
} elseif ($userType == 4) {
    $sql .= " AND u.USER_ID = :uid";
    $params['uid'] = $userId;
}

$sql .= " ORDER BY u.USER_NAME";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>User Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<?php include 'header.php'; ?>

<main class="container py-4">
    <h3>User Management</h3>
    <?= $message ?>

    <!-- User Form -->
    <?php if ($canInsert || $editData): ?>
    <div class="card mb-4 shadow-sm">
        <div class="card-body">
            <form method="post">
                <?php if ($editData): ?>
                    <input type="hidden" name="edit_user_id" value="<?= htmlspecialchars($editData['USER_ID']) ?>">
                <?php endif; ?>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label>Login Name</label>
                        <input type="text" name="login_name" class="form-control" 
                               value="<?= htmlspecialchars($editData['USER_ID'] ?? '') ?>" 
                               <?= $editData ? 'readonly' : 'required' ?>>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label>Password <?= $editData ? '(unchanged)' : '' ?></label>
                        <input type="password" name="password" class="form-control" <?= $editData ? '' : 'required' ?>>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label>User Name</label>
                        <input type="text" name="user_name" class="form-control" required
                               value="<?= htmlspecialchars($editData['USER_NAME'] ?? '') ?>">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label>Email</label>
                        <input type="email" name="email" class="form-control"
                               value="<?= htmlspecialchars($editData['EMAIL'] ?? '') ?>">
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label>Phone</label>
                        <input type="text" name="phone" class="form-control"
                               value="<?= htmlspecialchars($editData['PHONE'] ?? '') ?>">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label>Organization</label>
                        <select name="org_code" id="orgSelect" class="form-select" <?= $userType != 1 ? 'readonly' : 'required' ?>>
                            <option value="">-- Select Organization --</option>
                            <?php foreach ($orgs as $org): ?>
                                <option value="<?= $org['ORG_CODE'] ?>" 
                                    <?= ($editData && $editData['ORG_CODE'] == $org['ORG_CODE']) || (!$editData && $userOrg == $org['ORG_CODE']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($org['ORGANIZATION_NAME']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label>Branch</label>
                        <select name="br_code" id="brSelect" class="form-select" required>
                            <option value="">-- Select Branch --</option>
                            <?php
                            // Load branches for editData or login user's org
                            $branchOrg = $editData['ORG_CODE'] ?? $userOrg;
                            $stmt = $pdo->prepare("SELECT BR_CODE, BRANCH_NAME FROM branch_info WHERE ORG_CODE = :org ORDER BY BRANCH_NAME");
                            $stmt->execute(['org' => $branchOrg]);
                            $branches = $stmt->fetchAll(PDO::FETCH_ASSOC);
                            foreach ($branches as $b) {
                                $selected = ($editData && $editData['BR_CODE'] == $b['BR_CODE']) || (!$editData && $userBr == $b['BR_CODE']) ? 'selected' : '';
                                echo "<option value='{$b['BR_CODE']}' $selected>{$b['BRANCH_NAME']}</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label>User Type</label>
                        <select name="user_type_id" class="form-select" required>
                            <option value="">-- Select User Type --</option>
                            
                          <?php foreach ($userTypes as $ut): ?>
    <?php if ($ut['USER_TYPE_ID'] > $userType): // only lower types ?>
        <option value="<?= $ut['USER_TYPE_ID'] ?>" 
            <?= ($editData && $editData['USER_TYPE_ID'] == $ut['USER_TYPE_ID']) ? 'selected' : '' ?>>
            <?= htmlspecialchars($ut['USER_TYPE_NAME']) ?>
        </option>
    <?php endif; ?>
<?php endforeach; ?>

                            
                        </select>
                    </div>
                </div>
                
                
               

                
                

                <div class="col-md-6 mb-3">
                    <label>Authorization</label>
                    <select name="authorized_status" class="form-select" required>
                        <option value="Y" <?= ($editData && $editData['AUTHORIZED_STATUS']=='Y')?'selected':'' ?>>Y</option>
                        <option value="N" <?= ($editData && $editData['AUTHORIZED_STATUS']=='N')?'selected':'' ?>>N</option>
                    </select>
                </div>

                <?php if ($editData): ?>
                    <button type="submit" name="update_user" class="btn btn-warning">✏️ Update User</button>
                    <a href="create_user.php" class="btn btn-secondary">Cancel</a>
                <?php else: ?>
                    <button type="submit" name="save_user" class="btn btn-primary">💾 Save User</button>
                <?php endif; ?>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <!-- User Table -->
    <div class="table-responsive">
        <table class="table table-bordered table-striped align-middle">
            <thead class="table-dark">
                <tr>
                    <th>Login Name</th>
                    <th>User Name</th>
                    <th>User Type</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Auth</th>
                    <th>Organization</th>
                    <th>Branch</th>
                    <th>Entry Date</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $u): ?>
                    <tr>
                        <td><?= htmlspecialchars($u['USER_ID']) ?></td>
                        <td><?= htmlspecialchars($u['USER_NAME']) ?></td>
                        <td><?= htmlspecialchars($u['USER_TYPE_NAME']) ?></td>
                        <td><?= htmlspecialchars($u['EMAIL']) ?></td>
                        <td><?= htmlspecialchars($u['PHONE']) ?></td>
                        <td><?= htmlspecialchars($u['AUTHORIZED_STATUS']) ?></td>
                        <td><?= htmlspecialchars($u['ORGANIZATION_NAME']) ?></td>
                        <td><?= htmlspecialchars($u['BRANCH_NAME']) ?></td>
                        <td><?= htmlspecialchars($u['ENTRY_DATE']) ?></td>
                        <td>
                            <?php if ($canEdit): ?>
                                <a href="?edit=<?= urlencode($u['USER_ID']) ?>" class="btn btn-sm btn-warning">Edit</a>
                            <?php endif; ?>
                            <?php if ($canDelete): ?>
                                <a href="?delete=<?= urlencode($u['USER_ID']) ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this user?')">Delete</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</main>

<script>
<?php if($userType == 1): ?>
// Dynamic branch loading when organization is selected
document.getElementById('orgSelect').addEventListener('change', function() {
    const org = this.value;
    const brSelect = document.getElementById('brSelect');
    brSelect.innerHTML = '<option value="">Loading...</option>';

    fetch('?ajax=1&org=' + org)
        .then(res => res.json())
        .then(data => {
            brSelect.innerHTML = '<option value="">-- Select Branch --</option>';
            data.forEach(b => {
                const option = document.createElement('option');
                option.value = b.BR_CODE;
                option.textContent = b.BRANCH_NAME;
                brSelect.appendChild(option);
            });
        });
});
<?php else: ?>
// Non-admin: branch is fixed
document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('brSelect').value = '<?= $userBr ?>';
});
<?php endif; ?>
</script>

<?php include 'footer.php'; ?>
</body>
</html>
