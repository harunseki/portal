<?php
require_once "../class/mysql.php";
header("Content-Type: application/json; charset=utf-8");

$sourceType = $_POST['source_type'];

$stmt = $dba->prepare("
    SELECT field_key, field_label
    FROM session_data_source_fields
    WHERE source_type = ?
    AND aktif = 1
    ORDER BY field_label 
");

$stmt->bind_param("s", $sourceType);
$stmt->execute();
$result = $stmt->get_result();

$data = [];
while($row = $result->fetch_assoc()){
    $data[] = $row;
}

echo json_encode($data);
exit();