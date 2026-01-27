<?php
// stock_entry.php
session_start();
require 'db.php';

/* ================= USER CONTEXT ================= */
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

$userId     = $_SESSION['user']['user_id'];
$brCode     = $_SESSION['user']['br_code'];
$orgCode    = $_SESSION['user']['org_code'];
$canInsert  = $_SESSION['user']['can_insert'] ?? 0;
$canEdit    = $_SESSION['user']['can_edit'] ?? 0;
$canDelete  = $_SESSION['user']['can_delete'] ?? 0;
$canApprove = $_SESSION['user']['can_approve'] ?? 0;

/* ================= HELPERS ================= */
function gen_id($prefix=''){
    return $prefix . date('YmdHis') . rand(100,999);
}

function gen_unique_stock_master_id($pdo,$brCode){
    $date = date('Ymd');
    do {
        $id = "{$brCode}-STK-{$date}-".str_pad(rand(0,999999),6,'0',STR_PAD_LEFT);
        $chk = $pdo->prepare("SELECT 1 FROM stock_mst WHERE stock_mst_id=?");
        $chk->execute([$id]);
    } while ($chk->fetchColumn());
    return $id;
}

/* ================= AJAX: CATEGORY ================= */
if(isset($_GET['fetch_cat'],$_GET['supplier_id'])){
    header('Content-Type: application/json');
    $stmt=$pdo->prepare("SELECT product_category_id,product_category_name 
                         FROM product_category 
                         WHERE supplier_id=? AND br_code=?");
    $stmt->execute([$_GET['supplier_id'],$brCode]);
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    exit;
}

/* ================= AJAX: MODEL ================= */
if(isset($_GET['fetch_model'],$_GET['product_category_id'])){
    header('Content-Type: application/json');
    $stmt=$pdo->prepare("SELECT model_id,model_name,price 
                         FROM product_model 
                         WHERE product_category_id=? AND br_code=?");
    $stmt->execute([$_GET['product_category_id'],$brCode]);
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    exit;
}

/* ================= AJAX: EDIT FETCH ================= */
if(isset($_GET['mst_id'])){
    header('Content-Type: application/json');
    $mstId=$_GET['mst_id'];

    $mst=$pdo->prepare("SELECT sm.*,d.DISTRIBUTOR_NAME
                        FROM stock_mst sm
                        LEFT JOIN distributor d 
                          ON sm.distributor_code=d.DISTRIBUTOR_CODE
                        WHERE sm.stock_mst_id=? AND sm.br_code=?");
    $mst->execute([$mstId,$brCode]);
    $master=$mst->fetch(PDO::FETCH_ASSOC);

    $dtl=$pdo->prepare("SELECT sd.*, 
                               s.supplier_name,
                               pc.product_category_name,
                               pm.model_name
                        FROM stock_dtl sd
                        LEFT JOIN supplier s 
                          ON sd.supplier_id=s.supplier_id
                        LEFT JOIN product_category pc 
                          ON sd.product_category_id=pc.product_category_id
                        LEFT JOIN product_model pm 
                          ON sd.model_id=pm.model_id
                        WHERE sd.stock_mst_id=? AND sd.br_code=?");
    $dtl->execute([$mstId,$brCode]);

    $details=[];
    foreach($dtl as $d){
        $details[]=[
            'distributor_code'=>$master['distributor_code'],
            'supplier_id'=>$d['supplier_id'],
            'supplier_name'=>$d['supplier_name'] ?? 'N/A',
            'product_category_id'=>$d['product_category_id'],
            'product_category_name'=>$d['product_category_name'] ?? 'N/A',
            'model_id'=>$d['model_id'],
            'model_name'=>$d['model_name'] ?? 'N/A',
            'price'=>(float)$d['price'],
            'quantity'=>(float)$d['quantity'],
            'commission_type'=>$d['commission_type'],
            'commission_value'=>(float)$d['commission_pct'],
            'total'=>(float)$d['total']
        ];
    }

    echo json_encode(['master'=>$master,'details'=>$details]);
    exit;
}

/* ================= ACTION: APPROVE ================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'approve' && $canApprove) {
    header('Content-Type: text/plain');
    $mstId = $_POST['approve_mst_id'] ?? '';
    try {
        $stmtCheck = $pdo->prepare("SELECT authorized_status FROM stock_mst WHERE stock_mst_id = :mst AND br_code = :br LIMIT 1");
        $stmtCheck->execute(['mst'=>$mstId,'br'=>$brCode]);
        if ($stmtCheck->fetchColumn() === 'Y') {
             throw new Exception("Stock entry is already approved.");
        }
        $stmt = $pdo->prepare("UPDATE stock_mst SET authorized_status = 'Y', authorized_user = :user, authorized_date = NOW() WHERE stock_mst_id = :mst AND br_code = :br");
        $stmt->execute(['user'=>$userId, 'mst'=>$mstId, 'br'=>$brCode]);
        echo "Stock entry approved successfully!";
    } catch (Exception $e) {
        echo "Error approving stock entry: " . htmlspecialchars($e->getMessage());
    }
    exit();
}

/* ================= ACTION: DELETE ================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete' && $canDelete) {
    header('Content-Type: text/plain');
    $mstId = $_POST['delete_mst_id'] ?? '';
    try {
        $pdo->beginTransaction();
        // Check if approved
        $chk = $pdo->prepare("SELECT authorized_status FROM stock_mst WHERE stock_mst_id=?");
        $chk->execute([$mstId]);
        if($chk->fetchColumn() === 'Y') throw new Exception("Cannot delete an approved entry.");

        $pdo->prepare("DELETE FROM stock_dtl WHERE stock_mst_id=? AND br_code=?")->execute([$mstId, $brCode]);
        $pdo->prepare("DELETE FROM stock_mst WHERE stock_mst_id=? AND br_code=?")->execute([$mstId, $brCode]);
        
        $pdo->commit();
        echo "Stock entry deleted successfully!";
    } catch (Exception $e) {
        $pdo->rollBack();
        echo "Error: " . $e->getMessage();
    }
    exit();
}

/* ================= SAVE / UPDATE ================= */
$message='';
if($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['action']) && $_POST['action']==='save' && $canInsert){

    $details=json_decode($_POST['details_json'],true);
    $voucher=trim($_POST['voucher_ref']);
    $discount=(float)$_POST['total_discount'];
    $payment=(float)$_POST['payment'];
    $dist=$_POST['distributor_code'];
    $mstEdit=$_POST['mst_id'];

    if(!$voucher || !$dist || empty($details)){
        $message="<div class='alert alert-danger'>Missing required data</div>";
    } else {
        try{
            $pdo->beginTransaction();

            if($mstEdit){
                $chk=$pdo->prepare("SELECT authorized_status FROM stock_mst WHERE stock_mst_id=?");
                $chk->execute([$mstEdit]);
                if($chk->fetchColumn()==='Y'){
                    throw new Exception("Approved entry cannot be edited");
                }

                $pdo->prepare("DELETE FROM stock_dtl WHERE stock_mst_id=? AND br_code=?")
                    ->execute([$mstEdit,$brCode]);

                $pdo->prepare("UPDATE stock_mst 
                               SET stock_voucher_ref=?, distributor_code=?, edit_user=?, edit_date=NOW()
                               WHERE stock_mst_id=? AND br_code=?")
                    ->execute([$voucher,$dist,$userId,$mstEdit,$brCode]);
                $mstId=$mstEdit;
            } else {
                $mstId=gen_unique_stock_master_id($pdo,$brCode);
                $pdo->prepare("INSERT INTO stock_mst
                    (stock_mst_id,stock_voucher_ref,stock_entry_date,org_code,br_code,
                     sub_total,discount,total_amount,payment,due_amount,entry_user,entry_date,distributor_code)
                    VALUES (?,?,NOW(),?,?,0,0,0,0,0,?,NOW(),?)")
                ->execute([$mstId,$voucher,$orgCode,$brCode,$userId,$dist]);
            }

            $sub=0;
            $ins = $pdo->prepare("
                INSERT INTO stock_dtl
                (stock_dtl_id, stock_mst_id, model_id, product_category_id, supplier_id, price, quantity, total, sub_total, commission_pct, commission_type, org_code, br_code, entry_user, entry_date)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ");

            foreach($details as $d){
                $row=$d['price']*$d['quantity'];
                $comm=($d['commission_type']=='PCT') ? ($row*$d['commission_value']/100) : $d['commission_value'];
                $total=$row-$comm;
                $sub+=$total;

                $ins->execute([
                    gen_id($brCode.'-DTL-'), 
                    $mstId, 
                    $d['model_id'], 
                    $d['product_category_id'], 
                    $d['supplier_id'], 
                    $d['price'], 
                    $d['quantity'], 
                    $total, 
                    $row, 
                    $d['commission_value'], 
                    $d['commission_type'], 
                    $orgCode, 
                    $brCode, 
                    $userId
                ]);
            }

            $net=$sub-$discount;
            $due=$net-$payment;

            $pdo->prepare("UPDATE stock_mst 
                           SET sub_total=?,discount=?,total_amount=?,payment=?,due_amount=?
                           WHERE stock_mst_id=? AND br_code=?")
                ->execute([$sub,$discount,$net,$payment,$due,$mstId,$brCode]);

            $pdo->commit();
            $_SESSION['success_msg']="Stock entry saved successfully!";
            header("Location: stock_entry.php");
            exit;

        } catch(Exception $e){
            $pdo->rollBack();
            $message="<div class='alert alert-danger'>{$e->getMessage()}</div>";
        }
    }
}

/* ================= SUCCESS MSG ================= */
if(isset($_SESSION['success_msg'])){
    $message="<div class='alert alert-success text-center'>{$_SESSION['success_msg']}</div>";
    unset($_SESSION['success_msg']);
}

/* ================= DROPDOWNS ================= */
$suppliers=$pdo->query("SELECT supplier_id,supplier_name FROM supplier WHERE br_code='$brCode'")->fetchAll();
$distributors=$pdo->query("SELECT DISTRIBUTOR_CODE,DISTRIBUTOR_NAME FROM distributor WHERE ORG_CODE='$orgCode'")->fetchAll();

$stockMasters=$pdo->query("SELECT sm.*,d.DISTRIBUTOR_NAME 
                           FROM stock_mst sm
                           LEFT JOIN distributor d ON sm.distributor_code=d.DISTRIBUTOR_CODE
                           WHERE sm.br_code='$brCode'
                           AND (sm.authorized_status IS NULL OR sm.authorized_status='N')
                           ORDER BY sm.stock_entry_date DESC")->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Stock Entry - Stock3600</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</head>
<body class="d-flex flex-column min-vh-100">
    <?php include 'header.php'; ?>
<?php // include 'header.php'; ?> 

<main class="flex-grow-1 container py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3><i class="bi bi-box-seam"></i> Stock Entry (Purchase)</h3>
    </div>

    <?= $message ?>

    <?php if($canInsert): ?>
    <div class="card mb-4 shadow-sm">
        
        <div class="card-body">
            <input type="hidden" id="mst_id_edit" name="mst_id_edit" value="">

            <div class="row g-2 mb-3 border p-3 rounded bg-light">
                <div class="col-md-4">
                    <label class="form-label">Distributor <span class="text-danger">*</span></label>
                    <select id="distributor" class="form-select" required>
                        <option value="">Select Distributor</option>
                        <?php foreach($distributors as $dist): ?>
                        <option value="<?= htmlspecialchars($dist['DISTRIBUTOR_CODE']) ?>"><?= htmlspecialchars($dist['DISTRIBUTOR_NAME']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Purchase Voucher No. <span class="text-danger">*</span></label>
                    <input type="text" id="voucher_ref" class="form-control" required>
                </div>
            </div>
            
            <hr>
            
            <h5 class="mt-3"><i class="bi bi-cart-plus"></i> Add Product</h5>
            <div class="row g-2 mb-2">
                <div class="col-md-3">
                    <label class="form-label">Brand</label>
                    <select id="supplier" class="form-select" required>
                        <option value="">Select</option>
                        <?php foreach($suppliers as $sup): ?>
                        <option value="<?= htmlspecialchars($sup['supplier_id']) ?>"><?= htmlspecialchars($sup['supplier_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Category</label>
                    <select id="category" class="form-select" required>
                        <option value="">Select</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Model</label>
                    <select id="model" class="form-select" required>
                        <option value="">Select</option>
                    </select>
                </div>
            </div>

            <div class="row g-2 mb-3">
                <div class="col-md-2">
                    <label class="form-label">Price</label>
                    <input type="number" id="price" class="form-control" step="0.01" min="0" value="0">
                </div>
                <div class="col-md-1">
                    <label class="form-label">Qty</label>
                    <input type="number" id="qty" class="form-control" step="1" min="1" value="1">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Commission Value</label>
                    <input type="number" id="commission" class="form-control" step="0.01" value="0" min="0">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Commission Type</label>
                    <select id="commission_type" class="form-select">
                        <option value="PCT">%</option>
                        <option value="AMT">Taka</option>
                    </select>
                </div>
                <div class="col-md-2 d-grid align-self-end"> 
                    <button class="btn btn-primary" id="addRowBtn"><i class="bi bi-plus-circle"></i> Add Item</button>
                </div>
            </div>

            <hr>

            <div class="table-responsive">
                <table class="table table-bordered  mt-3 table-hover border-3" id="entryTable">
        <thead class="table-secondary">
                        <tr>
                            <th>Brand</th><th>Category</th><th>Model</th>
                            <th>Price</th><th>Qty</th><th>Comm</th><th>Type</th><th>Line Total</th><th>Action</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>

            <div class="row mt-3">
                <div class="col-md-3 ms-auto">
                    <label>Sub Total</label>
                    <input type="text" id="sub_total" class="form-control text-end" readonly value="0.00">
                </div>
            </div>

            <div class="row mt-2">
                <div class="col-md-3 ms-auto">
                    <label>Total Discount</label>
                    <input type="number" id="total_discount" class="form-control text-end" step="0.01" value="0">
                </div>
            </div>

            <div class="row mt-2">
                <div class="col-md-3 ms-auto">
                    <label>Net Total</label>
                    <input type="text" id="total" class="form-control text-end" readonly value="0.00">
                </div>
            </div>

            <div class="row mt-2">
                <div class="col-md-3 ms-auto">
                    <label>Payment <span class="text-danger">*</span></label>
                    <input type="number" id="payment" class="form-control text-end" step="0.01" value="0" required>
                </div>
            </div>

            <div class="row mt-2">
                <div class="col-md-3 ms-auto">
                    <label>Due</label>
                    <input type="text" id="due" class="form-control text-end" readonly value="0.00">
                </div>
            </div>

            <div class="text-end mt-3">
                <form id="stockForm" method="post">
                    <input type="hidden" name="details_json" id="details_json">
                    <input type="hidden" name="total_discount" id="hidden_discount">
                    <input type="hidden" name="payment" id="hidden_payment">
                    <input type="hidden" name="voucher_ref" id="hidden_voucher_ref">
                    <input type="hidden" name="mst_id" id="hidden_mst_id"> <input type="hidden" name="distributor_code" id="hidden_distributor_code"> <input type="hidden" name="action" value="save">
                    <button type="submit" class="btn btn-success me-2" id="saveBtn"><i class="bi bi-save"></i> Save</button>
                    <button type="button" class="btn btn-secondary" id="cancelEditBtn" style="display:none;"><i class="bi bi-x-circle"></i> Cancel Edit</button>
                </form>
            </div>
        </div>
    </div>
    <?php else: ?>
    <div class="alert alert-warning">You do not have permission to insert records.</div>
    <?php endif; ?>

    <hr>

    <h4><i class="bi bi-list-check"></i> Unapproved Stock Entries</h4>
    <div class="table-responsive">
       <table class="table table-bordered  mt-3 table-hover border-3" id="entryTable">
        <thead class="table-secondary">
                <tr>
                    <th>Voucher</th>
                    <th>Distributor</th>
                    <th class="text-end">Net Total</th>
                    <th class="text-end">Payment</th>
                    <th class="text-end">Due</th>
                    <th>Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($stockMasters as $mst): ?>
                <tr>
                    <td><?= htmlspecialchars($mst['stock_voucher_ref']) ?></td>
                    <td><?= htmlspecialchars($mst['DISTRIBUTOR_NAME'] ?? $mst['distributor_code']) ?></td>
                    <td class="text-end"><?= number_format($mst['total_amount'], 2) ?></td>
                    <td class="text-end"><?= number_format($mst['payment'], 2) ?></td>
                    <td class="text-end"><?= number_format($mst['due_amount'], 2) ?></td>
                    <td><?= htmlspecialchars(date('Y-m-d H:i', strtotime($mst['stock_entry_date']))) ?></td>
                    <td>
                        <?php if ($canEdit): ?>
                            <button class="btn btn-sm btn-primary" onclick="editEntry('<?= htmlspecialchars($mst['stock_mst_id']) ?>')">
                                <i class="bi bi-pencil-square"></i> Edit
                            </button>
                        <?php endif; ?>

                        <?php if ($canApprove): ?>
                            <button class="btn btn-sm btn-success" onclick="approveEntry('<?= htmlspecialchars($mst['stock_mst_id']) ?>')">
                                <i class="bi bi-check-circle"></i> Approve
                            </button>
                        <?php endif; ?>

                        <?php if ($canDelete): ?>
                            <button class="btn btn-sm btn-danger" onclick="deleteEntry('<?= htmlspecialchars($mst['stock_mst_id']) ?>')">
                                <i class="bi bi-trash"></i> Delete
                            </button>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

</main>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
let details = [];

const distributor = document.getElementById('distributor');
const supplier = document.getElementById('supplier');
const category = document.getElementById('category');
const model = document.getElementById('model');

const voucherRefInput = document.getElementById('voucher_ref');
const priceInput = document.getElementById('price');
const qtyInput = document.getElementById('qty');
const commInput = document.getElementById('commission');
const commTypeSelect = document.getElementById('commission_type');
const totalDiscountInput = document.getElementById('total_discount');
const paymentInput = document.getElementById('payment');
const subTotalInput = document.getElementById('sub_total');
const totalInput = document.getElementById('total');
const dueInput = document.getElementById('due');
const mstIdHidden = document.getElementById('hidden_mst_id');
const saveBtn = document.getElementById('saveBtn');
const cancelEditBtn = document.getElementById('cancelEditBtn');


// --- Dependent Select Logic ---
supplier.addEventListener('change', function() {
    category.innerHTML = '<option value="">Select</option>';
    model.innerHTML = '<option value="">Select</option>';
    if (this.value) {
        fetch(`stock_entry.php?fetch_cat=1&supplier_id=${this.value}`)
            .then(res => res.json())
            .then(data => {
                data.forEach(c => category.innerHTML += `<option value="${c.product_category_id}">${c.product_category_name}</option>`);
            });
    }
});

category.addEventListener('change', function() {
    model.innerHTML = '<option value="">Select</option>';
    if (this.value) {
        fetch(`stock_entry.php?fetch_model=1&product_category_id=${this.value}`)
            .then(res => res.json())
            .then(data => {
                data.forEach(m => model.innerHTML += `<option value="${m.model_id}" data-price="${m.price}">${m.model_name}</option>`);
            });
    }
});

model.addEventListener('change', function() {
    priceInput.value = this.selectedOptions[0]?.getAttribute('data-price') || 0;
    qtyInput.value = 1;
    commInput.value = 0;
});


// --- Item Addition Logic ---
document.getElementById('addRowBtn').addEventListener('click', function(e) {
    e.preventDefault();
    const price = parseFloat(priceInput.value) || 0;
    const qty = parseFloat(qtyInput.value) || 0;
    const comm = parseFloat(commInput.value) || 0;
    const ctype = commTypeSelect.value;
    
    if(!distributor.value || !supplier.value || !category.value || !model.value || qty <= 0 || price <= 0){
        alert("Please select Distributor, Brand, Category, Model and enter valid Price/Quantity.");
        return;
    }

    const rowBase = price * qty;
    const commissionAmt = (ctype === 'PCT') ? (rowBase * comm / 100) : comm;
    const finalTotal = rowBase - commissionAmt;

    const entry = {
        // Names for display
        distributor_name: distributor.selectedOptions[0].text,
        supplier_name: supplier.selectedOptions[0].text,
        product_category_name: category.selectedOptions[0].text,
        model_name: model.selectedOptions[0].text,
        
        // IDs and values for saving
        distributor_code: distributor.value,
        supplier_id: supplier.value,
        product_category_id: category.value,
        model_id: model.value,
        price: price,
        quantity: qty,
        commission_type: ctype,
        commission_value: comm,
        total: finalTotal
    };
    details.push(entry);
    renderTable();

    // Reset item entry fields (except Distributor, as it's typically the same for the whole invoice)
    supplier.value = '';
    category.innerHTML='<option value="">Select</option>';
    model.innerHTML='<option value="">Select</option>';
    priceInput.value='0';
    qtyInput.value='1';
    commInput.value='0';
    commTypeSelect.value='PCT';
    supplier.focus();
});


// --- Table Rendering and Calculations ---
function renderTable(){
    const tbody = document.querySelector("#entryTable tbody");
    tbody.innerHTML="";
    let subtotal = 0;
    
    details.forEach((d,i)=>{
        subtotal += d.total;
        tbody.innerHTML+=`<tr>
            
            <td>${d.supplier_name}</td>
            <td>${d.product_category_name}</td>
            <td>${d.model_name}</td>
            <td class="text-end">${d.price.toFixed(2)}</td>
            <td class="text-center">${d.quantity}</td>
            <td class="text-end">${d.commission_value}</td>
            <td class="text-center">${d.commission_type}</td>
            <td class="text-end">${d.total.toFixed(2)}</td>
            <td class="text-center"><button class="btn btn-sm btn-danger" onclick="removeRow(${i})">X</button></td>
        </tr>`;
    });
    
    const disc = parseFloat(totalDiscountInput.value)||0;
    const pay = parseFloat(paymentInput.value)||0;
    const netTotal = subtotal - disc;
    const due = netTotal - pay;

    subTotalInput.value = subtotal.toFixed(2);
    totalInput.value = netTotal.toFixed(2);
    dueInput.value = due.toFixed(2);
}

function removeRow(idx){
    details.splice(idx,1);
    renderTable();
}

totalDiscountInput.addEventListener('input', renderTable);
paymentInput.addEventListener('input', renderTable);


// --- Form Submission ---
document.getElementById('stockForm').addEventListener('submit', function(e){
    const voucher = voucherRefInput.value.trim();
    const distCode = distributor.value;

    if (!voucher) {
        e.preventDefault();
        alert("Please enter Purchase Voucher Number.");
        voucherRefInput.focus();
        return;
    }
    
    if (!distCode) {
        e.preventDefault();
        alert("Please select a Distributor.");
        distributor.focus();
        return;
    }

    if (details.length === 0) {
        e.preventDefault();
        alert("Please add at least one item.");
        return;
    }

    // Set hidden fields for POST
    document.getElementById('details_json').value = JSON.stringify(details);
    document.getElementById('hidden_discount').value = totalDiscountInput.value;
    document.getElementById('hidden_payment').value = paymentInput.value;
    document.getElementById('hidden_voucher_ref').value = voucher;
    document.getElementById('hidden_mst_id').value = mstIdHidden.value; // For update
    document.getElementById('hidden_distributor_code').value = distCode;
});

// --- Edit/Cancel Functions ---
function resetForm() {
    details = [];
    renderTable();
    mstIdHidden.value = '';
    voucherRefInput.value = '';
    totalDiscountInput.value = '0';
    paymentInput.value = '0';
    distributor.value = '';

    // Clear item fields
    supplier.value = '';
    category.innerHTML = '<option value="">Select</option>';
    model.innerHTML = '<option value="">Select</option>';
    priceInput.value = '0';
    qtyInput.value = '1';
    commInput.value = '0';
    commTypeSelect.value = 'PCT';

    saveBtn.innerHTML = '<i class="bi bi-save"></i> Save';
    cancelEditBtn.style.display = 'none';
    window.scrollTo({top:0, behavior:'smooth'});
}

cancelEditBtn.addEventListener('click', resetForm);

function editEntry(mstId){
    fetch(`stock_entry.php?mst_id=${mstId}`)
    .then(res=>res.json())
    .then(data=>{
        if (!data.master) { alert('Master not found'); return; }

        voucherRefInput.value = data.master.stock_voucher_ref || '';
        totalDiscountInput.value = data.master.discount || 0;
        paymentInput.value = data.master.payment || 0;
        distributor.value = data.master.distributor_code || '';
        mstIdHidden.value = mstId;

        // The AJAX response already includes names (Distributor, Supplier, Category, Model)
        details = data.details; 
        
        renderTable();
        saveBtn.innerHTML = '<i class="bi bi-arrow-repeat"></i> Update';
        cancelEditBtn.style.display = 'inline-block';
        window.scrollTo({top:0, behavior:'smooth'});
    })
    .catch(e => console.error("Error fetching entry for edit:", e));
}

// --- Status Change Functions ---
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
    })
    .catch(e => alert("Network or server error during delete."));
}

function approveEntry(mstId){
    if(!confirm("Are you sure you want to approve this entry? This action cannot be undone.")) return;
    fetch('stock_entry.php', {
        method: 'POST',
        headers: {'Content-Type':'application/x-www-form-urlencoded'},
        body: 'action=approve&approve_mst_id=' + encodeURIComponent(mstId)
    })
    .then(res => res.text())
    .then(resp => {
        alert(resp);
        location.reload();
    })
    .catch(e => alert("Network or server error during approval."));
}

document.addEventListener('DOMContentLoaded', renderTable);
</script>
<?php  include 'footer.php'; ?>
</body>
</html>