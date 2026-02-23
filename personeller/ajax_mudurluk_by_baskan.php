<?php
// personeller/ajax_mudurluk_by_baskan.php
header('Content-Type: application/json; charset=utf-8');

// include/require ile kendi DB bağlantını çağır
// örn: require_once __DIR__ . '/../inc/db.php';
require '../class/mysql.php';

// Güvenli al
$baskan = isset($_GET['baskan']) ? intval($_GET['baskan']) : 0;

$data = [];

if ($baskan > 0) {
    // Başkan yardımcısına bağlı müdürlükler
    $sql = "
        SELECT m.flexy_id, m.mudurluk
        FROM mudurlukler m
        INNER JOIN baskan_yardimcilari_mudurlukler b
            ON b.mudurluk_id = m.flexy_id
        WHERE b.baskan_id = {$baskan}
        ORDER BY m.mudurluk
    ";
} else {
    // Tümü
    $sql = "SELECT flexy_id, mudurluk FROM mudurlukler ORDER BY mudurluk";
}

$res = $dba->query($sql);

if ($res) {
    while ($row = $res->fetch_assoc()) {
        // flexy_id'yi string yapıyoruz ki JS tarafında in_array vb ile uyumlu olsun
        $data[] = [
            'flexy_id' => (string)$row['flexy_id'],
            'mudurluk' => $row['mudurluk']
        ];
    }
}

// JSON olarak döndür
echo json_encode($data, JSON_UNESCAPED_UNICODE);
exit;