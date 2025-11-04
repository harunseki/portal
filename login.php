<?php
ob_start();
session_start();

error_reporting(E_ALL & ~E_WARNING & ~E_DEPRECATED);

require_once("class/mysql.php");
require_once("../class/functions.php");

require_once ("../htmlpurifier/library/HTMLPurifier.auto.php");
$config = HTMLPurifier_Config::createDefault();
$config->set('Core.Encoding', 'UTF-8'); // replace with your encoding
$config->set('HTML.Doctype', 'HTML 4.01 Transitional'); // replace with your doctype
$config->set('HTML.Doctype', 'HTML 4.01 Transitional'); // replace with your doctype
$purifier = new HTMLPurifier($config);

$q=$dba->query("SELECT site_baslik FROM ayarlar ");
$row=$dba->fetch_assoc($q);

set_csrf_token();
?>
<!DOCTYPE html>
<html class="bg-black">
<head>
    <meta charset="UTF-8">
    <title>Site Yönetim Paneli</title>
    <meta content='width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no' name='viewport'>
    <!-- bootstrap 3.0.2 -->
    <link href="css/bootstrap.min.css" rel="stylesheet" type="text/css" />
    <!-- font Awesome -->
    <link href="css/font-awesome.min.css" rel="stylesheet" type="text/css" />
    <!-- Theme style -->
    <link href="css/AdminLTE.css" rel="stylesheet" type="text/css" />

    <!-- HTML5 Shim and Respond.js IE8 support of HTML5 elements and media queries -->
    <!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
    <!--[if lt IE 9]>
    <script src="https://oss.maxcdn.com/libs/html5shiv/3.7.0/html5shiv.js"></script>
    <script src="https://oss.maxcdn.com/libs/respond.js/1.3.0/respond.min.js"></script>
    <![endif]-->
</head>
<body class="bg-black">
<div class="form-box" id="login-box">
    <?php
    if(isset($_POST['login'])) {
        if (isset($_POST['csrf_token']) && $_POST['csrf_token'] == get_csrf_token()) {
            $username = $purifier->purify(rescape($_POST['username'])) ?? '';
            $password = $purifier->purify(rescape($_POST['password'])) ?? '';
            $guvenlik_kodu = $purifier->purify(rescape($_POST['guvenlik_kodu'])) ?? '';

            if (!empty($username) && !empty($password)) {
                if (empty($username) or empty($password)) alert_danger("Lütfen LDAP Kullanıcı Adı ve Şifrenizi doğru giriniz");
                else if ($_SESSION['guvenlik_kodu'] != $guvenlik_kodu) alert_danger("Lütfen güvenlik kodunu doğru giriniz");
                else {
                    // LDAP sunucusu adresi
                    $ldap_server = "ldap://10.1.1.21"; // Örn: ldap://192.168.1.10
                    $ldap_port = 389; // Genelde 389, LDAPS için 636
                    $ldap_domain = "CANKAYA";


                    // LDAP sunucusuna bağlan
                    $ldap_conn = ldap_connect($ldap_server, $ldap_port);
                    if (!$ldap_conn) die("LDAP sunucusuna bağlanılamadı!");

                    // LDAP sürümünü ayarla
                    ldap_set_option($ldap_conn, LDAP_OPT_PROTOCOL_VERSION, 3);
                    ldap_set_option($ldap_conn, LDAP_OPT_REFERRALS, 0);

                    // Kullanıcı adı ve şifre ile kimlik doğrulaması (bind) dene
                    $bind = @ldap_bind($ldap_conn, $username . "@cankaya.bel.tr", $password);

                    if ($bind) {
                        $_SESSION['user'] = "$ldap_domain\\$username";
                        header("Location: index.php");
                        exit;
                    } else {
                        alert_danger("Kimlik doğrulama başarısız! Hatalı kullanıcı adı veya şifre.");
                    }

                    // Bağlantıyı kapat
                    ldap_unbind($ldap_conn);
                }
            } else {
                $error = "Lütfen tüm alanları doldurun!";
            }
        }
    }
    ?>
    <div class="header">
        <img alt="" src="img/logo-cankaya.png" style="height: 50px"><hr>
        <strong>KULLANICI GİRİŞ PANELİ</strong>
    </div>
    <form action="<?=htmlspecialchars($_SERVER['REQUEST_URI'], ENT_QUOTES, 'UTF-8') ?>" method="post" name="login" id="login">
        <div class="body bg-gray">
            <input type="hidden" name="csrf_token" value="<?=get_csrf_token() ?>"/>
            <!--<center><strong><?php /*=strip($row['site_baslik'])*/?><br>ADMİN GİRİŞ</strong></center>-->
            <div class="form-group">
                <input type="text" id="username" name="username" class="form-control" placeholder="TCNo veya Bilgisayar Kullanıcı Adı" required data-mask/>
            </div>
            <div class="form-group">
                <input type="password" AUTOCOMPLETE="off" id="password" name="password" class="form-control" placeholder="Bilgisayar Şifreniz" required/>
            </div>
            <div class="form-group">
                <div style="margin-bottom: 5px;"><img id="security" src="../security.php"></div>
                <input type="text" id="guvenlik_kodu" name="guvenlik_kodu" class="form-control" placeholder="Güvenlik Kodu" required/>
            </div>
        </div>
        <div class="footer bg-gray">
            <button type="submit" name="login" class="btn bg-olive btn-block">Giriş Yap</button>
            <a href="https://giris.turkiye.gov.tr/OAuth2AuthorizationServer/AuthorizationController?response_type=code&client_id=4d497451-7e31-40bb-b6d8-211a8f4cc9cd&state=uygulamalar&scope=Kimlik-Dogrula&redirect_uri=https://uygulamalar.kecioren.bel.tr/edevletgirisi/index.php"  name="edevlet" class="btn bg-olive btn-block" style="background-color: #e42d33 !important;"><img src="img/edevlet_logo.png" style="height: 25px !important; margin-right: 7px;" >E-Devlet Giriş</a>
        </div>
    </form>
</div>

<!-- jQuery 2.0.2 -->
<script src="http://ajax.googleapis.com/ajax/libs/jquery/2.0.2/jquery.min.js"></script>
<!-- Bootstrap -->
<script src="js/bootstrap.min.js" type="text/javascript"></script>

</body>
</html>