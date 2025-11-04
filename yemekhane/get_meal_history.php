<?php
require_once("../class/mysql.php");

header('Content-Type: application/json; charset=utf-8');

if (!isset($_POST['cardUserId']) || empty($_POST['cardUserId'])) {
    echo json_encode(['status' => 'error', 'message' => 'Kullanıcı ID eksik']);
    exit;
}

$cardUserId = (int)$_POST['cardUserId'];

$sql = "SELECT id, startDate, finishDate FROM cardmealallowement
        WHERE cardUserId = $cardUserId
        ORDER BY id DESC
        LIMIT 3";
$res = $dba->query($sql);

$rows = [];
if ($res) {
    while ($row = $dba->fetch_assoc($res)) {
        $rows[] = $row;
    }
}

echo json_encode([
    'status' => 'success',
    'data' => $rows
]);
