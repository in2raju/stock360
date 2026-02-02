<?php 
session_start();
require 'db.php';

$userId    = $_SESSION['user']['user_id'];
$brCode    = $_SESSION['user']['br_code'];
$orgCode   = $_SESSION['user']['org_code'];
$canInsert = $_SESSION['user']['can_insert'] ?? 1;
$canEdit   = $_SESSION['user']['can_edit'] ?? 0;
$canDelete = $_SESSION['user']['can_delete'] ?? 0;
$canApprove= $_SESSION['user']['can_approve'] ?? 0;

// Persistent Customer Selection
$selectedCust = $_POST['customer_id'] ?? $_GET['last_cust'] ?? '';

/* ==========================
    HANDLE UPDATE ACTION
========================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_installment']) && $canEdit) {
    $id    = $_POST['edit_installment_id'];
    $amt   = $_POST['installment_amount'];
    
    $stmt = $pdo->prepare("UPDATE customer_due_installment 
                           SET installment_amount = ?, edit_user = ?, edit_date = NOW() 
                           WHERE installment_id = ? AND br_code = ? AND authorized_status = 'N'");
    $stmt->execute([$amt, $userId, $id, $brCode]);
    
    $_SESSION['msg'] = "<div class='alert alert-info py-2'>Collection updated successfully.</div>";
    header("Location: due_collection.php?last_cust=" . $selectedCust);
    exit();
}

/* ==========================
    HANDLE APPROVE ACTION
========================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['approve_installment']) && $canApprove) {
    $id = $_POST['installment_id'] ?? '';
    $stmt = $pdo->prepare("UPDATE customer_due_installment 
                           SET authorized_status = 'Y', authorized_user = ?, authorized_date = NOW() 
                           WHERE installment_id = ? AND br_code = ? AND org_code = ? AND authorized_status = 'N'");
    $stmt->execute([$userId, $id, $brCode, $orgCode]);
    header("Location: due_collection.php?last_cust=" . $selectedCust);
    exit();
}

/* ==========================
    HANDLE DELETE ACTION
========================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_installment']) && $canDelete) {
    $id = $_POST['installment_id'] ?? '';
    $stmt = $pdo->prepare("DELETE FROM customer_due_installment WHERE installment_id=? AND br_code=? AND org_code=? AND authorized_status='N'");
    $stmt->execute([$id, $brCode, $orgCode]);
    header("Location: due_collection.php?last_cust=" . $selectedCust);
    exit();
}

/* ==========================
    HANDLE INSERT ACTION
========================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['installment_amount']) && !isset($_POST['update_installment']) && $canInsert) {
    $cust = $_POST['customer_id'] ?? '';
    $amt  = $_POST['installment_amount'] ?? 0;
    $sales_mst_id = $_POST['sales_mst_id'] ?: null;
    $installmentId = "DIN-" . $brCode . "-" . date('ymdHis') . rand(10,99);

    $stmt = $pdo->prepare("INSERT INTO customer_due_installment 
        (installment_id, customer_id, sales_mst_id, installment_amount, installment_date, payment_mode, entry_user, entry_date, org_code, br_code, authorized_status) 
        VALUES (?, ?, ?, ?, CURDATE(), 'Cash', ?, NOW(), ?, ?, 'N')");
    $stmt->execute([$installmentId, $cust, $sales_mst_id, $amt, $userId, $orgCode, $brCode]);

    $_SESSION['msg'] = "<div class='alert alert-success py-2'>Collection recorded successfully.</div>";
    header("Location: due_collection.php?last_cust=" . $cust);
    exit();
}

$customers = $pdo->query("SELECT customer_id, customer_name FROM customer_info WHERE br_code='$brCode' AND org_code='$orgCode' AND (delete_status!='Y' OR delete_status IS NULL) ORDER BY customer_name ASC")->fetchAll();
$salesList = $pdo->query("SELECT sales_mst_id, sales_voucher_ref FROM sales_mst WHERE br_code='$brCode' AND org_code='$orgCode' AND authorized_status='Y' ORDER BY sales_entry_date DESC")->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Customer Due Collection</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <style>
        .is-editing { border: 2px solid #0d6efd !important; background-color: #f0f7ff !important; }
        .table-vcenter td { vertical-align: middle; }
        /* Action container styling */
        .btn-action-group { 
            display: flex; 
            gap: 5px; 
            justify-content: center; 
            flex-wrap: nowrap; 
        }
        .btn-action { 
            display: inline-flex; 
            align-items: center; 
            gap: 4px; 
            white-space: nowrap; 
            padding: 0.25rem 0.5rem; 
            font-size: 0.8rem; 
        }
    </style>
</head>

<body class="d-flex flex-column min-vh-100 bg-light">
<?php include 'header.php'; ?>

<main class="container py-4 flex-grow-1">
    <h3><i class="bi bi-cash-coin"></i> Customer Due Collection</h3>

    <?php if (!empty($_SESSION['msg'])) { echo $_SESSION['msg']; unset($_SESSION['msg']); } ?>

    <?php if ($canInsert): ?>
    <div class="card shadow-sm mb-4" id="form_card">
        <div class="card-body">
            <form method="post" id="dueForm">
                <input type="hidden" name="edit_installment_id" id="edit_installment_id">
                
                <div class="row g-3 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label fw-bold">Customer</label>
                        <select name="customer_id" id="customer_id" class="form-select" required>
                            <option value="">-- Select Customer --</option>
                            <?php foreach($customers as $c): ?>
                            <option value="<?= $c['customer_id'] ?>" <?= ($selectedCust == $c['customer_id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($c['customer_name']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label fw-bold">Against</label>
                        <select name="sales_mst_id" id="sales_mst_id" class="form-select">
                            <option value="">Previous Due</option>
                            <?php foreach($salesList as $s): ?>
                            <option value="<?= $s['sales_mst_id'] ?>"><?= htmlspecialchars($s['sales_voucher_ref']) ?></option>
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
                            <i class="bi bi-save"></i> Save Collection
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <div class="card shadow-sm">
        <div class="card-body">
            <h5 class="mb-3">Customer Ledger</h5>
            <div class="table-responsive">
                <table class="table table-bordered table-hover border-3" id="ledger_table">
                    <thead class="table-secondary">
                        <tr>
                            <th>Date</th><th>Type</th><th>Voucher</th><th>Dr (Due)</th><th>Cr (Paid)</th><th>Balance</th><th width="280">Actions</th>
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
    if($("#customer_id").val()) {
        loadCurrentDue();
        loadLedger();
    }
});

function loadCurrentDue(){
    let cust = $("#customer_id").val();
    let selectedSale = $("#sales_mst_id").val();
    if(!cust){ $("#current_due").val("0.00"); return; }

    $.get('get_due.php', {customer_id:cust, sales_mst_id:selectedSale}, function(d){
        $("#current_due").val(parseFloat(d).toFixed(2));
    });

    $("#sales_mst_id option").each(function() {
        let option = $(this);
        let saleId = option.val();
        if(saleId === "") return;
        $.get('get_due.php', {customer_id:cust, sales_mst_id:saleId}, function(res){
            if(parseFloat(res) <= 0) { option.hide(); } 
            else { option.show(); }
        });
    });
}

function loadLedger() {
    let cust = $("#customer_id").val();
    if (!cust) return;

    $.getJSON('get_due_ledger.php', { customer_id: cust }, function(rows) {
        let tbody = $("#ledger_table tbody").empty();
        rows.forEach(r => {
            let actionHtml = '<div class="btn-action-group">';
            
            if(r.type === 'Installment Paid' && r.authorized_status === 'N') {
                if(auth.canEdit) {
                    actionHtml += `
                        <button class="btn btn-sm btn-primary btn-action edit-btn" 
                            data-id="${r.ref_id}" 
                            data-amt="${r.cr.replace(/,/g, '')}" 
                            data-cust="${cust}" 
                            data-sales="${r.sales_mst_id || ''}">
                            <i class="bi bi-pencil-square"></i> Edit
                        </button>`;
                }
                if(auth.canApprove) {
                    actionHtml += `
                        <button class="btn btn-sm btn-success btn-action approve-btn" data-id="${r.ref_id}">
                            <i class="bi bi-check-circle"></i> Approve
                        </button>`;
                }
                if(auth.canDelete) {
                    actionHtml += `
                        <button class="btn btn-sm btn-danger btn-action delete-btn" data-id="${r.ref_id}">
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
                <td><small class="fw-bold">${r.sales_voucher_ref ?? ''}</small></td>
                <td class="text-end text-danger">${r.dr || ''}</td>
                <td class="text-end text-primary">${r.cr || ''}</td>
                <td class="text-end fw-bold">${r.balance}</td>
                <td>${actionHtml}</td>
            </tr>`);
        });
    });
}

$(document).on('click', '.edit-btn', function() {
    if(!auth.canEdit) return;
    $("#customer_id, #sales_mst_id").prop('disabled', true);

    $("#edit_installment_id").val($(this).data('id'));
    $("#installment_amount").val($(this).data('amt')).focus();
    $("#customer_id").val($(this).data('cust'));
    $("#sales_mst_id").val($(this).data('sales'));

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

$("#customer_id, #sales_mst_id").change(() => { loadCurrentDue(); loadLedger(); });

function runAction(action, id) {
    let cust = $("#customer_id").val();
    let form = $('<form method="POST" action="due_collection.php?last_cust='+cust+'"></form>');
    form.append(`<input type="hidden" name="installment_id" value="${id}"><input type="hidden" name="${action}_installment" value="1">`);
    $('body').append(form).find('form').submit();
}

$(document).on('click', '.approve-btn', function() { 
    if(auth.canApprove && confirm("Authorize this collection?")) runAction('approve', $(this).data('id')); 
});

$(document).on('click', '.delete-btn', function() { 
    if(auth.canDelete && confirm("Delete this record?")) runAction('delete', $(this).data('id')); 
});

$("#dueForm").on("submit", function() { $("#customer_id, #sales_mst_id").prop('disabled', false); });
</script>

<?php include 'footer.php'; ?>
</body>
</html>