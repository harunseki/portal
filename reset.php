<?php
require_once("class/functions.php");

function generatePassword() {
    $upper   = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $lower   = 'abcdefghijklmnopqrstuvwxyz';
    $special = '!+.?';

    $year = date("Y"); // bulunduğumuz yıl (ör: 2025)

    // Şifreyi oluştur
    $password  = $upper[random_int(0, strlen($upper) - 1)];
    $password .= $lower[random_int(0, strlen($lower) - 1)];
    $password .= $lower[random_int(0, strlen($lower) - 1)];
    $password .= $year;
    $password .= $special[random_int(0, strlen($special) - 1)];

    return $password;
}
// Kullanıcı adı parametre ile geldi mi?
$user = $_GET['user'] ?? '';
if (!$user) {
    die("Kullanıcı seçilmedi.");
}

// Yeni şifre
$newPassword = generatePassword();
//$newPassword="Gs1905hs1987.";

// LDAP ayarları
$ldap_host = "ldap://10.1.1.21";
$ldap_port = 389;
$ldap_user = "cankaya\\smsadmin";
$ldap_pass = "Telefon01*";
$ldap_dn   = "DC=cankaya,DC=bel,DC=tr";

// LDAP bağlan
$ldap = ldap_connect($ldap_host, $ldap_port);
if (!$ldap) die("LDAP sunucusuna bağlanılamadı.");
ldap_set_option($ldap, LDAP_OPT_PROTOCOL_VERSION, 3);
ldap_set_option($ldap, LDAP_OPT_REFERRALS, 0);

if (!@ldap_bind($ldap, $ldap_user, $ldap_pass)) {
    die("LDAP Bind Başarısız! Hata: " . ldap_error($ldap));
}

// Kullanıcıyı bul
$filter = "(sAMAccountName=$user)";
$result = ldap_search($ldap, $ldap_dn, $filter, ["dn","telephoneNumber"]);
$entries = ldap_get_entries($ldap, $result);
if ($entries["count"] == 0) die("Kullanıcı bulunamadı.");

$user_dn = $entries[0]["dn"];
$telefon = $entries[0]["telephonenumber"][0] ?? '';

if ($telefon) {
    // Eğer numara varsa başına 9 ekleyelim
    $telefon = "9" . $telefon;
} else {
    die("⚠ Kullanıcının telefon numarası bulunmadığı için SMS gönderilemedi. ");
}

ldap_unbind($ldap);

// --- PowerShell ile şifre resetle ---
$ps = <<<PS
Import-Module ActiveDirectory
Set-ADAccountPassword -Identity "$user" -Reset -NewPassword (ConvertTo-SecureString -AsPlainText "$newPassword" -Force)
Unlock-ADAccount -Identity "$user"
Enable-ADAccount -Identity "$user"
PS;

$tmpfile = tempnam(sys_get_temp_dir(), "ps_") . ".ps1";
file_put_contents($tmpfile, $ps);
exec("powershell -ExecutionPolicy Bypass -File " . escapeshellarg($tmpfile) . " 2>&1", $output, $ret);
unlink($tmpfile);

if ($ret !== 0) {
    die("❌ Şifre sıfırlama başarısız!<br>" . implode("<br>", $output));
}

// --- SMS gönder ---
if ($telefon) {
    try {
        $client = new SoapClient("http://ws.ttmesaj.com/service1.asmx?WSDL", [
            "trace" => 1,
            "exceptions" => 1
        ]);

        $username   = "cankaya.iek";
        $password   = "D7G8M9S1F";
        $mesaj      = "Yeni şifreniz: ".$newPassword;

        $params = [
            "username"   => $username,
            "password"   => $password,
            "numbers"     => $telefon,
            "message"    => $mesaj,
            "origin" => "CANKAYA BLD",
            "sd"   => "",
            "ed"    => "",
            "isNotification"  => true,
            "recipentType"   => "0",
            "brandCode"   => "0"
        ];

        // SOAP çağrısı
        $result = $client->__soapCall("sendSingleSMS", [$params]);

        if (isset($result->sendSingleSMSResult) && strpos($result->sendSingleSMSResult, '*OK*') === 0) {
            echo "SMS başarıyla gönderildi.";
        } else {
            echo "SMS gönderilemedi. Hata kodu: " . ($result->sendSingleSMSResult ?? "bilinmiyor");
        }

    } catch (Exception $e) {
        echo "Hata: " . $e->getMessage();
    }
} else {
    echo "✅ Şifre sıfırlandı ama telefon numarası bulunamadı. ";
}
?>
