<?php
ob_start();
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

error_reporting(E_ALL & ~E_WARNING & ~E_DEPRECATED);

// 🔹 Kullanıcı adı Remote User'dan alınıyor
$remoteUser = $_SERVER['REMOTE_USER'] ?? $_SERVER['AUTH_USER'] ?? 'Bilinmiyor';
$ip = $_SERVER['REMOTE_ADDR'] ?? null;

if (!$remoteUser) {
    die("Kullanıcı adı alınamadı!");
}

// 🔹 DOMAIN\username formatını temizle
if (strpos($remoteUser, "\\") !== false) {
    list(, $ldap_username) = explode("\\", $remoteUser, 2);
} else {
    $ldap_username = $remoteUser;
}

if ($_SERVER['REMOTE_ADDR']=="10.2.200.79") {
    $admin = 1;
        /*$ldap_username ="sibelkoksal";*/
}

$sessionTimeout = 1800; // 30 dakika

$needLdap = false;

if (!isset($_SESSION['ldap_username'])) {
    $needLdap = true;
}
elseif (isset($_SESSION['ldap_last_check']) && (time() - $_SESSION['ldap_last_check'] > $sessionTimeout)) {
    $needLdap = true;
}
elseif ($_SESSION['ldap_username'] !== $ldap_username) {
    $needLdap = true;
}
$personelTC = "";
$cn = "";
if ($needLdap) {
    $ldap_host = "ldap://10.1.1.21";
    $ldap_port = 389;
    $ldap_user = "cankaya\\smsadmin";
    $ldap_pass = "Telefon01*";
    $ldap_dn   = "DC=cankaya,DC=bel,DC=tr";

    $ldap = null;

    try {
        // 🔹 Bağlantı
        $ldap = @ldap_connect($ldap_host, $ldap_port);

        if (!$ldap) {
            throw new Exception("LDAP sunucusuna bağlanılamadı");
        }

        ldap_set_option($ldap, LDAP_OPT_PROTOCOL_VERSION, 3);
        ldap_set_option($ldap, LDAP_OPT_REFERRALS, 0);
        ldap_set_option($ldap, LDAP_OPT_NETWORK_TIMEOUT, 5);

        // 🔹 Bind
        if (!@ldap_bind($ldap, $ldap_user, $ldap_pass)) {
            throw new Exception("LDAP bind başarısız: " . ldap_error($ldap));
        }

        // LDAP injection koruması
        $ldap_username = ldap_escape($ldap_username, '', LDAP_ESCAPE_FILTER);

        $filter = "(samaccountname={$ldap_username})";
        $attributes = [
                "displayname",
                "facsimileTelephoneNumber",
                "mail",
                "telephonenumber",
                "ipphone",
                "info",
                "department",
                "userprincipalname"
        ];

        $result = @ldap_search($ldap, $ldap_dn, $filter, $attributes);

        if (!$result) {
            throw new Exception("LDAP arama hatası: " . ldap_error($ldap));
        }

        $entries = ldap_get_entries($ldap, $result);

        if ($entries["count"] <= 0) {
            throw new Exception("LDAP kullanıcısı bulunamadı");
        }

        $entry = $entries[0];

        $cn              = $entry["displayname"][0] ?? 'Veri yok';
        $personelSicilNo = $entry["facsimiletelephonenumber"][0] ?? 'Veri yok';
        $mail            = $entry["mail"][0] ?? '';
        $telephonenumber = $entry["telephonenumber"][0] ?? 'Veri yok';
        $ipphone         = $entry["ipphone"][0] ?? 'Veri yok';
        $personelTC      = $entry["info"][0] ?? 'Veri yok';
        $department      = $entry["department"][0] ?? 'Veri yok';
        $cardNumber      = $entry["facsimiletelephonenumber"][0] ?? 'Veri yok';

        // Mail fallback
        if (empty($mail) && isset($entry['userprincipalname'][0])) {
            $mail = $entry['userprincipalname'][0];
        }

        if (!filter_var($mail, FILTER_VALIDATE_EMAIL)) {
            $mail = "mail@cankaya.bel.tr";
        }

        // Türkçe büyük harf dönüşümü
        $replace = [
                'i' => 'İ', 'ı' => 'I', 'ğ' => 'Ğ',
                'ü' => 'Ü', 'ş' => 'Ş',
                'ö' => 'Ö', 'ç' => 'Ç'
        ];
        $cnUpper = strtr(mb_strtoupper($cn, 'UTF-8'), $replace);

        // 🔹 Session set
        $_SESSION['ldap_username']   = $ldap_username;
        $_SESSION['personelTC']      = $personelTC;
        $_SESSION['personelSicilNo'] = $personelSicilNo;
        $_SESSION['kullanici_adi']   = $cn;
        $_SESSION['kullanici_adi1']  = $cnUpper;
        $_SESSION['mail']            = $mail;
        $_SESSION['telephonenumber'] = $telephonenumber;
        $_SESSION['ipphone']         = $ipphone;
        $_SESSION['department']      = $department;
        $_SESSION['cardNumber']      = $cardNumber;
        $_SESSION['ldap_last_check'] = time();

    } catch (Exception $e) {

        // 🔴 Gerçek hatayı kullanıcıya gösterme
        error_log("LDAP Bağlantı Hatası: " . $e->getMessage());

        // Session temizle
        session_unset();
        session_destroy();

        // Kullanıcı dostu mesaj
        header("Location: /bakim.php");
        exit;

    } finally {
        // 🔹 GARANTİLİ BAĞLANTI KAPAMA
        if ($ldap) {
            @ldap_unbind($ldap);
        }
    }
}
else {
    $personelTC = $_SESSION['personelTC'];
    $cn = $_SESSION['kullanici_adi'];
}

require_once("class/mysql.php");
require_once("../class/functions.php");

require_once ("../htmlpurifier/library/HTMLPurifier.auto.php");
$config = HTMLPurifier_Config::createDefault();
$config->set('Core.Encoding', 'UTF-8');
$config->set('HTML.Doctype', 'HTML 4.01 Transitional');
$purifier = new HTMLPurifier($config);
$def = $config->getHTMLDefinition(true);
$def->addAttribute('div', 'data-tab-set-title', 'CDATA');

$qUser = $dba->prepare("SELECT id, mudurluk FROM yetkili WHERE username=? AND yetkili_durumu='1'");
$qUser->bind_param("s", $ldap_username);
$qUser->execute();
$resUser = $qUser->get_result();
$user = $resUser->fetch_assoc();

$_SESSION['permissions'] = [];
$_SESSION['active_permissions'] = [];

if ($user) {
    $kullanici_id = $user['id'];
    $_SESSION['kullanici_mudurluk'] = $user['mudurluk'];
    $_SESSION['kullanici_id'] = $kullanici_id;

    $q = $dba->prepare("SELECT yt.id AS yetki_id, yt.yetki AS yetki_key, yt.isim  AS yetki_label, COALESCE(ym.deger,0) AS deger FROM mod_moduller yt
                        LEFT JOIN yetkili_moduller ym ON ym.yetki_key = yt.id AND ym.kullanici_id = ?
                        WHERE yt.aktif IN (1,5)
                        ORDER BY yt.isim ASC ");
    $q->bind_param("i", $kullanici_id);
    $q->execute();
    $result = $q->get_result();

    $_SESSION['permissions'] = [];
    $_SESSION['active_permissions'] = [];

    while ($row = $result->fetch_assoc()) {
        $item = [
            'id'    => (int)$row['yetki_id'],
            'key'   => $row['yetki_key'],
            'label' => $row['yetki_label'],
            'value' => (int)$row['deger']
        ];
        $_SESSION['permissions'][$row['yetki_id']] = $item;
        // Eski sistem uyumluluğu
        $_SESSION[$row['yetki_key']] = (int)$row['deger'];

        if ($item['value'] === 1) {
            $_SESSION['active_permissions'][$row['yetki_id']] = $item;
        }
    }
}

$hasPermission = false;

foreach ($_SESSION['permissions'] ?? [] as $perm) {
    if ($perm['value'] == 1) {
        $hasPermission = true;
        break;
    }
}

$x = isset($_GET['x']) ? $purifier->purify(rescape($_GET['x'])) : null;
$fazla_mesai = "";
$display3 = "";
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Portal Çankaya</title>
    <meta content='width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no' name='viewport'>
    <link rel="icon" type="image/x-icon" sizes="96x96" href="img/favicon.ico">

    <link href="assets/css/bootstrap.min.css" rel="stylesheet" type="text/css" />
    <link href="assets/css/font-awesome.min.css" rel="stylesheet" type="text/css" />
    <link href="assets/css/ionicons.min.css" rel="stylesheet" type="text/css" />
    <link href="assets/css/morris/morris.css" rel="stylesheet" type="text/css" />
    <link href="assets/css/jvectormap/jquery-jvectormap-1.2.2.css" rel="stylesheet" type="text/css" />
    <link href="assets/css/fullcalendar/fullcalendar.css" rel="stylesheet" type="text/css" />
    <link href="assets/css/daterangepicker/daterangepicker-bs3.css" rel="stylesheet" type="text/css" />
    <link href="assets/css/bootstrap-wysihtml5/bootstrap3-wysihtml5.min.css" rel="stylesheet" type="text/css" />
    <link href="assets/css/AdminLTE.css" rel="stylesheet" type="text/css" />
    <!-- jQuery (ÖNCE GELMELİ) -->
    <script src="assets/js/jquery-2.1.4.min.js"></script>
    <!-- Chosen CSS (Select2 ile çakışmaz, sadece class kullanmazsan sorun yok) -->
    <link rel="stylesheet" href="assets/css/chosen.css">
    <!-- Toastr CSS -->
    <link rel="stylesheet" href="assets/css/toastr/toastr.min.css" />
    <!-- Chosen JS -->
    <script src="assets/js/chosen.jquery.js"></script>
    <!-- Toastr JS -->
    <script src="assets/js/toastr/toastr.min.js"></script>
</head>
<body class="skin-blue" lang="tr">
    <a href="index" class="fixed-ataturk-link">
        <img src="img/ataturk1.png" class="fixed-ataturk">
    </a>
    <style>
        .fixed-ataturk-link {
            position: fixed;
            top: 10px;
            left: 45px;
            z-index: 1050; /* diğer elemanların üstünde kalsın */
        }

        .fixed-ataturk {
            height: 140px;
            transition: transform 0.2s ease;
        }
        /* Hover efekti istersen */
        .fixed-ataturk-link:hover .fixed-ataturk {
            transform: scale(1.05);
        }
    </style>
    <header class="header" style="background: linear-gradient(90deg,rgba(34, 34, 34, 1) 20%, rgba(15, 168, 64, 1) 50%, rgba(34, 34, 34, 1) 90%); ">
        <!--<a href="index.php" class="logo"  style="text-transform: uppercase; font-size: 15px; padding-left: 0 !important;" >
            <img src="img/ata.png" align="left" height="100%" style="float:left;"><img src="img/cankaya1.png" align="left" height="80%" style="align-items: center; margin: 5px"></a>-->
        <nav class="navbar navbar-static-top" role="navigation" style="display: inline;">
            <a href="index">
                <img src="img/logo5.png" align="left" style="height:60px;float:left; margin-left: 215px; margin-top: -10px;">
            </a>
            <div class="navbar-right" style="margin-right: 15px;">
                <div class="navbar-text" style="display:flex; align-items:center; gap:15px; margin: 0">
                    <!-- Hava durumu -->
                    <div id="footer-weather" style="font-size:14px; color:#ffffff; font-weight:bold;">
                        <?php
                        $sql = "SELECT * FROM weather WHERE id=1";
                        $res = $dba->query($sql);
                        $last = $res ? $res->fetch_assoc() : null;

                        $needUpdate = true;

                        if ($last) {
                            $lastTime = strtotime($last['created_at']);
                            if (time() - $lastTime > 1000) $needUpdate = false;
                        }

                        if (!$needUpdate) {
                            // Open-Meteo (Ankara) API
                            $lat = 39.9334;
                            $lon = 32.8597;
                            $om_url = "https://api.open-meteo.com/v1/forecast?latitude={$lat}&longitude={$lon}&current_weather=true&hourly=relativehumidity_2m&timezone=Europe/Istanbul";

                            $ch = curl_init();
                            curl_setopt_array($ch, [
                                CURLOPT_URL => $om_url,
                                CURLOPT_RETURNTRANSFER => true,
                                CURLOPT_TIMEOUT => 10,
                                CURLOPT_SSL_VERIFYHOST => 0,
                                CURLOPT_SSL_VERIFYPEER => 0
                            ]);
                            $response = curl_exec($ch);
                            curl_close($ch);

                            $data = $response ? json_decode($response, true) : null;
                            $temp = $data['current_weather']['temperature'] ?? '--';
                            $wind = $data['current_weather']['windspeed'] ?? '--';
                            $code = $data['current_weather']['weathercode'] ?? null;

                            $humidity = "";
                            if ($data && isset($data['hourly']['time'], $data['hourly']['relativehumidity_2m'])) {
                                $currentTime = date('Y-m-d\TH:00') ?? null;
                                $timeIndex = array_search($currentTime, $data['hourly']['time']);
                                if ($timeIndex !== false) {
                                    $humidity = $data['hourly']['relativehumidity_2m'][$timeIndex] ?? null;
                                }
                            }

                            $descMap = [
                                0=>"Açık", 1=>"Az Bulutlu", 2=>"Parçalı Bulutlu", 3=>"Bulutlu",
                                45=>"Sis", 48=>"Kırağılı Sis",
                                51=>"Çiseleme", 53=>"Orta Çiseleme", 55=>"Yoğun Çiseleme",
                                61=>"Hafif Yağmur", 63=>"Orta Yağmur", 65=>"Şiddetli Yağmur",
                                71=>"Hafif Kar", 73=>"Orta Kar", 75=>"Yoğun Kar",
                                80=>"Sağanak", 81=>"Orta Sağanak", 82=>"Şiddetli Sağanak",
                                95=>"Gök Gürültülü", 96=>"Gök Gürültülü + Hafif Dolu", 99=>"Gök Gürültülü + Yoğun Dolu"
                            ];
                            $desc = $descMap[$code] ?? 'Bilinmiyor';

                            // Hava durumu ikonları
                            $icon = '<i class="fa fa-question-circle" style="color:#6c757d"></i>';
                            if (in_array($code,[0,1])) $icon = '<i class="fa fa-sun-o" style="color:#f39c12"></i>';
                            elseif (in_array($code,[2,3])) $icon = '<i class="fa fa-cloud" style="color:#3498db"></i>';
                            elseif (in_array($code,[61,63,65,80,81,82])) $icon = '<i class="fa fa-tint" style="color:#007BFF"></i>';
                            elseif (in_array($code,[95,96,99])) $icon = '<i class="fa fa-bolt" style="color:#e74c3c"></i>';
                            elseif (in_array($code,[71,73,75])) $icon = '<i class="fa fa-snowflake-o" style="color:#00c0ef"></i>';
                            elseif (in_array($code,[45,48])) $icon = '<i class="fa fa-smog" style="color:#95a5a6"></i>';

                            // id=1 olan kaydı güncelle
                            if ($last) {
                                $stmt = $dba->prepare("UPDATE weather SET temperature=?, windspeed=?, humidity=?, weathercode=?, description=?, created_at=NOW() WHERE id=1");
                                $stmt->bind_param("dddss", $temp, $wind, $humidity, $code, $desc);
                                $stmt->execute();
                                $stmt->close();
                            } else {
                                // Eğer tablo boşsa insert yap
                                $stmt = $dba->prepare("INSERT INTO weather (id, temperature, windspeed, humidity, weathercode, description, created_at) VALUES (1,?,?,?,?,?,NOW())");
                                $stmt->bind_param("dddds", $temp, $wind, $humidity, $code, $desc);
                                $stmt->execute();
                                $stmt->close();
                            }
                        }
                        else {
                            // 5 dakikadan küçükse son kaydı kullan
                            $temp = $last['temperature'];
                            $wind = $last['windspeed'];
                            $humidity = $last['humidity'];
                            $code = $last['weathercode'];
                            $desc = $last['description'];

                            $icon = '<i class="fa fa-question-circle" style="color:#6c757d"></i>';
                            if (in_array($code,[0,1])) $icon = '<i class="fa fa-sun-o" style="color:#f39c12"></i>';
                            elseif (in_array($code,[2,3])) $icon = '<i class="fa fa-cloud" style="color:#3498db"></i>';
                            elseif (in_array($code,[61,63,65,80,81,82])) $icon = '<i class="fa fa-tint" style="color:#007BFF"></i>';
                            elseif (in_array($code,[95,96,99])) $icon = '<i class="fa fa-bolt" style="color:#e74c3c"></i>';
                            elseif (in_array($code,[71,73,75])) $icon = '<i class="fa fa-snowflake-o" style="color:#00c0ef"></i>';
                            elseif (in_array($code,[45,48])) $icon = '<i class="fa fa-smog" style="color:#95a5a6"></i>';
                        }
                        ?>
                        <?= $icon ?> &nbsp; <?= htmlspecialchars($desc) ?> | 🌡 <?= htmlspecialchars(round($temp)) ?>°C | 💨 <?= htmlspecialchars(round($wind)) ?> km/h | 💧 Nem: <?= htmlspecialchars($humidity) ?>%
                    </div>
                    <!-- Kullanıcı menüsü -->
                    <ul class="nav navbar-nav">
                        <li class="dropdown user user-menu">
                            <a href="#" class="dropdown-toggle" data-toggle="dropdown" style="color: #ffffff;">
                                <i class="glyphicon glyphicon-user"></i>
                                <span>Profilim&nbsp;<i class="caret"></i></span>
                            </a>
                            <ul class="dropdown-menu">
                                <li class="user-header bg-light" style="height: auto; background: #353535;">
                                    <p style="color: #ffffff;"><?= $cn ?></p>
                                </li>
                                <li class="user-footer">
                                    <div class="pull-left">
                                        <a href="<?= $ldap_username ?>-3-yetkili" class="btn btn-default btn-flat">Bilgilerim</a>
                                    </div>
                                    <div class="pull-right">
                                        <a href="#" onclick="logoutAndClose()" class="btn btn-default btn-flat">Çıkış</a>
                                    </div>
                                </li>
                            </ul>
                        </li>
                    </ul>
                    <script>
                        function logoutAndClose() {
                            window.location.href = 'logout.php';
                        }
                    </script>
                </div>
            </div>
        </nav>
    </header>
    <div class="wrapper row-offcanvas row-offcanvas-left">