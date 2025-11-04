<?php
ob_start();
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

function date_control($i, $j)
{
    $datetime1 = strtotime(date('Y-m-d', strtotime($i)));
    $datetime2 = strtotime(date('Y-m-d', strtotime($j)));

    $secs = $datetime2 - $datetime1;// == <seconds between the two times>
    if($secs>=0) return 1;
    else return 0;
}

function getGunAdi($gun) {

    switch ($gun){
        case 1:
            return 'Pazartesi';
        case 2:
            return 'Salı';
        case 3:
            return 'Çarşamba';
        case 4:
            return 'Perşembe';
        case 5:
            return 'Cuma';
        case 6:
            return 'Cumartesi';
        case 7:
            return 'Pazar';
        default:
            return '';
    }
}

function getDayNumber($gun) {

    switch ($gun){
        case 'Monday':
            return 1;
        case 'Tuesday':
            return 2;
        case 'Wednesday':
            return 3;
        case 'Thursday':
            return 4;
        case 'Friday':
            return 5;
        case 'Saturday':
            return 6;
        case 'Sunday':
            return 7;
        default:
            return '';
    }
}

function admin_is_takip_yetki()
{
    return $_SESSION['bim_takip_is_takip'];
}

function admin_yetki_turu()
{
    return $_SESSION['bim_takip_yetki_turu'];
}

function admin_yetki_mud_id()
{
    return $_SESSION['bim_takip_yetki_mud_id'];
}

function admin_yetki_birim_id()
{
    return $_SESSION['bim_takip_yetki_birim_id'];
}

function admin_yetki_alt_birim_id()
{
    return $_SESSION['bim_takip_yetki_alt_birim_id'];
}

function admin_yetki_gorev_ekle()
{
    return $_SESSION['is_takip_gorev_ekle'];
}

function correctlink($val)
{
    $find = array('Ç', 'Ş', 'Ğ', 'Ü', 'İ', 'Ö', 'ç', 'ş', 'ğ', 'ü', 'ö', 'ı', '+', '#', '?', '*', '!', '.', '(', ')');
    $replace = array('c', 's', 'g', 'u', 'i', 'o', 'c', 's', 'g', 'u', 'o', 'i', 'plus', 'sharp', '', '', '', '', '', '');
    $string = strtolower(str_replace($find, $replace, $val));
    $string = preg_replace("@[^A-Za-z0-9\-_\.\+]@i", ' ', $string);
    $string = trim(preg_replace('/\s+/', ' ', $string));
    $string = str_replace(' ', '_', $string);

    return $string;
}

function alert_success($text)
{
    ?>
    <div class="alert alert-success alert-dismissable">
        <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
        <p><?= $text ?></p>
    </div>
    <?php
}

function date_calc($i, $j)
{
    $datetime1 = strtotime($i);
    $datetime2 = strtotime($j);

    $secs = $datetime2 - $datetime1;// == <seconds between the two times>
    return floor($secs/(60*60*24));
}

function date_for_input()
{
    return $tarih = date("Y-m-d");
}

function alert_danger($hata)
{
    ?>
    <div class="alert alert-danger alert-dismissable" style="margin: 5px">
        <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
        <?php
        if (is_array($hata)) {
            foreach ($hata as $val) {
                echo $val;
            }
        } else {
            echo $hata;
        }
        ?>
    </div>
    <?php
}

function cep_tel_clear($cep_tel){
    $cep_tel=str_replace(" ","",$cep_tel);
    $cep_tel=str_replace("-","",$cep_tel);
    $cep_tel=str_replace("/","",$cep_tel);
    $cep_tel=str_replace("(","",$cep_tel);
    $cep_tel=str_replace(")","",$cep_tel);
    $cep_tel = ltrim($cep_tel, "0");
    return $cep_tel;
}

function select_sql($db_name, $tablo_name, $tc_tablo_name, $tc, $cep_telefonu_tablo_name, $cep_telefonu){

    require_once($_SERVER['DOCUMENT_ROOT']."/htmlpurifier/library/HTMLPurifier.auto.php");
    $config = HTMLPurifier_Config::createDefault();
    $config->set("Core", "Encoding", "utf-8");
    $purifier = new HTMLPurifier($config);

    $db_name = $purifier->purify(rescape($db_name));
    $tablo_name = $purifier->purify(rescape($tablo_name));
    $tc_tablo_name = $purifier->purify(rescape($tc_tablo_name));
    $tc = $purifier->purify(rescape($tc));
    $cep_telefonu_tablo_name = $purifier->purify(rescape($cep_telefonu_tablo_name));
    $cep_telefonu = $purifier->purify(rescape($cep_telefonu));


    $select_sql="SELECT $tablo_name.id, $tablo_name.adi, $tablo_name.soyadi, $tablo_name.$tc_tablo_name, $tablo_name.$cep_telefonu_tablo_name FROM $db_name.$tablo_name WHERE $tc_tablo_name='$tc' and $cep_telefonu_tablo_name='$cep_telefonu' and durum='1' ";
    return $select_sql;
}

function update_sql($db_name, $tablo_name, $tc_tablo_name, $tc, $cep_telefonu_tablo_name, $cep_telefonu, $password, $salt, $yetkili_id){

    require_once($_SERVER['DOCUMENT_ROOT']."/htmlpurifier/library/HTMLPurifier.auto.php");
    $config = HTMLPurifier_Config::createDefault();
    $config->set("Core", "Encoding", "utf-8");
    $purifier = new HTMLPurifier($config);

    $db_name = $purifier->purify(rescape($db_name));
    $tablo_name = $purifier->purify(rescape($tablo_name));
    $tc_tablo_name = $purifier->purify(rescape($tc_tablo_name));
    $tc = $purifier->purify(rescape($tc));
    $cep_telefonu_tablo_name = $purifier->purify(rescape($cep_telefonu_tablo_name));
    $cep_telefonu = $purifier->purify(rescape($cep_telefonu));
    $password = $purifier->purify(rescape($password));
    $salt = $purifier->purify(rescape($salt));
    $yetkili_id = $purifier->purify(rescape($yetkili_id));

    $update_sql="UPDATE $db_name.$tablo_name SET salt='$salt', password='$password' WHERE id='$yetkili_id' AND $tc_tablo_name='$tc' AND $cep_telefonu_tablo_name='$cep_telefonu' AND durum='1' ";

    return $update_sql;
}



function sifre_kontrol($password)
{
    if (strlen($password) < 6) {
        $hata[] ="<p> - Şifre en az 6 karakter olmalıdır</p>";
    }
    if (!preg_match("#[a-zA-Z]+#", $password)) {
        $hata[] = "<p> - Şifre en az 1 harf içermelidir</p>";
    }
    if (!preg_match("#[0-9]+#", $password)) {
        $hata[] = "<p> - Şifre en az 1 rakam içermelidir</p>";
    }

    return $hata;
}

function set_csrf_token()
{
    if (!isset($_SESSION['csrf_token_zabita_pazar_buro'])) {
        return $_SESSION['csrf_token_zabita_pazar_buro'] = base64_encode(openssl_random_pseudo_bytes(32));
    }
}

function get_csrf_token()
{
    return $_SESSION['csrf_token_zabita_pazar_buro'];
}

function uygulama_id()
{
    return "55";
}

function admin_id()
{
    return $_SESSION['bim_takip_admin_id'];
}

function admin_name()
{
    return $_SERVER['PHP_AUTH_USER'];
}

function admin_tc()
{
    return $_SESSION['bim_takip_admin_tc'];
}

function admin_mud_id()
{
    return $_SESSION['bim_takip_yetki_mud_id'];
}

function admin_birim_id()
{
    return $_SESSION['bim_takip_yetki_birim_id'];
}

function admin_alt_birim_id()
{
    return $_SESSION['bim_takip_yetki_alt_birim_id'];
}

function strtoupperTR($str)
{
    $str = str_replace(array('i', 'ı', 'ü', 'ğ', 'ş', 'ö', 'ç'), array('İ', 'I', 'Ü', 'Ğ', 'Ş', 'Ö', 'Ç'), $str);
    return strtoupper($str);
}

function GetIP()
{

    if (getenv("HTTP_CLIENT_IP")) {
        $ip = getenv("HTTP_CLIENT_IP");
    } elseif (getenv("HTTP_X_FORWARDED_FOR")) {
        $ip = getenv("HTTP_X_FORWARDED_FOR");
        if (strstr($ip, ',')) {
            $tmp = explode(',', $ip);
            $ip = trim($tmp[0]);
        }
    } else {
        $ip = getenv("REMOTE_ADDR");
    }
    return $ip;
}

function toplu_sms_yolla($dizi, $acil)
{

    require_once('/webroot/saysis_webservis/lib/nusoap.php');
    $client = new nusoap_client('https://'.strip($_SERVER['SERVER_NAME']).'/toplusms/service.php?wsdl', 'wsdl');
    $client->setUseCurl(true);
    $client->soap_defencoding = 'UTF-8';
    $client->decode_utf8 = false;

    $params = array(
        'kullaniciadi' => "muslum.gumusluoglu",
        'sifre' => "VdjvQV3zxx",
        'smsdizi' => json_encode($dizi),
        'kaynak' => '48',
        'acil' => $acil

    );

    $result = $client->call('smsGonder', $params);
    $durum = json_decode($result);

    return $durum;
    //return $MessageID=$durum[0];


}

function sms_yolla($cep_tel, $mesaj, $kullanici)
{

    require_once('/webroot/saysis_webservis/lib/nusoap.php');
    $client = new nusoap_client('https://'.strip($_SERVER['SERVER_NAME']).'/toplusms/service.php?wsdl', 'wsdl');
    $client->setUseCurl(true);
    $client->soap_defencoding = 'UTF-8';
    $client->decode_utf8 = false;
    $acil=1;
    $params = array(
        'kullaniciadi' => "muslum.gumusluoglu",
        'sifre' => "VdjvQV3zxx",
        'smsdizi' => json_encode(array(array("cep_tel" => $cep_tel, "mesaj" => $mesaj, 'kullanici' => $kullanici))),
        'kaynak' => '48',
        'acil' => $acil

    );

    $result = $client->call('smsGonder', $params);
    $durum = json_decode($result);

    return $MessageID = $durum[0];;
    //return $MessageID=$durum[0];


}

function sms_yolla_sifremi_unuttum($cep_tel, $mesaj, $kullanici, $acil){

    require_once('/webroot/saysis_webservis/lib/nusoap.php');
    $client = new nusoap_client('https://'.strip($_SERVER['SERVER_NAME']).'/toplusms/service.php?wsdl','wsdl');
    $client->setUseCurl(true);
    $client->soap_defencoding = 'UTF-8';
    $client->decode_utf8 = false;

    $params = array(
        'kullaniciadi' => "muslum.gumusluoglu",
        'sifre'         => "VdjvQV3zxx",
        'smsdizi' => json_encode(array(array("cep_tel"=>$cep_tel,"mesaj"=>$mesaj,'kullanici'=>$kullanici))),
        'kaynak' => '48',
        'acil' => $acil

    );

    $result = $client->call('smsGonder', $params);
    $durum=json_decode($result);

    return $MessageID=$durum[0];;
    //return $MessageID=$durum[0];


}

function trim_cep_tel($cep_tel)
{

    $cep_tel = rescape($cep_tel);
    $cep_tel = str_replace(" ", "", $cep_tel);
    $cep_tel = str_replace("(", "", $cep_tel);
    $cep_tel = str_replace(")", "", $cep_tel);
    $cep_tel = str_replace("-", "", $cep_tel);
    $cep_tel = ltrim($cep_tel, "0");
    return $cep_tel;
}

function tarihler_arasinde_gun($ilk_tarih, $ikinci_tarih)
{

    $aryRange = array();

    $iDateFrom = mktime(1, 0, 0, substr($ilk_tarih, 5, 2), substr($ilk_tarih, 8, 2), substr($ilk_tarih, 0, 4));
    $iDateTo = mktime(1, 0, 0, substr($ikinci_tarih, 5, 2), substr($ikinci_tarih, 8, 2), substr($ikinci_tarih, 0, 4));

    if ($iDateTo >= $iDateFrom) {
        array_push($aryRange, date('Y-m-d', $iDateFrom)); // first entry
        while ($iDateFrom < $iDateTo) {
            $iDateFrom += 86400; // add 24 hours
            array_push($aryRange, date('Y-m-d', $iDateFrom));
        }
    }
    return $aryRange;
}

function global_date_to_tr_db($tarih){
    if(!empty($tarih)){
        $yil=substr($tarih,0,4);
        $ay=substr($tarih,5,2);
        $gun=substr($tarih,8,2);
        $tarih=$gun."-".$ay."-".$yil;
        return $tarih;
    }else{
        return "";
    }

}

function global_date_to_tr_long($tarih)
{
    if (!empty($tarih)) {
        $yil = substr($tarih, 0, 4);
        $ay = substr($tarih, 5, 2);
        $gun = substr($tarih, 8, 2);
        $saat = substr($tarih, 11, 8);
        $tarih = $gun . "." . $ay . "." . $yil . " " . $saat;
        return $tarih;
    } else {
        return "";
    }

}

function global_date_to_tr($tarih)
{
    if (!empty($tarih)) {
        $yil = substr($tarih, 0, 4);
        $ay = substr($tarih, 5, 2);
        $gun = substr($tarih, 8, 2);
        $tarih = $gun . "." . $ay . "." . $yil;
        return $tarih;
    } else {
        return "";
    }

}

function global_date_to_tr_full($tarih){

    if(!empty($tarih)){
        $yil=substr($tarih,0,4);
        $ay=substr($tarih,5,2);
        $gun=substr($tarih,8,2);
        $dss=substr($tarih,10,9);
        $tarih=$gun.".".$ay.".".$yil.$dss;
        return $tarih;
    }else{
        return "";
    }

}

function tr_to_global_date($tarih)
{

    if (!empty($tarih)) {
        $gun = substr($tarih, 0, 2);
        $ay = substr($tarih, 3, 2);
        $yil = substr($tarih, 6, 4);
        $tarih = $yil . '-' . $ay . '-' . $gun;
        return $tarih;
    } else {
        return "";
    }

}

function akmasa_chtext($text)
{
    global $etext;

    $trkarakterler[0] = "/ü/";
    $trkarakterler[1] = "/ğ/";
    $trkarakterler[2] = "/ş/";
    $trkarakterler[3] = "/İ/";
    $trkarakterler[4] = "/Ş/";
    $trkarakterler[5] = "/ç/";
    $trkarakterler[6] = "/ö/";
    $trkarakterler[7] = "/ı/";
    $trkarakterler[8] = "/Ö/";
    $trkarakterler[9] = "/Ü/";
    $trkarakterler[10] = "/Ğ/";
    $trkarakterler[11] = "/Ç/";


    $trkarakterler2[0] = "u";
    $trkarakterler2[1] = "g";
    $trkarakterler2[2] = "s";
    $trkarakterler2[3] = "I";
    $trkarakterler2[4] = "S";
    $trkarakterler2[5] = "c";
    $trkarakterler2[6] = "o";
    $trkarakterler2[7] = "i";
    $trkarakterler2[8] = "O";
    $trkarakterler2[9] = "U";
    $trkarakterler2[10] = "G";
    $trkarakterler2[11] = "C";


    $etext = preg_replace($trkarakterler, $trkarakterler2, $text);
    return $etext;
}

function tarih()
{
    return $tarih = date("Y-m-d H:i:s");

}

function no_empty()
{
    ?>
    <!--<img src="images/red.png" title="Bu alan boş bırakılamaz" />-->
    <span style="color:#900; font-size:12px; font-weight:bold; margin-left:5px; cursor:pointer;"
          title="Bu alan boş bırakılamaz">*</span>
    <?php
}

function photo_chtext($str)
{

    $a = array('À', 'Á', 'Â', 'Ã', 'Ä', 'Å', 'Æ', 'Ç', 'È', 'É', 'Ê', 'Ë', 'Ì', 'Í', 'Î', 'Ï', '?', 'Ñ', 'Ò', 'Ó', 'Ô', 'Õ', 'Ö', 'Ø', 'Ù', 'Ú', 'Û', 'Ü', 'Y', 'ß', 'à', 'á', 'â', 'ã', 'ä', 'å', 'æ', 'ç', 'è', 'é', 'ê', 'ë', 'ì', 'í', 'î', 'ï', 'ñ', 'ò', 'ó', 'ô', 'õ', 'ö', 'ø', 'ù', 'ú', 'û', 'ü', 'y', 'ÿ', 'A', 'a', 'A', 'a', 'A', 'a', 'C', 'c', 'C', 'c', 'C', 'c', 'C', 'c', 'D', 'd', 'D', 'd', 'E', 'e', 'E', 'e', 'E', 'e', 'E', 'e', 'E', 'e', 'G', 'g', 'Ğ', 'ğ', 'G', 'g', 'G', 'g', 'H', 'h', 'H', 'h', 'I', 'i', 'I', 'i', 'I', 'i', 'I', 'i', 'İ', 'ı', '?', '?', 'J', 'j', 'K', 'k', 'L', 'l', 'L', 'l', 'L', 'l', '?', '?', 'L', 'l', 'N', 'n', 'N', 'n', 'N', 'n', '?', 'O', 'o', 'O', 'o', 'O', 'o', 'Œ', 'œ', 'R', 'r', 'R', 'r', 'R', 'r', 'S', 's', 'S', 's', 'Ş', 'ş', 'Š', 'š', 'T', 't', 'T', 't', 'T', 't', 'U', 'u', 'U', 'u', 'U', 'u', 'U', 'u', 'U', 'u', 'U', 'u', 'W', 'w', 'Y', 'y', 'Ÿ', 'Z', 'z', 'Z', 'z', 'Z', 'z', '?', 'ƒ', 'O', 'o', 'U', 'u', 'A', 'a', 'I', 'i', 'O', 'o', 'U', 'u', 'U', 'u', 'U', 'u', 'U', 'u', 'U', 'u', '?', '?', '?', '?', '?', '?', ' ', "'", '/\/', '´', '*', '#', '+', '^', ':', '&', '@', '}', '~', '{', ',', ';', '$', '-', '/', '(', ')', '!', '%', '=', '|', "'", '/\/');

    $b = array('A', 'A', 'A', 'A', 'A', 'A', 'AE', 'C', 'E', 'E', 'E', 'E', 'I', 'I', 'I', 'I', 'D', 'N', 'O', 'O', 'O', 'O', 'O', 'O', 'U', 'U', 'U', 'U', 'Y', 's', 'a', 'a', 'a', 'a', 'a', 'a', 'ae', 'c', 'e', 'e', 'e', 'e', 'i', 'i', 'i', 'i', 'n', 'o', 'o', 'o', 'o', 'o', 'o', 'u', 'u', 'u', 'u', 'y', 'y', 'A', 'a', 'A', 'a', 'A', 'a', 'C', 'c', 'C', 'c', 'C', 'c', 'C', 'c', 'D', 'd', 'D', 'd', 'E', 'e', 'E', 'e', 'E', 'e', 'E', 'e', 'E', 'e', 'G', 'g', 'G', 'g', 'G', 'g', 'G', 'g', 'H', 'h', 'H', 'h', 'I', 'i', 'I', 'i', 'I', 'i', 'I', 'i', 'I', 'i', 'IJ', 'ij', 'J', 'j', 'K', 'k', 'L', 'l', 'L', 'l', 'L', 'l', 'L', 'l', 'l', 'l', 'N', 'n', 'N', 'n', 'N', 'n', 'n', 'O', 'o', 'O', 'o', 'O', 'o', 'OE', 'oe', 'R', 'r', 'R', 'r', 'R', 'r', 'S', 's', 'S', 's', 'S', 's', 'S', 's', 'T', 't', 'T', 't', 'T', 't', 'U', 'u', 'U', 'u', 'U', 'u', 'U', 'u', 'U', 'u', 'U', 'u', 'W', 'w', 'Y', 'y', 'Y', 'Z', 'z', 'Z', 'z', 'Z', 'z', '', 'f', 'O', 'o', 'U', 'u', 'A', 'a', 'I', 'i', 'O', 'o', 'U', 'u', 'U', 'u', 'U', 'u', 'U', 'u', 'U', 'u', 'A', 'a', 'AE', 'ae', 'O', 'o', '_', '_', '_', '_', '_', '_', '_', '_', '_', '_', '_', '_', '_', '_', '', '', '', '_', '', '', '', '_', '', '', '', '', '');
    return str_replace($a, $b, $str);
}

function strip($text)
{
    $text = htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    return $text = stripslashes($text);
}

function file_extension($filename)
{
    return end(explode(".", $filename));
}

function ktext($text)
{

    global $etext;
    $trkarakterler[0] = "/ü/";
    $trkarakterler[1] = "/ğ/";
    $trkarakterler[2] = "/ş/";
    $trkarakterler[3] = "/İ/";
    $trkarakterler[4] = "/Ş/";
    $trkarakterler[5] = "/ç/";
    $trkarakterler[6] = "/ö/";
    $trkarakterler[7] = "/ı/";
    $trkarakterler[8] = "/Ö/";
    $trkarakterler[9] = "/Ü/";
    $trkarakterler[10] = "/Ğ/";
    $trkarakterler[11] = "/Ç/";
    $trkarakterler[12] = "/ /";
    //$trkarakterler[13] = "/\./";
    $trkarakterler[14] = "/\?/";
    $trkarakterler[15] = '/\$/';
    $trkarakterler[16] = '/\//';
    $trkarakterler[17] = '/\'/';
    $trkarakterler[18] = '/\"/';
    $trkarakterler[19] = "/\(/";
    $trkarakterler[20] = "/\)/";
    $trkarakterler[21] = "/\-/";
    $trkarakterler[22] = "/\:/";
    $trkarakterler[23] = "/\`/";
    $trkarakterler[24] = "/,/";
    $trkarakterler[25] = "/\./";
    $trkarakterler[26] = "/â/";
    $trkarakterler[27] = '/\&/';
    $trkarakterler[28] = '/\%/';
    $trkarakterler2[0] = "u";
    $trkarakterler2[1] = "g";
    $trkarakterler2[2] = "s";
    $trkarakterler2[3] = "I";
    $trkarakterler2[4] = "S";
    $trkarakterler2[5] = "c";
    $trkarakterler2[6] = "o";
    $trkarakterler2[7] = "i";
    $trkarakterler2[8] = "O";
    $trkarakterler2[9] = "U";
    $trkarakterler2[10] = "G";
    $trkarakterler2[11] = "C";
    $trkarakterler2[12] = "-";
    //$trkarakterler2[13] = "";
    $trkarakterler2[14] = "";
    $trkarakterler2[15] = "S";
    $trkarakterler2[16] = "-";
    $trkarakterler2[17] = "";
    $trkarakterler2[18] = "";
    $trkarakterler2[19] = "";
    $trkarakterler2[20] = "";
    $trkarakterler2[21] = "-";
    $trkarakterler2[22] = "-";
    $trkarakterler2[23] = "-";
    $trkarakterler2[24] = "-";
    $trkarakterler2[25] = "";
    $trkarakterler2[26] = "a";
    $trkarakterler2[27] = "-";
    $trkarakterler2[28] = "-";
    $etext = preg_replace($trkarakterler, $trkarakterler2, $text);
    return $etext;

}

function trim_ozet($text)
{
    global $etext;
    $trkarakterler[0] = "/&#8217;/";


    $trkarakterler2[0] = "\'";

    $etext = preg_replace($trkarakterler, $trkarakterler2, $text);
    return $etext;
}

function chtext($text)
{
    global $etext;

    $trkarakterler[0] = "/ü/";
    $trkarakterler[1] = "/ğ/";
    $trkarakterler[2] = "/ş/";
    $trkarakterler[3] = "/İ/";
    $trkarakterler[4] = "/Ş/";
    $trkarakterler[5] = "/ç/";
    $trkarakterler[6] = "/ö/";
    $trkarakterler[7] = "/ı/";
    $trkarakterler[8] = "/Ö/";
    $trkarakterler[9] = "/Ü/";
    $trkarakterler[10] = "/Ğ/";
    $trkarakterler[11] = "/Ç/";
    $trkarakterler[12] = "/ /";
    //$trkarakterler[13] = "/\./";
    $trkarakterler[14] = "/\?/";
    $trkarakterler[15] = '/\$/';
    $trkarakterler[16] = '/\//';
    $trkarakterler[17] = '/\'/';
    $trkarakterler[18] = '/\"/';
    $trkarakterler[19] = "/\(/";
    $trkarakterler[20] = "/\)/";
    $trkarakterler[21] = "/\-/";
    $trkarakterler[22] = "/\:/";
    $trkarakterler[23] = "/\`/";
    $trkarakterler[24] = "/,/";
    $trkarakterler[26] = "/â/";
    $trkarakterler[27] = '/\&/';
    $trkarakterler[28] = '/\%/';
    $trkarakterler[29] = "/ñ/";
    $trkarakterler[30] = "/é/";
    $trkarakterler[31] = "/ä/";
    $trkarakterler[39] = "/#8217;/";


    $trkarakterler2[0] = "u";
    $trkarakterler2[1] = "g";
    $trkarakterler2[2] = "s";
    $trkarakterler2[3] = "I";
    $trkarakterler2[4] = "S";
    $trkarakterler2[5] = "c";
    $trkarakterler2[6] = "o";
    $trkarakterler2[7] = "i";
    $trkarakterler2[8] = "O";
    $trkarakterler2[9] = "U";
    $trkarakterler2[10] = "G";
    $trkarakterler2[11] = "C";
    $trkarakterler2[12] = "_";
    //$trkarakterler2[13] = "_";
    $trkarakterler2[14] = "_";
    $trkarakterler2[15] = "S";
    $trkarakterler2[16] = "_";
    $trkarakterler2[17] = "_";
    $trkarakterler2[18] = "_";
    $trkarakterler2[19] = "_";
    $trkarakterler2[20] = "_";
    $trkarakterler2[21] = "_";
    $trkarakterler2[22] = "_";
    $trkarakterler2[23] = "_";
    $trkarakterler2[24] = "_";
    $trkarakterler2[26] = "a";
    $trkarakterler2[27] = "_";
    $trkarakterler2[28] = "_";
    $trkarakterler2[29] = "n";
    $trkarakterler2[30] = "e";
    $trkarakterler2[31] = "a";
    $trkarakterler2[39] = "_";

    $etext = preg_replace($trkarakterler, $trkarakterler2, $text);
    return $etext;
}

function valid_email($str)
{
    return (!preg_match("/^([a-z0-9\+_\-]+)(\.[a-z0-9\+_\-]+)*@([a-z0-9\-]+\.)+[a-z]{2,6}$/ix", $str)) ? FALSE : TRUE;
}

function rescape($text)
{
    include_once("dbname.php");
    $text = str_replace("SLEEP", "", $text);
    $text = str_replace("BENCHMARK", "", $text);
    return mysqli_real_escape_string(mysqli_connect("localhost", "bilgi_islem_takip_stg", "xw*X4[7{2M\Jm!j_5", db_name()), $text);
}

function valid_url($url)
{
    return preg_match('|^http(s)?://[a-z0-9-]+(.[a-z0-9-]+)*(:[0-9]+)?(/.*)?$|i', $url);
}

function Convert_Int_To_Money($text)
{
    $tl_formati = number_format($text, 2, ',', '.');
    return $tl_formati;
}

function Convert_Money_To_Int($text)
{
    return str_replace(",", ".", str_replace(".", "", $text));
}

function turkcetarih($f, $zt = 'now')
{
    $z = date("$f", strtotime($zt));
    $donustur = array(
        'Monday' => 'Pazartesi',
        'Tuesday' => 'Salı',
        'Wednesday' => 'Çarşamba',
        'Thursday' => 'Perşembe',
        'Friday' => 'Cuma',
        'Saturday' => 'Cumartesi',
        'Sunday' => 'Pazar',
        'January' => 'Ocak',
        'February' => 'Şubat',
        'March' => 'Mart',
        'April' => 'Nisan',
        'May' => 'Mayıs',
        'June' => 'Haziran',
        'July' => 'Temmuz',
        'August' => 'Ağustos',
        'September' => 'Eylül',
        'October' => 'Ekim',
        'November' => 'Kasım',
        'December' => 'Aralık',
        'Mon' => 'Pts',
        'Tue' => 'Sal',
        'Wed' => 'Çar',
        'Thu' => 'Per',
        'Fri' => 'Cum',
        'Sat' => 'Cts',
        'Sun' => 'Paz',
        'Jan' => 'Oca',
        'Feb' => 'Şub',
        'Mar' => 'Mar',
        'Apr' => 'Nis',
        'Jun' => 'Haz',
        'Jul' => 'Tem',
        'Aug' => 'Ağu',
        'Sep' => 'Eyl',
        'Oct' => 'Eki',
        'Nov' => 'Kas',
        'Dec' => 'Ara',
    );
    foreach ($donustur as $en => $tr) {
        $z = str_replace($en, $tr, $z);
    }
    if (strpos($z, 'Mayıs') !== false && strpos($f, 'F') === false) $z = str_replace('Mayıs', 'May', $z);
    return $z;
}

function generateStrongPassword($length = 9, $add_dashes = false, $available_sets = 'luds')
{
    $sets = array();
    if(strpos($available_sets, 'l') !== false)
        $sets[] = 'abcdefghjkmnpqrstuvwxyz';
    if(strpos($available_sets, 'u') !== false)
        $sets[] = 'ABCDEFGHJKMNPQRSTUVWXYZ';
    if(strpos($available_sets, 'd') !== false)
        $sets[] = '23456789';
    if(strpos($available_sets, 's') !== false)
        $sets[] = '!@#$%&*?';

    $all = '';
    $password = '';
    foreach($sets as $set)
    {
        $password .= $set[array_rand(str_split($set))];
        $all .= $set;
    }

    $all = str_split($all);
    for($i = 0; $i < $length - count($sets); $i++)
        $password .= $all[array_rand($all)];

    $password = str_shuffle($password);

    if(!$add_dashes)
        return $password;

    $dash_len = floor(sqrt($length));
    $dash_str = '';
    while(strlen($password) > $dash_len)
    {
        $dash_str .= substr($password, 0, $dash_len) . '-';
        $password = substr($password, $dash_len);
    }
    $dash_str .= $password;
    return $dash_str;
}

function valid_mime_types(){

    $valid_mime_types = array(

        'png' => 'image/png',
        'bmp' =>'image/bmp',
        'gif' => 'image/gif',
        'jpe' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'jpg' => 'image/jpeg',
        'pdf' => 'application/pdf',
        'doc' => 'application/msword',
        'dot' => 'application/msword',
        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'dotx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.template',
        'docm' => 'application/vnd.ms-word.document.macroEnabled.12',
        'dotm' => 'application/vnd.ms-word.template.macroEnabled.12',
        'xls' => 'application/vnd.ms-excel',
        'xlt' => 'application/vnd.ms-excel',
        'xla' => 'application/vnd.ms-excel',
        'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'xltx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.template',
        'xlsm' => 'application/vnd.ms-excel.sheet.macroEnabled.12',
        'xltm' => 'application/vnd.ms-excel.template.macroEnabled.12',
        'xlam' => 'application/vnd.ms-excel.addin.macroEnabled.12',
        'xlsb' => 'application/vnd.ms-excel.sheet.binary.macroEnabled.12'
    );

    return $valid_mime_types;
}

function valid_mime_types_excel(){

    $valid_mime_types = array(
        'xls' => 'application/vnd.ms-excel',
        'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
    );
    return $valid_mime_types;
}

function valid_mime_types_image(){

    $valid_mime_types = array(

        'png' => 'image/png',
        'bmp' =>'image/bmp',
        'gif' => 'image/gif',
        'jpe' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'jpg' => 'image/jpeg'
    );

    return $valid_mime_types;
}

function buyuk_harfe_cevir($text)
{
    global $etext;


    $trkarakterler[0] = "/ç/";
    $trkarakterler[1] = "/ğ/";
    $trkarakterler[2] = "/i/";
    $trkarakterler[3] = "/ö/";
    $trkarakterler[4] = "/ş/";
    $trkarakterler[5] = "/ü/";
    $trkarakterler[6] = "/ı/";
    $trkarakterler[7] = "/i/";

    $trkarakterler2[0] = "Ç";
    $trkarakterler2[1] = "Ğ";
    $trkarakterler2[2] = "İ";
    $trkarakterler2[3] = "Ö";
    $trkarakterler2[4] = "Ş";
    $trkarakterler2[5] = "Ü";
    $trkarakterler2[6] = "I";
    $trkarakterler2[7] = "İ";

    $etext = preg_replace($trkarakterler, $trkarakterler2, $text);
    $etext = strtoupper($etext);
    return $etext;
}
?>