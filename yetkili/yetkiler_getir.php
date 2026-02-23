<?php
require_once '../class/mysql.php';
header('Content-Type: application/json');

$user_id = (int)$_POST['user_id'];

$out = [];

$q = $dba->query(" SELECT yetki_key, deger FROM yetkili_moduller WHERE kullanici_id = $user_id ");

while ($r = $dba->fetch_assoc($q)) {
    $out[(int)$r['yetki_key']] = (int)$r['deger'];
}

echo json_encode([
    'success' => true,
    'yetkiler' => $out
]);