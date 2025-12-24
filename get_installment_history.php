<?php
session_start();
require 'db.php';

$customer_id = $_GET['customer_id'] ?? '';
$sales_mst_id = $_GET['sales_mst_id'] ?? null;
$brCode = $_SESSION['user']['br_code'] ?? '';
$orgCode = $_SESSION['user']['org_code'] ?? '';

if(!$customer_id) exit(json_encode([]));

// Previous due row
$stmtPrev = $pdo->prepare("SELECT previous_due_amount, entry_date, entry_user FROM customer_previous_due 
    WHERE customer_id=:cust AND br_code=:br AND org_code=:org
");
$stmtPrev->execute(['cust'=>$customer_id,'br'=>$brCode,'org'=>$orgCode]);
$prev = $stmtPrev->fetch(PDO::FETCH_ASSOC);

$history = [];
if($prev){
    $history[] = [
        'installment_date' => $prev['entry_date'],
        'sales_voucher_ref' => 'Previous Due',
        'installment_amount' => $prev['previous_due_amount'],
        'payment_mode' => '-',
        'entry_user' => $prev['entry_user']
    ];
}

// Installment history
$query = "SELECT d.*, m.sales_voucher_ref FROM customer_due_installment d 
          LEFT JOIN sales_mst m ON d.sales_mst_id = m.sales_mst_id
          WHERE d.customer_id=:cust AND d.br_code=:br AND d.org_code=:org";
$params = ['cust'=>$customer_id,'br'=>$brCode,'org'=>$orgCode];

if($sales_mst_id){
    $query .= " AND d.sales_mst_id=:sales";
    $params['sales'] = $sales_mst_id;
}

$query .= " ORDER BY d.installment_date ASC";

$stmtInst = $pdo->prepare($query);
$stmtInst->execute($params);
$installments = $stmtInst->fetchAll(PDO::FETCH_ASSOC);

$history = array_merge($history, $installments);

echo json_encode($history);
