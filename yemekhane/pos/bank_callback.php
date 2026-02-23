<?php
require_once "../../class/mysql.php";

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$orderCode = trim($_POST['orderCode'] ?? '');
$status    = $_POST['status'] ?? 'FAIL';
$amount    = (float)($_POST['amount'] ?? 0);
$txId      = $_POST['transactionId'] ?? uniqid('BANK-');
$returnUrl = $_POST['returnUrl'] ?? '/3-yemekhane';
$source = 'online';

function redirectWithResult($url, $success, $message) {
    $sep = (parse_url($url, PHP_URL_QUERY)) ? '&' : '?';
    header("Location: {$url}{$sep}" . http_build_query([
            'payment' => $success ? 'success' : 'fail',
            'msg'     => $message
        ]));
    exit;
}

if (!$orderCode) {
    redirectWithResult($returnUrl, false, 'Order kod kayıp');
}

/* ORDER */
$stmt = $dba->prepare("SELECT * FROM orders WHERE orderCode = ? LIMIT 1");
$stmt->bind_param("s", $orderCode);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();

if (!$order) {
    redirectWithResult($returnUrl, false, 'Order bulunamadı');
}

/* idempotent */
/*if (in_array($order['status'], ['completed','allowance_failed','payment_failed'], true)) {
    redirectWithResult($returnUrl, true, 'Bu işlem daha önce tamamlandı');
}*/

/* amount check */
if ((float)$order['amount'] !== $amount) {
    $dba->prepare("
        UPDATE orders SET status='payment_failed', errorMessage='Amount mismatch'
        WHERE id=?
    ")->execute([$order['id']]);

    redirectWithResult($returnUrl, false, 'Ödeme bilgileri eşleşmiyor');
}

/* payment fail */
if ($status !== 'SUCCESS') {
    $stmt = $dba->prepare("
        UPDATE orders SET
            status='payment_failed',
            paymentRef=?,
            errorMessage='Bank payment failed'
        WHERE id=?
    ");
    $stmt->bind_param("si", $txId, $order['id']);
    $stmt->execute();

    redirectWithResult($returnUrl, false, 'Ödeme başarısız');
}

$dba->begin_transaction();

try {
    $stmt = $dba->prepare("
    INSERT INTO cardmealallowement
    (tckn, cardNumber, startDate, finishDate, package, source, amount)
    VALUES (?,?,?,?,?,?,?)
");
    $stmt->bind_param(
        "sssssis",
        $order['tckn'],
        $order['cardNumber'],
        $order['startDate'],
        $order['finishDate'],
        $order['packageDays'],
        $order,
        $order['amount']
    );

    $stmt->execute();

    $stmt = $dba->prepare("
        UPDATE orders SET
            status='completed',
            paymentRef=?,
            errorMessage=''
        WHERE id=?
    ");
    $stmt->bind_param("si", $txId, $order['id']);
    $stmt->execute();

    $dba->commit();

    redirectWithResult($returnUrl, true, 'Ödeme başarıyla tamamlandı');

} catch (Throwable $e) {

    print_r($e->getMessage());

    $dba->rollback();

    $stmt = $dba->prepare("
        UPDATE orders SET
            status='allowance_failed',
            paymentRef=?,
            errorMessage=?
        WHERE id=?
    ");
    $msg = $e->getMessage();
    $stmt->bind_param("ssi", $txId, $msg, $order['id']);
    $stmt->execute();

    redirectWithResult($returnUrl, false, 'Yemek hakkı tanımlanamadı');
}