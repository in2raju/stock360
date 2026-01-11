<?php 
session_start();
require 'db.php';

$user      = $_SESSION['user'] ?? null;
$userId    = $user['user_id'] ?? '';
$brCode    = $user['br_code'] ?? '';
$orgCode   = $user['org_code'] ?? '';
$canInsert = $user['can_insert'] ?? 1;
$canEdit   = $user['can_edit'] ?? 0;
$canDelete = $user['can_delete'] ?? 0;
$canApprove= $user['can_approve'] ?? 0;

/* ==========================
   HANDLE DUE COLLECTION POST
========================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $installment_id  = $_POST['installment_id'] ?? '';
    $customer_id     = $_POST['customer_id'] ?? '';
    $installment     = $_POST['installment_amount'] ?? 0;
    $payment_date    = $_POST['installment_date'] ?? date('Y-m-d');
    $payment_mode    = $_POST['payment_mode'] ?? 'Cash';
    $sales_mst_id    = $_POST['sales_mst_id'] ?: null; // NULL = Previous Due

    // ----------------------------
    // DELETE
    // ----------------------------
    if ($installment_id && isset($_POST['delete']) && $canDelete) {
        $stmt = $pdo->prepare("DELETE FROM customer_due_installment WHERE installment_id=? AND br_code=? AND org_code=?");
        $stmt->execute([$installment_id,$brCode,$orgCode]);
        $_SESSION['msg'] = "<div class='alert alert-success'>Installment deleted successfully.</div>";

    // ----------------------------
    // APPROVE
    // ----------------------------
    } elseif ($installment_id && isset($_POST['approve']) && $canApprove) {
        $stmt = $pdo->prepare("UPDATE customer_due_installment SET authorized_status='Y' WHERE installment_id=? AND br_code=? AND org_code=?");
        $stmt->execute([$installment_id,$brCode,$orgCode]);
        $_SESSION['msg'] = "<div class='alert alert-success'>Installment approved successfully.</div>";

    // ----------------------------
    // INSERT or UPDATE
    // ----------------------------
    } elseif (($canInsert && !$installment_id) || ($canEdit && $installment_id)) {
        if (!$customer_id || !is_numeric($installment) || $installment <= 0) {
            $_SESSION['msg'] = "<div class='alert alert-danger'>Invalid customer or installment amount.</div>";
        } else {
            $installmentId = $installment_id ?: "DIN-" . $brCode . "-" . date('ymdHis') . rand(10,99);
            $stmt = $installment_id ?
                $pdo->prepare("UPDATE customer_due_installment SET customer_id=:cust, sales_mst_id=:sales, installment_amount=:amount, installment_date=:date, payment_mode=:mode WHERE installment_id=:id AND br_code=:br AND org_code=:org") :
                $pdo->prepare("INSERT INTO customer_due_installment (installment_id, customer_id, sales_mst_id, installment_amount, installment_date, payment_mode, entry_user, entry_date, org_code, br_code) VALUES (:id,:cust,:sales,:amount,:date,:mode,:user,NOW(),:org,:br)");

            $stmt->execute([
                'id'     => $installmentId,
                'cust'   => $customer_id,
                'sales'  => $sales_mst_id,
                'amount' => $installment,
                'date'   => $payment_date,
                'mode'   => $payment_mode,
                'user'   => $userId,
                'org'    => $orgCode,
                'br'     => $brCode
            ]);
            $_SESSION['msg'] = "<div class='alert alert-success'>Installment saved successfully. <b>ID:</b> $installmentId</div>";
        }
    }

    header("Location: due_collection.php");
    exit();
}

/* ==========================
   FETCH CUSTOMERS & SALES
========================== */
$stmt = $pdo->prepare("SELECT customer_id, customer_name FROM customer_info WHERE br_code=:br AND org_code=:org AND (delete_status!='Y' OR delete_status IS NULL) ORDER BY customer_name ASC");
$stmt->execute(['br'=>$brCode,'org'=>$orgCode]);
$customers = $stmt->fetchAll();

$stmtSales = $pdo->prepare("SELECT sales_mst_id, sales_voucher_ref FROM sales_mst WHERE br_code=:br AND org_code=:org AND authorized_status='Y' ORDER BY sales_entry_date DESC");
$stmtSales->execute(['br'=>$brCode,'org'=>$orgCode]);
$salesList = $stmtSales->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Customer Due Collection</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
</head>
<body class="d-flex flex-column min-vh-100 bg-light">
<?php include 'header.php'; ?>

<main class="container py-4 flex-grow-1">
<h3><i class="bi bi-cash-coin"></i> Customer Due Collection</h3>

<?php if(!empty($_SESSION['msg'])) { echo $_SESSION['msg']; unset($_SESSION['msg']); } ?>

<div class="card shadow-sm mb-4">
<div class="card-body">
<form method="post" id="dueForm">
<div class="row g-3 align-items-end">

<div class="col-md-4">
<label class="form-label">Customer</label>
<select name="customer_id" id="customer_id" class="form-select" required>
<option value="">-- Select Customer --</option>
<?php foreach($customers as $c): ?>
<option value="<?= $c['customer_id'] ?>"><?= htmlspecialchars($c['customer_name']) ?></option>
<?php endforeach; ?>
</select>
</div>

<div class="col-md-3">
<label class="form-label">Against</label>
<select name="sales_mst_id" id="sales_mst_id" class="form-select">
<option value="">Previous Due</option>
<?php foreach($salesList as $s): ?>
<option value="<?= $s['sales_mst_id'] ?>" data-voucher-id="<?= $s['sales_mst_id'] ?>"><?= htmlspecialchars($s['sales_voucher_ref']) ?></option>
<?php endforeach; ?>
</select>
</div>

<div class="col-md-2">
<label class="form-label">Current Due</label>
<input type="text" id="current_due" class="form-control" value="0.00" readonly>
</div>

<div class="col-md-3">
<label class="form-label">Installment Amount</label>
<input type="number" step="0.01" name="installment_amount" id="installment_amount" class="form-control" required>
</div>

<div class="col-md-2">
<label class="form-label">Date</label>
<input type="date" name="installment_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
</div>

<div class="col-md-2">
<label class="form-label">Payment Mode</label>
<select name="payment_mode" class="form-select">
<option value="Cash">Cash</option>
<option value="Bank">Bank</option>
<option value="Online">Online</option>
</select>
</div>

<input type="hidden" name="installment_id" id="installment_id">

<div class="col-md-2">
<button type="submit" class="btn btn-success w-100"><i class="bi bi-save"></i> Save</button>
</div>

</div>
</form>
</div>
</div>

<!-- Customer Ledger -->
<div class="card shadow-sm">
<div class="card-body">
<h5>Customer Ledger</h5>
<table class="table table-bordered table-striped" id="ledger_table">
<thead class="table-dark">
<tr align="center">
<th>Date</th>
<th>Type</th>
<th>Voucher</th>
<th>Dr</th>
<th>Cr</th>
<th>Balance</th>
<th>Actions</th>
</tr>
</thead>
<tbody></tbody>
</table>
</div>
</div>

</main>
<?php include 'footer.php'; ?>

<script>
// Load current due
function loadCurrentDue(){
    let cust = $("#customer_id").val();
    let sale = $("#sales_mst_id").val();

    if(!cust){
        $("#current_due").val("0.00");
        $("#installment_amount").prop("max",0);
        return;
    }

    $.get('get_due.php',{customer_id:cust, sales_mst_id:sale},function(d){
        $("#current_due").val(d);
        $("#installment_amount").attr("max",parseFloat(d));
    });

    $.get('get_due.php',{customer_id:cust, sales_mst_id:''}, function(prevDue){
        if(parseFloat(prevDue)<=0) $("#sales_mst_id option[value='']").hide();
        else $("#sales_mst_id option[value='']").show();
    });

    $("#sales_mst_id option[data-voucher-id]").each(function(){
        let voucherId = $(this).data('voucher-id');
        $.get('get_due.php',{customer_id:cust,sales_mst_id:voucherId},function(vdue){
            if(parseFloat(vdue)<=0) $(`#sales_mst_id option[value='${voucherId}']`).hide();
            else $(`#sales_mst_id option[value='${voucherId}']`).show();
        });
    });
}

// Load ledger with actions
function loadLedger(){
    let cust = $("#customer_id").val();
    if(!cust){ $("#ledger_table tbody").html('<tr><td colspan="7" class="text-center">No data</td></tr>'); return; }

    $.getJSON('get_due_ledger.php',{customer_id:cust}, function(rows){
        let tbody = $("#ledger_table tbody").empty();
        if(rows.length===0){ tbody.append('<tr><td colspan="7" class="text-center">No ledger found</td></tr>'); return; }

        rows.forEach(r=>{
            let actionHtml = '';
            if(r.type==='Installment Paid'){

                <?php if($canEdit): ?>
                actionHtml += `<button class="btn btn-sm btn-primary edit-btn me-1" data-id="${r.id}" data-cust="${r.customer_id}" data-sales="${r.sales_mst_id}" data-amount="${r.installment_amount}" data-date="${r.installment_date}" data-mode="${r.payment_mode}"><i class="bi bi-pencil-square"></i> Edit</button>`;
                <?php endif; ?>

                <?php if($canApprove): ?>
                actionHtml += `<button class="btn btn-sm btn-success approve-btn me-1" data-id="${r.id}"><i class="bi bi-check-circle"></i> Approve</button>`;
                <?php endif; ?>

                <?php if($canDelete): ?>
                actionHtml += `<button class="btn btn-sm btn-danger delete-btn me-1" data-id="${r.id}"><i class="bi bi-trash"></i> Delete</button>`;
                <?php endif; ?>
            }

            tbody.append(`<tr>
                <td>${r.entry_date}</td>
                <td>${r.type}</td>
                <td>${r.sales_voucher_ref ?? ''}</td>
                <td class="text-end">${r.dr||''}</td>
                <td class="text-end">${r.cr||''}</td>
                <td class="text-end fw-bold">${r.balance}</td>
                <td class="text-center">${actionHtml}</td>
            </tr>`);
        });
    });
}

// Edit
$(document).on('click','.edit-btn', function(){
    $("#installment_id").val($(this).data('id'));
    $("#customer_id").val($(this).data('cust')).change();
    $("#sales_mst_id").val($(this).data('sales'));
    $("#installment_amount").val($(this).data('amount'));
    $("input[name='installment_date']").val($(this).data('date'));
    $("select[name='payment_mode']").val($(this).data('mode'));
});

// Delete
$(document).on('click','.delete-btn', function(){
    if(confirm('Delete this installment?')){
        $('<form method="post"><input type="hidden" name="installment_id" value="'+$(this).data('id')+'"><input type="hidden" name="delete" value="1"></form>').appendTo('body').submit();
    }
});

// Approve
$(document).on('click','.approve-btn', function(){
    if(confirm('Approve this installment?')){
        $('<form method="post"><input type="hidden" name="installment_id" value="'+$(this).data('id')+'"><input type="hidden" name="approve" value="1"></form>').appendTo('body').submit();
    }
});

// Validate
$("form").on("submit",function(e){
    let currentDue = parseFloat($("#current_due").val()) || 0;
    let installment = parseFloat($("#installment_amount").val()) || 0;
    if(installment>currentDue){ alert("Installment cannot exceed current due!"); e.preventDefault(); }
});

// Trigger load on change
$("#customer_id,#sales_mst_id").change(function(){ loadCurrentDue(); loadLedger(); });
</script>

</body>
</html>
