<?php
require_once("../class/mysql.php");
header('Content-Type: application/json; charset=utf-8');

// 🔹 POST verilerini al
$cardNumber     = addslashes(trim($_POST['cardNumber'] ?? ''));
$TCKN           = addslashes(trim($_POST['TCKN'] ?? ''));
$adi            = addslashes(trim($_POST['adi'] ?? ''));
$soyadi         = addslashes(trim($_POST['soyadi'] ?? ''));
$sicilNo        = addslashes(trim($_POST['sicilNo'] ?? ''));
$cardDepartment = (int)($_POST['cardDepartment'] ?? 0);

// 🔹 Zorunlu alan kontrolü
if (empty($TCKN) || empty($adi) || empty($soyadi)) {
    echo json_encode(['status' => 'error', 'message' => 'Zorunlu alanlar boş olamaz.']);
    exit;
}

// 🔹 1. Bu kart zaten var mı?
$existingCard = $dba->query("SELECT * FROM carduser WHERE cardNumber = '$cardNumber' LIMIT 1");

// 🔹 2. Bu kişi zaten kayıtlı mı? (TCKN veya sicilNo üzerinden)
$existingPerson = $dba->query("
    SELECT * FROM carduser 
    WHERE (TCKN = '$TCKN' OR (sicilNo <> '' AND sicilNo = '$sicilNo'))
    LIMIT 1
");

// 🔹 3. Kart var → Güncelleme
if ($existingCard && $existingCard->num_rows > 0) {
    $row = $existingCard->fetch_assoc();
    $id = (int)$row['id'];

    $sql = "UPDATE carduser 
            SET 
                TCKN = '$TCKN',
                adi = '$adi',
                soyadi = '$soyadi',
                sicilNo = '$sicilNo',
                cardDepartment = '$cardDepartment',
                updateTarihi = NOW()
            WHERE id = $id";
    if ($dba->query($sql)) {
        echo json_encode(['status' => 'updated', 'message' => 'Kart bilgileri güncellendi.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Kart güncellenirken hata oluştu.']);
    }
    exit;
}

// 🔹 4. Kart yok ama kişi var → kart numarasını o kişiye bağla
if ($existingPerson && $existingPerson->num_rows > 0) {
    $row = $existingPerson->fetch_assoc();
    $id = (int)$row['id'];

    $sql = "UPDATE carduser 
            SET 
                cardNumber = '$cardNumber',
                cardDepartment = '$cardDepartment',
                updateTarihi = NOW()
            WHERE id = $id";
    if ($dba->query($sql)) {
        echo json_encode(['status' => 'updated', 'message' => 'Kullanıcı kart bilgisi güncellendi.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Kart eşleştirilirken hata oluştu.']);
    }
    exit;
}

// 🔹 5. Ne kart ne kişi var → yeni kayıt oluştur
$sql = "INSERT INTO carduser (cardNumber, TCKN, adi, soyadi, sicilNo, cardDepartment, kayitTarihi)
        VALUES ('$cardNumber', '$TCKN', '$adi', '$soyadi', '$sicilNo', '$cardDepartment', NOW())";

if ($dba->query($sql)) {
    echo json_encode(['status' => 'success', 'message' => 'Yeni kullanıcı başarıyla eklendi.']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Kayıt ekleme sırasında hata oluştu.']);
}
?>