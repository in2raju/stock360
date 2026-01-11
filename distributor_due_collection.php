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

/* ==========================
   HANDLE DUE COLLECTION POST
========================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $canInsert) {

    $distributor_code = $_POST['distributor_code'] ?? '';
    $installment      = $_POST['installment_amount'] ?? 0;
    $payment_date     = $_POST['installment_date'] ?? date('Y-m-d');
    $payment_mode     = $_POST['payment_mode'] ?? 'Cash';
    $stock_mst_id     = $_POST['stock_mst_id'] ?: null; // NULL = Previous Due

    if (!$distributor_code || !is_numeric($installment) || $installment <= 0) {
        $_SESSION['msg'] = "<div class='alert alert-danger'>Invalid distributor or installment amount.</div>";
    } else {

        // ----------------------------
        // Calculate current due
        // ----------------------------
        $stmtDue = $pdo->prepare("
            SELECT 
            COALESCE((SELECT due_amount FROM distributor_previous_due WHERE distributor_code=? AND br_code=? AND org_code=?),0)
            +
            COALESCE((SELECT due_amount FROM stock_mst WHERE stock_mst_id=? AND br_code=? AND org_code=?),0)
            -
            COALESCE((SELECT SUM(installment_amount) FROM distributor_due_installment WHERE distributor_code=? AND stock_mst_id=? AND br_code=? AND org_code=?),0)
        ");
        $stmtDue->execute([
            $distributor_code, $brCode, $orgCode,
            $stock_mst_id, $brCode, $orgCode,
            $distributor_code, $stock_mst_id, $brCode, $orgCode
        ]);

        $currentDue = $stmtDue->fetchColumn() ?: 0;

        if($installment > $currentDue){
            $_SESSION['msg'] = "<div class='alert alert-danger'>Installment cannot be greater than current due ($currentDue)</div>";
            header("Location: distributor_due_collection.php");
            exit();
        }

        // ----------------------------
        // Insert installment
        // ----------------------------
        $installmentId = "DDI-" . $brCode . "-" . date('ymdHis') . rand(10,99);

        $stmt = $pdo->prepare("
            INSERT INTO distributor_due_installment
            (installment_id, distributor_code, stock_mst_id,
             installment_amount, installment_date, payment_mode,
             entry_user, entry_date, org_code, br_code)
            VALUES
            (:id, :dist, :stock, :amount, :date, :mode, :user, NOW(), :org, :br)
        ");

        $stmt->execute([
            'id'     => $installmentId,
            'dist'   => $distributor_code,
            'stock'  => $stock_mst_id,
            'amount' => $installment,
            'date'   => $payment_date,
            'mode'   => $payment_mode,
            'user'   => $userId,
            'org'    => $orgCode,
            'br'     => $brCode
        ]);

        $_SESSION['msg'] = "<div class='alert alert-success'>Installment paid successfully. <b>ID:</b> $installmentId</div>";
    }

    header("Location: distributor_due_collection.php");
    exit();
}

/* ==========================
   FETCH DISTRIBUTORS
========================== */
$stmt = $pdo->prepare("
    SELECT distributor_code, distributor_name
    FROM distributor
    WHERE br_code=:br AND org_code=:org
    ORDER BY distributor_name ASC
");
$stmt->execute(['br'=>$brCode,'org'=>$orgCode]);
$distributors = $stmt->fetchAll();

/* ==========================
   FETCH STOCK (Vouchers)
========================== */
$stmtStock = $pdo->prepare("
    SELECT stock_mst_id, stock_voucher_ref
    FROM stock_mst
    WHERE br_code=:br AND org_code=:org
    ORDER BY stock_entry_date DESC
");
$stmtStock->execute(['br'=>$brCode,'org'=>$orgCode]);
$stockList = $stmtStock->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Distributor Due Payment</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
</head>

<body class="d-flex flex-column min-vh-100 bg-light">
<?php include 'header.php'; ?>

<main class="container py-4 flex-grow-1">

<h3><i class="bi bi-cash-stack"></i> Distributor Due Payment</h3>

<?php
if (!empty($_SESSION['msg'])) {
    echo $_SESSION['msg'];
    unset($_SESSION['msg']);
}
?>

<div class="card shadow-sm mb-4">
<div class="card-body">
<form method="post">
<div class="row g-3 align-items-end">

<!-- Distributor -->
<div class="col-md-4">
<label class="form-label">Distributor</label>
<select name="distributor_code" id="distributor_code" class="form-select" required>
<option value="">-- Select Distributor --</option>
<?php foreach($distributors as $d): ?>
<option value="<?= $d['distributor_code'] ?>"><?= htmlspecialchars($d['distributor_name']) ?></option>
<?php endforeach; ?>
</select>
</div>

<!-- Voucher / Previous Due -->
<div class="col-md-3">
<label class="form-label">Against</label>
<select name="stock_mst_id" id="stock_mst_id" class="form-select">
<option value="">Previous Due</option>
<?php foreach($stockList as $s): ?>
<option value="<?= $s['stock_mst_id'] ?>" data-stock-id="<?= $s['stock_mst_id'] ?>"><?= htmlspecialchars($s['stock_voucher_ref']) ?></option>
<?php endforeach; ?>
</select>
</div>

<!-- Current Due -->
<div class="col-md-2">
<label class="form-label">Current Due</label>
<input type="text" id="current_due" class="form-control" value="0.00" readonly>
</div>

<!-- Installment -->
<div class="col-md-3">
<label class="form-label">Installment Amount</label>
<input type="number" step="0.01" name="installment_amount" id="installment_amount" class="form-control" required>
</div>

<!-- Date -->
<div class="col-md-2">
<label class="form-label">Date</label>
<input type="date" name="installment_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
</div>

<!-- Payment Mode -->
<div class="col-md-2">
<label class="form-label">Payment Mode</label>
<select name="payment_mode" class="form-select">
<option value="Cash">Cash</option>
<option value="Bank">Bank</option>
<option value="Online">Online</option>
</select>
</div>

<!-- Submit -->
<div class="col-md-2">
<button type="submit" class="btn btn-success w-100">
<i class="bi bi-save"></i> Save
</button>
</div>

</div>
</form>
</div>
</div>

<!-- Distributor Ledger -->
<div class="card shadow-sm">
    <div class="card-body">
        <h5>Distributor Ledger</h5>
        <table class="table table-bordered table-striped align-middle" id="ledger_table">
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
    let dist = $("#distributor_code").val();
    let stock = $("#stock_mst_id").val();

    if(!dist){
        $("#current_due").val("0.00");
        $("#installment_amount").prop("max",0);
        return;
    }

    $.get('get_distributor_due.php',{distributor_code:dist, stock_mst_id:stock}, function(d){
        $("#current_due").val(d);
        $("#installment_amount").attr("max", parseFloat(d));
    });

    $.get('get_distributor_due.php',{distributor_code:dist, stock_mst_id:''}, function(prevDue){
        if(parseFloat(prevDue) <= 0) $("#stock_mst_id option[value='']").hide();
        else $("#stock_mst_id option[value='']").show();
    });

    $("#stock_mst_id option[data-stock-id]").each(function(){
        let stockId = $(this).data('stock-id');
        $.get('get_distributor_due.php',{distributor_code:dist, stock_mst_id:stockId}, function(vdue){
            if(parseFloat(vdue) <= 0) $(`#stock_mst_id option[value='${stockId}']`).hide();
            else $(`#stock_mst_id option[value='${stockId}']`).show();
        });
    });
}

// Load ledger with permissions
function loadLedger() {
    let dist = $("#distributor_code").val();
    if (!dist) {
        $("#ledger_table tbody").html('<tr><td colspan="7" class="text-center">No data</td></tr>');
        return;
    }

    $.getJSON('get_distributor_due_ledger.php', { distributor_code: dist }, function(rows) {
        let tbody = $("#ledger_table tbody").empty();
        if (rows.length === 0) {
            tbody.append('<tr><td colspan="7" class="text-center">No ledger found</td></tr>');
        } else {
            rows.forEach(r => {
                let actionHtml = '';
                if(r.type === 'Payment to Distributor') {

    <?php if($canEdit): ?>
    actionHtml += `<button class="btn btn-sm btn-primary edit-btn me-1" data-id="${r.id}" data-dist="${r.distributor_code}" data-stock="${r.stock_mst_id}" data-amount="${r.installment_amount}" data-date="${r.installment_date}" data-mode="${r.payment_mode}"><i class="bi bi-pencil-square"></i> Edit</button>`;
    <?php endif; ?>

    <?php if($canApprove): ?>
    actionHtml += `<button class="btn btn-sm btn-success approve-btn me-1" data-id="${r.id}"><i class="bi bi-check-circle"></i> Approve</button>`;
    <?php endif; ?>

    <?php if($canDelete): ?>
    actionHtml += `<button class="btn btn-sm btn-danger delete-btn" data-id="${r.id}"><i class="bi bi-trash"></i> Delete</button>`;
    <?php endif; ?>
}

                tbody.append(`
                    <tr align="center">
                        <td>${r.entry_date}</td>
                        <td align="left">${r.type}</td>
                        <td>${r.stock_voucher_ref ?? ''}</td>
                        <td class="text-end">${r.dr || ''}</td>
                        <td class="text-end">${r.cr || ''}</td>
                        <td class="text-end fw-bold">${r.balance}</td>
                        <td>${actionHtml}</td>
                    </tr>`);
            });
        }
    });
}

// Front-end validation
$("form").on("submit", function(e){
    let currentDue = parseFloat($("#current_due").val()) || 0;
    let installment = parseFloat($("#installment_amount").val()) || 0;
    if(installment > currentDue){
        alert("Installment Amount cannot be greater than Current Due!");
        e.preventDefault();
    }
});

// Trigger load on change
$("#distributor_code,#stock_mst_id").change(function(){
    loadCurrentDue();
    loadLedger();
});
</script>

</body>
</html>
