<?php
session_start();
require 'db.php';

$customer_id = $_GET['customer_id'] ?? '';
$brCode  = $_SESSION['user']['br_code'];
$orgCode = $_SESSION['user']['org_code'];

$ledger = [];
$balance = 0;

// -------------------------
// 1. All Previous Dues (Debit)
// -------------------------
$stmt = $pdo->prepare("
    SELECT prev_due_id, previous_due_amount, entry_date, authorized_status
    FROM customer_previous_due
    WHERE customer_id = ? 
      AND br_code = ? 
      AND org_code = ?
      AND authorized_status = 'Y'
    ORDER BY entry_date ASC
");
$stmt->execute([$customer_id, $brCode, $orgCode]);
$allPrevDues = $stmt->fetchAll(PDO::FETCH_ASSOC);

if ($allPrevDues) {
    foreach ($allPrevDues as $due) {
        $amount = (float)$due['previous_due_amount'];
        $balance += $amount;
        
        $ledger[] = [
            'entry_date'        => $due['entry_date'],
            'type'              => 'Previous Due',
            'ref_id'            => $due['prev_due_id'],
            'sales_mst_id'      => null,
            // Changed from 'PREV-DUE' to the actual ID from the database
            'sales_voucher_ref' => $due['prev_due_id'], 
            'dr'                => number_format($amount, 2, '.', ''),
            'cr'                => '',
            'balance'           => $balance, 
            'authorized_status' => $due['authorized_status']
        ];
    }
}

// -------------------------
// 2. Voucher Due (Debit)
// -------------------------
$stmt = $pdo->prepare("
    SELECT sales_mst_id, sales_voucher_ref, due_amount, sales_entry_date
    FROM sales_mst
    WHERE customer_id = ? AND br_code = ? AND org_code = ? AND authorized_status='Y'
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
        'sales_mst_id' => $v['sales_mst_id'],
        'sales_voucher_ref' => $v['sales_voucher_ref'],
        'dr' => number_format($v['due_amount'], 2),
        'cr' => '',
        'balance' => $balance,
        'authorized_status' => 'Y'
    ];
}

// -------------------------
// 3. Installment Paid (Credit)
// -------------------------
$stmt = $pdo->prepare("
    SELECT 
        d.installment_id, 
        d.sales_mst_id, 
        d.installment_amount, 
        d.installment_date, 
        d.authorized_status,
        m.sales_voucher_ref
    FROM customer_due_installment d
    LEFT JOIN sales_mst m ON d.sales_mst_id = m.sales_mst_id
    WHERE d.customer_id = ? AND d.br_code = ? AND d.org_code = ?
    ORDER BY d.installment_date ASC
");
$stmt->execute([$customer_id, $brCode, $orgCode]);
$installments = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($installments as $i) {
    $balance -= $i['installment_amount'];
    $ledger[] = [
        'entry_date' => $i['installment_date'],
        'type' => 'Installment Paid',
        'ref_id' => $i['installment_id'],
        'sales_mst_id' => $i['sales_mst_id'],
        'sales_voucher_ref' => $i['sales_mst_id'] ? $i['sales_voucher_ref'] : 'Previous Due',
        'dr' => '',
        'cr' => number_format($i['installment_amount'], 2),
        'balance' => $balance,
        'authorized_status' => $i['authorized_status']
    ];
}

// -------------------------
// 4. Final Sorting & Formatting
// -------------------------
// Sort by date so the running balance logic holds up
usort($ledger, function($a, $b) {
    return strtotime($a['entry_date']) - strtotime($b['entry_date']);
});

// Format the numeric balance for the JSON response
foreach ($ledger as &$row) {
    $row['balance'] = number_format($row['balance'], 2);
}

header('Content-Type: application/json');
echo json_encode($ledger);