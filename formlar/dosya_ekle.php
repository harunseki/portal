<?php
header("Content-Type: application/json; charset=UTF-8");

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception("Geçersiz istek metodu.");
    }

    if (empty($_FILES['file']['name'])) {
        throw new Exception("Dosya seçiniz.");
    }

    $allowed_ext = ['doc','docx','jpg','jpeg','png','gif','pdf'];
    $ext = strtolower(pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION));

    if (!in_array($ext, $allowed_ext)) {
        throw new Exception("Sadece DOC, DOCX, JPG, PNG, GIF veya PDF yükleyebilirsiniz.");
    }

    $upload_dir = "C:/inetpub/wwwroot/portal/backup/formlar/" ;
    if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);

    // Aynı isim varsa benzersiz isim oluştur
    $base_name = pathinfo($_FILES['file']['name'], PATHINFO_FILENAME);
    /*$safe_name = preg_replace("/[^a-zA-Z0-9_-]/", "_", $base_name);*/
    $filename = $base_name . '.' . $ext;
    $counter = 1;
    while(file_exists($upload_dir . $filename)) {
        $filename = $safe_name . '_' . $counter . '.' . $ext;
        $counter++;
    }

    if (!move_uploaded_file($_FILES['file']['tmp_name'], $upload_dir . $filename)) {
        throw new Exception("Dosya yüklenemedi.");
    }

    echo json_encode([
        "status" => "success",
        "message" => "Dosya başarıyla yüklendi.",
        "file_name" => $filename
    ]);

} catch(Exception $e){
    echo json_encode([
        "status" => "error",
        "message" => $e->getMessage()
    ]);
}
