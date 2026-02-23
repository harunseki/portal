<?php
require_once '../class/mysql.php'; // $dba

// Basit güvenlik
if (empty($_POST['title'])) {
    http_response_code(400);
    exit;
}

$id   = isset($_POST['id']) && $_POST['id'] !== '' ? (int)$_POST['id'] : null;
$title = trim($_POST['title']);
$link   = trim($_POST['link'] ?? '');

$parent_id = ($_POST['parent_id'] === '' || !isset($_POST['parent_id']))
    ? null
    : (int)$_POST['parent_id'];

$target_blank = isset($_POST['target_blank']) ? 1 : 0;
$is_active = isset($_POST['is_active']) ? 1 : 0;
$requires_any_permission = isset($_POST['requires_any_permission']) ? 1 : 0;
$allowed_mudurluk = isset($_POST['allowed_mudurluk']) ? 1 : 0;

// === UPDATE ===
if ($id !== null) {

    if ($parent_id === null) {
        $stmt = $dba->prepare("UPDATE menus
                               SET title = ?, link = ?, parent_id = NULL, target_blank = ?, requires_any_permission = ?, is_active = ?, allowed_mudurluk = ?
                               WHERE id = ? ");
        $stmt->bind_param(
            'ssiiisi',
            $title,
            $link,
            $target_blank,
            $requires_any_permission,
            $is_active,
            $allowed_mudurluk,
            $id
        );
    } else {
        $stmt = $dba->prepare("UPDATE menus
                               SET title = ?, link = ?, parent_id = ?, target_blank = ?, requires_any_permission = ?, is_active = ?, allowed_mudurluk = ?
                               WHERE id = ? ");
        $stmt->bind_param(
            'ssiiisi',
            $title,
            $link,
            $parent_id,
            $target_blank,
            $requires_any_permission,
            $is_active,
            $allowed_mudurluk,
            $id
        );
    }

    $stmt->execute();
    exit;
}

// === INSERT ===
if ($parent_id === null) {
    $stmt = $dba->prepare("INSERT INTO menus (title, link, parent_id, target_blank, requires_any_permission, is_active, sort_order, allowed_mudurluk)
                           VALUES (?, ?, NULL, ?, ?, 1, 0, ?)");
    $stmt->bind_param(
        'ssiiis',
        $title,
        $link,
        $target_blank,
        $requires_any_permission,
        $allowed_mudurluk
    );
} else {
    $stmt = $dba->prepare("INSERT INTO menus (title, link, parent_id, target_blank, requires_any_permission, is_active, sort_order, allowed_mudurluk)
                           VALUES (?, ?, ?, ?, ?, 1, 0, ?)");
    $stmt->bind_param(
        'ssiiis',
        $title,
        $link,
        $parent_id,
        $target_blank,
        $requires_any_permission,
        $allowed_mudurluk
    );
}

$stmt->execute();
