<?php
header("Content-Type: application/json; charset=utf-8");

$kategori = $_GET['kategori'] ?? '';
$baseDir = __DIR__ . "/../backup/" . $kategori;
$webPath = "mevzuatlar/" . $kategori;

$dosyalar = [];
if(is_dir($baseDir)){
    foreach(scandir($baseDir) as $file){
        if($file !== "." && $file !== ".."){
            $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
            $dosyalar[] = [
                'isim' => $file,
                'uzanti' => $ext,
                'yol' => $webPath . "/" . $file
            ];
        }
    }
}

echo json_encode($dosyalar, JSON_UNESCAPED_UNICODE);