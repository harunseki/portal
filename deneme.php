<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
header('Content-Type: application/json; charset=utf-8');
require_once("class/mysql.php");


$url = "https://ybscanli.cankaya.bel.tr/FlexCityUi/rest/json/sis/RestDataset";

$headers = [
    "Authorization: applicationkey=PRONETA,requestdate=2025-05-02T15:02:47+03:00,md5hashcode=9ead7fa09a505ba912689a4deffb55ab",
    "Content-Type: application/json",
];

$postData = "datasetName=PRONETA_PERSONEL_YAZILIM&parameters={TCKIMLIKNO:'',DURUMU:'',USERNAME:'',ALTTURU:'',MUDURLUK:'',STATU_TURU:''}";

// Eğer servis form-data bekliyorsa (dosyanın raw alanında sanki form parametre gibi yazılmış)
# $postData = http_build_query($data);

$ch = curl_init($url);

curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => $headers,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => $postData,
]);

$response = curl_exec($ch);
$updated = 0;
$skipped = 0;
$notFound = 0;
if (curl_errno($ch)) {
    echo "Hata: " . curl_error($ch);
}
else {
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    "HTTP Kod: $httpCode\n";

    // Yanıtı diziye çevir ve yazdır
    $json = json_decode($response, true);
    if ($json !== null) {
        $personList = $json['resultSet'];
        /*foreach ($personList as $person) {
            // Alanları temizle
            $adi = trim($person['ADI'] ?? '');
            $soyadi = trim($person['SOYADI'] ?? '');
            $sicilNo = trim($person['SICIL_NO'] ?? '');
            $tckn = trim($person['TCKN'] ?? '');

            if ($adi === '' || $soyadi === '') continue;

            // Eşleşen kullanıcı var mı kontrol et
            $check = $dba->query("SELECT id FROM carduser WHERE adi = '$adi' AND soyadi = '$soyadi' AND sicilNo = '$sicilNo'");
            if ($dba->num_rows($check) > 0) {

                // Bu TCKN başka kullanıcıda var mı?
                $dupCheck = $dba->query("SELECT id FROM carduser WHERE TCKN = '$tckn' AND NOT (adi = '$adi' AND soyadi = '$soyadi' AND sicilNo = '$sicilNo')");
                if ($dba->num_rows($dupCheck) > 0) {
                    $skipped++;
                    continue; // çakışma varsa atla
                }

                // Güncelle
                $update = $dba->query("UPDATE carduser SET TCKN = '$tckn' WHERE adi = '$adi' AND soyadi = '$soyadi' AND sicilNo = '$sicilNo'");
                if ($update) $updated += $dba->affected_rows();

            } else {
                $notFound++;
            }
        }*/

    } else {
        echo "Gelen yanıt JSON değil:\n$response";
    }
}
curl_close($ch);

//40178010708

echo "Güncelleme tamamlandı.\n";
echo "Toplam servis kaydı: " . count($personList) . "\n";
echo "Güncellenen kayıt sayısı: $updated\n";
echo "Atlanan (TCKN çakışması) kayıt sayısı: $skipped\n";
echo "Eşleşmeyen kayıt sayısı: $notFound\n";