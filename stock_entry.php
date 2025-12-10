<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

$userId    = $_SESSION['user']['user_id'];
$brCode    = $_SESSION['user']['br_code'];
$orgCode   = $_SESSION['user']['org_code'];
$canInsert = $_SESSION['user']['can_insert'] ?? 0;
$canEdit   = $_SESSION['user']['can_edit'] ?? 0;
$canDelete = $_SESSION['user']['can_delete'] ?? 0;
$canApprove = $_SESSION['user']['can_approve'] ?? 0;

function gen_id($prefix = '') {
    $ms = (int)floor(microtime(true) * 1000) % 1000;
    $ts = date('YmdHis') . sprintf('%03d', $ms);
    return $prefix . $ts . rand(100,999);
}

// ---- AJAX endpoints for fetching categories/models ----
if (isset($_GET['fetch_cat'], $_GET['supplier_id'])) {
    header('Content-Type: application/json');
    $stmt = $pdo->prepare("SELECT product_category_id, product_category_name 
                           FROM product_category 
                           WHERE supplier_id = :sup AND br_code = :br 
                           ORDER BY product_category_name");
    $stmt->execute(['sup' => $_GET['supplier_id'], 'br' => $brCode]);
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    exit();
}

if (isset($_GET['fetch_model'], $_GET['product_category_id'])) {
    header('Content-Type: application/json');
    $stmt = $pdo->prepare("SELECT model_id, model_name, price 
                           FROM product_model 
                           WHERE product_category_id = :cat AND br_code = :br 
                           ORDER BY model_name");
    $stmt->execute(['cat' => $_GET['product_category_id'], 'br' => $brCode]);
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    exit();
}

// ---- AJAX endpoint: fetch stock master + details for edit ----
if (isset($_GET['mst_id'])) {
    header('Content-Type: application/json');
    $mstId = $_GET['mst_id'];

    $stmt = $pdo->prepare("SELECT * FROM stock_mst WHERE stock_mst_id=:mst LIMIT 1");
    $stmt->execute(['mst'=>$mstId]);
    $master = $stmt->fetch(PDO::FETCH_ASSOC);

    $stmtDtl = $pdo->prepare("SELECT * FROM stock_dtl WHERE stock_mst_id=:mst AND br_code=:br");
    $stmtDtl->execute(['mst'=>$mstId, 'br'=>$brCode]);
    $details = $stmtDtl->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['master'=>$master,'details'=>$details]);
    exit();
}

// ---- POST: Handle Save/Update stock entry ----
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {

    if ($_POST['action']==='save' && $canInsert) {
        $details     = json_decode($_POST['details_json'], true);
        $discount    = floatval($_POST['total_discount'] ?? 0);
        $payment     = trim($_POST['payment'] ?? '');
        $voucherRef  = trim($_POST['voucher_ref'] ?? '');
        $mstIdEdit   = trim($_POST['mst_id'] ?? '');

        $oldVoucher  = htmlspecialchars($voucherRef);
        $oldDiscount = htmlspecialchars($discount);
        $oldPayment  = htmlspecialchars($payment);

        if (!$voucherRef) {
            $message = "<div class='alert alert-danger'>Please enter Purchase Voucher Number.</div>";
        } elseif (!is_array($details) || count($details) === 0) {
            $message = "<div class='alert alert-danger'>No items to save.</div>";
        } elseif ($payment === '') {
            $message = "<div class='alert alert-danger'>Payment cannot be empty.</div>";
        } else {
            try {
                $pdo->beginTransaction();

                // --- Master Record ---
                if ($mstIdEdit) {
                    $mstId = $mstIdEdit;
                    $pdo->prepare("DELETE FROM stock_dtl WHERE stock_mst_id=:mst AND br_code=:br")
                        ->execute(['mst'=>$mstId,'br'=>$brCode]);
                } else {
                    $mstId = gen_id($brCode.'-');
                    $pdo->prepare("INSERT INTO stock_mst (stock_mst_id, stock_voucher_ref, stock_entry_date, org_code, br_code, sub_total, discount, total_amount, payment, due_amount, entry_user, entry_date) 
                        VALUES (:mst_id,:ref,NOW(),:org,:br,0,0,0,0,0,:user,NOW())")
                        ->execute([
                            'mst_id'=>$mstId,'ref'=>$voucherRef,'org'=>$orgCode,'br'=>$brCode,'user'=>$userId
                        ]);
                }

                // --- Detail Records ---
                $subTotal = 0;
                $stmtDtl = $pdo->prepare("INSERT INTO stock_dtl (stock_dtl_id, stock_mst_id, model_id, product_category_id, supplier_id, price, quantity, total, sub_total, original_price, commission_pct, commission_type, org_code, br_code, distributor_code, entry_user, entry_date)
                    VALUES (:dtl_id,:mst_id,:model,:cat,:sup,:price,:qty,:total,:sub_total,:orig_price,:cpct,:ctype,:org,:br,:dist,:user,NOW())");

                foreach($details as $d){
                    $rowSubTotal   = $d['price'] * $d['quantity'];
                    $commissionAmt = ($d['commission_type']=='PCT') ? ($rowSubTotal * $d['commission_value']/100) : $d['commission_value'];
                    $finalTotal    = $rowSubTotal - $commissionAmt;
                    $dtlId         = gen_id($brCode.'-DTL-');

                    $stmtDtl->execute([
                        'dtl_id'=>$dtlId,
                        'mst_id'=>$mstId,
                        'model'=>$d['model_id'],
                        'cat'=>$d['product_category_id'],
                        'sup'=>$d['supplier_id'],
                        'price'=>$d['price'],
                        'qty'=>$d['quantity'],
                        'total'=>$finalTotal,
                        'sub_total'=>$rowSubTotal,
                        'orig_price'=>$d['price'],
                        'cpct'=>$d['commission_value'],
                        'ctype'=>$d['commission_type'],
                        'org'=>$orgCode,
                        'br'=>$brCode,
                        'dist'=>$d['distributor_code'],
                        'user'=>$userId
                    ]);
                    $subTotal += $finalTotal;
                }

                // --- Update Master totals ---
                $totalAmount = $subTotal - $discount;
                $dueAmount   = $totalAmount - $payment;

                $pdo->prepare("UPDATE stock_mst SET sub_total=:sub, discount=:disc, total_amount=:total, payment=:pay, due_amount=:due WHERE stock_mst_id=:mst_id")
                    ->execute(['sub'=>$subTotal,'disc'=>$discount,'total'=>$totalAmount,'pay'=>$payment,'due'=>$dueAmount,'mst_id'=>$mstId]);

                $pdo->commit();
                $_SESSION['success_msg'] = "Stock entry saved successfully!";
                header("Location: ".$_SERVER['PHP_SELF']);
                exit();

            } catch(Exception $e){
                $pdo->rollBack();
                $message = "<div class='alert alert-danger'>Error: ".htmlspecialchars($e->getMessage())."</div>";
            }
        }
    }

    // ---- DELETE ----
    if ($_POST['action']==='delete' && $canDelete){
        $mstId = $_POST['delete_mst_id'];
        try {
            $pdo->beginTransaction();
            $pdo->prepare("DELETE FROM stock_dtl WHERE stock_mst_id=:mst AND br_code=:br")->execute(['mst'=>$mstId, 'br'=>$brCode]);
            $pdo->prepare("DELETE FROM stock_mst WHERE stock_mst_id=:mst AND br_code=:br")->execute(['mst'=>$mstId, 'br'=>$brCode]);
            $pdo->commit();
            echo "Stock entry deleted successfully!";
        } catch(Exception $e) {
            $pdo->rollBack();
            echo "Error deleting stock entry: ".htmlspecialchars($e->getMessage());
        }
        exit();
    }

    // ---- APPROVE ----
    if ($_POST['action']==='approve'){
        $mstId = $_POST['approve_mst_id'];
        try {
            $stmt = $pdo->prepare("UPDATE stock_mst 
                                   SET authorized_status='Y', authorized_user=:user, authorized_date=NOW() 
                                   WHERE stock_mst_id=:mst AND br_code=:br");
            $stmt->execute(['user'=>$userId,'mst'=>$mstId,'br'=>$brCode]);
            echo "Stock entry approved successfully!";
        } catch(Exception $e) {
            echo "Error approving stock entry: ".htmlspecialchars($e->getMessage());
        }
        exit();
    }
}

// Show success message after redirect
if(!empty($_SESSION['success_msg'])){
    $message = "<div class='alert alert-success text-center'>".$_SESSION['success_msg']."</div>";
    unset($_SESSION['success_msg']);
}

// ---- Fetch suppliers & distributors for selects ----
$stmtSup = $pdo->prepare("SELECT supplier_id, supplier_name FROM supplier WHERE br_code = :br ORDER BY supplier_name");
$stmtSup->execute(['br' => $brCode]);
$suppliers = $stmtSup->fetchAll(PDO::FETCH_ASSOC);

$stmtDist = $pdo->prepare("SELECT DISTRIBUTOR_CODE, DISTRIBUTOR_NAME FROM distributor WHERE ORG_CODE = :org AND DELETE_DATE IS NULL ORDER BY DISTRIBUTOR_NAME");
$stmtDist->execute(['org' => $orgCode]);
$distributors = $stmtDist->fetchAll(PDO::FETCH_ASSOC);

// ---- Fetch all masters (only unapproved) ----
$stmtMstAll = $pdo->prepare("SELECT * FROM stock_mst 
                             WHERE br_code=:br AND (authorized_status IS NULL OR authorized_status='N') 
                             ORDER BY stock_entry_date DESC");
$stmtMstAll->execute(['br'=>$brCode]);
$stockMasters = $stmtMstAll->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Stock Entry - Stock3600</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

</head>
<body class="d-flex flex-column min-vh-100">
<?php include 'header.php'; ?>
<main class="flex-grow-1 container py-4">
<h3>Stock Entry</h3>

<?= $message ?>

<?php if($canInsert): ?>
<div class="card mb-4">
<div class="card-body">
<!-- Distributor / Supplier / Category / Model -->
<div class="row g-2 mb-2">
    <div class="col-md-3">
        <label>Distributor</label>
        <select id="distributor" class="form-select" required>
            <option value="">Select Distributor</option>
            <?php foreach($distributors as $dist): ?>
            <option value="<?= $dist['DISTRIBUTOR_CODE'] ?>"><?= htmlspecialchars($dist['DISTRIBUTOR_NAME']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-md-3">
        <label>Brand</label>
        <select id="supplier" class="form-select" required>
            <option value="">Select</option>
            <?php foreach($suppliers as $sup): ?>
            <option value="<?= $sup['supplier_id'] ?>"><?= htmlspecialchars($sup['supplier_name']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-md-3">
        <label>Category</label>
        <select id="category" class="form-select" required>
            <option value="">Select</option>
        </select>
    </div>
    <div class="col-md-3">
        <label>Model</label>
        <select id="model" class="form-select" required>
            <option value="">Select</option>
        </select>
    </div>
</div>

<!-- Price / Qty / Commission / Type / Voucher -->
<div class="row g-2 mb-2">
    <div class="col-md-2">
        <label>Price</label>
        <input type="number" id="price" class="form-control" step="0.01">
    </div>
    <div class="col-md-1">
        <label>Qty</label>
        <input type="number" id="qty" class="form-control" step="1" min="1">
    </div>
    <div class="col-md-2">
        <label>Commission</label>
        <input type="number" id="commission" class="form-control" step="0.01" value="0">
    </div>
    <div class="col-md-2">
        <label>Type</label>
        <select id="commission_type" class="form-select">
            <option value="PCT">%</option>
            <option value="AMT">Taka</option>
        </select>
    </div>
    <div class="col-md-3">
        <label>Purchase Voucher No.</label>
        <input type="text" id="voucher_ref" class="form-control" required>
    </div>
    <div class="col-md-1 d-grid"> 
        <button class="btn btn-primary" id="addRowBtn">Add</button>
    </div>
</div>

<hr>

<table class="table table-bordered mt-3" id="entryTable">
<thead class="table-dark">
<tr>
<th>Distributor</th><th>Brand</th><th>Category</th><th>Model</th>
<th>Price</th><th>Qty</th><th>Commission</th><th>Type</th><th>Total</th><th>Action</th>
</tr>
</thead>
<tbody></tbody>
</table>

<div class="row mt-3">
<div class="col-md-3 ms-auto">
<label>Sub Total</label>
<input type="number" id="sub_total" class="form-control" readonly>
</div>
</div>

<div class="row mt-2">
<div class="col-md-3 ms-auto">
<label>Total Discount</label>
<input type="number" id="total_discount" class="form-control" step="0.01" value="0">
</div>
</div>

<div class="row mt-2">
<div class="col-md-3 ms-auto">
<label>Total</label>
<input type="number" id="total" class="form-control" readonly>
</div>
</div>

<div class="row mt-2">
<div class="col-md-3 ms-auto">
<label>Payment</label>
<input type="number" id="payment" class="form-control" step="0.01" value="0" required>
</div>
</div>

<div class="row mt-2">
<div class="col-md-3 ms-auto">
<label>Due</label>
<input type="number" id="due" class="form-control" readonly>
</div>
</div>

<div class="text-end mt-3">
<form id="stockForm" method="post">
    <input type="hidden" name="details_json" id="details_json">
    <input type="hidden" name="total_discount" id="hidden_discount">
    <input type="hidden" name="payment" id="hidden_payment">
    <input type="hidden" name="voucher_ref" id="hidden_voucher_ref">
    <input type="hidden" name="mst_id" id="mst_id">
    <input type="hidden" name="action" id="action" value="save">
    <button type="submit" class="btn btn-success" id="saveBtn">Save</button>
</form>
</div>

</div>
</div>
<?php else: ?>
<div class="alert alert-warning">You do not have permission to insert records.</div>
<?php endif; ?>

<!-- Display all master entries -->
<h4>All Stock Entries (Unapproved)</h4>
<table class="table table-striped table-bordered">
<thead class="table-dark">
<tr>
<th>Voucher</th><th>Sub Total</th><th>Discount</th><th>Total</th><th>Payment</th><th>Due</th><th>Date</th><th>Actions</th>
</tr>
</thead>
<tbody>
<?php foreach($stockMasters as $mst): ?>
<tr>
<td><?= htmlspecialchars($mst['stock_voucher_ref']) ?></td>
<td><?= $mst['sub_total'] ?></td>
<td><?= $mst['discount'] ?></td>
<td><?= $mst['total_amount'] ?></td>
<td><?= $mst['payment'] ?></td>
<td><?= $mst['due_amount'] ?></td>
<td><?= $mst['stock_entry_date'] ?></td>
<td>
    <?php if ($canEdit): ?>
        <button class="btn btn-sm btn-primary" onclick="editEntry('<?= $mst['stock_mst_id'] ?>')">
            <i class="bi bi-pencil-square"></i> Edit
        </button>
    <?php endif; ?>

    <?php if ($canApprove): ?>
        <button class="btn btn-sm btn-success" onclick="approveEntry('<?= $mst['stock_mst_id'] ?>')">
            <i class="bi bi-check-circle"></i> Approve
        </button>
    <?php endif; ?>

    <?php if ($canDelete): ?>
        <button class="btn btn-sm btn-danger" onclick="deleteEntry('<?= $mst['stock_mst_id'] ?>')">
            <i class="bi bi-trash"></i> Delete
        </button>
    <?php endif; ?>
</td>


</td>
</tr>
<?php endforeach; ?>
</tbody>
</table>

</main>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
let details = [];

const distributor = document.getElementById('distributor');
const supplier = document.getElementById('supplier');
const category = document.getElementById('category');
const model = document.getElementById('model');

supplier.addEventListener('change', function() {
    fetch(`stock_entry.php?fetch_cat=1&supplier_id=${this.value}`)
        .then(res => res.json())
        .then(data => {
            category.innerHTML = '<option value="">Select</option>';
            data.forEach(c => category.innerHTML += `<option value="${c.product_category_id}">${c.product_category_name}</option>`);
        });
});

category.addEventListener('change', function() {
    fetch(`stock_entry.php?fetch_model=1&product_category_id=${this.value}`)
        .then(res => res.json())
        .then(data => {
            model.innerHTML = '<option value="">Select</option>';
            data.forEach(m => model.innerHTML += `<option value="${m.model_id}" data-price="${m.price}">${m.model_name}</option>`);
        });
});

model.addEventListener('change', function() {
    document.getElementById('price').value = this.selectedOptions[0]?.getAttribute('data-price') || '';
});

document.getElementById('addRowBtn').addEventListener('click', function(e) {
    e.preventDefault();
    const price = parseFloat(document.getElementById('price').value) || 0;
    const qty = parseFloat(document.getElementById('qty').value) || 0;
    const comm = parseFloat(document.getElementById('commission').value) || 0;
    const ctype = document.getElementById('commission_type').value;

    if(!distributor.value || !supplier.value || !category.value || !model.value || qty<=0){
        alert("Please fill all fields correctly.");
        return;
    }

    const rowBase = price*qty;
    const total = ctype==='PCT'? rowBase-(rowBase*comm/100): rowBase-comm;

    const entry = {
        distributor_code: distributor.value,
        distributor_name: distributor.selectedOptions[0].text,
        supplier_id: supplier.value,
        supplier_name: supplier.selectedOptions[0].text,
        product_category_id: category.value,
        product_category_name: category.selectedOptions[0].text,
        model_id: model.value,
        model_name: model.selectedOptions[0].text,
        price: price,
        quantity: qty,
        commission_type: ctype,
        commission_value: comm,
        total: total
    };
    details.push(entry);
    renderTable();

    supplier.value = '';
    category.innerHTML='<option value="">Select</option>';
    model.innerHTML='<option value="">Select</option>';
    document.getElementById('price').value='';
    document.getElementById('qty').value='';
    document.getElementById('commission').value='';
});

function renderTable(){
    const tbody = document.querySelector("#entryTable tbody");
    tbody.innerHTML="";
    let subtotal = 0;
    details.forEach((d,i)=>{
        subtotal+=d.total;
        tbody.innerHTML+=`<tr>
        <td>${d.distributor_name}</td>
        <td>${d.supplier_name}</td>
        <td>${d.product_category_name}</td>
        <td>${d.model_name}</td>
        <td>${d.price.toFixed(2)}</td>
        <td>${d.quantity}</td>
        <td>${d.commission_value}</td>
        <td>${d.commission_type}</td>
        <td>${d.total.toFixed(2)}</td>
        <td><button class="btn btn-sm btn-danger" onclick="removeRow(${i})">X</button></td>
        </tr>`;
    });
    document.getElementById('sub_total').value=subtotal.toFixed(2);
    const disc = parseFloat(document.getElementById('total_discount').value)||0;
    const pay = parseFloat(document.getElementById('payment').value)||0;
    document.getElementById('total').value=(subtotal-disc).toFixed(2);
    document.getElementById('due').value=((subtotal-disc)-pay).toFixed(2);
}

function removeRow(idx){
    details.splice(idx,1);
    renderTable();
}

document.getElementById('total_discount').addEventListener('input', renderTable);
document.getElementById('payment').addEventListener('input', renderTable);

document.getElementById('stockForm').addEventListener('submit', function(e){
    const voucher = document.getElementById('voucher_ref').value.trim();

    if (!voucher) {
        e.preventDefault();
        alert("Please enter Purchase Voucher Number.");
        document.getElementById('voucher_ref').focus();
        return;
    }

    if (details.length === 0) {
        e.preventDefault();
        alert("Please add at least one item.");
        return;
    }

    document.getElementById('details_json').value = JSON.stringify(details);
    document.getElementById('hidden_discount').value = document.getElementById('total_discount').value;
    document.getElementById('hidden_payment').value = document.getElementById('payment').value;
    document.getElementById('hidden_voucher_ref').value = voucher;
    document.getElementById('action').value = 'save';
});

function editEntry(mstId){
    fetch(`stock_entry.php?mst_id=${mstId}`)
    .then(res=>res.json())
    .then(data=>{
        document.getElementById('voucher_ref').value = data.master.stock_voucher_ref;
        document.getElementById('total_discount').value = data.master.discount;
        document.getElementById('payment').value = data.master.payment;
        document.getElementById('mst_id').value = mstId;

        details = data.details.map(d=>({
            distributor_code: d.distributor_code,
            distributor_name: d.distributor_code,
            supplier_id: d.supplier_id,
            supplier_name: d.supplier_id,
            product_category_id: d.product_category_id,
            product_category_name: d.product_category_id,
            model_id: d.model_id,
            model_name: d.model_id,
            price: parseFloat(d.price),
            quantity: parseFloat(d.quantity),
            commission_type: d.commission_type,
            commission_value: parseFloat(d.commission_pct),
            total: parseFloat(d.total)
        }));
        renderTable();
        document.getElementById('saveBtn').textContent='Update';
    });
}

function deleteEntry(mstId){
    if(!confirm("Are you sure you want to delete this stock entry?")) return;
    fetch('stock_entry.php', {
        method: 'POST',
        headers: {'Content-Type':'application/x-www-form-urlencoded'},
        body: 'action=delete&delete_mst_id=' + encodeURIComponent(mstId)
    })
    .then(res => res.text())
    .then(resp => {
        alert(resp);
        location.reload();
    });
}

function approveEntry(mstId){
    if(!confirm("Are you sure you want to approve this entry?")) return;
    fetch('stock_entry.php', {
        method: 'POST',
        headers: {'Content-Type':'application/x-www-form-urlencoded'},
        body: 'action=approve&approve_mst_id=' + encodeURIComponent(mstId)
    })
    .then(res => res.text())
    .then(resp => {
        alert(resp);
        location.reload();
    });
}

</script>
<?php include 'footer.php'; ?>
</body>
</html>
