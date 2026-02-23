<?php
header('Content-Type: application/json; charset=utf-8');
require_once "../class/mysql.php";

$msg = '';
$tckn       = trim($_POST['tckn'] ?? '');
$cardNumber = trim($_POST['cardNumber'] ?? '');
$startDate  = trim($_POST['startDate'] ?? '');
$finishDate = trim($_POST['finishDate'] ?? '');
$excludeId  = (int)($_POST['excludeId'] ?? 0); // opsiyonel
$source     = $_POST['source'] ?? 'online';   // admin | online

/* ===============================
   VALIDATION
=============================== */

if (!$tckn || !$cardNumber || !$startDate || !$finishDate) {
    echo json_encode([
        'status'  => 'error',
        'message' => 'Eksik veri'
    ]);
    exit;
}

/* Tarihleri normalize et */
try {
    $startDate  = (new DateTime($startDate))->format('Y-m-d');
    $finishDate = (new DateTime($finishDate))->format('Y-m-d');
} catch (Exception $e) {
    echo json_encode([
        'status'  => 'error',
        'message' => 'Geçersiz tarih formatı'
    ]);
    exit;
}

/* ===============================
   CONFLICT CHECK
=============================== */

$sql = "
    SELECT id
    FROM cardmealallowement
    WHERE tckn = ?
      AND startDate <= ?
      AND finishDate >= ?
";

$params = [$tckn, $finishDate, $startDate];

/* Update senaryosu varsa */
if ($excludeId > 0) {
    $sql .= " AND id != ?";
    $params[] = $excludeId;
}

$sql .= " LIMIT 1";

$stmt = $dba->prepare($sql);
$stmt->bind_param(
    str_repeat('s', count($params)),
    ...$params
);
$stmt->execute();
$res = $stmt->get_result();

if ($res && $res->num_rows > 0) {
    $msg = 'Personele ait aktif yemek hakkı bulunduğu için yeni hak tanımlanamaz';
    if ($source == 'online') {
        $msg = 'Aktif yemek hakkınız bulunduğu için yenisini alamazsınız..!';
    }
    echo json_encode([
        'status'  => 'conflict',
        'message' => $msg
    ]);
    exit;
}

echo json_encode([
    'status' => 'ok'
]);