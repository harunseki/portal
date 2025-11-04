<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/phpqrcode/qrlib.php';

$text = isset($_GET['adres']) ? urldecode($_GET['adres']) : '';
$size = isset($_GET['size']) ? (int)$_GET['size'] : 5;

if (empty($text)) {
    die("QR için veri girilmedi!");
}

ob_clean(); // çıktı tamponunu temizle
header('Content-Type: image/png');

QRcode::png($text, false, QR_ECLEVEL_L, $size, 2);
