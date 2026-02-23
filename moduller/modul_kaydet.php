<?php
ob_start();
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once "../class/mysql.php";
header("Content-Type: application/json");

$izinliSessionlar = []; // id => session_key

$result = $dba->query("SELECT id, session_key FROM session_parameters WHERE aktif = 1");

while ($row = $result->fetch_assoc()) {
    $izinliSessionlar[$row['id']] = $row['id'];
}

$id          = $_POST['id'] ?? '';
$kategori_id = $_POST['kategori_id'];
$isim        = $_POST['isim'];
$ikon        = $_POST['ikon'];
$yetki       = $_POST['yetki'];
$badge       = $_POST['badge'];
$siralama    = $_POST['siralama'];
$aktif       = $_POST['aktif'];
$style       = $_POST['style'];

$modul_tipi   = $_POST['modul_tipi'] ?? 'sayfa';
$hedef_url    = $_POST['hedef_url'] ?? null;
$parametreler = null;

/*
|--------------------------------------------------------------------------
| 3) Eğer iframe ise parametreleri DB whitelist üzerinden doğrula
|--------------------------------------------------------------------------
*/

if ($modul_tipi === 'iframe') {

    $secimler = $_POST['iframe_parametreler'] ?? []; // artık ID geliyor

    if (!empty($secimler)) {

        $filtreliSessionKeyler = [];

        foreach ($secimler as $secimId) {
            // integer güvenlik
            $secimId = (int)$secimId;

            if (isset($izinliSessionlar[$secimId])) {
                $filtreliSessionKeyler[] = $izinliSessionlar[$secimId];
            }
        }

        if (!empty($filtreliSessionKeyler)) {
            $parametreler = implode(",", $filtreliSessionKeyler);
        }
    }
}

/*
|--------------------------------------------------------------------------
| 4) INSERT veya UPDATE
|--------------------------------------------------------------------------
*/

if ($id == "") {

    // EKLE
    $stmt = $dba->prepare("INSERT INTO mod_moduller (kategori_id, isim, ikon, yetki, badge, siralama, aktif, modul_tipi, hedef_url, parametreler, style, kayit_yetkili)
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

    $stmt->bind_param(
        "issssiissssi",
        $kategori_id,
        $isim,
        $ikon,
        $yetki,
        $badge,
        $siralama,
        $aktif,
        $modul_tipi,
        $hedef_url,
        $parametreler,
        $style,
        $_SESSION['kullanici_id']
    );

    $islem = "ekle";

} else {
    // GÜNCELLE
    $stmt = $dba->prepare("UPDATE mod_moduller SET kategori_id = ?, isim = ?, ikon = ?, yetki = ?, badge = ?, siralama = ?, aktif = ?, modul_tipi = ?, hedef_url = ?, parametreler = ?, style = ?, update_yetkili = ? WHERE id = ?");

    $stmt->bind_param(
        "issssiissssii",
        $kategori_id,
        $isim,
        $ikon,
        $yetki,
        $badge,
        $siralama,
        $aktif,
        $modul_tipi,
        $hedef_url,
        $parametreler,
        $style,
        $_SESSION['kullanici_id'],
        $id
    );

    $islem = "guncelle";
}

if ($stmt->execute()) {
    echo json_encode([
        "ok" => true,
        "mesaj" => ($islem == "ekle" ? "Modül başarıyla eklendi" : "Modül güncellendi")
    ]);
} else {
    echo json_encode([
        "ok" => false,
        "mesaj" => $stmt->error
    ]);
}