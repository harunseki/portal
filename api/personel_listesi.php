<?php
//header('Content-Type: application/json;charset=utf-8');

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
    global $dba, $response, $i;
    global $kullanicibilgileri;

    if ($kullanicibilgileri['pts_kontrol'][0]['kullanici'] == $username and $kullanicibilgileri['pts_kontrol'][0]['sifre'] == $password) {
            $sql="";


            $sql .= " AND personel.personel_durumu =0 AND (personel_son_calisma_yeri.isten_cikis_tarihi is null or personel_son_calisma_yeri.isten_cikis_tarihi='0000-00-00') ";


            $i=1;

            $sql="SELECT
            personel.id,
            personel.tc,
            personel.adi,
            personel.soyadi,
            personel.cep_telefonu as telefon,
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
            ORDER BY tip DESC,adi,soyadi ASC";
            echo $dba->error();
            $q=$dba->query($sql);
            while ($row=$dba->fetch_assoc($q)){
                $response["message"][$i] = $row;
                $i++;
            }

            $sqlKadrolu="SELECT
            kadrolu_personel.id,  
            kadrolu_personel.tcno as tc,  
            kadrolu_personel.ad as adi,  
            kadrolu_personel.soy as soyadi, 
            kadrolu_personel.cep as telefon, 
            kadrolu_personel.xxxxmudurlukk as baglimudurluk,
            kadrolu_personel.xxxxmudurluk as calistigimudurluk,
            kadrolu_personel_tip.tip
            FROM
            kadrolu_personel
            INNER JOIN kadrolu_personel_tip ON kadrolu_personel.ne = kadrolu_personel_tip.tip
            WHERE kadrolu_personel.ne!='T' AND kadrolu_personel.ne!='J' AND kadrolu_personel.ne!='S' AND kadrolu_personel.ne!='B' AND kadrolu_personel.ne!='H'
            ORDER BY  kadrolu_personel.ad, kadrolu_personel.soy";
            echo $dba->error();
            $qKadrolu=$dba->query($sqlKadrolu);
            while ($rowKadrolu=$dba->fetch_assoc($qKadrolu)){
                $response["message"][$i] = $rowKadrolu;
                $i++;
            }


        array_multisort(array_column($response, "adi"), SORT_ASC, array_column($response, "soyadi"), SORT_ASC, $response);

        if(sizeof($response["message"])>1){
            $response["success"] = 1;
            $jsonn = json_encode($response, JSON_UNESCAPED_UNICODE);
            return $jsonn;
        }else{
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