<?php
header("Content-Type: application/json; charset=UTF-8");
require_once("../class/mysql.php"); // $dba bağlantısı

try {
    if($_SERVER['REQUEST_METHOD'] !== 'POST') throw new Exception("Geçersiz istek metodu.");
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    if($id <= 0) throw new Exception("Geçersiz ID.");

    // Dosya adını al
    $res = $dba->query("SELECT file FROM files WHERE id='$id' ");
    if($res->num_rows == 0) throw new Exception("Dosya bulunamadı.");
    $row = $res->fetch_assoc();
    $file_path = __DIR__ . "/../img/files/" . $row['file'];
    $isim=$row['file'];
    // Dosyayı sil
    if(file_exists($file_path)) unlink($file_path);

    // DB kaydını sil
    $dba->query("DELETE FROM files WHERE id=$id");

    echo json_encode([
        "status"=>"success",
        "message"=>"Dosya başarıyla silindi",
        "file_name"=>$isim
    ]);
} catch(Exception $e){
    echo json_encode([
        "status"=>"error",
        "message"=>$e->getMessage()
    ]);
}