<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
header('Content-Type: application/json; charset=utf-8');
require_once("../class/mysql.php");

$tckn = trim($_POST['tckn'] ?? '');
$cardNumber = trim($_POST['cardNumber'] ?? '');

if (strlen($tckn) != 11) {
    echo json_encode(['status' => 'error', 'message' => 'Geçersiz TCKN']);
    exit;
}

/* 🔹 1. Yerel carduser tablosunda kontrol */
$sql = "
    SELECT id, cardNumber, TCKN, adi, soyadi, sicilNo, cardDepartment
    FROM carduser
    WHERE TCKN = '$tckn' 
       OR sicilNo IN (SELECT sicilNo FROM carduser WHERE sicilNo != '' AND TCKN = '$tckn')
    LIMIT 1
";
$exists = $dba->query($sql);

if ($exists && $exists->num_rows > 0) {
    $u = $exists->fetch_assoc();
    echo json_encode([
        'status' => 'found_local',
        'message' => 'Kullanıcı zaten sistemde kayıtlı.',
        'adi' => $u['adi'],
        'soyadi' => $u['soyadi'],
        'sicilNo' => $u['sicilNo'],
        'cardDepartment' => $u['cardDepartment'],
        'cardNumber' => $u['cardNumber'],
        'cardUserId' => $u['id']
    ]);
    exit;
}

/* 🔹 2. Servisten veri çek */
$url = "https://ybscanli.cankaya.bel.tr/FlexCityUi/rest/json/sis/RestDataset";
$headers = [
    "Authorization: applicationkey=PRONETA,requestdate=2025-05-02T15:02:47+03:00,md5hashcode=9ead7fa09a505ba912689a4deffb55ab",
    "Content-Type: application/json",
];
$postData = "datasetName=PRONETA_PERSONEL_YAZILIM&parameters={TCKIMLIKNO:'{$tckn}',DURUMU:'',USERNAME:'',ALTTURU:''}";

$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => $headers,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => $postData,
]);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode !== 200 || !$response) {
    echo json_encode(['status' => 'error', 'message' => "Servis HTTP $httpCode hatası"]);
    exit;
}

$json = json_decode($response, true);
if (!$json || empty($json['resultSet'])) {
    echo json_encode(['status' => 'error', 'message' => 'Servisten kayıt bulunamadı.']);
    exit;
}

$person = $json['resultSet'][0];

/* 🔹 3. EK_GOSTERGE → cardDepartment eşlemesi */
$ekGosterge = trim($person['EK_GOSTERGE'] ?? '');
$departmentId = null;
if ($ekGosterge !== '') {
    $res = $dba->query("SELECT id FROM carddepartment WHERE name LIKE '%$ekGosterge%' LIMIT 1");
    if ($res && $res->num_rows > 0) {
        $departmentId = $res->fetch_assoc()['id'];
    }
}

/* 🔹 4. Servis verisine göre mevcut kullanıcı kontrolü */
$adi = addslashes($person['ADI']);
$soyadi = addslashes($person['SOYADI']);
$sicilNo = addslashes($person['SICIL_NO']);
$tckn = addslashes($person['TCKN']);

$checkExisting = $dba->query("
    SELECT id, cardNumber 
    FROM carduser 
    WHERE (sicilNo = '$sicilNo' OR (adi = '$adi' AND soyadi = '$soyadi'))
    LIMIT 1
");

$existingCardNumber = null;
$newUserId = null;

if ($checkExisting && $checkExisting->num_rows > 0) {
    // Aynı kişi zaten var, sadece cardNumber bilgisini döndür
    $row = $checkExisting->fetch_assoc();
    $existingCardNumber = $row['cardNumber'];
    $newUserId = $row['id'];
}
else if ($cardNumber) {
    // Yeni kişi oluştur
    $dept = $departmentId ? (int)$departmentId : 'NULL';
    $dba->query("
        INSERT INTO carduser (cardNumber, TCKN, adi, soyadi, sicilNo, cardDepartment)
        VALUES ('$cardNumber', '$tckn', '$adi', '$soyadi', '$sicilNo', $dept)
    ");
    $newUserId = $dba->insert_id;
}

/* 🔹 5. JSON yanıt */
echo json_encode([
    'status' => 'ok',
    'message' => 'Servisten veri alındı.',
    'adi' => $adi,
    'soyadi' => $soyadi,
    'sicilNo' => $sicilNo,
    'cardDepartment' => $departmentId ?? '',
    'cardNumber' => $existingCardNumber ?? $cardNumber,
    'newUserId' => $newUserId,
    'departmentName' => $person['GOREV_YAPTIGI_MUDURLUK'] ?? $person['GOREV_YAPTIGI_BIRIM'] ?? '',
    'birimText' => ($person['GOREV_YAPTIGI_MUDURLUK'] ?? '') . ' / ' . ($person['GOREV_YAPTIGI_BIRIM'] ?? '')
]);