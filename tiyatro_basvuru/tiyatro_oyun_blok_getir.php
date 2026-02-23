<?php
ob_start();
session_start();


require_once("../admin_panel/class/mysql.php");
require_once("../admin_panel/class/functions.php");
require_once ($_SERVER['DOCUMENT_ROOT']."/htmlpurifier/library/HTMLPurifier.auto.php");
$purifier = new HTMLPurifier($config);



$q=$dba->query("SELECT * FROM tiyatro_oyunu_koltuk_konumu Order By id ASC");
echo $dba->error();
?>
    <option value="">..:: Blok Seçiniz ::..</option>
<?php
while ($row=$dba->fetch_assoc($q)){
    ?>
    <option value="<?=strip($row['id'])?>"><?=strip($row['koltuk_konumu'])?></option>
    <?php
}
?>