<?php
ini_set('display_errors', 0);         // Hataları ekranda gösterme
ini_set('display_startup_errors', 0);
error_reporting(0);


require_once("class/mysql.php");
require_once("class/apiconfig.php");
require_once("../../htmlpurifier/library/HTMLPurifier.auto.php");

function rescape($text){
    $text=addslashes($text);
    $text=str_ireplace("SLEEP","",$text);
    $text=str_ireplace("BENCHMARK","",$text);

    return mysqli_real_escape_string(mysqli_connect("10.2.207.61", "root", "kobil2013", "cankaya"),$text);
}
// HTML Purifier ayarları
$config = HTMLPurifier_Config::createDefault();
$config->set('Core.Encoding', 'UTF-8');
$config->set('HTML.Doctype', 'HTML 4.01 Transitional');
$purifier = new HTMLPurifier($config);
$def = $config->getHTMLDefinition(true);
$def->addAttribute('div', 'data-tab-set-title', 'CDATA');

// Giriş verilerini temizle
$username = $purifier->purify(rescape($_POST['username']));
$password = $purifier->purify(rescape($_POST['password']));
$ad_soyad = $purifier->purify(rescape($_POST['ad_soyad']));
$tarih_baslangic = $purifier->purify(rescape($_POST['tarih_baslangic']));
$tarih_bitis = $purifier->purify(rescape($_POST['tarih_bitis']));

header('Content-Type: application/json; charset=utf-8');

// Veriyi çek
verigetir_stream($username, $password, $ad_soyad, $tarih_baslangic, $tarih_bitis);

function verigetir_stream($username, $password, $ad_soyad, $tarih_baslangic, $tarih_bitis)
{
    global $dba, $kullanicibilgileri;

    // Kullanıcı doğrulama
    if ($kullanicibilgileri['yemekhane_giris_kontrol'][0]['kullanici'] !== $username ||
        $kullanicibilgileri['yemekhane_giris_kontrol'][0]['sifre'] !== $password) {
        echo json_encode([
            "success" => 0,
            "message" => "Kullanıcı adı veya parola hatalı"
        ], JSON_UNESCAPED_UNICODE);
        return;
    }

    // Filtre oluştur
    $where = [];
    if (!empty($ad_soyad)) {
        $where[] = "CONCAT(carduser.`name`, ' ', carduser.surname) = '" . addslashes($ad_soyad) . "'";
    }
    // Eğer tarih başlangıç boşsa bugünden 1 hafta öncesini al
    if (empty($tarih_baslangic)) {
        $tarih_baslangic = date('Y-m-d', strtotime('-7 days'));
    }

    // Eğer bitiş tarihi boşsa bugünün tarihini al
    if (empty($tarih_bitis)) {
        $tarih_bitis = date('Y-m-d');
    }

    // Eğer başlangıç tarihi bitişten büyükse, yer değiştir
    if (strtotime($tarih_baslangic) > strtotime($tarih_bitis)) {
        [$tarih_baslangic, $tarih_bitis] = [$tarih_bitis, $tarih_baslangic];
    }

    // Tarih aralığına göre filtre oluştur
    $where[] = "cardmovement.date BETWEEN '" . addslashes($tarih_baslangic . ' 00:00:00') . "' 
            AND '" . addslashes($tarih_bitis . ' 23:59:59') . "'";

    $where_sql = !empty($where) ? " AND " . implode(" AND ", $where) : "";

    // JSON başlangıcı
    echo '{"success":1,"message":[';

    $limit = 1000;
    $offset = 0;
    $first = true;

    while (true) {
        $sql = "SELECT 
                    cardmovement.id, 
                    cardmovement.date AS giris_tarihi,  
                    CONCAT(carduser.`name`, ' ', carduser.surname) AS ad_soyad, 
                    carddevice.`name` AS yemekhane_adi
                FROM cardmovement
                INNER JOIN cardmealallowement ON cardmovement.meal = cardmealallowement.id
                INNER JOIN carduser ON cardmealallowement.employee = carduser.id 
                                     AND cardmovement.employee = carduser.id
                INNER JOIN carddevice ON cardmovement.device = carddevice.id
                WHERE cardmovement.id != '' $where_sql
                ORDER BY cardmovement.id
                LIMIT $limit OFFSET $offset";

        $qc = $dba->query($sql);
        $count = 0;

        while ($rowc = $dba->fetch_assoc($qc)) {
            if (!$first) echo ",";
            echo json_encode($rowc, JSON_UNESCAPED_UNICODE);
            $first = false;
            $count++;
        }

        if ($count < $limit) break; // veri bitti
        $offset += $limit;
    }

    // JSON kapanışı
    echo "]}";
}
