<?php
session_start();
require 'db.php';

/* ==========================
    USER PERMISSIONS & INFO
========================== */
$user       = $_SESSION['user'] ?? null;
$userId     = $user['user_id'] ?? '';
$brCode     = $user['br_code'] ?? '';
$orgCode    = $user['org_code'] ?? '';

// Defaulting to 0 for security, assuming session holds actual bits
$canInsert  = $user['can_insert'] ?? 0;
$canEdit    = $user['can_edit'] ?? 0;
$canDelete  = $user['can_delete'] ?? 0;
$canApprove = $user['can_approve'] ?? 0;

/* ==========================
    HANDLE ACTIONS (POST)
========================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // 1. DELETE ACTION
    if (isset($_POST['delete_id']) && $canDelete) {
        $id = $_POST['delete_id'];
        // Only allow delete if not authorized
        $stmt = $pdo->prepare("DELETE FROM customer_previous_due WHERE prev_due_id=? AND authorized_status='N' AND br_code=? AND org_code=?");
        $stmt->execute([$id, $brCode, $orgCode]);
        $_SESSION['msg'] = "<div class='alert alert-danger py-2'>Record deleted successfully.</div>";
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }

    // 2. APPROVE ACTION
    if (isset($_POST['approve_id']) && $canApprove) {
        $id = $_POST['approve_id'];
        $stmt = $pdo->prepare("UPDATE customer_previous_due SET authorized_status='Y', authorized_user=?, authorized_date=NOW() WHERE prev_due_id=? AND br_code=? AND org_code=? AND authorized_status='N'");
        $stmt->execute([$userId, $id, $brCode, $orgCode]);
        $_SESSION['msg'] = "<div class='alert alert-success py-2'>Record approved and locked.</div>";
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }

    // 3. INSERT / UPDATE ACTION
    $customer_id = $_POST['customer_id'] ?? '';
    $due_amount  = $_POST['due_amount'] ?? 0;
    $due_date    = !empty($_POST['due_date']) ? $_POST['due_date'] : null;
    $remarks     = $_POST['remarks'] ?? '';
    $edit_id     = $_POST['edit_prev_due_id'] ?? '';

    if ($customer_id && $due_amount > 0) {
        if (!empty($edit_id) && $canEdit) {
            $stmt = $pdo->prepare("UPDATE customer_previous_due SET customer_id=?, previous_due_amount=?, due_date=?, remarks=?, edit_user=?, edit_date=NOW() WHERE prev_due_id=? AND authorized_status='N' AND br_code=? AND org_code=?");
            $stmt->execute([$customer_id, $due_amount, $due_date, $remarks, $userId, $edit_id, $brCode, $orgCode]);
            $_SESSION['msg'] = "<div class='alert alert-info py-2'>Record updated successfully.</div>";
        } elseif ($canInsert) {
            $newId = "CPD-" . $brCode . "-" . date('ymdHis') . rand(10,99);
            $stmt = $pdo->prepare("INSERT INTO customer_previous_due (prev_due_id, customer_id, previous_due_amount, due_date, remarks, entry_user, entry_date, org_code, br_code, authorized_status) VALUES (?,?,?,?,?,?,NOW(),?,?,'N')");
            $stmt->execute([$newId, $customer_id, $due_amount, $due_date, $remarks, $userId, $orgCode, $brCode]);
            $_SESSION['msg'] = "<div class='alert alert-success py-2'>Record saved successfully.</div>";
        }
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }
}

/* ==========================
    FETCH DATA
========================== */
$customers = $pdo->query("SELECT customer_id, customer_name FROM customer_info WHERE br_code='$brCode' AND org_code='$orgCode' AND (delete_status!='Y' OR delete_status IS NULL) ORDER BY customer_name")->fetchAll();

$prevDueList = $pdo->query("SELECT p.*, c.customer_name, c.customer_phone FROM customer_previous_due p JOIN customer_info c ON p.customer_id=c.customer_id WHERE p.br_code='$brCode' AND p.org_code='$orgCode' ORDER BY p.entry_date DESC")->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Customer Due Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        .is-editing { border: 2px solid #0d6efd !important; background-color: #f0f7ff !important; }
        .btn-action { padding: 0.25rem 0.5rem; font-size: 0.8rem; display: inline-flex; align-items: center; gap: 4px; }
        .bg-authorized { background-color: #f8f9fa; }
        .search-box { position: relative; }
        .search-box i { position: absolute; left: 10px; top: 50%; transform: translateY(-50%); color: #6c757d; }
        .search-box input { padding-left: 35px; border-radius: 20px; }
    </style>
</head>
<body class="bg-light">
<?php include 'header.php'; ?>

<div class="container py-4">
    <h3><i class="bi bi-cash-stack"></i> Customer Previous Due</h3>

    <?php if (isset($_SESSION['msg'])) { echo $_SESSION['msg']; unset($_SESSION['msg']); } ?>

    <div class="card shadow-sm mb-4" id="form_card">
        <div class="card-body">
            <form method="post" id="mainForm">
                <input type="hidden" name="edit_prev_due_id" id="edit_id">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Customer</label>
                        <select name="customer_id" id="f_customer" class="form-select" required>
                            <option value="">-- Select Customer --</option>
                            <?php foreach($customers as $c): ?>
                                <option value="<?= $c['customer_id'] ?>"><?= htmlspecialchars($c['customer_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label fw-bold">Amount</label>
                        <input type="number" step="0.01" name="due_amount" id="f_amount" class="form-control" required>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label fw-bold">Due Date</label>
                        <input type="date" name="due_date" id="f_date" class="form-control">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label fw-bold">Remarks</label>
                        <input type="text" name="remarks" id="f_remarks" class="form-control">
                    </div>
                    <div class="col-md-2 d-grid" id="btn_container">
                        <?php if($canInsert): ?>
                            <button type="submit" class="btn btn-success mt-4"><i class="bi bi-save"></i> Save Entry</button>
                        <?php else: ?>
                            <small class="text-danger mt-4 text-center">No permission</small>
                        <?php endif; ?>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="mb-0">Customer Record List</h5>
                <div class="search-box">
                    <i class="bi bi-search"></i>
                    <input type="text" id="globalSearch" class="form-control form-control-sm" placeholder="Search customer or ID...">
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-bordered table-hover border-3">
                    <thead class="table-secondary">
                        <tr>
                            <th class="text-center">Date</th>
                            <th>Customer</th>
                            <th class="text-end">Due Amount</th>
                            <th>Remarks</th>
                            <th class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="tableBody">
                        <?php foreach($prevDueList as $d): 
                            $isAuth = ($d['authorized_status'] == 'Y');
                        ?>
                        <tr class="<?= $isAuth ? 'bg-light' : '' ?>">
                            <td class="text-center small"><?= date('d/m/Y', strtotime($d['entry_date'])) ?></td>
                            <td>
                                <strong><?= htmlspecialchars($d['customer_name']) ?></strong><br>
                                <small class="text-muted"><?= $d['customer_phone'] ?></small>
                            </td>
                            <td class="text-end fw-bold text-primary"><?= number_format($d['previous_due_amount'], 2) ?></td>
                            <td><small><?= htmlspecialchars($d['remarks']) ?></small></td>
                            <td>
                                <div class="d-flex gap-1 justify-content-center">
                                    <?php if(!$isAuth): ?>
                                        <?php if($canEdit): ?>
                                            <button class="btn btn-primary btn-action" onclick="editRow('<?= $d['prev_due_id'] ?>', '<?= $d['customer_id'] ?>', '<?= $d['previous_due_amount'] ?>', '<?= $d['due_date'] ?>', '<?= addslashes($d['remarks']) ?>')">
                                                <i class="bi bi-pencil"></i> Edit
                                            </button>
                                        <?php endif; ?>

                                        <?php if($canApprove): ?>
                                            <button class="btn btn-success btn-action" onclick="runAction('approve_id', '<?= $d['prev_due_id'] ?>', 'Approve this record?')">
                                                <i class="bi bi-check-lg"></i> Approve
                                            </button>
                                        <?php endif; ?>

                                        <?php if($canDelete): ?>
                                            <button class="btn btn-danger btn-action" onclick="runAction('delete_id', '<?= $d['prev_due_id'] ?>', 'Delete this record?')">
                                                <i class="bi bi-trash"></i> Delete
                                            </button>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="badge bg-secondary py-2"><i class="bi bi-lock-fill"></i> Authorized</span>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<form id="actionForm" method="POST" style="display:none;">
    <input type="hidden" name="" id="actionInput">
</form>

<script>
$(document).ready(function() {
    // Client-side search logic
    $("#globalSearch").on("keyup", function() {
        var value = $(this).val().toLowerCase();
        $("#tableBody tr").filter(function() {
            $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
        });
    });
});

function editRow(id, cid, amt, ddate, rem) {
    $("#edit_id").val(id);
    $("#f_customer").val(cid);
    $("#f_amount").val(amt);
    $("#f_date").val(ddate);
    $("#f_remarks").val(rem);
    
    $("#form_card").addClass('is-editing');
    $("#btn_container").html(`
        <button type="submit" class="btn btn-primary"><i class="bi bi-arrow-repeat"></i> Update Record</button>
        <button type="button" class="btn btn-outline-secondary" onclick="location.reload()">Cancel</button>
    `);
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

function runAction(name, id, msg) {
    if(confirm(msg)) {
        $("#actionInput").attr("name", name).val(id);
        $("#actionForm").submit();
    }
}
</script>

<?php include 'footer.php'; ?>
</body>
</html>