<?php
ob_start();
session_start();

require_once("../class/mysql.php");
require_once("../class/functions.php");
require_once($_SERVER['DOCUMENT_ROOT'] . "/htmlpurifier/library/HTMLPurifier.auto.php");
$purifier = new HTMLPurifier($config);

$cins_id=$purifier->purify(rescape((int)$_GET['cins_id']));
if (!empty($cins_id)){

    $q=$dba->query("SELECT turler.id, turler.baslik 
                    FROM  turler
                    INNER JOIN cinsler ON cinsler.id = turler.cins_id
                    WHERE turler.cins_id = '$cins_id' AND turler.durum=1
                    ORDER BY cinsler.baslik ASC ");
    echo $dba->error();
    if($dba->affected_rows()>0){
        ?>
        <option value="">..: Tür Seçiniz :..</option>
        <?php
    }else {
        ?>
        <option value="">..: Kayıt Bulunmamaktadır. :..</option>
        <?php
    }
    while ($row=$dba->fetch_assoc($q)){
        ?>
        <option value="<?=(int)$row['id']?>"><?=strip($row['baslik'])?></option>
        <?php
    }
}

$tur_id=$purifier->purify(rescape((int)$_GET['tur_id']));
if (!empty($tur_id)){

    $q=$dba->query("SELECT renk, id
                    FROM  dostlar
                    WHERE tur_id = $tur_id AND durum=1 AND web_basvurusu_alinsin=1
                    GROUP BY renk
                    ORDER BY renk ASC");
    echo $dba->error();
    if($dba->affected_rows()>0){
        ?>
        <option value="">..: Renk Seçiniz :..</option>
        <?php
    }else {
        ?>
        <option value="">..: Kayıt Bulunmamaktadır. :..</option>
        <?php
    }
    while ($row=$dba->fetch_assoc($q)){
        ?>
        <option value="<?=$row['renk']?>"><?=strip($row['renk'])?></option>
        <?php
    }
}
?>