<?php
require "../class/mysql.php";

$id = intval($_POST['id']);

$stmt = $dba->prepare("
SELECT *
FROM services
WHERE id=? AND durum != 5
ORDER BY name DESC
");

$stmt->bind_param("i",$id);

$stmt->execute();

$res = $stmt->get_result();

echo json_encode($res->fetch_assoc());