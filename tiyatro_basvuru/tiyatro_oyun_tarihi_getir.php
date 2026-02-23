<?php
ob_start();
session_start();


require_once("../admin_panel/class/mysql.php");
require_once("../admin_panel/class/functions.php");
require_once ($_SERVER['DOCUMENT_ROOT']."/htmlpurifier/library/HTMLPurifier.auto.php");
$purifier = new HTMLPurifier($config);


$tiyatro_oyunu=$purifier->purify(rescape((int)$_GET['tiyatro_oyunu']));
$admin=$purifier->purify(rescape((int)$_GET['admin']));

$sql = " AND  tiyatro_oyunu_seans.tarih>=DATE(NOW())";
if (!empty($admin)) $sql = " ";

$q=$dba->query("SELECT
tiyatro_oyunu_seans.tarih
FROM
tiyatro_oyunu_seans
WHERE
tiyatro_oyunu_seans.tiyatro_oyunu = '$tiyatro_oyunu' $sql
GROUP BY
tiyatro_oyunu_seans.tarih
ORDER BY
tiyatro_oyunu_seans.tarih ASC ");
echo $dba->error();
?>
<option value="">..:: Tarih Seçiniz ::..</option>
<?php
while ($row=$dba->fetch_assoc($q)){
?>
<option value="<?=strip($row['tarih'])?>"><?=strip(global_date_to_tr($row['tarih']))?></option>
<?php
}
?>