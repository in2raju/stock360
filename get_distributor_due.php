<?php
session_start();
require 'db.php';

$distributor_code = $_GET['distributor_code'] ?? '';
$stock_mst_id     = $_GET['stock_mst_id'] ?? null;

$brCode  = $_SESSION['user']['br_code'];
$orgCode = $_SESSION['user']['org_code'];

$previousDue = 0;
$voucherDue  = 0;
$paidAmount  = 0;

/* -------------------------
   Get Previous Due
--------------------------*/
$stmt = $pdo->prepare("
    SELECT sum(due_amount)
    FROM distributor_previous_due
    WHERE distributor_code = ? AND br_code = ? AND org_code = ? and authorized_status='Y'
");
$stmt->execute([$distributor_code, $brCode, $orgCode]);
$previousDue = $stmt->fetchColumn() ?: 0;

/* -------------------------
   Stock Voucher Selected
--------------------------*/
if (!empty($stock_mst_id)) {
    // Voucher total due
    $stmt = $pdo->prepare("
        SELECT due_amount
        FROM stock_mst
        WHERE stock_mst_id = ? AND distributor_code = ? AND br_code = ? AND org_code = ?
    ");
    $stmt->execute([$stock_mst_id, $distributor_code, $brCode, $orgCode]);
    $voucherDue = $stmt->fetchColumn() ?: 0;

    // Paid against this voucher
    $stmt = $pdo->prepare("
        SELECT IFNULL(SUM(installment_amount),0)
        FROM distributor_due_installment
        WHERE distributor_code = ? AND stock_mst_id = ? AND br_code = ? AND org_code = ?
    ");
    $stmt->execute([$distributor_code, $stock_mst_id, $brCode, $orgCode]);
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
        FROM distributor_due_installment
        WHERE distributor_code = ? AND stock_mst_id IS NULL AND br_code = ? AND org_code = ?
    ");
    $stmt->execute([$distributor_code, $brCode, $orgCode]);
    $paidAmount = $stmt->fetchColumn() ?: 0;

    $finalDue = $previousDue - $paidAmount;
}

if ($finalDue < 0) $finalDue = 0;

echo number_format($finalDue, 2, '.', '');
