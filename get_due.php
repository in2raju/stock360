<?php
session_start();
require 'db.php';

$customer_id = $_GET['customer_id'] ?? '';
$sales_mst_id = $_GET['sales_mst_id'] ?? null;
$brCode = $_SESSION['user']['br_code'] ?? '';
$orgCode = $_SESSION['user']['org_code'] ?? '';

if(!$customer_id) exit("0.00");

// Previous due
$stmtPrev = $pdo->prepare("SELECT previous_due_amount FROM customer_previous_due 
    WHERE customer_id=:cust AND br_code=:br AND org_code=:org
");
$stmtPrev->execute(['cust'=>$customer_id,'br'=>$brCode,'org'=>$orgCode]);
$prevDue = $stmtPrev->fetchColumn() ?: 0;

// Total installments paid
$queryInstall = "SELECT SUM(installment_amount) FROM customer_due_installment WHERE customer_id=:cust AND br_code=:br AND org_code=:org";
$params = ['cust'=>$customer_id,'br'=>$brCode,'org'=>$orgCode];

if($sales_mst_id) {
    $queryInstall .= " AND sales_mst_id=:sales";
    $params['sales'] = $sales_mst_id;
}

$stmtInst = $pdo->prepare($queryInstall);
$stmtInst->execute($params);
$paid = $stmtInst->fetchColumn() ?: 0;

// Total sale amount (if voucher selected)
$totalSale = 0;
if($sales_mst_id){
    $stmtSale = $pdo->prepare("SELECT total_amount FROM sales_mst WHERE sales_mst_id=:sales AND br_code=:br AND org_code=:org");
    $stmtSale->execute(['sales'=>$sales_mst_id,'br'=>$brCode,'org'=>$orgCode]);
    $totalSale = $stmtSale->fetchColumn() ?: 0;
}

$currentDue = $prevDue + $totalSale - $paid;
echo number_format($currentDue,2);
