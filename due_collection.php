<?php
session_start();
require 'db.php';

$user      = $_SESSION['user'] ?? null;
$userId    = $user['user_id'] ?? '';
$brCode    = $user['br_code'] ?? '';
$orgCode   = $user['org_code'] ?? '';
$canInsert = $user['can_insert'] ?? 1;

/* ==========================
   HANDLE DUE COLLECTION POST
========================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $canInsert) {

    $customer_id   = $_POST['customer_id'] ?? '';
    $installment   = $_POST['installment_amount'] ?? 0;
    $payment_date  = $_POST['installment_date'] ?? date('Y-m-d');
    $payment_mode  = $_POST['payment_mode'] ?? 'Cash';
    $sales_mst_id  = $_POST['sales_mst_id'] ?: null; // NULL = Previous Due

    if (!$customer_id || !is_numeric($installment) || $installment <= 0) {
        $_SESSION['msg'] = "<div class='alert alert-danger'>Invalid customer or installment amount.</div>";
    } else {
        // ----------------------------
        // Calculate current due
        // ----------------------------
        $stmtDue = $pdo->prepare("
            SELECT 
            COALESCE((SELECT previous_due_amount FROM customer_previous_due WHERE customer_id=? AND br_code=? AND org_code=?),0) +
            COALESCE((SELECT due_amount FROM sales_mst WHERE sales_mst_id=? AND br_code=? AND org_code=?),0) -
            COALESCE((SELECT SUM(installment_amount) FROM customer_due_installment WHERE customer_id=? AND sales_mst_id=? AND br_code=? AND org_code=?),0)
        ");
        $stmtDue->execute([
            $customer_id, $brCode, $orgCode,
            $sales_mst_id, $brCode, $orgCode,
            $customer_id, $sales_mst_id, $brCode, $orgCode
        ]);
        $currentDue = $stmtDue->fetchColumn() ?: 0;

        if($installment > $currentDue){
            $_SESSION['msg'] = "<div class='alert alert-danger'>Installment cannot be greater than current due ($currentDue)</div>";
            header("Location: due_collection.php");
            exit();
        }

        $installmentId = "DIN-" . $brCode . "-" . date('ymdHis') . rand(10,99);

        $stmt = $pdo->prepare("
            INSERT INTO customer_due_installment
            (installment_id, customer_id, sales_mst_id, installment_amount,
             installment_date, payment_mode, entry_user, entry_date, org_code, br_code)
            VALUES
            (:id, :customer, :sales, :amount, :date, :mode, :user, NOW(), :org, :br)
        ");

        $stmt->execute([
            'id'       => $installmentId,
            'customer' => $customer_id,
            'sales'    => $sales_mst_id,
            'amount'   => $installment,
            'date'     => $payment_date,
            'mode'     => $payment_mode,
            'user'     => $userId,
            'org'      => $orgCode,
            'br'       => $brCode
        ]);

        $_SESSION['msg'] = "<div class='alert alert-success'>Installment paid successfully. <b>ID:</b> $installmentId</div>";
    }

    header("Location: due_collection.php");
    exit();
}

/* ==========================
   FETCH CUSTOMERS
========================== */
$stmt = $pdo->prepare("
    SELECT customer_id, customer_name
    FROM customer_info
    WHERE br_code = :br AND org_code = :org
      AND (delete_status != 'Y' OR delete_status IS NULL)
    ORDER BY customer_name ASC
");
$stmt->execute(['br' => $brCode, 'org' => $orgCode]);
$customers = $stmt->fetchAll();

/* ==========================
   FETCH SALES (Vouchers)
========================== */
$stmtSales = $pdo->prepare("
    SELECT sales_mst_id, sales_voucher_ref
    FROM sales_mst
    WHERE br_code = :br AND org_code = :org
      AND authorized_status='Y'
    ORDER BY sales_entry_date DESC
");
$stmtSales->execute(['br' => $brCode, 'org' => $orgCode]);
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

<!-- Customer -->
<div class="col-md-4">
<label class="form-label">Customer</label>
<select name="customer_id" id="customer_id" class="form-select" required>
<option value="">-- Select Customer --</option>
<?php foreach ($customers as $c): ?>
<option value="<?= $c['customer_id'] ?>"><?= htmlspecialchars($c['customer_name']) ?></option>
<?php endforeach; ?>
</select>
</div>

<!-- Voucher / Previous Due -->
<div class="col-md-3">
<label class="form-label">Against</label>
<select name="sales_mst_id" id="sales_mst_id" class="form-select">
<option value="">Previous Due</option>
<?php foreach ($salesList as $s): ?>
<option value="<?= $s['sales_mst_id'] ?>" data-voucher-id="<?= $s['sales_mst_id'] ?>"><?= htmlspecialchars($s['sales_voucher_ref']) ?></option>
<?php endforeach; ?>
</select>
</div>

<!-- Current Due -->
<div class="col-md-2">
<label class="form-label">Current Due</label>
<input type="text" id="current_due" class="form-control" value="0.00" readonly>
</div>

<!-- Installment Amount -->
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

<!-- Customer Ledger -->
<div class="card shadow-sm">
<div class="card-body">
<h5>Customer Ledger</h5>
<table class="table table-bordered table-striped" id="ledger_table">
<thead class="table-dark">
<tr>
<th>Date</th>
<th>Type</th>
<th>Voucher</th>
<th>Dr</th>
<th>Cr</th>
<th>Balance</th>
</tr>
</thead>
<tbody></tbody>
</table>
</div>
</div>

</main>
<?php include 'footer.php'; ?>

<script>
// Load current due for selected customer / voucher
function loadCurrentDue(){
    let cust = $("#customer_id").val();
    let sale = $("#sales_mst_id").val();

    if(!cust){
        $("#current_due").val("0.00");
        $("#installment_amount").prop("max", 0);
        return;
    }

    // Get current due for selected voucher / previous due
    $.get('get_due.php',{customer_id:cust, sales_mst_id:sale}, function(d){
        $("#current_due").val(d);
        $("#installment_amount").attr("max", parseFloat(d));
    });

    // Hide Previous Due if zero
    $.get('get_due.php',{customer_id:cust, sales_mst_id:''}, function(prevDue){
        if(parseFloat(prevDue) <= 0){
            $("#sales_mst_id option[value='']").hide();
        } else {
            $("#sales_mst_id option[value='']").show();
        }
    });

    // Hide vouchers with 0 due dynamically
    $("#sales_mst_id option[data-voucher-id]").each(function(){
        let voucherId = $(this).data('voucher-id');
        $.get('get_due.php',{customer_id:cust, sales_mst_id:voucherId}, function(vdue){
            if(parseFloat(vdue) <= 0){
                $(`#sales_mst_id option[value='${voucherId}']`).hide();
            } else {
                $(`#sales_mst_id option[value='${voucherId}']`).show();
            }
        });
    });
}

// Load ledger with Dr/Cr balance
function loadLedger(){
    let cust = $("#customer_id").val();
    if(!cust){
        $("#ledger_table tbody").html('<tr><td colspan="6" class="text-center">No data</td></tr>');
        return;
    }

    $.getJSON('get_due_ledger.php',{customer_id:cust}, function(rows){
        let tbody = $("#ledger_table tbody").empty();
        if(rows.length === 0){
            tbody.append('<tr><td colspan="6" class="text-center">No ledger found</td></tr>');
        } else {
            rows.forEach(r=>{
                tbody.append(`<tr>
                    <td>${r.entry_date}</td>
                    <td>${r.type}</td>
                    <td>${r.sales_voucher_ref ?? ''}</td>
                    <td class="text-end">${r.dr || ''}</td>
                    <td class="text-end">${r.cr || ''}</td>
                    <td class="text-end">${r.balance}</td>
                </tr>`);
            });
        }
    });
}

// Front-end validation: installment <= current due
$("form").on("submit", function(e){
    let currentDue = parseFloat($("#current_due").val()) || 0;
    let installment = parseFloat($("#installment_amount").val()) || 0;

    if(installment > currentDue){
        alert("Installment Amount cannot be greater than Current Due!");
        e.preventDefault();
        return false;
    }
});

// Trigger load on change
$("#customer_id,#sales_mst_id").change(function(){
    loadCurrentDue();
    loadLedger();
});
</script>

</body>
</html>
