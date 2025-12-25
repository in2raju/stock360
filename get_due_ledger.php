<?php
session_start();
require 'db.php';

$customer_id = $_GET['customer_id'] ?? '';
$brCode  = $_SESSION['user']['br_code'];
$orgCode = $_SESSION['user']['org_code'];

$ledger = [];
$balance = 0;

// -------------------------
// 1. Previous Due
// -------------------------
$stmt = $pdo->prepare("
    SELECT previous_due_amount, entry_date
    FROM customer_previous_due
    WHERE customer_id = ?
      AND br_code = ?
      AND org_code = ?
");
$stmt->execute([$customer_id, $brCode, $orgCode]);
$prevDue = $stmt->fetch(PDO::FETCH_ASSOC);
if ($prevDue) {
    $balance += $prevDue['previous_due_amount'];
    $ledger[] = [
        'entry_date' => $prevDue['entry_date'] ?? '',
        'type' => 'Previous Due',
        'ref_id' => null,
        'sales_voucher_ref' => 'Previous Due',
        'dr' => number_format($prevDue['previous_due_amount'],2),
        'cr' => '',
        'balance' => number_format($balance,2)
    ];
}

// -------------------------
// 2. Voucher Due
// -------------------------
$stmt = $pdo->prepare("
    SELECT sales_mst_id, sales_voucher_ref, due_amount, sales_entry_date
    FROM sales_mst
    WHERE customer_id = ?
      AND br_code = ?
      AND org_code = ?
      AND authorized_status='Y'
    ORDER BY sales_entry_date ASC
");
$stmt->execute([$customer_id, $brCode, $orgCode]);
$vouchers = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($vouchers as $v) {
    $balance += $v['due_amount'];
    $ledger[] = [
        'entry_date' => $v['sales_entry_date'],
        'type' => 'Voucher Due',
        'ref_id' => $v['sales_mst_id'],
        'sales_voucher_ref' => $v['sales_voucher_ref'],
        'dr' => number_format($v['due_amount'],2),
        'cr' => '',
        'balance' => number_format($balance,2)
    ];
}

// -------------------------
// 3. Installment Paid
// -------------------------
$stmt = $pdo->prepare("
    SELECT d.installment_id, d.sales_mst_id, d.installment_amount, d.installment_date, 
           m.sales_voucher_ref
    FROM customer_due_installment d
    LEFT JOIN sales_mst m ON d.sales_mst_id = m.sales_mst_id
    WHERE d.customer_id = ?
      AND d.br_code = ?
      AND d.org_code = ?
    ORDER BY d.installment_date ASC
");
$stmt->execute([$customer_id, $brCode, $orgCode]);
$installments = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($installments as $i) {
    $balance -= $i['installment_amount'];

    // Determine reference
    $ref = $i['sales_voucher_ref'] ?? ($i['sales_mst_id'] === null ? 'Previous Due' : 'Unknown');

    $ledger[] = [
        'entry_date' => $i['installment_date'],
        'type' => 'Installment Paid',
        'ref_id' => $i['installment_id'],
        'sales_voucher_ref' => $ref,
        'dr' => '',
        'cr' => number_format($i['installment_amount'],2),
        'balance' => number_format($balance,2)
    ];
}

header('Content-Type: application/json');
echo json_encode($ledger);
