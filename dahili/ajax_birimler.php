<?php
include "../class/mysql.php";

$mudurluk_id = (int)($_GET['mudurluk_id'] ?? 0);
$data = [];

if ($mudurluk_id > 0) {
    $query = $dba->query("SELECT id, baslik FROM birimler WHERE mudurluk_id=$mudurluk_id ORDER BY baslik ASC");
    while ($row = $query->fetch_assoc()) $data[] = $row;
}

echo json_encode($data, JSON_UNESCAPED_UNICODE);