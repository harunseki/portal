<?php
ob_start();
session_start();

/*error_reporting(E_ALL);
ini_set("display_errors", 1);*/

require_once("../class/functions.php");
require_once("../class/mysql.php");

require_once( "../htmlpurifier/library/HTMLPurifier.auto.php");
$config = HTMLPurifier_Config::createDefault();
$config->set('Core.Encoding', 'UTF-8'); // replace with your encoding
$config->set('HTML.Doctype', 'XHTML 1.0 Transitional'); // replace with your doctype
$purifier = new HTMLPurifier($config);

$kullanici_id=admin_id();
$kullanici_adi=admin_name();
if(empty($kullanici_id) or empty($kullanici_adi) ) exit();

if ($purifier->purify(rescape($_POST['asagi']))) {
    $id = $purifier->purify(rescape((int)$_POST['id']));
    $sira = $purifier->purify(rescape((int)$_POST['sira']));

    $q = $dba->query("SELECT * FROM faaliyetler WHERE sira > $sira ORDER BY sira ASC LIMIT 1");
    $row = $dba->fetch_assoc($q);
    $id1 = $row['id'];
    $sira1 = $sira + 1;
    $q1 = $dba->multi_query("UPDATE faaliyetler SET sira='$sira1' WHERE id='$id';
                             UPDATE faaliyetler SET sira='$sira' WHERE id='$id1' ");
   if ($dba->error()) echo "1";
   else echo "2";
}

if ($purifier->purify(rescape($_POST['yukari']))) {
    $id = $purifier->purify(rescape((int)$_POST['id']));
    $sira = $purifier->purify(rescape((int)$_POST['sira']));

    $q = $dba->query("SELECT * FROM faaliyetler WHERE sira < $sira ORDER BY sira DESC LIMIT 1");
    $row = $dba->fetch_assoc($q);
    $id1 = $row['id'];
    $sira1 = $sira - 1;

    $q1 = $dba->multi_query("UPDATE faaliyetler SET sira='$sira1' WHERE id='$id';
                             UPDATE faaliyetler SET sira='$sira' WHERE id='$id1' ");
    if ($dba->error()) echo "1";
    else echo "2";
}
