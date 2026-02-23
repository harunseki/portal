<?php
header('Content-Type: application/json');

require_once "get_personel.php";

$mudurlukID = $_POST['mudurluk'] ?? $_GET['mudurluk'] ?? '';

if (is_array($mudurlukID)) {
    $mudurlukID = $mudurlukID[0];
}

echo json_encode(getPersonelList($mudurlukID));
exit;