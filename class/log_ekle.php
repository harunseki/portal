<?php
session_start();
require_once 'mysql.php'; // dba bağlantısı varsa

$ip = $_SERVER['REMOTE_ADDR'];
$username = $_SESSION['ldap_username'];
$personelTC = $_SESSION['personelTC'];

if ($username!='harunseki') {
    if (isset($_POST["formlar"])) {
        $sil = $_POST['sil'] ?? '';
        $file = $_POST['file_name'] ?? '';
        $action = 'delete';
        $desc = "Form silindi : " . $file;

        if ($file !== '') {
            if ($sil !== '1') {
                $action = 'create';
                $desc = "Yeni form ekledi : " . $file;
            }
            $dba->addLog($ip, $username, $personelTC, $action, $desc);
            echo "Log kaydedildi.";
        } else {
            echo "Dosya adı eksik.";
        }
    } else if (isset($_POST["etkinlik_duyuru"])) {
        $sil = $_POST['sil'] ?? '';
        $file = $_POST['file_name'] ?? '';
        $action = 'delete';
        $desc = "Etkinlik-duyuru silindi: " . $file;

        if ($file !== '') {
            if ($sil !== '1') {
                $action = 'create';
                $desc = "Yeni etkinlik-duyuru ekledi : " . $file;
            }
            $dba->addLog($ip, $username, $personelTC, $action, $desc);
            echo "Log kaydedildi.";
        } else {
            echo "Dosya adı eksik.";
        }
    } else if (isset($_POST["sifre"])) {
        $file = $_POST['username'] ?? '';
        $action = 'update';
        $desc = "Kullanıcı şifresi sıfırlandı : " . $file;

        if ($file !== '') {
            $dba->addLog($ip, $username, $personelTC, $action, $desc);
            echo "Log kaydedildi.";
        } else {
            echo "Kullanıcı adı eksik.";
        }
    }
}
else echo "Log kaydedildi.";