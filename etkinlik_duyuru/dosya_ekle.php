<?php
header("Content-Type: application/json; charset=UTF-8");

try {
    require_once("../class/mysql.php"); // $dba

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception("Geçersiz istek metodu.");
    }

    // ==== Silme işlemi ====
    if(isset($_POST['delete_id'])) {
        $id = (int)$_POST['delete_id'];
        if($id <= 0) throw new Exception("Geçersiz ID");

        $fileData = $dba->query("SELECT file FROM files WHERE id='$id'")->fetch_assoc();
        if($fileData) {
            $filePath = __DIR__ . "/../img/files/" . $fileData['file'];
            if(file_exists($filePath)) unlink($filePath);
        }

        $dba->query("DELETE FROM files WHERE id='$id'");
        echo json_encode(["status"=>"success","message"=>"Dosya silindi"]);
        exit;
    }

    // ==== Ekleme işlemi ====
    $adi = isset($_POST['adi']) ? trim($_POST['adi']) : '';
    if(empty($adi)) throw new Exception("Dosya adı giriniz.");

    if(empty($_FILES['file']['name'])) throw new Exception("Dosya seçiniz.");

    $allowed_ext = ['jpg','jpeg','png','gif','pdf'];
    $ext = strtolower(pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION));
    if(!in_array($ext, $allowed_ext)) throw new Exception("Sadece JPG, PNG, GIF veya PDF yükleyebilirsiniz.");

    $adi_safe = $dba->escape($adi);
    $in_time = date('Y-m-d H:i:s');
    $yetkili_id = 1;

    $q = $dba->query("INSERT INTO files (adi, in_time, in_yetkili) VALUES ('$adi_safe','$in_time','$yetkili_id')");
    if(!$q) throw new Exception("Veritabanı hatası: " . $dba->error);

    $insertid = $dba->insert_id();
    $upload_dir = __DIR__ . "/../img/files/";
    if(!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);

    $isim = preg_replace("/[^a-zA-Z0-9_-]/", "", $adi) . '_' . $insertid . '.' . $ext;
    if(!move_uploaded_file($_FILES['file']['tmp_name'], $upload_dir . $isim)) {
        throw new Exception("Dosya yüklenemedi.");
    }

    $dba->query("UPDATE files SET file='".$dba->escape($isim)."' WHERE id='$insertid'");

    // Dosya tipi belirle
    $file_type = in_array($ext, ['jpg','jpeg','png','gif']) ? "image" : "pdf";

    echo json_encode([
        "status"=>"success",
        "message"=>"Dosya başarıyla eklendi",
        "id"=>$insertid,
        "adi"=>$adi,
        "file_name"=>$isim.$file_type
    ]);

} catch(Exception $e){
    echo json_encode(["status"=>"error","message"=>$e->getMessage()]);
}
?>
