<?php
require_once '../class/mysql.php'; // $dba

if (empty($_GET['id'])) {
    http_response_code(400);
    exit;
}

$id = (int)$_GET['id'];

$stmt = $dba->prepare("SELECT 
                            id,
                            parent_id,
                            title,
                            link,
                            target_blank,
                            requires_any_permission,
                            allowed_mudurluk,
                            is_active
                       FROM menus
                       WHERE id = ?
                       LIMIT 1");
$stmt->bind_param('i', $id);
$stmt->execute();

$result = $stmt->get_result();
$menu = $result->fetch_assoc();

if (!$menu) {
    http_response_code(404);
    exit;
}

header('Content-Type: application/json; charset=utf-8');
echo json_encode($menu);
