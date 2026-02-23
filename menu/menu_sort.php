<?php
require_once '../class/mysql.php'; // $dba

if (empty($_POST['order']) || !is_array($_POST['order'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Geçersiz sıralama verisi'
    ]);
    exit;
}

$dba->begin_transaction();

try {
    $stmt = $dba->prepare("UPDATE menus SET sort_order = ? WHERE id = ?");

    foreach ($_POST['order'] as $row) {
        $sort = (int)$row['sort_order'];
        $id   = (int)$row['id'];

        $stmt->bind_param('ii', $sort, $id);
        $stmt->execute();
    }

    $dba->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Menü sıralaması başarıyla güncellendi'
    ]);
} catch (Throwable $e) {
    $dba->rollback();

    echo json_encode([
        'success' => false,
        'message' => 'Sıralama güncellenirken hata oluştu'
    ]);
}