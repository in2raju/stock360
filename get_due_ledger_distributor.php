<?php
session_start();
require 'db.php';

$distributor_code = $_GET['distributor_code'] ?? '';
$brCode  = $_SESSION['user']['br_code'];
$orgCode = $_SESSION['user']['org_code'];

$ledger = [];
$balance = 0;

/* -------------------------
   1. Previous Due
--------------------------*/
$stmt = $pdo->prepare("
    SELECT distributor_due_id, due_amount, due_date
    FROM distributor_previous_due
    WHERE distributor_code = ?
      AND br_code = ?
      AND org_code = ?
");
$stmt->execute([$distributor_code, $brCode, $orgCode]);
$prevDue = $stmt->fetch(PDO::FETCH_ASSOC);

if ($prevDue) {
    $balance += $prevDue['due_amount'];
    $ledger[] = [
        'entry_date' => $prevDue['due_date'] ?? '',
        'type' => 'Previous Due',
        'ref_id' => $prevDue['distributor_due_id'],             // ref_id হিসেবে distributor_due_id
        'stock_voucher_ref' => $prevDue['distributor_due_id'], // Ledger-এর Voucher কলামে দেখাবে
        'dr' => number_format($prevDue['due_amount'], 2),
        'cr' => '',
        'balance' => number_format($balance, 2)
    ];
}

/* -------------------------
   2. Stock Voucher Due
--------------------------*/
$stmt = $pdo->prepare("
    SELECT stock_mst_id, stock_voucher_ref, due_amount, stock_entry_date
    FROM stock_mst
    WHERE distributor_code = ?
      AND br_code = ?
      AND org_code = ?
      AND authorized_status='Y'
    ORDER BY stock_entry_date ASC
");
$stmt->execute([$distributor_code, $brCode, $orgCode]);
$vouchers = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($vouchers as $v) {
    $balance += $v['due_amount'];
    $ledger[] = [
        'entry_date' => $v['stock_entry_date'],
        'type' => 'Voucher Due',
        'ref_id' => $v['stock_mst_id'],
        'stock_voucher_ref' => $v['stock_voucher_ref'],
        'dr' => number_format($v['due_amount'],2),
        'cr' => '',
        'balance' => number_format($balance,2)
    ];
}

/* -------------------------
   3. Installment Paid
--------------------------*/
$stmt = $pdo->prepare("
    SELECT 
        d.installment_id, 
        d.stock_mst_id, 
        d.installment_amount, 
        d.installment_date, 
        m.stock_voucher_ref,
        p.distributor_due_id
    FROM distributor_due_installment d
    LEFT JOIN stock_mst m ON d.stock_mst_id = m.stock_mst_id
    LEFT JOIN distributor_previous_due p 
        ON d.stock_mst_id IS NULL 
        AND d.distributor_code = p.distributor_code
        AND d.br_code = p.br_code
        AND d.org_code = p.org_code
    WHERE d.distributor_code = ?
      AND d.br_code = ?
      AND d.org_code = ?
    ORDER BY d.installment_date ASC
");
$stmt->execute([$distributor_code, $brCode, $orgCode]);
$installments = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($installments as $i) {
    $balance -= $i['installment_amount'];

    // Determine reference: stock_voucher_ref normally, or distributor_due_id if stock_mst_id is NULL
    if ($i['stock_mst_id'] === null) {
        $ref = $i['distributor_due_id'] ?? 'Previous Due';
    } else {
        $ref = $i['stock_voucher_ref'] ?? 'Unknown';
    }

    $ledger[] = [
        'entry_date' => $i['installment_date'],
        'type' => 'Installment Paid',
        'ref_id' => $i['installment_id'],
        'stock_voucher_ref' => $ref,
        'dr' => '',
        'cr' => number_format($i['installment_amount'], 2),
        'balance' => number_format($balance, 2)
    ];
}

header('Content-Type: application/json');
echo json_encode($ledger);
