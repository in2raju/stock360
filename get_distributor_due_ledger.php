<?php
session_start();
require 'db.php';

$distributor_code = $_GET['distributor_code'] ?? '';
$brCode  = $_SESSION['user']['br_code'] ?? '';
$orgCode = $_SESSION['user']['org_code'] ?? '';

$ledger  = [];
$balance = 0;

/* -------------------------
// 1. Distributor Previous Dues (Credit)
// ------------------------- */
$stmt = $pdo->prepare("
    SELECT distributor_due_id, due_amount, due_date, entry_date, authorized_status, remarks
    FROM distributor_previous_due 
    WHERE distributor_code = ? 
      AND br_code = ? 
      AND org_code = ?
      AND authorized_status = 'Y'
    ORDER BY due_date ASC, entry_date ASC
");
$stmt->execute([$distributor_code, $brCode, $orgCode]);
$allDistPrevDues = $stmt->fetchAll(PDO::FETCH_ASSOC);

if ($allDistPrevDues) {
    foreach ($allDistPrevDues as $due) {
        $amount = (float)$due['due_amount'];
        
        // For a Distributor (Supplier), previous due increases the Credit balance
        $balance += $amount; 
        
        $ledger[] = [
            'entry_date'        => !empty($due['due_date']) ? $due['due_date'] : $due['entry_date'],
            'type'              => 'Previous Due',
            'ref_id'            => $due['distributor_due_id'],
            'stock_voucher_ref' => $due['distributor_due_id'], // Using actual ID as reference
            'remarks'           => $due['remarks'],
            'dr'                => '', // No Debit for opening dues to suppliers
            'cr'                => number_format($amount, 2, '.', ''),
            'balance'           => $balance, 
            'authorized_status' => $due['authorized_status']
        ];
    }
}

/* 2. Stock Vouchers (CR) */
$stmt = $pdo->prepare("SELECT stock_mst_id, stock_voucher_ref, due_amount, stock_entry_date FROM stock_mst WHERE distributor_code = ? AND br_code = ? AND org_code = ? AND authorized_status = 'Y' ORDER BY stock_entry_date ASC");
$stmt->execute([$distributor_code, $brCode, $orgCode]);
$vouchers = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($vouchers as $v) {
    if ($v['due_amount'] <= 0) continue;
    $balance += $v['due_amount'];
    $ledger[] = [
        'entry_date'        => $v['stock_entry_date'],
        'type'              => 'Stock Voucher Due',
        'stock_voucher_ref' => $v['stock_voucher_ref'],
        'dr'                => '',
        'cr'                => number_format($v['due_amount'], 2),
        'balance'           => number_format($balance, 2),
        'authorized_status' => 'Y'
    ];
}

/* 3. Installments/Payments (DR) */
$stmt = $pdo->prepare("
    SELECT d.installment_id, d.installment_amount, d.installment_date, d.authorized_status, m.stock_voucher_ref
    FROM distributor_due_installment d
    LEFT JOIN stock_mst m ON d.stock_mst_id = m.stock_mst_id
    WHERE d.distributor_code = ? AND d.br_code = ? AND d.org_code = ?
    ORDER BY d.installment_date ASC
");
$stmt->execute([$distributor_code, $brCode, $orgCode]);
$installments = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($installments as $i) {
    $balance -= $i['installment_amount'];
    $ledger[] = [
        'id'                => $i['installment_id'],
        'entry_date'        => $i['installment_date'],
        'type'              => 'Payment to Distributor',
        'stock_voucher_ref' => $i['stock_voucher_ref'] ?? 'Previous Due',
        'dr'                => number_format($i['installment_amount'], 2),
        'cr'                => '',
        'balance'           => number_format($balance, 2),
        'authorized_status' => $i['authorized_status'] 
    ];
}

header('Content-Type: application/json');
echo json_encode($ledger);