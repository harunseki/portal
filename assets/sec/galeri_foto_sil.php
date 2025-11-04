<?php
ob_start();
session_start();

require_once("../class/mysql.php");
require_once("../class/functions.php");

require_once ("../htmlpurifier/library/HTMLPurifier.auto.php");
$config = HTMLPurifier_Config::createDefault();
$config->set('Core.Encoding', 'UTF-8'); // replace with your encoding
$config->set('HTML.Doctype', 'HTML 4.01 Transitional'); // replace with your doctype
$purifier = new HTMLPurifier($config);

$id=$purifier->purify(rescape((int)$_POST['id']));
$q=$dba->query("SELECT image FROM galeri WHERE id='$id' ");
$row=$dba->fetch_assoc($q);

if(!empty($row['image'])) unlink("../img/galeri/".$row['image']);

$qq=$dba->query("DELETE FROM galeri WHERE id='$id' ");
if($dba->affected_rows()>0) echo "1";
else echo "2";
?>