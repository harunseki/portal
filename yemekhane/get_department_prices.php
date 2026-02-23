<?php
header('Content-Type: application/json; charset=utf-8');
require_once("../class/mysql.php");

$departmentId = (int)($_POST['departmentId'] ?? 0);

if ($departmentId <= 0) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Geçersiz departman'
    ]);
    exit;
}

$q = $dba->query("SELECT price FROM carddepartment WHERE id = $departmentId AND durum = 1 LIMIT 1");

if (!$q || $q->num_rows === 0) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Bu departman için fiyat tanımlı değil'
    ]);
    exit;
}

$r = $q->fetch_assoc();

echo json_encode([
    'status' => 'ok',
    'prices' => [
        'price' => (int)$r['price']
    ]
]);