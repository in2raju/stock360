<?php
session_start();
require 'db.php';

// Logged-in user info
$user      = $_SESSION['user'] ?? null;
$userId    = $user['user_id'] ?? '';
$brCode    = $user['br_code'] ?? '';
$orgCode   = $user['org_code'] ?? '';
$canInsert = $user['can_insert'] ?? 1;

$message = "";

// Handle Due Collection Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $canInsert) {
    $customer_id   = $_POST['customer_id'] ?? '';
    $installment   = $_POST['installment_amount'] ?? 0;
    $payment_date  = $_POST['installment_date'] ?? date('Y-m-d');
    $payment_mode  = $_POST['payment_mode'] ?? 'Cash';
    $sales_mst_id  = $_POST['sales_mst_id'] ?? null;

    // Validation
    if (!$customer_id || !is_numeric($installment) || $installment <= 0) {
        $message = "<div class='alert alert-danger'>Invalid customer or installment amount.</div>";
    } else {
        // Insert installment
        $installmentId = "DIN-" . $brCode . "-" . date('ymdHis') . rand(10,99);
        $stmt = $pdo->prepare("INSERT INTO customer_due_installment
            (installment_id, customer_id, sales_mst_id, installment_amount, installment_date, payment_mode, entry_user, entry_date, org_code, br_code)
            VALUES (:id, :customer, :sales, :amount, :date, :mode, :user, NOW(), :org, :br)
        ");
        $stmt->execute([
            'id' => $installmentId,
            'customer' => $customer_id,
            'sales' => $sales_mst_id,
            'amount' => $installment,
            'date' => $payment_date,
            'mode' => $payment_mode,
            'user' => $userId,
            'org' => $orgCode,
            'br' => $brCode
        ]);
        $message = "<div class='alert alert-success'>Installment Paid Successfully: $installmentId</div>";
    }
}

// Fetch Customers
$stmt = $pdo->prepare("SELECT customer_id, customer_name FROM customer_info 
    WHERE br_code = :br AND org_code = :org AND (delete_status != 'Y' OR delete_status IS NULL)
    ORDER BY customer_name ASC
");
$stmt->execute(['br' => $brCode, 'org' => $orgCode]);
$customers = $stmt->fetchAll();

// Fetch Sales for optional selection
$stmtSales = $pdo->prepare("SELECT sales_mst_id, sales_voucher_ref FROM sales_mst 
    WHERE br_code = :br AND org_code = :org AND authorized_status='Y'
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
<?= $message ?>

<div class="card shadow-sm mb-4">
    <div class="card-body">
        <form method="post">
            <div class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label class="form-label">Customer</label>
                    <select name="customer_id" id="customer_id" class="form-select" required>
                        <option value="">-- Select Customer --</option>
                        <?php foreach ($customers as $c): ?>
                            <option value="<?= $c['customer_id'] ?>"><?= htmlspecialchars($c['customer_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Sale (Optional)</label>
                    <select name="sales_mst_id" id="sales_mst_id" class="form-select">
                        <option value="">-- Against Sale --</option>
                        <?php foreach ($salesList as $s): ?>
                            <option value="<?= $s['sales_mst_id'] ?>"><?= htmlspecialchars($s['sales_voucher_ref']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Current Due</label>
                    <input type="text" id="current_due" class="form-control" value="0.00" readonly>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Installment Amount</label>
                    <input type="number" step="0.01" name="installment_amount" class="form-control" placeholder="0.00" required>
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
                <div class="col-md-2">
                    <button type="submit" class="btn btn-success w-100"><i class="bi bi-save"></i> Save</button>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-body">
        <h5>Installment History</h5>
        <table class="table table-bordered table-striped" id="installment_table">
            <thead class="table-dark">
                <tr>
                    <th>Date</th>
                    <th>Sale Voucher</th>
                    <th>Installment Amount</th>
                    <th>Payment Mode</th>
                    <th>Entry User</th>
                </tr>
            </thead>
            <tbody>
                <!-- Filled dynamically -->
            </tbody>
        </table>
    </div>
</div>

</main>
<?php include 'footer.php'; ?>

<script>
function loadDueAndHistory() {
    var cust = $("#customer_id").val();
    var sale = $("#sales_mst_id").val();

    if(cust){
        // Update current due
        $.get('get_due.php',{customer_id:cust,sales_mst_id:sale}, function(data){
            $("#current_due").val(data);
        });

        // Update installment history
        $.getJSON('get_installment_history.php',{customer_id:cust,sales_mst_id:sale}, function(data){
            var tbody = $("#installment_table tbody");
            tbody.empty();
            if(data.length==0){
                tbody.append('<tr><td colspan="5" class="text-center">No installments found</td></tr>');
            }else{
                data.forEach(function(row){
                    tbody.append('<tr>'+
                        '<td>'+row.installment_date+'</td>'+
                        '<td>'+row.sales_voucher_ref+'</td>'+
                        '<td>'+parseFloat(row.installment_amount).toFixed(2)+'</td>'+
                        '<td>'+row.payment_mode+'</td>'+
                        '<td>'+row.entry_user+'</td>'+
                    '</tr>');
                });
            }
        });
    }else{
        $("#current_due").val("0.00");
        $("#installment_table tbody").html('<tr><td colspan="5" class="text-center">No installments found</td></tr>');
    }
}

$("#customer_id, #sales_mst_id").change(loadDueAndHistory);
</script>

</body>
</html>
