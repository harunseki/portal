<?php
header('Content-Type: application/json; charset=utf-8');
require_once "../class/mysql.php";

try {
    $q = $dba->query("SELECT price FROM carddepartment WHERE durum = 1 AND sabit = 1 LIMIT 1");

    if (!$q || $q->num_rows === 0) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Sabit fiyat bulunamadı'
        ]);
        exit;
    }

    $r = $q->fetch_assoc();

    echo json_encode([
        'status' => 'ok',
        'daily_price' => (float)$r['price']
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Veritabanı hatası'
    ]);
}