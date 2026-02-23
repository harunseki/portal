<?php
ob_start();
session_start();


require_once("../admin_panel/class/mysql.php");
require_once("../admin_panel/class/functions.php");
require_once ($_SERVER['DOCUMENT_ROOT']."/htmlpurifier/library/HTMLPurifier.auto.php");
$purifier = new HTMLPurifier($config);


$salon_id=$purifier->purify(rescape((int)$_GET['salon_id']));
$tiyatro_oyunu=$purifier->purify(rescape((int)$_GET['tiyatro_oyunu']));
$tarih=$purifier->purify(rescape($_GET['tarih']));
$seans=$purifier->purify(rescape((int)$_GET['seans']));
$blok_id=$purifier->purify(rescape((int)$_GET['blok_id']));

if (!empty($blok_id)) $sql=" AND tiyatro_oyunu_koltuk_konumu='$blok_id'";

$q=$dba->query("SELECT
                tiyatro_oyunu_koltuk_sira.id,
                tiyatro_oyunu_koltuk_sira.tiyatro_oyunu_koltuk_konumu,
                tiyatro_oyunu_koltuk_sira.sira
                FROM
                tiyatro_oyunu_koltuk_sira 
                WHERE salon_id='$salon_id' $sql
                  AND tiyatro_oyunu_koltuk_sira.durum='1'
				  AND (SELECT COUNT(tiyatro_oyunu_basvurusu.id) FROM tiyatro_oyunu_basvurusu 
                       WHERE salon_id='$salon_id'
                         AND tiyatro_oyunu_basvurusu.tiyatro_oyunu='$tiyatro_oyunu' 
                         AND tiyatro_oyunu_basvurusu.oyun_tarihi='$tarih' 
                         AND seans='$seans' 
                         AND (tiyatro_oyunu_basvurusu.koltuk1=tiyatro_oyunu_koltuk_sira.id 
                         OR tiyatro_oyunu_basvurusu.koltuk2=tiyatro_oyunu_koltuk_sira.id
                         OR tiyatro_oyunu_basvurusu.koltuk3=tiyatro_oyunu_koltuk_sira.id
                         OR tiyatro_oyunu_basvurusu.koltuk4=tiyatro_oyunu_koltuk_sira.id)  )<1
                ORDER BY tiyatro_oyunu_koltuk_sira.tiyatro_oyunu_koltuk_konumu, tiyatro_oyunu_koltuk_sira.id");
$rowk=$dba->num_rows($q);
if ($rowk>0) { ?>
    <option value="">..:: Koltuk Seçiniz ::..</option>
    <?php while ($row=$dba->fetch_assoc($q)) {
        $konum="";
        if (empty($blok_id)) {
            $konum = strip($row['tiyatro_oyunu_koltuk_konumu']) ==1 ? 'SOL - ' : (strip($row['tiyatro_oyunu_koltuk_konumu']) ==2 ? 'ORTA - ' : 'SAĞ - ');
        }
        ?>
    <option value="<?=strip($row['id'])?>"><?= $konum. strip($row['sira'])?></option>
<?php }
}
else { ?>
    <option value="">..:: Boş Koltuk Bulunmamaktadır ::..</option>
<?php } ?>