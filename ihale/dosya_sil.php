<?php
header("Content-Type: application/json; charset=UTF-8");

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception("Geçersiz istek metodu.");
    }

    if (empty($_POST['file_name'])) {
        throw new Exception("Dosya adı gönderilmedi.");
    }

    $file_name = basename($_POST['file_name']); // güvenlik için sadece dosya adı al
    $file_path = "C:/inetpub/wwwroot/portal/backup/formlar/" . $file_name;

    if (!file_exists($file_path)) {
        throw new Exception("Dosya bulunamadı.");
    }

    if (!unlink($file_path)) {
        throw new Exception("Dosya silinemedi.");
    }

    echo json_encode([
        "status" => "success",
        "message" => "Dosya başarıyla silindi."
    ]);

} catch(Exception $e){
    echo json_encode([
        "status" => "error",
        "message" => $e->getMessage()
    ]);
}
