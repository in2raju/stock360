<?php
session_start();
require 'db.php';

// Ensure user is logged in
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

// User info & permissions
$user      = $_SESSION['user'];
$userId    = $user['user_id'];
$userType  = $user['user_type_id'];
$orgCode   = $user['org_code'];
$brCode    = $user['br_code'];
$canInsert = $user['can_insert'] ?? 1;
$canEdit   = $user['can_edit'] ?? 1;
$canDelete = $user['can_delete'] ?? 1;

$editCustomer = null;
$message = "";
$formData = []; // Stores previous input

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $customerId = $_POST['customer_id'] ?? '';
    $formData = [
        'customer_name'      => trim($_POST['customer_name'] ?? ''),
        'customer_phone'     => trim($_POST['customer_phone'] ?? ''),
        'email'              => trim($_POST['email'] ?? ''),
        'address'            => trim($_POST['address'] ?? ''),
        'reg_date'           => $_POST['reg_date'] ?? date('Y-m-d'),
        'next_payment_date'  => $_POST['next_payment_date'] ?? '',
        'gurantor_name'      => trim($_POST['gurantor_name'] ?? ''),
        'gurantor_phone'     => trim($_POST['gurantor_phone'] ?? ''),
        'nid'                => trim($_POST['nid'] ?? ''),
    ];

    // Phone validation
    $phone = $formData['customer_phone'];
    $bdPhoneRegex = '/^(?:\+8801|01)[3-9]\d{8}$/';

    if (!preg_match($bdPhoneRegex, $phone)) {
        $message = "<div class='alert alert-danger text-center'>
                        Invalid phone number format.<br>Use 01XXXXXXXXX or +8801XXXXXXXXX
                    </div>";
    } else {
        if (str_starts_with($phone, '+880')) $phone = '0' . substr($phone, 4);
        $formData['customer_phone'] = $phone;

        // Check unique in branch
        $stmtCheck = $pdo->prepare("SELECT customer_id FROM customer_info WHERE customer_phone = ? AND br_code = ? AND delete_status != 'Y'");
        $stmtCheck->execute([$phone, $brCode]);
        $existing = $stmtCheck->fetch();

        if ($existing && (!$customerId || $existing['customer_id'] != $customerId)) {
            $message = "<div class='alert alert-danger text-center'>Phone number already exists in this branch!</div>";
        } else {
            if ($customerId && $canEdit) {
                $stmt = $pdo->prepare("UPDATE customer_info 
                    SET customer_name=:name, customer_phone=:phone, email=:email, address=:address,
                        reg_date=:reg, next_payment_date=:next, gurantor_name=:gn, gurantor_phone=:gp,
                        nid=:nid, edit_user=:user, edit_date=NOW()
                    WHERE customer_id=:id AND br_code=:br");
                $stmt->execute([
                    'name'=>$formData['customer_name'], 'phone'=>$formData['customer_phone'], 'email'=>$formData['email'], 
                    'address'=>$formData['address'], 'reg'=>$formData['reg_date'], 'next'=>$formData['next_payment_date'],
                    'gn'=>$formData['gurantor_name'], 'gp'=>$formData['gurantor_phone'], 'nid'=>$formData['nid'],
                    'user'=>$userId, 'id'=>$customerId, 'br'=>$brCode
                ]);
                $message = "<div class='alert alert-success text-center'>Customer updated successfully!</div>";
                $formData = [];
            } elseif ($canInsert) {
                $newId = "C-" . $brCode . "-" . date('ymdHis');
                $stmt = $pdo->prepare("INSERT INTO customer_info 
                    (customer_id, customer_name, customer_phone, email, address, reg_date, next_payment_date,
                     gurantor_name, gurantor_phone, nid, entry_user, entry_date, delete_status, org_code, br_code)
                    VALUES (:id, :name, :phone, :email, :address, :reg, :next, :gn, :gp, :nid, :user, NOW(), 'N', :org, :br)");
                $stmt->execute([
                    'id'=>$newId,'name'=>$formData['customer_name'],'phone'=>$formData['customer_phone'],'email'=>$formData['email'],
                    'address'=>$formData['address'],'reg'=>$formData['reg_date'],'next'=>$formData['next_payment_date'],
                    'gn'=>$formData['gurantor_name'],'gp'=>$formData['gurantor_phone'],'nid'=>$formData['nid'],
                    'user'=>$userId,'org'=>$orgCode,'br'=>$brCode
                ]);
                $message = "<div class='alert alert-success text-center'>New Customer registered: $newId</div>";
                $formData = [];
            }
        }
    }
}

// Load for edit
if (isset($_GET['edit']) && $canEdit) {
    $stmt = $pdo->prepare("SELECT * FROM customer_info WHERE customer_id=:id AND br_code=:br");
    $stmt->execute(['id'=>$_GET['edit'], 'br'=>$brCode]);
    $editCustomer = $stmt->fetch();
    if ($editCustomer) $formData = $editCustomer;
}

// Delete
if (isset($_GET['delete']) && $canDelete) {
    $stmt = $pdo->prepare("UPDATE customer_info SET delete_status='Y', delete_user=:user, delete_date=NOW() WHERE customer_id=:id AND br_code=:br");
    $stmt->execute(['user'=>$userId,'id'=>$_GET['delete'],'br'=>$brCode]);
    header("Location: add_customer.php");
    exit();
}

// Fetch customers for branch
$query = "SELECT * FROM customer_info WHERE (delete_status != 'Y' OR delete_status IS NULL)";
$params = [];
if ($userType != 1) { 
    $query .= " AND br_code = :br AND org_code = :org";
    $params = ['br'=>$brCode,'org'=>$orgCode];
}
$query .= " ORDER BY entry_date DESC";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$customers = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Customer Setup</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<style>
    body { background-color: #f8f9fa; }
    .card { border-radius: 1rem; }
    .form-control:focus { box-shadow: 0 0 0 0.2rem rgba(13,110,253,.25); }
</style>
</head>
<body class="d-flex flex-column min-vh-100">
<?php include 'header.php'; ?>

<main class="container py-4 flex-grow-1">
<h3 class="mb-4"><i class="bi bi-people"></i> Customer Management</h3>
<?= $message ?>

<?php if ($canInsert || $editCustomer): ?>
<div class="card shadow-sm mb-4">
<div class="card-body">
<form method="post" id="customerForm">
<input type="hidden" name="customer_id" value="<?= htmlspecialchars($formData['customer_id'] ?? '') ?>">
<div class="row g-3">

<div class="col-md-3">
<label class="form-label fw-bold">Phone Number</label>
<input type="text" name="customer_phone" id="customer_phone" class="form-control form-control-lg" required
       maxlength="14" value="<?= htmlspecialchars($formData['customer_phone'] ?? '') ?>">
<div class="form-text text-muted">Format: 01XXXXXXXXX or +8801XXXXXXXXX</div>
</div>

<div class="col-md-3">
<label class="form-label fw-bold">Customer Name</label>
<input type="text" name="customer_name" class="form-control form-control-lg" required
       value="<?= htmlspecialchars($formData['customer_name'] ?? '') ?>">
</div>

<div class="col-md-3">
<label class="form-label">NID</label>
<input type="text" name="nid" class="form-control form-control-lg"
       value="<?= htmlspecialchars($formData['nid'] ?? '') ?>">
</div>

<div class="col-md-3">
<label class="form-label">Email</label>
<input type="email" name="email" class="form-control form-control-lg"
       value="<?= htmlspecialchars($formData['email'] ?? '') ?>">
</div>

<div class="col-md-6">
<label class="form-label">Address</label>
<input type="text" name="address" class="form-control form-control-lg"
       value="<?= htmlspecialchars($formData['address'] ?? '') ?>">
</div>

<div class="col-md-3">
<label class="form-label">Reg. Date</label>
<input type="date" name="reg_date" class="form-control form-control-lg"
       value="<?= $formData['reg_date'] ?? date('Y-m-d') ?>">
</div>

<div class="col-md-3">
<label class="form-label text-primary">Next Payment Date</label>
<input type="date" name="next_payment_date" class="form-control form-control-lg"
       value="<?= $formData['next_payment_date'] ?? '' ?>">
</div>

<div class="col-md-3">
<label class="form-label">Guarantor Name</label>
<input type="text" name="gurantor_name" class="form-control form-control-lg"
       value="<?= htmlspecialchars($formData['gurantor_name'] ?? '') ?>">
</div>

<div class="col-md-3">
<label class="form-label">Guarantor Phone</label>
<input type="text" name="gurantor_phone" class="form-control form-control-lg"
       value="<?= htmlspecialchars($formData['gurantor_phone'] ?? '') ?>">
</div>

<div class="col-12 text-end">
<?php if ($editCustomer): ?>
    <a href="add_customer.php" class="btn btn-outline-secondary btn-lg me-2">Cancel</a>
<?php endif; ?>
<button type="submit" class="btn btn-primary btn-lg px-5">
    <?= $editCustomer ? 'Update Customer' : 'Save Customer' ?>
</button>
</div>

</div>
</form>
</div>
</div>
<?php endif; ?>

<div class="card shadow-sm">
<div class="table-responsive">
<table class="table table-hover align-middle mb-0 table-bordered">
<thead class="table-dark">
<tr>
    <th>ID</th>
    <th>Customer Name</th>
    <th>Phone</th>
    <th>Next Payment</th>
    <th class="text-center">Action</th>
</tr>
</thead>
<tbody>
<?php foreach ($customers as $row): ?>
<tr>
    <td><?= $row['customer_id'] ?></td>
    <td><?= htmlspecialchars($row['customer_name']) ?></td>
    <td><?= htmlspecialchars($row['customer_phone']) ?></td>
    <td><span class="badge bg-info text-dark"><?= $row['next_payment_date'] ?></span></td>
    <td class="text-center">
        <?php if ($canEdit): ?>
            <a href="?edit=<?= urlencode($row['customer_id']) ?>" class="btn btn-sm btn-warning"><i class="bi bi-pencil"></i></a>
        <?php endif; ?>
        <?php if ($canDelete): ?>
            <a href="?delete=<?= urlencode($row['customer_id']) ?>" class="btn btn-sm btn-danger"
               onclick="return confirm('Delete this customer?')"><i class="bi bi-trash"></i></a>
        <?php endif; ?>
    </td>
</tr>
<?php endforeach; ?>
<?php if (empty($customers)): ?>
<tr><td colspan="5" class="text-center py-4">No customers found.</td></tr>
<?php endif; ?>
</tbody>
</table>
</div>
</div>
</main>

<?php include 'footer.php'; ?>
<script>
document.getElementById('customer_phone').addEventListener('input', function () {
    const phone = this.value.trim();
    const regex = /^(?:\+8801|01)[3-9]\d{8}$/;
    if (!regex.test(phone)) {
        this.classList.add('is-invalid');
    } else {
        this.classList.remove('is-invalid');
    }
});
</script>
</body>
</html>
