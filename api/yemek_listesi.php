<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(1);

require_once("../class/mysql.php");
require_once("class/apiconfig.php");
require_once("../../htmlpurifier/library/HTMLPurifier.auto.php");

function rescape($text){
    $text = addslashes($text);
    $text = str_ireplace(["SLEEP","BENCHMARK"], "", $text);
    return mysqli_real_escape_string(mysqli_connect("localhost", "portal_hs", "hlVO2U2JrI.oB97Y", "portal"), $text);
}

// HTML Purifier
$config = HTMLPurifier_Config::createDefault();
$config->set('Core.Encoding', 'UTF-8');
$config->set('HTML.Doctype', 'HTML 4.01 Transitional');
$purifier = new HTMLPurifier($config);

$username = $purifier->purify(rescape($_POST['username']));
$password = $purifier->purify(rescape($_POST['password']));

header('Content-Type: application/json; charset=utf-8');

// Servisi çalıştır
verigetir_yemek($username, $password);

function verigetir_yemek($username, $password)
{
    global $dba, $kullanicibilgileri;

    // 🔒 Kullanıcı doğrulama
    if ($kullanicibilgileri['yemek_listesi_kontrol'][0]['kullanici'] !== $username ||
        $kullanicibilgileri['yemek_listesi_kontrol'][0]['sifre'] !== $password) {
        echo json_encode([
            "success" => 0,
            "message" => "Kullanıcı adı veya parola hatalı"
        ], JSON_UNESCAPED_UNICODE);
        return;
    }

    // 📅 Bu haftanın Pazartesi ve Cuma tarihlerini bul
    $bugun = date('Y-m-d');
    $hafta_baslangic = date('Y-m-d', strtotime('monday this week', strtotime($bugun)));
    $hafta_bitis = date('Y-m-d', strtotime('friday this week', strtotime($bugun)));

    // 🔎 SQL sorgusu
    $sql = "SELECT gun, tarih,
            JSON_ARRAYAGG(
                JSON_OBJECT(
                    'yemek', yemek, 'kalori', kalori
                )
            ) AS yemekler
            FROM yemekler
            WHERE tarih BETWEEN '$hafta_baslangic' AND '$hafta_bitis'
            GROUP BY gun, tarih
            ORDER BY tarih ASC";

    $qc = $dba->query($sql);

    $yemekler = [];
    while ($row = $dba->fetch_assoc($qc)) {
        $yemekler[] = [
            "gun" => $row['gun'],
            "tarih" => $row['tarih'],
            "yemekler" => json_decode($row['yemekler'], true) // JSON’u çözüyoruz 🔥
        ];
    }

    echo json_encode([
        "success" => 1,
        "message" => $yemekler
    ], JSON_UNESCAPED_UNICODE);
}