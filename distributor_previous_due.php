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
        $stmt = $pdo->prepare("DELETE FROM distributor_previous_due WHERE distributor_due_id=? AND authorized_status='N' AND br_code=? AND org_code=?");
        $stmt->execute([$id, $brCode, $orgCode]);
        $_SESSION['msg'] = "<div class='alert alert-danger py-2'>Record deleted successfully.</div>";
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }

    // 2. APPROVE ACTION
    if (isset($_POST['approve_id']) && $canApprove) {
        $id = $_POST['approve_id'];
        $stmt = $pdo->prepare("UPDATE distributor_previous_due SET authorized_status='Y', authorized_user=?, authorized_date=NOW() WHERE distributor_due_id=? AND br_code=? AND org_code=? AND authorized_status='N'");
        $stmt->execute([$userId, $id, $brCode, $orgCode]);
        $_SESSION['msg'] = "<div class='alert alert-success py-2'>Record approved and locked.</div>";
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }

    // 3. INSERT / UPDATE ACTION
    $dist_code  = $_POST['distributor_code'] ?? '';
    $due_amount = $_POST['due_amount'] ?? 0;
    $due_date   = !empty($_POST['due_date']) ? $_POST['due_date'] : null;
    $remarks    = $_POST['remarks'] ?? '';
    $edit_id    = $_POST['edit_dist_due_id'] ?? '';

    if ($dist_code && $due_amount > 0) {
        if (!empty($edit_id) && $canEdit) {
            // UPDATE
            $stmt = $pdo->prepare("UPDATE distributor_previous_due SET distributor_code=?, due_amount=?, due_date=?, remarks=?, edit_user=?, edit_date=NOW() WHERE distributor_due_id=? AND authorized_status='N' AND br_code=? AND org_code=?");
            $stmt->execute([$dist_code, $due_amount, $due_date, $remarks, $userId, $edit_id, $brCode, $orgCode]);
            $_SESSION['msg'] = "<div class='alert alert-info py-2'>Record updated successfully.</div>";
        } elseif ($canInsert) {
            // INSERT (Always inserts new record even if distributor already exists)
            $newId = "DPD-" . $brCode . "-" . date('ymdHis') . rand(10,99);
            $stmt = $pdo->prepare("INSERT INTO distributor_previous_due (distributor_due_id, distributor_code, due_amount, due_date, remarks, entry_user, entry_date, br_code, org_code, authorized_status) VALUES (?,?,?,?,?,?,NOW(),?,?,'N')");
            $stmt->execute([$newId, $dist_code, $due_amount, $due_date, $remarks, $userId, $brCode, $orgCode]);
            $_SESSION['msg'] = "<div class='alert alert-success py-2'>Record saved successfully.</div>";
        }
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }
}

/* ==========================
    FETCH DATA
========================== */
$distributors = $pdo->query("SELECT distributor_code, distributor_name FROM distributor WHERE br_code='$brCode' AND org_code='$orgCode' ORDER BY distributor_name")->fetchAll();

$dueList = $pdo->query("SELECT d.*, m.distributor_name, m.distributor_contact FROM distributor_previous_due d JOIN distributor m ON d.distributor_code = m.distributor_code WHERE d.br_code='$brCode' AND d.org_code='$orgCode' ORDER BY d.entry_date DESC")->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Distributor Due Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        .is-editing { border: 2px solid #0d6efd !important; background-color: #f0f7ff !important; }
        .btn-action { padding: 0.25rem 0.5rem; font-size: 0.8rem; display: inline-flex; align-items: center; gap: 4px; }
        .bg-authorized { background-color: #f8f9fa; }
        .search-box { position: relative; max-width: 300px; margin-left: auto; }
        .search-box i { position: absolute; left: 10px; top: 50%; transform: translateY(-50%); color: #6c757d; }
        .search-box input { padding-left: 35px; border-radius: 20px; }
    </style>
</head>
<body class="bg-light">
<?php include 'header.php'; ?>

<div class="container py-4">
    <h3><i class="bi bi-truck"></i> Distributor Previous Due</h3>

    <?php if (isset($_SESSION['msg'])) { echo $_SESSION['msg']; unset($_SESSION['msg']); } ?>

    <div class="card shadow-sm mb-4" id="form_card">
        <div class="card-body">
            <form method="post" id="mainForm">
                <input type="hidden" name="edit_dist_due_id" id="edit_id">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label fw-bold">Distributor</label>
                        <select name="distributor_code" id="f_distributor" class="form-select" required>
                            <option value="">-- Select Distributor --</option>
                            <?php foreach($distributors as $d): ?>
                                <option value="<?= $d['distributor_code'] ?>"><?= htmlspecialchars($d['distributor_name']) ?></option>
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
                    <div class="col-md-3">
                        <label class="form-label fw-bold">Remarks</label>
                        <input type="text" name="remarks" id="f_remarks" class="form-control" placeholder="Optional notes">
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
                <h5 class="mb-0">Distributor Record List</h5>
                <div class="search-box">
                    <i class="bi bi-search"></i>
                    <input type="text" id="globalSearch" class="form-control form-control-sm" placeholder="Search distributor or ID...">
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-bordered table-hover border-3">
                    <thead class="table-secondary">
                        <tr>
                            <th>Entry Date</th>
                            <th>Distributor</th>
                            <th class="text-end">Due Amount</th>
                            <th>Due Date</th>
                            <th>Remarks</th>
                            <th class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="tableBody">
                        <?php foreach ($dueList as $row): 
                            $isAuth = ($row['authorized_status'] == 'Y');
                        ?>
                        <tr class="<?= $isAuth ? 'bg-authorized' : '' ?>">
                            <td class="small text-center"><?= date('d/m/Y', strtotime($row['entry_date'])) ?></td>
                            <td>
                                <strong><?= htmlspecialchars($row['distributor_name']) ?></strong><br>
                                <small class="text-muted"><?= $row['distributor_contact'] ?></small>
                            </td>
                            <td class="text-end fw-bold text-danger"><?= number_format($row['due_amount'], 2) ?></td>
                            <td class="text-center small"><?= $row['due_date'] ? date('d/m/Y', strtotime($row['due_date'])) : '-' ?></td>
                            <td><small><?= htmlspecialchars($row['remarks']) ?></small></td>
                            <td>
                                <div class="d-flex gap-1 justify-content-center">
                                    <?php if(!$isAuth): ?>
                                        <?php if($canEdit): ?>
                                            <button class="btn btn-primary btn-action" onclick="editRow('<?= $row['distributor_due_id'] ?>', '<?= $row['distributor_code'] ?>', '<?= $row['due_amount'] ?>', '<?= $row['due_date'] ?>', '<?= addslashes($row['remarks']) ?>')">
                                                <i class="bi bi-pencil"></i> Edit
                                            </button>
                                        <?php endif; ?>

                                        <?php if($canApprove): ?>
                                            <button class="btn btn-success btn-action" onclick="runAction('approve_id', '<?= $row['distributor_due_id'] ?>', 'Approve this record?')">
                                                <i class="bi bi-check-lg"></i> Approve
                                            </button>
                                        <?php endif; ?>

                                        <?php if($canDelete): ?>
                                            <button class="btn btn-danger btn-action" onclick="runAction('delete_id', '<?= $row['distributor_due_id'] ?>', 'Delete this record?')">
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
    $("#globalSearch").on("keyup", function() {
        var value = $(this).val().toLowerCase();
        $("#tableBody tr").filter(function() {
            $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
        });
    });
});

function editRow(id, dcode, amt, ddate, rem) {
    $("#edit_id").val(id);
    $("#f_distributor").val(dcode);
    $("#f_amount").val(amt);
    $("#f_date").val(ddate);
    $("#f_remarks").val(rem);
    
    $("#form_card").addClass('is-editing');
    $("#btn_container").html(`
        <div class="d-flex gap-2 mt-4">
            <button type="submit" class="btn btn-primary w-100"><i class="bi bi-arrow-repeat"></i> Update</button>
            <button type="button" class="btn btn-outline-secondary" onclick="location.reload()">Cancel</button>
        </div>
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