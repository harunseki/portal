<?php
require_once("../class/mysql.php"); // senin db sınıfın

header('Content-Type: application/json; charset=utf-8');

if (!isset($_POST['cardNumber']) || empty($_POST['cardNumber'])) {
    echo json_encode(['status' => 'error', 'message' => 'Kart numarası eksik']);
    exit;
}

$cardNumber = addslashes(trim($_POST['cardNumber']));

// Kart numarasına göre sorgu
$sql = "SELECT * FROM carduser WHERE cardNumber = '$cardNumber' LIMIT 1";
$res = $dba->query($sql);

if ($res && $res->num_rows > 0) {
    $data = $res->fetch_assoc();
    echo json_encode([
        'status' => 'found',
        'data' => $data
    ]);
} else {
    echo json_encode([
        'status' => 'not_found',
        'message' => 'Bu kart kayıtlı değil.'
    ]);
}
?>