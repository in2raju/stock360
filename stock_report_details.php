<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

$user    = $_SESSION['user'];
$orgCode = $user['org_code'];
$brCode  = $user['br_code'];   // ✅ BRANCH CODE ADDED

// Organization info
$orgStmt = $pdo->prepare("
    SELECT ORGANIZATION_NAME, ORGANIZATION_ADDRESS 
    FROM organization_info 
    WHERE ORG_CODE = :org_code
");
$orgStmt->execute(['org_code' => $orgCode]);
$org = $orgStmt->fetch(PDO::FETCH_ASSOC);

$orgName    = $org['ORGANIZATION_NAME'] ?? 'Organization';
$orgAddress = $org['ORGANIZATION_ADDRESS'] ?? '';

// Stock report (ORG + BRANCH)
$stockStmt = $pdo->prepare("
    SELECT 
    a.stock_mst_id AS stock_id,
    DATE(a.stock_date) AS stock_date,
    a.model,
    a.product_category_name AS product,
    a.supplier_name AS brand,
    b.price,
    a.remaining AS in_stock,
    b.price * a.remaining AS total_stock_value
FROM stock_details_view a
INNER JOIN product_model b 
    ON a.model_id = b.MODEL_ID
WHERE a.org_code = :org_code
  AND a.br_code  = :br_code
ORDER BY product, brand;
");

$stockStmt->execute([
    'org_code' => $orgCode,
    'br_code'  => $brCode
]);

$stockData = $stockStmt->fetchAll(PDO::FETCH_ASSOC);

include 'header.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Stock Status Report</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

<style>
body { background:#f8f9fa; }
.report-container { background:#fff; padding:20px; border-radius:6px; }

.table thead { background:#343a40; color:#fff; }
.table tfoot { background:#e9ecef; font-weight:bold; }

.print-header, .print-footer { display:none; }

@media print {

    /* ✅ LANDSCAPE MODE */
    @page {
        size: landscape;
        margin: 1.5cm;
    }

    body { background:#fff !important; }

    header, footer, nav, .no-print {
        display:none !important;
    }

    .print-header {
        display:block;
        text-align:center;
        margin-bottom:20px;
    }

    .print-header h2 {
        font-size:26px;
        font-weight:700;
        margin-bottom:4px;
    }

    .print-header h5 {
        font-size:14px;
        margin-bottom:4px;
    }

    .print-header h4 {
        font-size:16px;
        margin-top:6px;
    }

    .print-footer {
        display:block;
        position:fixed;
        bottom:0;
        width:100%;
        text-align:center;
        font-size:10px;
        color:#777;
        border-top:1px solid #ddd;
        padding-top:4px;
    }

    .table-responsive { overflow:visible !important; }
    table { font-size:12px; }
}
</style>
</head>

<body>

<div class="container-fluid report-container">

    <!-- SEARCH & PRINT -->
    <div class="row mb-3 no-print">
        <div class="col-md-4">
            <input type="text" id="stockSearch" class="form-control"
                   placeholder="Search any field..." onkeyup="filterTable()">
        </div>
        <div class="col-md-8 text-end">
            <button class="btn btn-success" onclick="window.print()">Print Report</button>
        </div>
    </div>

    <!-- PRINT HEADER -->
    <div class="print-header">
        <h2><?= htmlspecialchars($orgName) ?></h2>
        <h5><?= htmlspecialchars($orgAddress) ?></h5>
        <h4 class="text-secondary">Stock Status Report</h4>
        <p>
            Branch Code: <strong><?= htmlspecialchars($brCode) ?></strong> |
            <?= date('d M Y, H:i') ?>
        </p>
        <hr>
    </div>

    <!-- TABLE -->
    <div class="table-responsive">
        <table class="table table-bordered table-striped table-hover" id="stockTable">
            <thead>
                <tr>
                    <th>SL</th>
                    <th>Stock ID</th>
                    <th>Date</th>
                    <th>Model</th>
                    <th>Category</th>
                    <th>Brand</th>
                    <th class="text-end">Price</th>
                    <th class="text-end">In Stock</th>
                    <th class="text-end">Stock Value</th>
                </tr>
            </thead>
            <tbody>
            <?php
            $sl = 1;
            $grandQty = 0;
            $grandValue = 0;

            foreach ($stockData as $row):
                $grandQty   += $row['in_stock'];
                $grandValue += $row['total_stock_value'];
            ?>
                <tr>
                    <td class="sl"><?= $sl++ ?></td>
                    <td><?= htmlspecialchars($row['stock_id']) ?></td>
                    <td><?= htmlspecialchars($row['stock_date']) ?></td>
                    <td><?= htmlspecialchars($row['model']) ?></td>
                    <td><?= htmlspecialchars($row['product']) ?></td>
                    <td><?= htmlspecialchars($row['brand']) ?></td>
                    <td class="text-end"><?= number_format($row['price'],2) ?></td>
                    <td class="text-end in-stock"><?= number_format($row['in_stock']) ?></td>
                    <td class="text-end stock-value"><?= number_format($row['total_stock_value'],2) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="7" class="text-end">Grand Total</td>
                    <td class="text-end" id="grandInStock"><?= number_format($grandQty) ?></td>
                    <td class="text-end" id="grandTotal"><?= number_format($grandValue,2) ?></td>
                </tr>
            </tfoot>
        </table>
    </div>

    <!-- PRINT FOOTER -->
    <div class="print-footer">
        Generated by Stock Management System
    </div>

</div>

<script>
function filterTable() {
    const q = document.getElementById('stockSearch').value.toLowerCase();
    const rows = document.querySelectorAll('#stockTable tbody tr');

    let totalQty = 0;
    let totalValue = 0;
    let sl = 1;

    rows.forEach(row => {
        if (row.innerText.toLowerCase().includes(q)) {
            row.style.display = '';
            row.querySelector('.sl').innerText = sl++;

            totalQty += parseFloat(row.querySelector('.in-stock').innerText.replace(/,/g,'')) || 0;
            totalValue += parseFloat(row.querySelector('.stock-value').innerText.replace(/,/g,'')) || 0;
        } else {
            row.style.display = 'none';
        }
    });

    document.getElementById('grandInStock').innerText = totalQty.toLocaleString();
    document.getElementById('grandTotal').innerText =
        totalValue.toLocaleString(undefined,{minimumFractionDigits:2});
}
</script>

<?php include 'footer.php'; ?>
</body>
</html>
