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
$canApprove= $_SESSION['user']['can_approve'] ?? 0;

// --- ID Generation Functions ---

/**
 * Generates a simple, sequential sales voucher reference (Voucher Ref).
 */
function gen_sales_voucher($brCode) {
    $microtime = microtime(true);
    $dateStr = date('Ymd', $microtime);
    $timeStr = date('His', $microtime) . substr((string)($microtime - floor($microtime)), 1, 4);
    return "{$brCode}-V-{$dateStr}-{$timeStr}";
}

/**
 * Generates a unique sales master ID as BR_CODE-YYYYMMDD-6RANDOM.
 * Loops until uniqueness is confirmed.
 */
function gen_unique_sales_master_id($pdo, $brCode) {
    $dateStr = date('Ymd');
    $attempts = 0;
    do {
        $rand = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT); // 6-digit random number
        $newId = "{$brCode}-{$dateStr}-{$rand}";
        
        // Check for uniqueness in the database
        $stmt = $pdo->prepare("SELECT sales_mst_id FROM sales_mst WHERE sales_mst_id = :id LIMIT 1");
        $stmt->execute(['id' => $newId]);
        $isDuplicate = $stmt->fetchColumn();
        $attempts++;
        if ($attempts > 100) {
            throw new Exception("Failed to generate a unique sales master ID after 100 attempts.");
        }
    } while ($isDuplicate);
    
    return $newId;
}

/**
 * Generates a unique sales detail ID.
 */
function gen_detail_id($brCode) {
    $dateStr = date('YmdHis'); 
    $rand = rand(1000, 9999); 
    return "{$brCode}-D-{$dateStr}-{$rand}";
}

/**
 * Generates a unique customer ID.
 */
function gen_customer_id() {
    // Using a simple timestamp-based ID for this example. Replace with your actual customer ID generation logic.
    return 'C-' . date('YmdHis') . rand(100, 999); 
}


// --- AJAX Handlers ---
// ---------- AJAX: fetch categories that exist in stock_dtl ----------
if (isset($_GET['fetch_cat'], $_GET['supplier_id'])) {
    header('Content-Type: application/json');

    $sql = "
        SELECT DISTINCT
            pc.product_category_id,
            pc.product_category_name
        FROM stock_dtl d
        INNER JOIN product_category pc
            ON pc.product_category_id = d.product_category_id
        WHERE d.supplier_id = :sup
          AND d.br_code     = :br
          AND d.org_code    = :org
        GROUP BY 
            pc.product_category_id,
            pc.product_category_name
        HAVING SUM(d.quantity) > 0
        ORDER BY pc.product_category_name
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':sup' => (int) $_GET['supplier_id'],
        ':br'  => $brCode,
        ':org' => $orgCode
    ]);

    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    exit();
}

// ---------- AJAX: fetch models that exist in stock_dtl ----------
if (isset($_GET['fetch_model'], $_GET['product_category_id'])) {
    header('Content-Type: application/json');

    $sql = "
        SELECT 
    sd.model_id,
    sd.price,
    sd.stock_mst_id,
    sd.stock_dtl_id, -- required for your Foreign Key
    CONCAT(
        CAST(pm.model_name AS CHAR),
        ' |', CAST(sd.price AS CHAR),
        ' |', CAST(sd.buying_price AS CHAR),
        ' |', CAST(sd.remaining AS CHAR),
        ' |', CAST(DATE(sd.stock_date) AS CHAR)
    ) AS model_name
FROM stock_details_view sd
INNER JOIN product_model pm
    ON pm.model_id = sd.model_id
WHERE sd.product_category_id = :cat
  AND sd.br_code = :br
  AND sd.org_code = :org
ORDER BY pm.model_name;

    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':cat' => (int) $_GET['product_category_id'],
        ':br'  => $brCode,
        ':org' => $orgCode
    ]);

    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    exit();
}



// ---------- AJAX: Look up customer by phone number (NEW) ----------
if (isset($_GET['lookup_customer_by_phone'], $_GET['phone'])) {
    header('Content-Type: application/json');
    $phone = trim($_GET['phone']);
    $response = ['customer_id' => null, 'customer_name' => '', 'found' => false];
    
    if (!empty($phone)) {
        // Query customer_info table
        $stmt = $pdo->prepare("SELECT customer_id, customer_name FROM customer_info WHERE customer_phone = :phone LIMIT 1");
        $stmt->execute(['phone' => $phone]);
        $customer = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($customer) {
            $response['customer_id'] = $customer['customer_id'];
            $response['customer_name'] = $customer['customer_name'];
            $response['found'] = true;
        }
    }
    echo json_encode($response);
    exit();
}

// ---------- AJAX: fetch sales master + details for edit ----------
if (isset($_GET['mst_id'])) {
    header('Content-Type: application/json');
    $mstId = $_GET['mst_id'];
    
    // Fetch master and join to get customer info including phone
    $stmt = $pdo->prepare("SELECT sm.*, ci.customer_name, ci.customer_phone 
                           FROM sales_mst sm
                           LEFT JOIN customer_info ci ON sm.customer_id = ci.customer_id
                           WHERE sm.sales_mst_id = :mst AND sm.br_code = :br LIMIT 1");
    $stmt->execute(['mst'=>$mstId, 'br'=>$brCode]);
    $master = $stmt->fetch(PDO::FETCH_ASSOC);

    // Fetch details with joined names
    $stmtDtl = $pdo->prepare("SELECT sales_dtl.*, s.supplier_name, pc.product_category_name, pm.model_name 
                              FROM sales_dtl 
                              LEFT JOIN supplier s ON sales_dtl.supplier_id = s.supplier_id AND sales_dtl.br_code = s.br_code
                              LEFT JOIN product_category pc ON sales_dtl.product_category_id = pc.product_category_id AND sales_dtl.br_code = pc.br_code
                              LEFT JOIN product_model pm ON sales_dtl.model_id = pm.model_id AND sales_dtl.br_code = pm.br_code
                              WHERE sales_mst_id = :mst AND sales_dtl.br_code = :br 
                              ORDER BY sales_dtl.entry_date");
    $stmtDtl->execute(['mst'=>$mstId,'br'=>$brCode]);
    $details = $stmtDtl->fetchAll(PDO::FETCH_ASSOC);

    $mappedDetails = array_map(function($d) {
        $commission_value = floatval($d['commission_pct']); 
        return [
            'supplier_id' => $d['supplier_id'],
            'supplier_name' => $d['supplier_name'],
            'product_category_id' => $d['product_category_id'],
            'product_category_name' => $d['product_category_name'],
            'model_id' => $d['model_id'],
            'model_name' => $d['model_name'],
            'price' => floatval($d['price']),
            'quantity' => floatval($d['quantity']),
            'commission_type' => $d['commission_type'],
            'commission_value' => $commission_value, 
            'total' => floatval($d['total']) 
        ];
    }, $details);
    
    // Ensure customer_phone is included in master data for form prefill
    $master['customer_phone'] = $master['customer_phone'] ?? '';

    echo json_encode(['master'=>$master,'details'=>$mappedDetails]);
    exit();
}

// --- POST Handlers ---
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {

    // SAVE / UPDATE
    if ($_POST['action'] === 'save' && $canInsert) {
        $details = json_decode($_POST['details_json'] ?? '[]', true);
        $discount = floatval($_POST['total_discount'] ?? 0);
        $payment = floatval($_POST['payment'] ?? 0);
        $voucherRef = trim($_POST['voucher_ref'] ?? '');
        $mstIdEdit = trim($_POST['mst_id'] ?? '');

        // NEW: Customer data from form
        $customerId = trim($_POST['customer_id'] ?? '');
        $customerName = trim($_POST['customer_name'] ?? '');
        $customerPhone = trim($_POST['customer_phone'] ?? '');

        if (!$voucherRef) {
            $message = "<div class='alert alert-danger'>Please enter Sales Voucher Number.</div>";
        } elseif (empty($customerName) || empty($customerPhone)) {
            $message = "<div class='alert alert-danger'>Customer Name and Phone are required.</div>";
        } elseif (!is_array($details) || count($details) === 0) {
            $message = "<div class='alert alert-danger'>No items to save.</div>";
        } else {
            try {
                $pdo->beginTransaction();

                // 1. Customer Management (INSERT if new)
                if (empty($customerId)) {
                    // Check if phone number exists in DB again (security against concurrency)
                    $stmtCheck = $pdo->prepare("SELECT customer_id FROM customer_info WHERE customer_phone = :phone LIMIT 1");
                    $stmtCheck->execute(['phone' => $customerPhone]);
                    $existingId = $stmtCheck->fetchColumn();

                    if ($existingId) {
                        // Use existing ID if found
                        $customerId = $existingId;
                    } else {
                        // Generate new customer ID and insert the new customer
                        $newCustId = gen_customer_id(); 
                        
                        $stmtInsCust = $pdo->prepare("INSERT INTO customer_info 
                            (customer_id, customer_name, customer_phone, org_code, br_code, entry_user, entry_date)
                            VALUES (:id, :name, :phone, :org, :br, :user, NOW())");
                        
                        $stmtInsCust->execute([
                            'id' => $newCustId, 
                            'name' => $customerName, 
                            'phone' => $customerPhone, 
                            'org' => $orgCode, 
                            'br' => $brCode, 
                            'user' => $userId
                        ]);
                        $customerId = $newCustId;
                    }
                }

                // 2. Master record (Insert new or update existing)
                $subTotal = 0;
                if ($mstIdEdit) {
                    $mstId = $mstIdEdit;
                    // Check if approved before editing
                    $stmtCheck = $pdo->prepare("SELECT authorized_status FROM sales_mst WHERE sales_mst_id = :mst LIMIT 1");
                    $stmtCheck->execute(['mst'=>$mstId]);
                    if ($stmtCheck->fetchColumn() === 'Y') {
                         throw new Exception("Cannot edit an approved sales entry.");
                    }

                    // Delete previous details and update master
                    $pdo->prepare("DELETE FROM sales_dtl WHERE sales_mst_id = :mst AND br_code = :br")
                         ->execute(['mst'=>$mstId,'br'=>$brCode]);
                    
                    $pdo->prepare("UPDATE sales_mst SET 
                                    sales_voucher_ref = :ref, 
                                    customer_id = :cust, 
                                    edit_user = :user, 
                                    edit_date = NOW() 
                                    WHERE sales_mst_id = :mst AND br_code = :br")
                         ->execute(['ref'=>$voucherRef, 'cust'=>$customerId, 'user'=>$userId, 'mst'=>$mstId, 'br'=>$brCode]);
                    
                } else {
                    // Generate Unique Sales Master ID (BR_CODE-YYYYMMDD-6RANDOM)
                    $mstId = gen_unique_sales_master_id($pdo, $brCode);

                    $stmtInsM = $pdo->prepare("INSERT INTO sales_mst 
                        (sales_mst_id, sales_voucher_ref, sales_entry_date, org_code, br_code, sub_total, discount, total_amount, payment, due_amount, entry_user, entry_date, authorized_status, customer_id)
                        VALUES (:mst, :ref, NOW(), :org, :br, 0, 0, 0, 0, 0, :user, NOW(), 'N', :cust)");
                    
                    $stmtInsM->execute(['mst'=>$mstId, 'ref'=>$voucherRef, 'org'=>$orgCode, 'br'=>$brCode, 'user'=>$userId, 'cust'=>$customerId]);
                }

                // 3. Insert detail rows
                $stmtDtlIns = $pdo->prepare("
    INSERT INTO sales_dtl 
    (sales_dtl_id, sales_mst_id, model_id, product_category_id, supplier_id, price, quantity, total, 
     original_price, commission_pct, commission_type, org_code, br_code, sales_voucher_ref, 
     entry_user, entry_date, authorized_status, stock_mst_id, stock_dtl_id)
    VALUES
    (:dtl, :mst, :model, :cat, :sup, :price, :qty, :total, 
     :orig, :cpct, :ctype, :org, :br, :ref, 
     :user, NOW(), 'N', :stock_mst_id, :stock_dtl_id)
");

foreach ($details as $d) {
    $rowBase = floatval($d['price']) * floatval($d['quantity']);
    $commissionVal = floatval($d['commission_value']);
    $commissionType = $d['commission_type'];
    $commissionAmt = ($commissionType === 'PCT') ? ($rowBase * ($commissionVal / 100.0)) : $commissionVal;
    $finalTotal = $rowBase - $commissionAmt;
    $dtlId = gen_detail_id($brCode);

    $stmtDtlIns->execute([
        'dtl'          => $dtlId,
        'mst'          => $mstId,
        'model'        => $d['model_id'],
        'cat'          => $d['product_category_id'],
        'sup'          => $d['supplier_id'],
        'price'        => floatval($d['price']),
        'qty'          => floatval($d['quantity']),
        'total'        => $finalTotal,
        'orig'         => floatval($d['price']),
        'cpct'         => $commissionVal,
        'ctype'        => $commissionType,
        'org'          => $orgCode,
        'br'           => $brCode,
        'ref'          => $voucherRef,
        'user'         => $userId,
        'stock_mst_id' => $d['stock_mst_id'],
        'stock_dtl_id' => $d['stock_dtl_id']
    ]);

    $subTotal += $finalTotal; 
}

                // 4. Update master totals
                $totalAmount = $subTotal - $discount;
                $dueAmount = $totalAmount - $payment;

                $stmtUpd = $pdo->prepare("UPDATE sales_mst SET 
                                          sub_total = :sub, discount = :disc, total_amount = :total, 
                                          payment = :pay, due_amount = :due 
                                          WHERE sales_mst_id = :mst AND br_code = :br");
                $stmtUpd->execute([
                    'sub'=>$subTotal, 'disc'=>$discount, 'total'=>$totalAmount, 
                    'pay'=>$payment, 'due'=>$dueAmount, 'mst'=>$mstId, 'br'=>$brCode
                ]);

                $pdo->commit();
                $_SESSION['success_msg'] = "Sales entry **" . ($mstIdEdit ? "updated" : "saved") . "** successfully! Master ID: " . htmlspecialchars($mstId);
                header("Location: " . $_SERVER['PHP_SELF']);
                exit();

            } catch (Exception $e) {
                $pdo->rollBack();
                $message = "<div class='alert alert-danger'>Transaction Error: " . htmlspecialchars($e->getMessage()) . "</div>";
            }
        }
    }
    
    // DELETE
    if ($_POST['action'] === 'delete' && $canDelete) {
        header('Content-Type: text/plain');
        $mstId = $_POST['delete_mst_id'] ?? '';
        try {
            $pdo->beginTransaction();
            $stmtCheck = $pdo->prepare("SELECT authorized_status FROM sales_mst WHERE sales_mst_id = :mst AND br_code = :br LIMIT 1");
            $stmtCheck->execute(['mst'=>$mstId,'br'=>$brCode]);
            if ($stmtCheck->fetchColumn() === 'Y') {
                 throw new Exception("Cannot delete an approved sales entry.");
            }
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
        header('Content-Type: text/plain');
        $mstId = $_POST['approve_mst_id'] ?? '';
        try {
            $stmtCheck = $pdo->prepare("SELECT authorized_status FROM sales_mst WHERE sales_mst_id = :mst AND br_code = :br LIMIT 1");
            $stmtCheck->execute(['mst'=>$mstId,'br'=>$brCode]);
            if ($stmtCheck->fetchColumn() === 'Y') {
                 throw new Exception("Sales entry is already approved.");
            }
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

// Fetch suppliers for the form
$stmtSup = $pdo->prepare("SELECT supplier_id, supplier_name FROM supplier WHERE br_code = :br ORDER BY supplier_name");
$stmtSup->execute(['br'=>$brCode]);
$suppliers = $stmtSup->fetchAll(PDO::FETCH_ASSOC);

// Fetch masters (unapproved) for the list
$stmtMstAll = $pdo->prepare("SELECT sm.*, ci.customer_name 
                            FROM sales_mst sm
                            LEFT JOIN customer_info ci ON sm.customer_id = ci.customer_id
                            WHERE sm.br_code = :br AND (sm.authorized_status IS NULL OR sm.authorized_status = 'N') 
                            ORDER BY sales_entry_date DESC");
$stmtMstAll->execute(['br'=>$brCode]);
$salesMasters = $stmtMstAll->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sales Entry</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    </head>
<body class="d-flex flex-column min-vh-100">
<?php include 'header.php'; ?>
<main class="container py-4 flex-grow-1">
  <div class="d-flex justify-content-between align-items-center mb-3">
      <h3 class="d-flex align-items-center">
    <i class="bi bi-cart-check-fill me-2 text-primary"></i> 
    Sales Entry
</h3>
  </div>

  <?= $message ?>

  <?php if ($canInsert): ?>
  <div class="card mb-4">
    <div class="card-body">
      <input type="hidden" id="mst_id_edit" value="">
      
      <div class="row g-2 mb-3 border p-2 rounded bg-light">
        
        <div class="col-md-3">
          <label>Customer Phone <span class="text-danger">*</span></label>
          <input type="text" id="customer_phone" class="form-control" required placeholder="Enter phone number">
          <input type="hidden" id="customer_id_hidden">
        </div>
        
        <div class="col-md-3">
          <label>Customer Name <span class="text-danger">*</span></label>
          <input type="text" id="customer_name" class="form-control" required placeholder="Enter customer name">
        </div>
        
        <div class="col-md-3">
          <label>Sales Voucher No. <span class="text-danger">*</span></label>
          <input type="text" id="voucher_ref" class="form-control" required value="<?= htmlspecialchars(gen_sales_voucher($brCode)) ?>">
        </div>
      </div>
      
      <hr>
      
      <div class="row g-2 mb-2">
        <h5><i class="bi bi-box-seam"></i> Add Item</h5>
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

        <div class="col-md-3">
          <label>Model</label>
          <select id="model" class="form-select">
            <option value="">Select</option>
          </select>
        </div>
      </div>

      <div class="row g-2 mb-2">
        <div class="col-md-2">
          <label>Price</label>
          <input type="number" id="price" class="form-control" step="0.01" min="0" value="0">
        </div>

        <div class="col-md-1">
          <label>Qty</label>
          <input type="number" id="qty" class="form-control" step="1" min="1" value="1">
        </div>

        <div class="col-md-2">
          <label>Commission Value</label>
          <input type="number" id="commission" class="form-control" step="0.01" min="0" value="0">
        </div>

        <div class="col-md-2">
          <label>Commission Type</label>
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
            <th>Price</th><th>Qty</th><th>Commission Value</th><th>Commission Type</th><th>Line Total</th><th>Action</th>
          </tr>
        </thead>
        <tbody></tbody>
      </table>

      <div class="row mt-3">
        <div class="col-md-3 ms-auto">
          <label>Sub Total</label>
          <input type="text" id="sub_total" class="form-control" readonly value="0.00">
        </div>
      </div>

      <div class="row mt-2">
        <div class="col-md-3 ms-auto">
          <label>Total Discount</label>
          <input type="number" id="total_discount" class="form-control" step="0.01" min="0" value="0">
        </div>
      </div>

      <div class="row mt-2">
        <div class="col-md-3 ms-auto">
          <label>Net Total</label>
          <input type="text" id="total" class="form-control" readonly value="0.00">
        </div>
      </div>

      <div class="row mt-2">
        <div class="col-md-3 ms-auto">
          <label>Payment</label>
          <input type="number" id="payment" class="form-control" step="0.01" min="0" value="0">
        </div>
      </div>

      <div class="row mt-2">
        <div class="col-md-3 ms-auto">
          <label>Due</label>
          <input type="text" id="due" class="form-control" readonly value="0.00">
        </div>
      </div>
            </div>
      <div class="text-end mt-3">
        <form id="salesForm" method="post">
          <input type="hidden" name="details_json" id="details_json">
          <input type="hidden" name="total_discount" id="hidden_discount">
          <input type="hidden" name="payment" id="hidden_payment">
          <input type="hidden" name="voucher_ref" id="hidden_voucher_ref">
          <input type="hidden" name="mst_id" id="hidden_mst_id">
          
          <input type="hidden" name="customer_id" id="hidden_customer_id">
          <input type="hidden" name="customer_name" id="hidden_customer_name">
          <input type="hidden" name="customer_phone" id="hidden_customer_phone">
          
          <input type="hidden" name="action" value="save">
          <button type="submit" class="btn btn-success" id="saveBtn"><i class="bi bi-save"></i> Save</button>
          <button type="button" class="btn btn-secondary" id="cancelEditBtn" style="display:none;"><i class="bi bi-x-circle"></i> Cancel Edit</button>
        </form>
      </div>

    </div>
  </div>
  <?php else: ?>
    <div class="alert alert-warning">You do not have permission to insert records.</div>
  <?php endif; ?>

  <hr>

  <h4>Unapproved Sales Entries</h4>
  <div class="table-responsive">
  <table class="table table-bordered  mt-3 table-hover border-3">
    <thead class="table-secondary">
      <tr>
        <th>Voucher</th><th>Customer</th><th>Net Total</th><th>Payment</th><th>Due</th><th>Date</th><th>Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($salesMasters as $mst): ?>
        <tr>
          <td><?= htmlspecialchars($mst['sales_voucher_ref']) ?></td>
          <td><?= htmlspecialchars($mst['customer_name'] ?? 'N/A') ?></td> 
          <td><?= number_format($mst['total_amount'], 2) ?></td>
          <td><?= number_format($mst['payment'], 2) ?></td>
          <td><?= number_format($mst['due_amount'], 2) ?></td>
          <td><?= htmlspecialchars(date('Y-m-d H:i', strtotime($mst['sales_entry_date']))) ?></td>
          <td>
            <?php if ($canEdit): ?>
              <button class="btn btn-sm btn-primary" onclick="editEntry('<?= htmlspecialchars($mst['sales_mst_id']) ?>')">
                <i class="bi bi-pencil-square"></i> Edit
              </button>
            <?php endif; ?>

            <?php if ($canApprove): ?>
              <button class="btn btn-sm btn-success" onclick="approveEntry('<?= htmlspecialchars($mst['sales_mst_id']) ?>')">
                <i class="bi bi-check-circle"></i> Approve
              </button>
            <?php endif; ?>

            <?php if ($canDelete): ?>
              <button class="btn btn-sm btn-danger" onclick="deleteEntry('<?= htmlspecialchars($mst['sales_mst_id']) ?>')">
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
const initialVoucherRef = document.getElementById('voucher_ref').value;

// Customer elements
const customerPhoneInput = document.getElementById('customer_phone');
const customerNameInput = document.getElementById('customer_name');
const customerIdHidden = document.getElementById('customer_id_hidden'); 

// Other elements
const supplier = document.getElementById('supplier');
const category = document.getElementById('category');
const model = document.getElementById('model');
const priceInput = document.getElementById('price');
const qtyInput = document.getElementById('qty');
const commInput = document.getElementById('commission');
const commTypeSelect = document.getElementById('commission_type');
const totalDiscountInput = document.getElementById('total_discount');
const paymentInput = document.getElementById('payment');
const saveBtn = document.getElementById('saveBtn');
const cancelEditBtn = document.getElementById('cancelEditBtn');
const mstIdHidden = document.getElementById('mst_id_edit');

// --- Utility: Debounce Function ---
function debounce(func, delay) {
    let timeoutId;
    return function(...args) {
        clearTimeout(timeoutId);
        timeoutId = setTimeout(() => {
            func.apply(this, args);
        }, delay);
    };
}

// --- Core Lookup Function ---
function lookupCustomerByPhone() {
    const phone = customerPhoneInput.value.trim();

    // Reset fields
    customerNameInput.value = '';
    customerIdHidden.value = '';
    customerNameInput.readOnly = false;

    // Only lookup when phone is exactly 11 digits
    if (!/^\d{11}$/.test(phone)) {
        return;
    }

    fetch(`sales_entry.php?lookup_customer_by_phone=1&phone=${encodeURIComponent(phone)}`)
        .then(res => res.json())
        .then(data => {
            if (data.found) {
                customerNameInput.value   = data.customer_name;
                customerIdHidden.value    = data.customer_id;
                customerNameInput.readOnly = true; // lock field
            } else {
                customerNameInput.readOnly = false;
                customerNameInput.focus();
            }
        })
        .catch(err => console.error("Lookup error:", err));
}


// --- Event Handlers Setup ---

// 1. Debounced Lookup (on key release)
const debouncedLookup = debounce(lookupCustomerByPhone, 500);
// Store the timeout ID externally for manual cancellation
let debouncedLookupTimeout = null; 

customerPhoneInput?.addEventListener('keyup', function(e) {
    // Only trigger the debounced lookup if the key isn't Enter
    if (e.key !== 'Enter') {
        // We use a custom call to debounce here to manage the timeout ID
        debouncedLookupTimeout = setTimeout(() => {
            lookupCustomerByPhone();
        }, 500);
    }
});

// 2. Immediate Lookup (on Enter key press)
customerPhoneInput?.addEventListener('keydown', function(e) {
    if (e.key === 'Enter') {
        e.preventDefault(); // Prevent form submission or default browser behavior
        clearTimeout(debouncedLookupTimeout); // Cancel any pending debounced lookup
        lookupCustomerByPhone(true); // Perform immediate lookup
    }
});

// 3. Immediate reset on input clearing
customerPhoneInput?.addEventListener('input', function() {
    if (this.value.trim() === '') {
        customerNameInput.value = '';
        customerIdHidden.value = '';
        customerNameInput.readOnly = false;
        clearTimeout(debouncedLookupTimeout); // Stop any pending lookup if the user clears the field
    }
});

// --- Dependent Select/Item Logic (Unchanged) ---
supplier?.addEventListener('change', function() {
  category.innerHTML = '<option value="">Select</option>';
  model.innerHTML = '<option value="">Select</option>';
  if (this.value) {
    fetch(`sales_entry.php?fetch_cat=1&supplier_id=${this.value}`)
      .then(r => r.json())
      .then(data => {
        data.forEach(c => category.innerHTML += `<option value="${c.product_category_id}">${c.product_category_name}</option>`);
      });
  }
});

category?.addEventListener('change', function() {
  model.innerHTML = '<option value="">Select</option>';
  if (this.value) {
    fetch(`sales_entry.php?fetch_model=1&product_category_id=${this.value}`)
      .then(r => r.json())
      .then(data => {
        data.forEach(m => {
          model.innerHTML += `<option 
                                value="${m.model_id}" 
                                data-price="${m.price}" 
                                data-stock-mst="${m.stock_mst_id}" 
                                data-stock-dtl="${m.stock_dtl_id}">
                                ${m.model_name}
                              </option>`;
        });
      });
  }
});

// When model is selected, priceInput remains same as before
model?.addEventListener('change', function() {
    const selected = this.selectedOptions[0];
    if (!selected) return;

    priceInput.value = selected.getAttribute('data-price') || 0;
    
    // Store these in the model element's dataset for the Add Row button to grab
    this.dataset.stockMstId = selected.getAttribute('data-stock-mst');
    this.dataset.stockDtlId = selected.getAttribute('data-stock-dtl');
});

model?.addEventListener('change', function() {
  priceInput.value = this.selectedOptions[0]?.getAttribute('data-price') || 0;
});

// --- Item Addition Logic (Updated) ---
document.getElementById('addRowBtn')?.addEventListener('click', function(e){
  e.preventDefault();
  
  const custName = customerNameInput.value.trim();
  const custPhone = customerPhoneInput.value.trim();
  
  if (!custName || !custPhone) {
    alert('Please ensure Customer Phone is checked and Name is entered first.');
    return;
  }
  
  const supplier_id = supplier.value;
  const product_category_id = category.value;
  const model_id = model.value;
  const price = parseFloat(priceInput.value) || 0;
  const qty = parseInt(qtyInput.value) || 0;
  const comm = parseFloat(commInput.value) || 0;
  const ctype = commTypeSelect.value;
  
  const selectedModel = model.selectedOptions[0];
  if (!model_id || qty <= 0 || price <= 0) {
    alert('Please select model and enter valid price and quantity (min 1).');
    return;
  }

  // --- NEW: Extract clean model name for display ---
  // Transforms "WAP-OL06 | 20500.00 | ..." into "WAP-OL06"
  const cleanModelName = selectedModel.text.split('|')[0].trim();

  // --- Get stock IDs and Original Buying Price ---
  const stockMstId = selectedModel.getAttribute('data-stock-mst');
  const stockDtlId = selectedModel.getAttribute('data-stock-dtl');
  const originalPrice = parseFloat(selectedModel.getAttribute('data-original-price')) || 0;

  const rowBase = price * qty;
  let commissionAmt = (ctype === 'PCT') ? (rowBase * comm / 100) : comm;
  const total = rowBase - commissionAmt;

  const entry = {
    supplier_name: supplier.selectedOptions?.[0]?.text || supplier_id,
    product_category_name: category.selectedOptions?.[0]?.text || product_category_id,
    
    // Use the clean name for the table rendering
    model_name: cleanModelName, 
    
    supplier_id: supplier_id,
    product_category_id: product_category_id,
    model_id: model_id,
    price: price,           // Selling Price
    original_price: originalPrice, // Buying Price (Cost)
    quantity: qty,
    commission_type: ctype,
    commission_value: comm, 
    total: total,
    
    stock_mst_id: stockMstId,
    stock_dtl_id: stockDtlId
  };

  details.push(entry);
  renderTable();

  // --- Reset fields ---
  model.value = '';
  priceInput.value = 0;
  qtyInput.value = 1;
  commInput.value = 0;
  commTypeSelect.value = 'PCT';
  model.focus(); 
});


// --- Table and Calculation Management (Unchanged) ---
function renderTable(){
  const tbody = document.querySelector('#entryTable tbody');
  tbody.innerHTML = '';
  let subtotal = 0; 
  
  details.forEach((d, i) => {
    subtotal += d.total;
    tbody.innerHTML += `<tr>
      <td>${d.supplier_name}</td>
      <td>${d.product_category_name}</td>
      <td>${d.model_name}</td>
      <td>${Number(d.price).toFixed(2)}</td>
      <td>${d.quantity}</td>
      <td>${d.commission_value}</td>
      <td>${d.commission_type}</td>
      <td>${Number(d.total).toFixed(2)}</td>
      <td><button type="button" class="btn btn-sm btn-danger" onclick="removeRow(${i})">X</button></td>
    </tr>`;
  });
  
  // Calculations
  const disc = parseFloat(totalDiscountInput.value) || 0;
  const pay = parseFloat(paymentInput.value) || 0;
  
  const netTotal = subtotal - disc;
  const due = netTotal - pay;
  
  document.getElementById('sub_total').value = subtotal.toFixed(2);
  document.getElementById('total').value = netTotal.toFixed(2);
  document.getElementById('due').value = due.toFixed(2);
}

function removeRow(i) {
  details.splice(i,1);
  renderTable();
}

totalDiscountInput?.addEventListener('input', renderTable);
paymentInput?.addEventListener('input', renderTable);

// --- Form Submission (Unchanged) ---
document.getElementById('salesForm')?.addEventListener('submit', function(e) {
  const voucher = document.getElementById('voucher_ref').value.trim();
  const custName = customerNameInput.value.trim();
  const custPhone = customerPhoneInput.value.trim();
  
  if (!voucher) {
    e.preventDefault();
    alert('Please enter Sales Voucher Number.');
    return;
  }
  if (!custName || !custPhone) {
    e.preventDefault();
    alert('Please enter Customer Phone and Name.');
    return;
  }
  if (details.length === 0) {
    e.preventDefault();
    alert('Please add at least one item.');
    return;
  }

  // Set hidden fields for POST
  document.getElementById('details_json').value = JSON.stringify(details);
  document.getElementById('hidden_discount').value = totalDiscountInput.value;
  document.getElementById('hidden_payment').value = paymentInput.value;
  document.getElementById('hidden_voucher_ref').value = voucher;
  document.getElementById('hidden_mst_id').value = mstIdHidden.value;
  
  // Pass customer ID, Name, and Phone
  document.getElementById('hidden_customer_id').value = customerIdHidden.value;
  document.getElementById('hidden_customer_name').value = custName;
  document.getElementById('hidden_customer_phone').value = custPhone;
});

// --- Edit/Reset Functions (Unchanged) ---
function resetForm() {
    details = [];
    renderTable();
    mstIdHidden.value = '';
    document.getElementById('voucher_ref').value = initialVoucherRef;
    
    // Reset customer fields
    customerPhoneInput.value = '';
    customerNameInput.value = '';
    customerIdHidden.value = '';
    customerNameInput.readOnly = false;
    
    totalDiscountInput.value = '0';
    paymentInput.value = '0';
    saveBtn.innerHTML = '<i class="bi bi-save"></i> Save';
    cancelEditBtn.style.display = 'none';
    window.scrollTo({top:0, behavior:'smooth'});
}

cancelEditBtn?.addEventListener('click', resetForm);

function editEntry(mstId){
  fetch(`sales_entry.php?mst_id=${mstId}`)
    .then(r => r.json())
    .then(data => {
      if (!data.master) { alert('Master not found'); return; }
      
      document.getElementById('voucher_ref').value = data.master.sales_voucher_ref || '';
      totalDiscountInput.value = data.master.discount || 0;
      paymentInput.value = data.master.payment || 0;
      
      // Load customer data
      customerPhoneInput.value = data.master.customer_phone || '';
      customerNameInput.value = data.master.customer_name || '';
      customerIdHidden.value = data.master.customer_id;
      customerNameInput.readOnly = true; 

      // Set the master ID for update
      mstIdHidden.value = mstId; 
      
      details = data.details;

      renderTable();
      saveBtn.innerHTML = '<i class="bi bi-arrow-repeat"></i> Update';
      cancelEditBtn.style.display = 'inline-block';
      window.scrollTo({top:0, behavior:'smooth'});
    })
    .catch(e => console.error("Error fetching entry for edit:", e));
}

// --- Status Change Functions (Unchanged) ---
function deleteEntry(mstId){
  if (!confirm('Are you sure you want to delete this sales entry?')) return;
  fetch('sales_entry.php', {
    method: 'POST',
    headers: {'Content-Type':'application/x-www-form-urlencoded'},
    body: 'action=delete&delete_mst_id=' + encodeURIComponent(mstId)
  })
  .then(r => r.text())
  .then(txt => { alert(txt); location.reload(); })
  .catch(e => alert("Network or server error during delete."));
}

function approveEntry(mstId){
  if (!confirm('Are you sure you want to approve this entry? This action cannot be undone.')) return;
  fetch('sales_entry.php', {
    method: 'POST',
    headers: {'Content-Type':'application/x-www-form-urlencoded'},
    body: 'action=approve&approve_mst_id=' + encodeURIComponent(mstId)
  })
  .then(r => r.text())
  .then(txt => { alert(txt); location.reload(); })
  .catch(e => alert("Network or server error during approval."));
}

document.addEventListener('DOMContentLoaded', renderTable);
</script>
<?php 
if (file_exists('footer.php')) include 'footer.php'; 
?>
</body>
</html>