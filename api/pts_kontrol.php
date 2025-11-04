<?php
header('Content-Type: application/json;charset=utf-8');

require_once("../class/mysql.php");
require_once("../class/functions.php");
require_once("class/apiconfig.php");

require_once("../../htmlpurifier/library/HTMLPurifier.auto.php");
$config = HTMLPurifier_Config::createDefault();
$config->set('Core.Encoding', 'UTF-8'); // replace with your encoding
$config->set('HTML.Doctype', 'XHTML 1.0 Transitional'); // replace with your doctype
$purifier = new HTMLPurifier($config);

$username =$purifier->purify(rescape($_SERVER['PHP_AUTH_USER']));
$password =$purifier->purify(rescape($_SERVER['PHP_AUTH_PW']));

$tc = $purifier->purify(rescape($_POST['tc']));
$ad = $purifier->purify(rescape($_POST['ad']));
$soyad = $purifier->purify(rescape($_POST['soyad']));
$durum = $purifier->purify(rescape($_POST['durum']));

$deneme = verigetir($username, $password, $tc, $ad, $soyad,$durum);
print_r($deneme);
function verigetir($username, $password, $tc, $adi, $soyadi,$durum) {
    global $dba;
    global $kullanicibilgileri;

    if ($kullanicibilgileri['pts_kontrol'][0]['kullanici'] == $username and $kullanicibilgileri['pts_kontrol'][0]['sifre'] == $password) {
            $sql="";
            if (!empty($tc)) $sql .= " AND tc = $tc";
            if (!empty($adi)) $sql .= " AND adi LIKE '%$adi%'";
            if (!empty($soyadi)) $sql .= " AND soyadi LIKE '%$soyadi%'";

            if($durum==0) $sql .= " AND personel.personel_durumu =0 AND (personel_son_calisma_yeri.isten_cikis_tarihi is null or personel_son_calisma_yeri.isten_cikis_tarihi='0000-00-00') ";

            if($durum==1) $sql .= " AND personel.personel_durumu=1 ";

            $sql="SELECT
                personel.id,
                personel.tc,
                personel.adi,
                personel.soyadi,
                personel.cep_telefonu,
                personel_son_calisma_yeri.ise_giris_tarihi,
                personel_son_calisma_yeri.isten_cikis_tarihi,
                m.mudurluk AS baglimudurluk,
                m2.mudurluk AS calistigimudurluk,
                tip.tip
                FROM
                personel
                INNER JOIN tip ON personel.tip = tip.id
                INNER JOIN personel_son_calisma_yeri ON personel.id = personel_son_calisma_yeri.personel_id
                INNER JOIN mudurluk AS m ON personel_son_calisma_yeri.bagli_oldugu_mudurluk = m.id
                INNER JOIN mudurluk AS m2 ON personel_son_calisma_yeri.calistigi_mudurluk = m2.id 
                WHERE personel.sil=0 $sql
                ORDER BY tip DESC,adi,soyadi,baglimudurluk,calistigimudurluk ASC";

            $qc = $dba->query($sql);
            echo $dba->error();
            if ($dba->num_rows($qc) > 0) {
                $response["success"] = 1;
                $i=1;
                while ($rowc = $dba->fetch_assoc($qc)) {
                    $response["message"][$i] = $rowc;
                    $i++;
                }
                $jsonn = json_encode($response, JSON_UNESCAPED_UNICODE);
                return $jsonn;
            }
            else {
                $response["success"] = 0;
                $response["message"] = "Belirttiğiniz kayıtlara uygun çalışan personel bulunmamaktadır. Tekrar deneyiniz!";
                $jsonn = json_encode($response, JSON_UNESCAPED_UNICODE);
                return $jsonn;
            }
    }
    else {
        $response["success"] = 0;
        $response["message"] = "Kullanıcı şifre veya parola hatası";
        $jsonn = json_encode($response, JSON_UNESCAPED_UNICODE);
        return $jsonn;
    }
}
?>