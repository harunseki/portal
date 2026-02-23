<?php
require_once '../class/mysql.php';
header('Content-Type: application/json');

// POST verileri
$user_id       = (int)($_POST['id'] ?? 0);            // yetkili.id
$yetki_id      = (int)($_POST['yetki_id'] ?? 0);      // yetki_tanimlari.id
$ip            = $_POST['ip'] ?? '';
$ldap_username = $_POST['ldap_username'] ?? '';
$personelTC    = $_POST['personelTC'] ?? '';

if (!$user_id || !$yetki_id) {
    echo json_encode(['success' => false, 'message' => 'Geçersiz parametre']);
    exit;
}

/* YETKİ VAR MI KONTROL ET */
$sql = $dba->prepare("SELECT id FROM yetkili_moduller WHERE kullanici_id = ? AND yetki_key = ?");
$sql->bind_param("ii", $user_id, $yetki_id);
$sql->execute();
$res = $sql->get_result();
$kayit_var = $res->num_rows > 0;

/* KULLANICININ ADINI LOG İÇİN AL */
$u = $dba->query("SELECT username FROM yetkili WHERE id = $user_id");
$urow = $dba->fetch_assoc($u);
$username = $urow['username'] ?? '';

/* YETKİYİ AÇ / KAPAT */
if ($kayit_var) {
    // YETKİ KAPATILIYOR → kaydı sil
    $dba->query("DELETE FROM yetkili_moduller WHERE kullanici_id = $user_id AND yetki_key = $yetki_id");

    $dba->addLog(
        $ip,
        $ldap_username,
        $personelTC,
        "update",
        "Yetki kapatıldı (user: $username, yetki_id: $yetki_id)"
    );

    echo json_encode(['success' => true, 'yeni_durum' => 0]);
} else {
    // YETKİ AÇILIYOR → kayıt ekle
    $dba->query("INSERT INTO yetkili_moduller (kullanici_id, yetki_key, deger) VALUES ($user_id, $yetki_id, 1)");

    $dba->addLog(
        $ip,
        $ldap_username,
        $personelTC,
        "update",
        "Yetki aktif edildi (user: $username, yetki_id: $yetki_id)"
    );

    echo json_encode(['success' => true, 'yeni_durum' => 1]);
}