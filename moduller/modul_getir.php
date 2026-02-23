<?php
require_once "../class/mysql.php";
header("Content-Type: application/json; charset=utf-8");

if (!isset($_POST['id'])) {
    echo json_encode(["status" => "error", "message" => "ID bulunamadı"]);
    exit;
}

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;

$stmt = $dba->prepare("SELECT * FROM mod_moduller WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();

$result = $stmt->get_result();
$row = $result->fetch_assoc();   // 🔥 KRİTİK SATIR

if ($row) {

    echo json_encode([
        "status" => "success",
        "data" => $row
    ]);

} else {

    echo json_encode([
        "status" => "error",
        "message" => "Modül bulunamadı!"
    ]);
}