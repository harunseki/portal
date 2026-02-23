<?php
ob_start();
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once "../class/mysql.php";

header("Content-Type: application/json; charset=utf-8");

if (!isset($_POST['id'])) {
    echo json_encode([
        "status" => "error",
        "message" => "ID eksik"
    ]);
    exit;
}

$id = intval($_POST['id']);

$stmt = $dba->prepare("UPDATE mod_moduller SET update_yetkili=?, aktif='0' WHERE id = ?");
$stmt->bind_param("ii", $_SESSION['kullanici_id'], $id);

if ($stmt->execute()) {
    echo json_encode([
        "status" => "success",
        "message" => "Modül başarıyla silindi."
    ]);
} else {
    echo json_encode([
        "status" => "error",
        "message" => "Silme işlemi başarısız!"
    ]);
}