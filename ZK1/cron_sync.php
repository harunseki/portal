<?php
// Gerekli dosyalar ve ayarlar
date_default_timezone_set('Europe/Istanbul');
include("zklib/zklib.php");
include("../class/mysql.php");

// --- 2. ZKTeco Cihazına Bağlan ---
$zk = new ZKLib("10.2.200.20", 4370);

if ($zk->connect()) {
    $zk->disableDevice();

    // Cihazdan logları çek (artık doğru çalışıyor)
    $attendance = $zk->getAttendance();

    if (!empty($attendance)) {

        // --- 3. Veritabanına Yazma ---

        // SQL Sorgusu: INSERT IGNORE sayesinde, 1. Adım'da oluşturduğumuz
        // UNIQUE anahtar sayesinde, mükerrer (zaten var olan) kayıtlar
        // hata vermez, sessizce atlanır.
        $stmt = $dba->prepare("INSERT IGNORE INTO att_logs (user_id, checktime, state) VALUES (?, ?, ?)");

        foreach ($attendance as $attItem) {
            $userid = $attItem[1];
            $time   = $attItem[3];
            $state  = $attItem[2]; // state bilgisini de alalım

            // Sadece geçerli kullanıcı ID'lerini ekle
            if ($userid > 0) {
                // Sorguyu çalıştır
                $stmt->bind_param("isi", $userid, $time, $state);
                $stmt->execute();
            }
        }

        $stmt->close();
        echo count($attendance) . " adet log kaydı işlendi.";

    } else {
        echo "Cihazda yeni log kaydı bulunamadı.";
    }

    $zk->enableDevice();
    $zk->disconnect();

} else {
    echo "Cihaza bağlanılamadı.";
}

$dba->close();
?>