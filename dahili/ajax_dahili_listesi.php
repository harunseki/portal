<?php
include "../class/mysql.php";

$mudurluk = (int)($_GET['mudurluk'] ?? 0);
$birim = $_GET['birim'] ?? '';

if (!$mudurluk) {
    echo json_encode([]);
    exit;
}

if ($birim && $birim != 'all') {
    $query = $dba->query("SELECT baslik, dahili FROM birimler WHERE id=".(int)$birim);
} else {
    $query = $dba->query("SELECT baslik, dahili FROM birimler WHERE mudurluk_id=$mudurluk ORDER BY baslik ASC");
}

$data = [];
while ($row = $query->fetch_assoc()) $data[] = $row;

echo json_encode($data, JSON_UNESCAPED_UNICODE);