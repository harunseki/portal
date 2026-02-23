<?php
header('Content-Type: application/json; charset=utf-8');
require_once "../class/mysql.php";

$id    = (int)($_POST['id'] ?? 0);
$name  = trim($_POST['name'] ?? '');
$price = (float)($_POST['price'] ?? 0);

if ($name === '' || $price <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Eksik veya hatalı bilgi']);
    exit;
}

try {
    if ($id > 0) {
        $stmt = $dba->prepare("UPDATE carddepartment SET name = ?, price = ? WHERE id = ?");

        if (!$stmt) throw new Exception($dba->error());

        $stmt->bind_param("sdi", $name, $price, $id);
        $stmt->execute();

        echo json_encode(['status' => 'success', 'message' => 'Departman güncellendi']);

    }
    else {
        $stmt = $dba->prepare("INSERT INTO carddepartment (name, price) VALUES (?, ?)");

        if (!$stmt) throw new Exception($dba->error());

        $stmt->bind_param("sd", $name, $price);
        $stmt->execute();

        echo json_encode(['status' => 'success', 'message' => 'Departman eklendi']);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Veritabanı hatası',
        'debug' => $e->getMessage() // prod ortamda kaldırılabilir
    ]);
}