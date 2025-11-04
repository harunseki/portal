<?php
require_once '../class/mysql.php'; // veritabanı bağlantın
header('Content-Type: application/json');

$id = (int)($_POST['id'] ?? 0);
$field = $_POST['field'] ?? '';
$ip = $_POST['ip'] ?? '';
$ldap_username = $_POST['ldap_username'] ?? '';
$personelTC = $_POST['personelTC'] ?? '';

$izinli = ['admin','sifre','yemekhane','etkinlik_duyuru','yetkili_islemleri','qrcode','formlar','popup_yonetim'];
if (!$id || !in_array($field, $izinli)) {
    echo json_encode(['success' => false, 'message' => 'Geçersiz parametre']);
    exit;
}

$q = $dba->query("SELECT `$field`, username FROM yetkili WHERE id=$id");
$row = $dba->fetch_assoc($q);
$mevcut = (int)$row[$field];
$yeni = $mevcut ? 0 : 1;

$dba->query("UPDATE yetkili SET `$field`=$yeni WHERE id=$id");

$dba->addLog($ip, $ldap_username, $personelTC, "update", "Yetkili bilgileri güncellendi : ".$row['username']);

echo json_encode(['success' => true, 'yeni_durum' => $yeni]);
