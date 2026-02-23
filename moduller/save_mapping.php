<?php
require_once "../class/mysql.php";

header("Content-Type: application/json; charset=utf-8");

$response = [
    "success" => false,
    "message" => ""
];

try {

    if (empty($_POST['session_key']) || empty($_POST['source_type']) || empty($_POST['field_key'])) {
        throw new Exception("Eksik parametre gönderildi.");
    }

    $sessionKey = trim($_POST['session_key']);
    $sourceType = trim($_POST['source_type']);
    $fieldKey   = trim($_POST['field_key']);

    $stmt = $dba->prepare("INSERT INTO session_mappings (session_key, source_type, field_key, aktif) VALUES (?, ?, ?, 1) ON DUPLICATE KEY UPDATE source_type = VALUES(source_type), field_key   = VALUES(field_key), aktif = 1");

    if (!$stmt) {
        throw new Exception("Sorgu hazırlanamadı.");
    }

    $stmt->bind_param("sss", $sessionKey, $sourceType, $fieldKey);

    if (!$stmt->execute()) {
        throw new Exception("Mapping kaydedilemedi.");
    }

    $response["success"] = true;
    $response["message"] = "Mapping başarıyla kaydedildi.";

    http_response_code(200);

} catch (Exception $e) {

    $response["message"] = $e->getMessage();
    http_response_code(400);
}

echo json_encode($response);
exit;