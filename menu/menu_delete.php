<?php
require_once '../class/mysql.php'; // $dba

if (empty($_POST['id'])) {
    http_response_code(400);
    exit;
}

$id = (int)$_POST['id'];

function deleteMenu(mysqli $dba, int $id): void
{
    // Önce çocukları sil
    $stmt = $dba->prepare("SELECT id FROM menus WHERE parent_id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $res = $stmt->get_result();

    while ($row = $res->fetch_assoc()) {
        deleteMenu($dba, (int)$row['id']);
    }

    // Sonra kendisini sil
    $stmt = $dba->prepare("DELETE FROM menus WHERE id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
}

deleteMenu($dba, $id);
