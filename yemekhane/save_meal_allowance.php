<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
require_once("../class/mysql.php");

header('Content-Type: application/json; charset=utf-8');

$cardUserId     = $_POST['cardUserId'] ?? '';
$cardUserTCKN   = $_POST['cardUserTCKN'] ?? '';
$mealDays       = $_POST['mealDays'] ?? '';

if (!$cardUserId || !$cardUserTCKN || !$mealDays) {
    echo json_encode(['status' => 'error', 'message' => 'Eksik bilgi gönderildi.']);
    exit;
}

// Şu anki tarih bilgilerini al
$today = new DateTime();
$currentMonth = (int)$today->format('m');
$currentYear = (int)$today->format('Y');

// Aynı ayda zaten 30 günlük hakkı var mı kontrol et
$checkSql = "
    SELECT * FROM cardmealallowement 
    WHERE cardUserId = '$cardUserId'
    AND MONTH(startDate) = '$currentMonth'
    AND YEAR(startDate) = '$currentYear'
    AND DATEDIFF(finishDate, startDate) >= 29
    LIMIT 1
";
$check = $dba->query($checkSql);

if ($check && $check->num_rows > 0) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Bu kullanıcıya bu ay zaten 30 günlük yemek hakkı tanımlanmış.'
    ]);
    exit;
}

// Tarih aralıklarını hesapla
if ($mealDays == '30') {
    // 30 günlük için: bu ayın 15'inden diğer ayın 15'ine kadar
    $startDate = new DateTime("$currentYear-$currentMonth-15");

    // Eğer bugün 15'inden önceyse, bugünden başlat
    if ((int)$today->format('d') < 15) {
        $startDate = new DateTime("$currentYear-$currentMonth-15");
    }

    // Bitiş tarihi -> bir sonraki ayın 15'i
    $finishDate = clone $startDate;
    $finishDate->modify('+1 month');

} elseif ($mealDays == '15') {
    // 15 günlük için: gün durumuna göre otomatik belirle
    $day = (int)$today->format('d');
    $daysInMonth = (int)$today->format('t');

    if ($day < 15) {
        // 1-15 arası
        $startDate = new DateTime("$currentYear-$currentMonth-01");
        $finishDate = new DateTime("$currentYear-$currentMonth-15");
    } else {
        // 15 - ay sonu arası
        $startDate = new DateTime("$currentYear-$currentMonth-15");
        $finishDate = new DateTime("$currentYear-$currentMonth-$daysInMonth");
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Geçersiz süre seçimi.']);
    exit;
}

$start = $startDate->format('Y-m-d');
$finish = $finishDate->format('Y-m-d');

// Kayıt ekle
$insertSql = "
    INSERT INTO cardmealallowement (cardUserTCKN, cardUserId, startDate, finishDate)
    VALUES ('$cardUserTCKN', '$cardUserId', '$start', '$finish')
";

if ($dba->query($insertSql)) {
    echo json_encode([
        'status' => 'success',
        'message' => "Yemek hakkı eklendi: $start - $finish"
    ]);
} else {
    echo json_encode([
        'status' => 'error',
        'message' => 'Kayıt sırasında hata oluştu.'
    ]);
}
?>