<?php
// sales_entry.php
session_start();
require 'db.php'; // must provide $pdo (PDO instance)

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
$canApprove= $_SESSION['user']['can_approve'] ?? 0;

function gen_id($prefix = '') {
    $ms = (int)floor(microtime(true) * 1000) % 1000;
    $ts = date('YmdHis') . sprintf('%03d', $ms);
    return $prefix . $ts . rand(100,999);
}

// ---------- AJAX: fetch categories by supplier ----------
if (isset($_GET['fetch_cat'], $_GET['supplier_id'])) {
    header('Content-Type: application/json');
    $stmt = $pdo->prepare("SELECT product_category_id, product_category_name 
                           FROM product_category 
                           WHERE supplier_id = :sup AND br_code = :br 
                           ORDER BY product_category_name");
    $stmt->execute(['sup'=>$_GET['supplier_id'],'br'=>$brCode]);
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    exit();
}

// ---------- AJAX: fetch models by category ----------
if (isset($_GET['fetch_model'], $_GET['product_category_id'])) {
    header('Content-Type: application/json');
    $stmt = $pdo->prepare("SELECT model_id, model_name, price 
                           FROM product_model 
                           WHERE product_category_id = :cat AND br_code = :br 
                           ORDER BY model_name");
    $stmt->execute(['cat'=>$_GET['product_category_id'],'br'=>$brCode]);
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    exit();
}

// ---------- AJAX: fetch sales master + details for edit ----------
if (isset($_GET['mst_id'])) {
    header('Content-Type: application/json');
    $mstId = $_GET['mst_id'];

    $stmt = $pdo->prepare("SELECT * FROM sales_mst WHERE sales_mst_id = :mst LIMIT 1");
    $stmt->execute(['mst'=>$mstId]);
    $master = $stmt->fetch(PDO::FETCH_ASSOC);

    $stmtDtl = $pdo->prepare("SELECT * FROM sales_dtl WHERE sales_mst_id = :mst AND br_code = :br ORDER BY entry_date");
    $stmtDtl->execute(['mst'=>$mstId,'br'=>$brCode]);
    $details = $stmtDtl->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['master'=>$master,'details'=>$details]);
    exit();
}

// ---------- POST: Save / Update / Delete / Approve ----------
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {

    // SAVE / UPDATE
    if ($_POST['action'] === 'save' && $canInsert) {
        $details = json_decode($_POST['details_json'], true);
        $discount = floatval($_POST['total_discount'] ?? 0);
        $payment = floatval($_POST['payment'] ?? 0);
        $voucherRef = trim($_POST['voucher_ref'] ?? '');
        $mstIdEdit = trim($_POST['mst_id'] ?? '');

        if (!$voucherRef) {
            $message = "<div class='alert alert-danger'>Please enter Sales Voucher Number.</div>";
        } elseif (!is_array($details) || count($details) === 0) {
            $message = "<div class='alert alert-danger'>No items to save.</div>";
        } else {
            try {
                $pdo->beginTransaction();

                // Master record (insert new or keep existing id for update)
                if ($mstIdEdit) {
                    $mstId = $mstIdEdit;
                    // remove previous detail rows for this master (we will re-insert)
                    $pdo->prepare("DELETE FROM sales_dtl WHERE sales_mst_id = :mst AND br_code = :br")
                        ->execute(['mst'=>$mstId,'br'=>$brCode]);
                    // update voucher ref and entry date optional (we'll update totals later)
                    $pdo->prepare("UPDATE sales_mst SET sales_voucher_ref = :ref, edit_user = :user, edit_date = NOW() WHERE sales_mst_id = :mst")
                        ->execute(['ref'=>$voucherRef,'user'=>$userId,'mst'=>$mstId]);
                } else {
                    $mstId = gen_id($brCode . '-SLS-');
                    $stmtInsM = $pdo->prepare("INSERT INTO sales_mst (sales_mst_id, sales_voucher_ref, sales_entry_date, org_code, br_code, sub_total, discount, total_amount, payment, due_amount, entry_user, entry_date, authorized_status)
                        VALUES (:mst, :ref, NOW(), :org, :br, 0, 0, 0, 0, 0, :user, NOW(), 'N')");
                    $stmtInsM->execute(['mst'=>$mstId, 'ref'=>$voucherRef, 'org'=>$orgCode, 'br'=>$brCode, 'user'=>$userId]);
                }

                // Insert detail rows
                $subTotal = 0;
                $stmtDtlIns = $pdo->prepare("INSERT INTO sales_dtl 
                    (sales_dtl_id, sales_mst_id, customer_id, model_id, product_category_id, supplier_id, price, quantity, total, sub_total, original_price, commission_pct, commission_type, org_code, br_code, distributor_code, sales_voucher_ref, entry_user, entry_date, authorized_status)
                    VALUES
                    (:dtl, :mst, :cust, :model, :cat, :sup, :price, :qty, :total, :sub_total, :orig, :cpct, :ctype, :org, :br, :dist, :ref, :user, NOW(), 'N')");

                foreach ($details as $d) {
                    $rowSub = floatval($d['price']) * floatval($d['quantity']);
                    $commissionAmt = ($d['commission_type'] === 'PCT') ? ($rowSub * (floatval($d['commission_value'])/100.0)) : floatval($d['commission_value']);
                    $finalTotal = $rowSub - $commissionAmt;
                    $dtlId = gen_id($brCode . '-SLSDTL-');

                    $stmtDtlIns->execute([
                        'dtl' => $dtlId,
                        'mst' => $mstId,
                        'cust'=> isset($d['customer_id']) ? $d['customer_id'] : null,
                        'model'=> $d['model_id'],
                        'cat' => $d['product_category_id'],
                        'sup' => $d['supplier_id'],
                        'price'=> $d['price'],
                        'qty' => $d['quantity'],
                        'total'=> $finalTotal,
                        'sub_total'=> $rowSub,
                        'orig' => $d['price'],
                        'cpct'=> $d['commission_value'],
                        'ctype'=> $d['commission_type'],
                        'org' => $orgCode,
                        'br' => $brCode,
                        'dist'=> $d['distributor_code'] ?? null,
                        'ref' => $voucherRef,
                        'user'=> $userId
                    ]);

                    $subTotal += $finalTotal;
                }

                // Update master totals
                $totalAmount = $subTotal - $discount;
                $dueAmount = $totalAmount - $payment;

                $stmtUpd = $pdo->prepare("UPDATE sales_mst SET sub_total = :sub, discount = :disc, total_amount = :total, payment = :pay, due_amount = :due WHERE sales_mst_id = :mst");
                $stmtUpd->execute(['sub'=>$subTotal, 'disc'=>$discount, 'total'=>$totalAmount, 'pay'=>$payment, 'due'=>$dueAmount, 'mst'=>$mstId]);

                $pdo->commit();
                $_SESSION['success_msg'] = "Sales entry saved successfully!";
                header("Location: " . $_SERVER['PHP_SELF']);
                exit();

            } catch (Exception $e) {
                $pdo->rollBack();
                $message = "<div class='alert alert-danger'>Error: " . htmlspecialchars($e->getMessage()) . "</div>";
            }
        }
    }

    // DELETE
    if ($_POST['action'] === 'delete' && $canDelete) {
        $mstId = $_POST['delete_mst_id'] ?? '';
        try {
            $pdo->beginTransaction();
            $pdo->prepare("DELETE FROM sales_dtl WHERE sales_mst_id = :mst AND br_code = :br")->execute(['mst'=>$mstId,'br'=>$brCode]);
            $pdo->prepare("DELETE FROM sales_mst WHERE sales_mst_id = :mst AND br_code = :br")->execute(['mst'=>$mstId,'br'=>$brCode]);
            $pdo->commit();
            echo "Sales entry deleted successfully!";
        } catch (Exception $e) {
            $pdo->rollBack();
            echo "Error deleting sales entry: " . htmlspecialchars($e->getMessage());
        }
        exit();
    }

    // APPROVE
    if ($_POST['action'] === 'approve' && $canApprove) {
        $mstId = $_POST['approve_mst_id'] ?? '';
        try {
            $stmt = $pdo->prepare("UPDATE sales_mst SET authorized_status = 'Y', authorized_user = :user, authorized_date = NOW() WHERE sales_mst_id = :mst AND br_code = :br");
            $stmt->execute(['user'=>$userId, 'mst'=>$mstId, 'br'=>$brCode]);
            echo "Sales entry approved successfully!";
        } catch (Exception $e) {
            echo "Error approving sales entry: " . htmlspecialchars($e->getMessage());
        }
        exit();
    }
}

// show success message if any
if (!empty($_SESSION['success_msg'])) {
    $message = "<div class='alert alert-success text-center'>".$_SESSION['success_msg']."</div>";
    unset($_SESSION['success_msg']);
}

// ---------- fetch lists for selects ----------
$stmtSup = $pdo->prepare("SELECT supplier_id, supplier_name FROM supplier WHERE br_code = :br ORDER BY supplier_name");
$stmtSup->execute(['br'=>$brCode]);
$suppliers = $stmtSup->fetchAll(PDO::FETCH_ASSOC);

$stmtDist = $pdo->prepare("SELECT distributor_code, distributor_name FROM distributor WHERE org_code = :org AND delete_date IS NULL ORDER BY distributor_name");
$stmtDist->execute(['org'=>$orgCode]);
$distributors = $stmtDist->fetchAll(PDO::FETCH_ASSOC);

$stmtCust = $pdo->prepare("SELECT customer_id, customer_name FROM customer_info WHERE delete_status IS NULL OR delete_status = '' ORDER BY customer_name");
$stmtCust->execute();
$customers = $stmtCust->fetchAll(PDO::FETCH_ASSOC);

// fetch masters (unapproved)
$stmtMstAll = $pdo->prepare("SELECT * FROM sales_mst WHERE br_code = :br AND (authorized_status IS NULL OR authorized_status = 'N') ORDER BY sales_entry_date DESC");
$stmtMstAll->execute(['br'=>$brCode]);
$salesMasters = $stmtMstAll->fetchAll(PDO::FETCH_ASSOC);
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Sales Entry</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body class="d-flex flex-column min-vh-100">
<?php if (file_exists('header.php')) include 'header.php'; ?>

<main class="container py-4 flex-grow-1">
  <h3>Sales Entry</h3>

  <?= $message ?>

  <?php if ($canInsert): ?>
  <div class="card mb-4">
    <div class="card-body">
      <!-- top row: customer, distributor, supplier, category, model -->
      <div class="row g-2 mb-2">
        <div class="col-md-3">
          <label>Customer</label>
          <select id="customer" class="form-select">
            <option value="">-- Select Customer --</option>
            <?php foreach($customers as $c): ?>
              <option value="<?= $c['customer_id'] ?>"><?= htmlspecialchars($c['customer_name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="col-md-3">
          <label>Distributor</label>
          <select id="distributor" class="form-select">
            <option value="">Select Distributor</option>
            <?php foreach($distributors as $d): ?>
              <option value="<?= htmlspecialchars($d['distributor_code']) ?>"><?= htmlspecialchars($d['distributor_name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="col-md-3">
          <label>Brand (Supplier)</label>
          <select id="supplier" class="form-select">
            <option value="">Select</option>
            <?php foreach($suppliers as $s): ?>
              <option value="<?= htmlspecialchars($s['supplier_id']) ?>"><?= htmlspecialchars($s['supplier_name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="col-md-3">
          <label>Category</label>
          <select id="category" class="form-select">
            <option value="">Select</option>
          </select>
        </div>

        <div class="col-md-3 mt-2">
          <label>Model</label>
          <select id="model" class="form-select">
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
          <label>Sales Voucher No.</label>
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
            <th>Customer</th><th>Distributor</th><th>Brand</th><th>Category</th><th>Model</th>
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
          <input type="number" id="payment" class="form-control" step="0.01" value="0">
        </div>
      </div>

      <div class="row mt-2">
        <div class="col-md-3 ms-auto">
          <label>Due</label>
          <input type="number" id="due" class="form-control" readonly>
        </div>
      </div>

      <div class="text-end mt-3">
        <form id="salesForm" method="post">
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
  <h4>All Sales Entries (Unapproved)</h4>
  <table class="table table-striped table-bordered">
    <thead class="table-dark">
      <tr>
        <th>Voucher</th><th>Sub Total</th><th>Discount</th><th>Total</th><th>Payment</th><th>Due</th><th>Date</th><th>Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($salesMasters as $mst): ?>
        <tr>
          <td><?= htmlspecialchars($mst['sales_voucher_ref']) ?></td>
          <td><?= htmlspecialchars($mst['sub_total']) ?></td>
          <td><?= htmlspecialchars($mst['discount']) ?></td>
          <td><?= htmlspecialchars($mst['total_amount']) ?></td>
          <td><?= htmlspecialchars($mst['payment']) ?></td>
          <td><?= htmlspecialchars($mst['due_amount']) ?></td>
          <td><?= htmlspecialchars($mst['sales_entry_date']) ?></td>
          <td>
            <?php if ($canEdit): ?>
              <button class="btn btn-sm btn-primary" onclick="editEntry('<?= $mst['sales_mst_id'] ?>')">
                <i class="bi bi-pencil-square"></i> Edit
              </button>
            <?php endif; ?>

            <?php if ($canApprove): ?>
              <button class="btn btn-sm btn-success" onclick="approveEntry('<?= $mst['sales_mst_id'] ?>')">
                <i class="bi bi-check-circle"></i> Approve
              </button>
            <?php endif; ?>

            <?php if ($canDelete): ?>
              <button class="btn btn-sm btn-danger" onclick="deleteEntry('<?= $mst['sales_mst_id'] ?>')">
                <i class="bi bi-trash"></i> Delete
              </button>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>

</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
let details = [];

const customer = document.getElementById('customer');
const distributor = document.getElementById('distributor');
const supplier = document.getElementById('supplier');
const category = document.getElementById('category');
const model = document.getElementById('model');

supplier?.addEventListener('change', function() {
  fetch(`sales_entry.php?fetch_cat=1&supplier_id=${this.value}`)
    .then(r => r.json())
    .then(data => {
      category.innerHTML = '<option value="">Select</option>';
      data.forEach(c => category.innerHTML += `<option value="${c.product_category_id}">${c.product_category_name}</option>`);
    });
});

category?.addEventListener('change', function() {
  fetch(`sales_entry.php?fetch_model=1&product_category_id=${this.value}`)
    .then(r => r.json())
    .then(data => {
      model.innerHTML = '<option value="">Select</option>';
      data.forEach(m => model.innerHTML += `<option value="${m.model_id}" data-price="${m.price}">${m.model_name}</option>`);
    });
});

model?.addEventListener('change', function() {
  document.getElementById('price').value = this.selectedOptions[0]?.getAttribute('data-price') || '';
});

document.getElementById('addRowBtn')?.addEventListener('click', function(e){
  e.preventDefault();
  const custId = customer.value || null;
  const distributor_code = distributor.value || null;
  const supplier_id = supplier.value || null;
  const product_category_id = category.value || null;
  const model_id = model.value || null;
  const price = parseFloat(document.getElementById('price').value) || 0;
  const qty = parseFloat(document.getElementById('qty').value) || 0;
  const comm = parseFloat(document.getElementById('commission').value) || 0;
  const ctype = document.getElementById('commission_type').value;

  if (!model_id || qty <= 0) {
    alert('Please select model and enter quantity.');
    return;
  }

  const rowBase = price * qty;
  const total = ctype === 'PCT' ? rowBase - (rowBase * comm / 100) : rowBase - comm;

  const entry = {
    customer_id: custId,
    customer_name: customer.selectedOptions?.[0]?.text || '',
    distributor_code: distributor_code,
    distributor_name: distributor.selectedOptions?.[0]?.text || '',
    supplier_id: supplier_id,
    supplier_name: supplier.selectedOptions?.[0]?.text || '',
    product_category_id: product_category_id,
    product_category_name: category.selectedOptions?.[0]?.text || '',
    model_id: model_id,
    model_name: model.selectedOptions?.[0]?.text || '',
    price: price,
    quantity: qty,
    commission_type: ctype,
    commission_value: comm,
    total: total
  };

  details.push(entry);
  renderTable();

  // reset some fields
  model.innerHTML = '<option value="">Select</option>';
  category.innerHTML = '<option value="">Select</option>';
  supplier.value = '';
  document.getElementById('price').value = '';
  document.getElementById('qty').value = '';
  document.getElementById('commission').value = '';
});

function renderTable(){
  const tbody = document.querySelector('#entryTable tbody');
  tbody.innerHTML = '';
  let subtotal = 0;
  details.forEach((d, i) => {
    subtotal += d.total;
    tbody.innerHTML += `<tr>
      <td>${d.customer_name || ''}</td>
      <td>${d.distributor_name || ''}</td>
      <td>${d.supplier_name || ''}</td>
      <td>${d.product_category_name || ''}</td>
      <td>${d.model_name || ''}</td>
      <td>${Number(d.price).toFixed(2)}</td>
      <td>${d.quantity}</td>
      <td>${d.commission_value}</td>
      <td>${d.commission_type}</td>
      <td>${Number(d.total).toFixed(2)}</td>
      <td><button class="btn btn-sm btn-danger" onclick="removeRow(${i})">X</button></td>
    </tr>`;
  });
  document.getElementById('sub_total').value = subtotal.toFixed(2);
  const disc = parseFloat(document.getElementById('total_discount').value) || 0;
  const pay = parseFloat(document.getElementById('payment').value) || 0;
  document.getElementById('total').value = (subtotal - disc).toFixed(2);
  document.getElementById('due').value = ((subtotal - disc) - pay).toFixed(2);
}

function removeRow(i) {
  details.splice(i,1);
  renderTable();
}

document.getElementById('total_discount')?.addEventListener('input', renderTable);
document.getElementById('payment')?.addEventListener('input', renderTable);

document.getElementById('salesForm')?.addEventListener('submit', function(e) {
  const voucher = document.getElementById('voucher_ref').value.trim();
  if (!voucher) {
    e.preventDefault();
    alert('Please enter Sales Voucher Number.');
    return;
  }
  if (details.length === 0) {
    e.preventDefault();
    alert('Please add at least one item.');
    return;
  }

  document.getElementById('details_json').value = JSON.stringify(details);
  document.getElementById('hidden_discount').value = document.getElementById('total_discount').value;
  document.getElementById('hidden_payment').value = document.getElementById('payment').value;
  document.getElementById('hidden_voucher_ref').value = voucher;
  document.getElementById('action').value = 'save';
});

function editEntry(mstId){
  fetch(`sales_entry.php?mst_id=${mstId}`)
    .then(r => r.json())
    .then(data => {
      if (!data.master) { alert('Master not found'); return; }
      document.getElementById('voucher_ref').value = data.master.sales_voucher_ref || '';
      document.getElementById('total_discount').value = data.master.discount || 0;
      document.getElementById('payment').value = data.master.payment || 0;
      document.getElementById('mst_id').value = mstId;

      details = data.details.map(d => ({
        customer_id: d.customer_id,
        customer_name: d.customer_id, // we only have id here; optional: fetch name separately
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
      document.getElementById('saveBtn').textContent = 'Update';
      window.scrollTo({top:0, behavior:'smooth'});
    });
}

function deleteEntry(mstId){
  if (!confirm('Are you sure you want to delete this sales entry?')) return;
  fetch('sales_entry.php', {
    method: 'POST',
    headers: {'Content-Type':'application/x-www-form-urlencoded'},
    body: 'action=delete&delete_mst_id=' + encodeURIComponent(mstId)
  })
  .then(r => r.text())
  .then(txt => { alert(txt); location.reload(); });
}

function approveEntry(mstId){
  if (!confirm('Are you sure you want to approve this entry?')) return;
  fetch('sales_entry.php', {
    method: 'POST',
    headers: {'Content-Type':'application/x-www-form-urlencoded'},
    body: 'action=approve&approve_mst_id=' + encodeURIComponent(mstId)
  })
  .then(r => r.text())
  .then(txt => { alert(txt); location.reload(); });
}

</script>
<?php include 'footer.php'; ?>
</body>
</html>
