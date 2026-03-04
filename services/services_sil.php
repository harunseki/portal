<?php
require "../class/mysql.php";

$id=intval($_POST['id']);

$stmt=$dba->prepare(" UPDATE services SET durum=5 WHERE id=? ");

$stmt->bind_param("i",$id);

$stmt->execute();