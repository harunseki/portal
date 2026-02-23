<?php
header('Content-Type: application/json; charset=utf-8');
require_once "../class/mysql.php";

$tckn = trim($_POST['tckn'] ?? '');
$cardNumber = trim($_POST['cardNumber'] ?? '');
$startDate  = $_POST['startDate'] ?? '';
$finishDate = $_POST['finishDate'] ?? '';

if (!$cardNumber || !$startDate || !$finishDate) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Eksik veri'
    ]);
    exit;
}

/* Tarih normalize */
$start = DateTime::createFromFormat('Y-m-d', $startDate);
$end   = DateTime::createFromFormat('Y-m-d', $finishDate);

if (!$start || !$end) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Tarih formatı geçersiz'
    ]);
    exit;
}

$startStr = $start->format('Y-m-d');
$endStr   = $end->format('Y-m-d');

/* Çakışma kontrolü */
$stmt = $dba->prepare("
    SELECT id
    FROM cardmealallowement
    WHERE tckn = ?
      AND startDate <= ?
      AND finishDate >= ?
    LIMIT 1
");

$stmt->bind_param('sss', $tckn, $startStr, $endStr);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows > 0) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Bu tarih aralığında aktif yemek hakkınız bulunmaktadır'
    ]);
    exit;
}

echo json_encode([
    'status' => 'ok'
]);