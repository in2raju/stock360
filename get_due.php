<?php
session_start();
require 'db.php';

$customer_id  = $_GET['customer_id'] ?? '';
$sales_mst_id = $_GET['sales_mst_id'] ?? null;

$brCode  = $_SESSION['user']['br_code'];
$orgCode = $_SESSION['user']['org_code'];

$previousDue = 0;
$voucherDue  = 0;
$paidAmount  = 0;

/* -------------------------
   Get Previous Due
--------------------------*/
$stmt = $pdo->prepare("
    SELECT previous_due_amount
    FROM customer_previous_due
    WHERE customer_id = ? AND br_code = ? AND org_code = ?
");
$stmt->execute([$customer_id, $brCode, $orgCode]);
$previousDue = $stmt->fetchColumn() ?: 0;

/* -------------------------
   Voucher Selected
--------------------------*/
if (!empty($sales_mst_id)) {
    // Voucher total due
    $stmt = $pdo->prepare("
        SELECT due_amount
        FROM sales_mst
        WHERE sales_mst_id = ? AND customer_id = ? AND br_code = ? AND org_code = ?
    ");
    $stmt->execute([$sales_mst_id, $customer_id, $brCode, $orgCode]);
    $voucherDue = $stmt->fetchColumn() ?: 0;

    // Paid against this voucher
    $stmt = $pdo->prepare("
        SELECT IFNULL(SUM(installment_amount),0)
        FROM customer_due_installment
        WHERE customer_id = ? AND sales_mst_id = ? AND br_code = ? AND org_code = ?
    ");
    $stmt->execute([$customer_id, $sales_mst_id, $brCode, $orgCode]);
    $paidAmount = $stmt->fetchColumn() ?: 0;

    $finalDue = $voucherDue - $paidAmount;
}
/* -------------------------
   Previous Due Selected
--------------------------*/
else {
    // Paid against previous due
    $stmt = $pdo->prepare("
        SELECT IFNULL(SUM(installment_amount),0)
        FROM customer_due_installment
        WHERE customer_id = ? AND sales_mst_id IS NULL AND br_code = ? AND org_code = ?
    ");
    $stmt->execute([$customer_id, $brCode, $orgCode]);
    $paidAmount = $stmt->fetchColumn() ?: 0;

    $finalDue = $previousDue - $paidAmount;
}

if ($finalDue < 0) $finalDue = 0;

echo number_format($finalDue, 2, '.', '');
