<?php
header("Content-Type: application/json; charset=utf-8");

$allowed = ['genelgeler','yonergeler','yonetmelikler'];
$kategori = $_GET['kategori'] ?? '';

if (!in_array($kategori, $allowed, true)) {
    echo json_encode([]);
    exit;
}

$baseDir = __DIR__ . "/../backup/" . $kategori;
$webPath = "/backup/" . $kategori;

$dosyalar = [];

if (is_dir($baseDir)) {
    foreach (scandir($baseDir) as $file) {
        if ($file !== "." && $file !== "..") {

            $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));

            $dosyalar[] = [
                'isim'   => $file,
                'uzanti' => $ext,
                'yol'    => $webPath . "/" . rawurlencode($file)
            ];
        }
    }
}

echo json_encode($dosyalar, JSON_UNESCAPED_UNICODE);