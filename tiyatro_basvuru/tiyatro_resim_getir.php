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
$tiyatro_salonu_resim=$purifier->purify(rescape((int)$_GET['tiyatro_salonu_resim']));
if ($tiyatro_salonu>0 AND $tiyatro_salonu_resim==0) { ?>
    <center><p class="btn btn-info" data-toggle="modal" data-target="#exampleModal" data-id="<?=$tiyatro_salonu?>" onchange="salon_getir(<?=$tiyatro_salonu?>);">Tiyatro Salonu Oturma Planı</p></center>
<?php }
else {
    $qm = $dba->query("SELECT * FROM tiyatro_oyunu_salon_adi WHERE id='$tiyatro_salonu_resim' ");
    $rowm = $dba->fetch_assoc($qm); ?>
    <img src="https://www.kecioren.bel.tr/images/files/<?= strip($rowm['image']) ?>" style="max-width: 100%;"/>
<?php } ?>