<?php
ob_start();
session_start();


require_once("../admin_panel/class/mysql.php");
require_once("../admin_panel/class/functions.php");
require_once ($_SERVER['DOCUMENT_ROOT']."/htmlpurifier/library/HTMLPurifier.auto.php");
$purifier = new HTMLPurifier($config);


$tiyatro_oyunu=$purifier->purify(rescape((int)$_GET['tiyatro_oyunu']));
$tarih=$purifier->purify(rescape($_GET['tarih']));


$q=$dba->query("SELECT
tiyatro_oyunu_seans.id,
tiyatro_oyunu_seans.seans
FROM
tiyatro_oyunu_seans
WHERE
tiyatro_oyunu_seans.tiyatro_oyunu = '$tiyatro_oyunu' and tiyatro_oyunu_seans.tarih='$tarih'  ");
echo $dba->error();
?>
<option value="">..:: Seans Seçiniz ::..</option>
<?php
while ($row=$dba->fetch_assoc($q)){
?>
<option value="<?=strip($row['id'])?>"><?=strip($row['seans'])?></option>
<?php
}
?>