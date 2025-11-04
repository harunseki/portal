<?php
require_once("../class/mysql.php");
session_start();


if ($_SERVER['REQUEST_METHOD'] === "POST" && $_POST['action'] === "delete_today") {
    $today = date('Y-m-d');

    $stmt = $dba->prepare("DELETE FROM pdks_logs WHERE date=?");
    $stmt->bind_param("s", $today);
    $success = $stmt->execute();
    $stmt->close();

    if ($success) {
        echo json_encode(["status" => "success", "today" => $today]);
        $ip = $_SERVER['REMOTE_ADDR'];
        $username = $_SESSION['ldap_username'];
        $personelTC = $_SESSION['personelTC'];
        $action='delete';
        $desc = "PDKS log silindi : ". $today;
        $dba->addLog($ip, $username, $personelTC, $action, $desc);
    } else {
        echo json_encode(["status" => "error", "message" => "Kayıt silinemedi"]);
    }
    exit;
}
echo json_encode(["status" => "error", "message" => "Geçersiz istek"]);