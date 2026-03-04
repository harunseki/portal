<?php
ob_start();
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
function require_admin() {
    if (empty($_SESSION['admin'])) {
        http_response_code(403);
        exit;
    }
}
require_admin();
require_once '../class/mysql.php';

$modul_id = isset($_GET['id']) ? trim($_GET['id']) : null;
if (!$modul_id) {
    http_response_code(400);
    exit("Modül ID belirtilmedi.");
}

$sql = "SELECT y.id, y.adi, y.soyadi, y.username, ym.deger FROM yetkili_moduller ym
        JOIN yetkili y ON y.id = ym.kullanici_id
        WHERE ym.yetki_key = ? AND ym.deger = 1
        ORDER BY y.adi, y.soyadi";
$stmt = $dba->prepare($sql);
if (!$stmt) {
    http_response_code(500);
    exit("SQL Hatası: " . $dba->error);
}
$key = (string)$modul_id;
$stmt->bind_param("s", $key);
$stmt->execute();
$result = $stmt->get_result();
if (!$result) {
    http_response_code(500);
    exit("Result hatası: " . $stmt->error);
}
?>
<table class="table table-striped">
    <thead>
    <tr>
        <th>Ad Soyad</th>
        <th>Ldap Username</th>
        <th>Durum</th>
    </tr>
    </thead>
    <tbody>
    <?php while($row = $result->fetch_assoc()): ?>
        <tr>
            <td><?= htmlspecialchars($row['adi'])." ".htmlspecialchars($row['soyadi']) ?></td>
            <td><?= htmlspecialchars($row['username']) ?></td>
            <td>Aktif</td>
        </tr>
    <?php endwhile; ?>
    </tbody>
</table>