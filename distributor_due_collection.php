<?php
session_start();
require 'db.php';

$userId    = $_SESSION['user']['user_id'];
$brCode    = $_SESSION['user']['br_code'];
$orgCode   = $_SESSION['user']['org_code'];
$canInsert = $_SESSION['user']['can_insert'] ?? 0;
$canEdit   = $_SESSION['user']['can_edit'] ?? 0;
$canDelete = $_SESSION['user']['can_delete'] ?? 0;
$canApprove= $_SESSION['user']['can_approve'] ?? 0;

// Persistent Distributor Selection
$selectedDist = $_POST['distributor_code'] ?? $_GET['last_dist'] ?? '';

/* ==========================
    HANDLE UPDATE ACTION
========================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_installment']) && $canEdit) {
    $id  = $_POST['edit_installment_id'];
    $amt = $_POST['installment_amount'];
    
    $stmt = $pdo->prepare("UPDATE distributor_due_installment 
                           SET installment_amount = ?, edit_user = ?, edit_date = NOW() 
                           WHERE installment_id = ? AND br_code = ? AND authorized_status = 'N'");
    $stmt->execute([$amt, $userId, $id, $brCode]);
    
    $_SESSION['msg'] = "<div class='alert alert-info py-2'>Installment updated successfully.</div>";
    header("Location: distributor_due_collection.php?last_dist=" . $selectedDist);
    exit();
}

/* ==========================
    HANDLE APPROVE ACTION
========================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['approve_installment']) && $canApprove) {
    $id = $_POST['installment_id'] ?? '';
    $stmt = $pdo->prepare("UPDATE distributor_due_installment 
                           SET authorized_status = 'Y', authorized_user = ?, authorized_date = NOW() 
                           WHERE installment_id = ? AND br_code = ? AND org_code = ? AND authorized_status = 'N'");
    $stmt->execute([$userId, $id, $brCode, $orgCode]);
    header("Location: distributor_due_collection.php?last_dist=" . $selectedDist);
    exit();
}

/* ==========================
    HANDLE DELETE ACTION
========================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_installment']) && $canDelete) {
    $id = $_POST['installment_id'] ?? '';
    $stmt = $pdo->prepare("DELETE FROM distributor_due_installment WHERE installment_id=? AND br_code=? AND org_code=? AND authorized_status='N'");
    $stmt->execute([$id, $brCode, $orgCode]);
    header("Location: distributor_due_collection.php?last_dist=" . $selectedDist);
    exit();
}

/* ==========================
    HANDLE INSERT ACTION
========================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['installment_amount']) && !isset($_POST['update_installment']) && $canInsert) {
    $dist = $_POST['distributor_code'] ?? '';
    $amt  = $_POST['installment_amount'] ?? 0;
    $stock_mst_id = $_POST['stock_mst_id'] ?: null;
    $installmentId = "DDI-" . $brCode . "-" . date('ymdHis') . rand(10,99);

    $stmt = $pdo->prepare("INSERT INTO distributor_due_installment 
        (installment_id, distributor_code, stock_mst_id, installment_amount, installment_date, payment_mode, entry_user, entry_date, org_code, br_code, authorized_status) 
        VALUES (?, ?, ?, ?, CURDATE(), 'Cash', ?, NOW(), ?, ?, 'N')");
    $stmt->execute([$installmentId, $dist, $stock_mst_id, $amt, $userId, $orgCode, $brCode]);

    $_SESSION['msg'] = "<div class='alert alert-success py-2'>Installment paid successfully.</div>";
    header("Location: distributor_due_collection.php?last_dist=" . $dist);
    exit();
}

$distributors = $pdo->query("SELECT distributor_code, distributor_name FROM distributor WHERE br_code='$brCode' AND org_code='$orgCode' ORDER BY distributor_name ASC")->fetchAll();
$stockList = $pdo->query("SELECT stock_mst_id, stock_voucher_ref FROM stock_mst WHERE br_code='$brCode' AND org_code='$orgCode' ORDER BY stock_entry_date DESC")->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Distributor Due Payment</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <style>
        .is-editing { border: 2px solid #0d6efd !important; background-color: #f0f7ff !important; }
        .table-vcenter td { vertical-align: middle; }
        .btn-action-group { display: flex; gap: 5px; justify-content: center; flex-wrap: nowrap; }
        .btn-action { display: inline-flex; align-items: center; gap: 4px; white-space: nowrap; padding: 0.25rem 0.5rem; font-size: 0.8rem; }
    </style>
</head>

<body class="d-flex flex-column min-vh-100 bg-light">
<?php include 'header.php'; ?>

<main class="container py-4 flex-grow-1">
    <h3><i class="bi bi-cash-stack"></i> Distributor Due Payment</h3>

    <?php if (!empty($_SESSION['msg'])) { echo $_SESSION['msg']; unset($_SESSION['msg']); } ?>

    <?php if ($canInsert): ?>
    <div class="card shadow-sm mb-4" id="form_card">
        <div class="card-body">
            <form method="post" id="payForm">
                <input type="hidden" name="edit_installment_id" id="edit_installment_id">
                
                <div class="row g-3 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label fw-bold">Distributor</label>
                        <select name="distributor_code" id="distributor_code" class="form-select" required>
                            <option value="">-- Select Distributor --</option>
                            <?php foreach($distributors as $d): ?>
                            <option value="<?= $d['distributor_code'] ?>" <?= ($selectedDist == $d['distributor_code']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($d['distributor_name']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label fw-bold">Against</label>
                        <select name="stock_mst_id" id="stock_mst_id" class="form-select">
                            <option value="">Previous Due</option>
                            <?php foreach($stockList as $s): ?>
                            <option value="<?= $s['stock_mst_id'] ?>"><?= htmlspecialchars($s['stock_voucher_ref']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-2">
                        <label class="form-label fw-bold">Current Due</label>
                        <input type="text" id="current_due" class="form-control bg-white text-danger fw-bold" value="0.00" readonly>
                    </div>

                    <div class="col-md-2">
                        <label class="form-label fw-bold">Amount</label>
                        <input type="number" step="0.01" name="installment_amount" id="installment_amount" class="form-control" required>
                    </div>

                    <div class="col-md-2" id="btn_container">
                        <button type="submit" class="btn btn-success w-100 d-flex align-items-center justify-content-center gap-2">
                            <i class="bi bi-save"></i> Save Payment
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <div class="card shadow-sm">
        <div class="card-body">
            <h5 class="mb-3">Distributor Ledger</h5>
            <div class="table-responsive">
                <table class="table table-bordered table-hover border-3" id="ledger_table">
                    <thead class="table-secondary">
                        <tr>
                            <th>Date</th><th>Type</th><th>Voucher</th><th>Dr (Paid)</th><th>Cr (Due)</th><th>Balance</th><th width="280">Actions</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
</main>

<script>
const auth = {
    canEdit: <?= $canEdit ? 1 : 0 ?>,
    canDelete: <?= $canDelete ? 1 : 0 ?>,
    canApprove: <?= $canApprove ? 1 : 0 ?>
};

$(document).ready(function() {
    if($("#distributor_code").val()) {
        loadCurrentDue();
        loadLedger();
    }
});

function loadCurrentDue(){
    let dist = $("#distributor_code").val();
    let selectedStock = $("#stock_mst_id").val();
    if(!dist){ $("#current_due").val("0.00"); return; }

    $.get('get_distributor_due.php', {distributor_code:dist, stock_mst_id:selectedStock}, function(d){
        $("#current_due").val(parseFloat(d).toFixed(2));
    });

    $("#stock_mst_id option").each(function() {
        let option = $(this);
        let stockId = option.val();
        if(!stockId) return;
        $.get('get_distributor_due.php', {distributor_code:dist, stock_mst_id:stockId}, function(res){
            if(parseFloat(res) <= 0) { 
                option.hide(); 
                if(stockId === selectedStock) { $("#stock_mst_id").val(""); }
            } else { option.show(); }
        });
    });
}

function loadLedger() {
    let dist = $("#distributor_code").val();
    if (!dist) return;

    $.getJSON('get_distributor_due_ledger.php', { distributor_code: dist }, function(rows) {
        let tbody = $("#ledger_table tbody").empty();
        rows.forEach(r => {
            let actionHtml = '<div class="btn-action-group">';
            
            if(r.type === 'Payment to Distributor' && r.authorized_status === 'N') {
                if(auth.canEdit) {
                    actionHtml += `
                        <button class="btn btn-sm btn-primary btn-action edit-btn" 
                            data-id="${r.id}" 
                            data-amt="${r.dr.toString().replace(/,/g, "")}" 
                            data-dist="${dist}" 
                            data-voucher="${r.stock_mst_id || ''}">
                            <i class="bi bi-pencil-square"></i> Edit
                        </button>`;
                }
                if(auth.canApprove) {
                    actionHtml += `
                        <button class="btn btn-sm btn-success btn-action approve-btn" data-id="${r.id}">
                            <i class="bi bi-check-circle"></i> Approve
                        </button>`;
                }
                if(auth.canDelete) {
                    actionHtml += `
                        <button class="btn btn-sm btn-danger btn-action delete-btn" data-id="${r.id}">
                            <i class="bi bi-trash"></i> Delete
                        </button>`;
                }
            } else if (r.authorized_status === 'Y') {
                actionHtml += '<span class="badge bg-secondary px-3 py-2"><i class="bi bi-shield-lock"></i> Authorized</span>';
            }
            actionHtml += '</div>';

            tbody.append(`<tr align="center">
                <td>${r.entry_date}</td>
                <td align="left">${r.type}</td>
                <td><small class="fw-bold">${r.stock_voucher_ref ?? ''}</small></td>
                <td class="text-end text-primary">${r.dr || ''}</td>
                <td class="text-end text-danger">${r.cr || ''}</td>
                <td class="text-end fw-bold">${r.balance}</td>
                <td>${actionHtml}</td>
            </tr>`);
        });
    });
}

// UPDATE: EDIT BUTTON HANDLER
$(document).on('click', '.edit-btn', function() {
    if(!auth.canEdit) return;

    // Disable relevant fields during edit mode
    $("#distributor_code, #stock_mst_id").prop('disabled', true);
    // Disable the current due field as requested
    $("#current_due").prop('disabled', true).addClass('bg-light');

    let id = $(this).data('id');
    let amt = $(this).data('amt'); 
    let dist = $(this).data('dist');
    let voucher = $(this).data('voucher');

    $("#edit_installment_id").val(id);
    $("#installment_amount").val(amt).focus();
    $("#distributor_code").val(dist);
    $("#stock_mst_id").val(voucher);

    loadCurrentDue();

    $("#form_card").addClass('is-editing');
    $("#btn_container").html(`
        <div class="btn-group w-100">
            <button type="submit" name="update_installment" class="btn btn-primary d-flex align-items-center justify-content-center gap-1">
                <i class="bi bi-arrow-repeat"></i> Update
            </button>
            <button type="button" onclick="location.reload()" class="btn btn-outline-secondary">Cancel</button>
        </div>
    `);
    
    window.scrollTo({ top: 0, behavior: 'smooth' });
});

$("#distributor_code, #stock_mst_id").change(() => { loadCurrentDue(); loadLedger(); });

function runAction(action, id) {
    let dist = $("#distributor_code").val();
    let form = $('<form method="POST" action="distributor_due_collection.php?last_dist='+dist+'"></form>');
    form.append(`<input type="hidden" name="installment_id" value="${id}"><input type="hidden" name="${action}" value="1">`);
    $('body').append(form).find('form').submit();
}

$(document).on('click', '.approve-btn', function() { 
    if(auth.canApprove && confirm("Confirm Authorization? Record will be locked.")) {
        runAction('approve_installment', $(this).data('id')); 
    }
});

$(document).on('click', '.delete-btn', function() { 
    if(auth.canDelete && confirm("Delete this payment?")) {
        runAction('delete_installment', $(this).data('id')); 
    }
});

// UPDATE: FORM SUBMIT HANDLER
$("#payForm").on("submit", function(e) {
    // Re-enable fields before submitting so PHP can receive the data
    $("#distributor_code, #stock_mst_id, #current_due").prop('disabled', false);

    let currentDue = parseFloat($("#current_due").val()) || 0;
    let amount     = parseFloat($("#installment_amount").val()) || 0;

    // Validation
    if (amount > currentDue) {
        alert("❌ Amount cannot exceed Current Due (" + currentDue.toFixed(2) + ")");
        $("#installment_amount").focus();
        
        // If validation fails and we are in edit mode, re-disable them to maintain UI state
        if($("#edit_installment_id").val() !== "") {
            $("#distributor_code, #stock_mst_id, #current_due").prop('disabled', true);
        }
        
        e.preventDefault();
        return false;
    }
});
</script>

<?php include 'footer.php'; ?>
</body>
</html>