<?php
ob_start();
session_start();

require_once("../admin_panel/class/mysql.php");
require_once("../admin_panel/class/functions.php");
require_once ($_SERVER['DOCUMENT_ROOT']."/htmlpurifier/library/HTMLPurifier.auto.php");
$purifier = new HTMLPurifier($config);

$config = HTMLPurifier_Config::createDefault();
$config->set('Core.Encoding', 'UTF-8'); // replace with your encoding
$config->set('HTML.Doctype', 'XHTML 1.0 Transitional'); // replace with your doctype
$purifier = new HTMLPurifier($config);

$tiyatro_salonu=$purifier->purify(rescape((int)$_GET['tiyatro_salonu']));
$cocuk=$purifier->purify(rescape((int)$_GET['cocuk']));
$admin=$purifier->purify(rescape((int)$_GET['admin']));

if (!empty($cocuk)) $sql = " AND cocuk='$cocuk'";

$sql1 = " AND  tiyatro_oyunu_seans.tarih>=DATE(NOW())";
if ($admin==1) $sql1 = " ";

$q=$dba->query("SELECT DISTINCT tiyatro_oyunu.id, tiyatro_oyunu.oyun_adi FROM tiyatro_oyunu
	            INNER JOIN tiyatro_oyunu_seans ON tiyatro_oyunu_seans.tiyatro_oyunu = tiyatro_oyunu.id 
                WHERE tiyatro_oyunu.salon_id = '$tiyatro_salonu' AND tiyatro_oyunu.online_basvuru='1' $sql $sql1
                ORDER BY tiyatro_oyunu.oyun_adi ASC ");
echo $dba->error();
?>
    <option value="">..:: Oyun Seçiniz ::..</option>
<?php
while ($row=$dba->fetch_assoc($q)) { ?>
    <option value="<?=strip($row['id'])?>"><?=strip(($row['oyun_adi']))?></option>
<?php } ?>