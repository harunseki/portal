<?php

require_once '../class/mysql.php';

$ust_id = intval($_POST['ust_id']);

$stmt = $dba->prepare(" SELECT id, mudurluk  FROM mudurlukler ORDER BY isim");

$stmt->bind_param("i",$ust_id);
$stmt->execute();

$result = $stmt->get_result();

$data=[];

while($row=$result->fetch_assoc()){
    $data[]=$row;
}

echo json_encode($data);