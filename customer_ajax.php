<?php
session_start();
require 'db.php'; // $pdo instance

if (!isset($_SESSION['user'])) {
    echo json_encode(['found'=>false, 'error'=>'Unauthorized']);
    exit();
}

$brCode = $_SESSION['user']['br_code'];

// ---------- Fetch customer by phone ----------
if (isset($_GET['phone'])) {
    header('Content-Type: application/json');
    $phone = trim($_GET['phone']);
    $stmt = $pdo->prepare("SELECT customer_id, customer_name FROM customer_info WHERE phone = :phone AND br_code = :br LIMIT 1");
    $stmt->execute(['phone'=>$phone,'br'=>$brCode]);
    $cust = $stmt->fetch(PDO::FETCH_ASSOC);
    echo json_encode($cust ? ['found'=>true,'customer_id'=>$cust['customer_id'],'customer_name'=>$cust['customer_name']] : ['found'=>false]);
    exit();
}

// ---------- Optional: add new customer ----------
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['customer_name'], $_POST['customer_phone'])) {
    header('Content-Type: application/json');
    $custName  = trim($_POST['customer_name']);
    $custPhone = trim($_POST['customer_phone']);
    if (!$custName || !$custPhone) {
        echo json_encode(['success'=>false,'error'=>'Name or phone missing']);
        exit();
    }

    $stmtCheck = $pdo->prepare("SELECT customer_id FROM customer_info WHERE phone = :phone AND br_code = :br LIMIT 1");
    $stmtCheck->execute(['phone'=>$custPhone,'br'=>$brCode]);
    if ($row = $stmtCheck->fetch(PDO::FETCH_ASSOC)) {
        echo json_encode(['success'=>true,'customer_id'=>$row['customer_id'],'message'=>'Customer exists']);
        exit();
    }

    $custId = $brCode . '-' . date('YmdHis') . '-' . rand(1000,9999);
    $stmtIns = $pdo->prepare("INSERT INTO customer_info (customer_id, customer_name, phone, br_code, entry_user, entry_date) VALUES (:id, :name, :phone, :br, :user, NOW())");
    $stmtIns->execute(['id'=>$custId,'name'=>$custName,'phone'=>$custPhone,'br'=>$brCode,'user'=>$_SESSION['user']['user_id']]);
    echo json_encode(['success'=>true,'customer_id'=>$custId,'message'=>'Customer added']);
}
