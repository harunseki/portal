<?php
require_once("../../class/functions.php");

if(!isset($_GET['lastDate'])){
    die("Tarih bilgisi yok.");
}

$lastDate = $_GET['lastDate']; // YYYY-MM-DD formatında
$personelTckimlikno = $_GET['personelTckimlikno']; // YYYY-MM-DD formatında
$apiUrl = "https://pdks.cankaya.bel.tr/api/pdksModule/PdksActivity/GetPdksPersonelFirstEnterLastExitLog";

$end = date('Y-m-d', strtotime('-1 day', strtotime($lastDate)));
$start = date('Y-m-d', strtotime('-15 days', strtotime($lastDate)));

$accessToken = "5c84a43b-0afc-49b5-91fd-e647df2b87f8";
$includeNonworkerPersonels = "true";

// cURL başlat
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "$apiUrl?Start=$start&End=$end&access_token=$accessToken&IncludeNonworkerPersonels=$includeNonworkerPersonels&personelTckimlikno=$personelTckimlikno");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 30); // timeout 30 saniye
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // sadece test için
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false); // sadece test için

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

$data = json_decode($response, true);

// Tarih bazlı eşleme
$dataMap = [];
if (!empty($data)) {
    foreach ($data as $entry) {
        $dateKey = substr($entry['date'], 0, 10);
        $dataMap[$dateKey] = $entry;
    }
}

$turkceGunler = ['Pazartesi','Salı','Çarşamba','Perşembe','Cuma','Cumartesi','Pazar'];

for ($i=0; $i<15; $i++) {
    $date = date('Y-m-d', strtotime("-$i day", strtotime($end)));
    $dayOfWeek = date('N', strtotime($date));
    $isWeekend = $dayOfWeek > 5;
    $dayName = $isWeekend ? ( $dayOfWeek == 6 ? 'Cumartesi' : 'Pazar' ) : $turkceGunler[$dayOfWeek-1];

    $enterTime = $exitTime = '';
    if(isset($dataMap[$date])){
        $entry = $dataMap[$date];
        if(!empty($entry['enterDate'])){
            $dtEnter = new DateTime($entry['enterDate']);
            $dtEnter->modify('+3 hours');
            $enterTime = $dtEnter->format('H:i:s');
        } else { $enterTime = 'Giriş Yapılmadı'; }
        if(!empty($entry['exitDate'])){
            $dtExit = new DateTime($entry['exitDate']);
            $dtExit->modify('+3 hours');
            $exitTime = $dtExit->format('H:i:s');
        } else { $exitTime = 'Çıkış Yapılmadı'; }
    } else {
        $enterTime = '-';
        $exitTime = '-';
    }

    $rowClass = $isWeekend ? 'bg-light text-muted' : '';
    $rowColor = $isWeekend ? 'style="background-color: #efefef;"' : '';
    $dayDisplay = $isWeekend ? "<strong><em>$dayName</em></strong>" : $dayName;

    echo "<tr $rowColor data-date='$date'>";
    echo "<td>".htmlspecialchars(global_date_to_tr($date))."</td>";
    echo "<td>$dayDisplay</td>";
    echo "<td>".htmlspecialchars($enterTime)."</td>";
    echo "<td>".htmlspecialchars($exitTime)."</td>";
    echo "</tr>";
}

?>
