<?php
require_once "../../class/mysql.php";
function jsonError($message, $code = 400) {
    http_response_code($code);
    echo json_encode([
        'status'  => 'error',
        'message' => $message
    ]);
    exit;
}

$tckn         = $_POST['tckn'] ?? null;
$cardNumber   = $_POST['cardNumber'] ?? null;
$package      = intval($_POST['packageDays'] ?? 0);
$departmentId = intval($_POST['departmentId'] ?? 0);

if (!$tckn || !$cardNumber || !in_array($package,[1,15,30])) {
    jsonError('Geçersiz istek');
}

/* ===============================
   TARİH HESABI
=============================== */

$today = new DateTime();
$day   = (int)$today->format('d');
$month = (int)$today->format('m');
$year  = (int)$today->format('Y');

if ($package === 1) {
    $start = clone $today;
    $end   = clone $today;
}

if ($package === 15) {
    if ($day !== 1 && $day !== 15) {
        jsonError('15 günlük paket bu tarihte alınamaz');
    }

    if ($day === 1) {
        $start = new DateTime("$year-$month-01");
        $end   = new DateTime("$year-$month-15");
    }

    if ($day === 15) {
        $start = new DateTime("$year-$month-15");
        $end   = new DateTime(date('Y-m-t'));
    }
}

if ($package === 30) {
    if ($day < 15 || $day > 20) {
        jsonError('30 günlük paket bu tarihte alınamaz');
    }

    $start = new DateTime("$year-$month-16");
    $end   = (new DateTime())->modify('first day of next month')->modify('+14 days');
}

/* ===============================
   FİYAT HESABI
=============================== */

if ($package === 1) {

    $q = $dba->query("
        SELECT price 
        FROM carddepartment 
        WHERE durum = 1 AND sabit = 1
        LIMIT 1
    ");

    if ($q->num_rows === 0) {
        jsonError('Günlük fiyat tanımlı değil');
    }

    $amount = (float)$q->fetch_assoc()['price'];
}

if ($package === 15 || $package === 30) {

    if ($departmentId <= 0) {
        jsonError('Departman bilgisi eksik');
    }

    $q = $dba->query("
        SELECT price 
        FROM carddepartment 
        WHERE id = $departmentId AND durum = 1
        LIMIT 1
    ");

    if ($q->num_rows === 0) {
        jsonError('Aylık fiyat bulunamadı');
    }

    $monthly = (float)$q->fetch_assoc()['price'];

    if ($package === 15) {
        $amount = $monthly / 2;
    }

    if ($package === 30) {
        $amount = $monthly;
    }
}

/* ===============================
   ORDER OLUŞTUR
=============================== */

$orderCode = 'ORD-' . date('YmdHis') . '-' . rand(1000,9999);

$dba->query("INSERT INTO orders SET
            orderCode       = '$orderCode',
            tckn            = '$tckn',
            cardNumber      = '$cardNumber',
            packageDays     = $package,
            startDate       = '".$start->format('Y-m-d 00:00:00')."',
            finishDate      = '".$end->format('Y-m-d 23:59:59')."',
            amount          = '$amount',
            paymentProvider = 'BANKA_POS',
            status          = 'pending'");
if (!$dba->affected_rows()) {
    jsonError('Sipariş oluşturulamadı');
}

/* ===============================
   BANKA YÖNLENDİRME
=============================== */
$bankUrl = ($_SERVER['HTTPS'] ?? false ? 'https' : 'http')
    . '://' . $_SERVER['HTTP_HOST']
    . '/yemekhane/pos/fake_bank.php';

$callbackUrl = ($_SERVER['HTTPS'] ?? false ? 'https' : 'http')
    . '://' . $_SERVER['HTTP_HOST']
    . '/yemekhane/pos/bank_callback.php';
$returnUrl = ($_SERVER['HTTPS'] ?? false ? 'https' : 'http')
    . '://' . $_SERVER['HTTP_HOST']
    . '/4-yemekhane';

/* FAKE BANK YÖNLENDİRME */
echo json_encode([
    'status' => 'redirect',
    'bankUrl' => $bankUrl,
    'payload' => [
        'orderCode'  => $orderCode,
        'amount'     => $amount,
        'callbackUrl'=> $callbackUrl,
        'returnUrl'  => $returnUrl
    ]
]);
exit;