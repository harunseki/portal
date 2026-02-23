<?php
header('Content-Type: application/json; charset=utf-8');
require_once "../class/mysql.php";

ini_set('display_errors', 1);
error_reporting(E_ALL);

/* ===============================
   HELPER
=============================== */
function jsonError(string $msg) {
    echo json_encode([
        'status'  => 'error',
        'message' => $msg
    ]);
    exit;
}

/* ===============================
   INPUTS
=============================== */
$tckn       = trim($_POST['tckn'] ?? '');
$cardNumber = trim($_POST['cardNumber'] ?? '');
$startDate  = $_POST['startDate'] ?? '';
$finishDate = $_POST['finishDate'] ?? '';
$package    = (int)($_POST['package'] ?? 0);
$source     = $_POST['source'] ?? 'online'; // online | admin

/* ===============================
   BASIC VALIDATION
=============================== */
if (!$tckn || strlen($tckn) !== 11) {
    jsonError('TCKN geçersiz');
}

if (strlen($cardNumber) < 5) {
    jsonError('Kart numarası geçersiz');
}

if (!in_array($package, [1, 15, 30], true)) {
    jsonError('Geçersiz paket seçimi');
}

if (!in_array($source, ['online', 'admin'], true)) {
    jsonError('Geçersiz işlem kaynağı');
}

if (!$startDate || !$finishDate) {
    jsonError('Tarih bilgisi eksik');
}

if (!strtotime($startDate) || !strtotime($finishDate)) {
    jsonError('Geçersiz tarih formatı');
}

if (strtotime($finishDate) < strtotime($startDate)) {
    jsonError('Bitiş tarihi başlangıç tarihinden küçük olamaz');
}

/* ===============================
   DATE NORMALIZATION
=============================== */
$startDate  = date('Y-m-d H:i:s', strtotime($startDate));
$finishDate = date('Y-m-d H:i:s', strtotime($finishDate));

/* ===============================
   DB INSERT
=============================== */
try {
    $stmt = $dba->prepare("
        INSERT INTO cardmealallowement
        (tckn, cardNumber, source, package, startDate, finishDate)
        VALUES (?, ?, ?, ?, ?, ?)
    ");

    if (!$stmt) {
        jsonError('Veritabanı hazırlık hatası');
    }

    $stmt->bind_param(
        'sssiss',
        $tckn,
        $cardNumber,
        $source,
        $package,
        $startDate,
        $finishDate
    );

    if (!$stmt->execute()) {
        jsonError('Kayıt eklenemedi');
    }

    $stmt->close();

    echo json_encode([
        'status'  => 'success',
        'message' => 'Yemek hakkı başarıyla tanımlandı'
    ]);
    exit;

} catch (Throwable $e) {

    echo json_encode([
        'status'  => 'error',
        'message' => 'Sistem hatası oluştu'
        // debug için:
        // 'debug' => $e->getMessage()
    ]);
    exit;
}