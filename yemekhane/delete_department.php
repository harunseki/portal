<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once "../class/mysql.php";

$id = intval($_POST['id'] ?? 0);

if ($id <= 0) {
    echo json_encode(['status'=>'error','message'=>'Geçersiz ID']);
    exit;
}

// Soft delete
$dba->query("UPDATE carddepartment SET durum=0 WHERE id=$id");

echo json_encode([
    'status' => 'success',
    'message' => 'Departman pasif hale getirildi'
]);