<?php
ob_start();
if (session_status() === PHP_SESSION_NONE) session_start();

require_once "../class/mysql.php";
header("Content-Type: application/json");

/*
|---------------------------------------------------------------------------
| 1) İzinli iframe session parametreleri
|---------------------------------------------------------------------------
*/
$izinliSessionlar = []; // id => session_key
$result = $dba->query("SELECT id, session_key FROM session_parameters WHERE aktif = 1");
while ($row = $result->fetch_assoc()) {
    $izinliSessionlar[$row['id']] = $row['id'];
}

/*
|---------------------------------------------------------------------------
| 2) POST verileri
|---------------------------------------------------------------------------
*/
$id          = $_POST['id'] ?? '';
$kategori_id = $_POST['kategori_id'] ?? null;
$isim        = $_POST['isim'] ?? '';
$ikon        = $_POST['ikon'] ?? '';
$yetki       = $_POST['yetki'] ?? '';
$badge       = $_POST['badge'] ?? '';
$siralama    = $_POST['siralama'] ?? 0;
$aktif       = $_POST['aktif'] ?? 1;
$style       = $_POST['style'] ?? '';
$modul_tipi  = $_POST['modul_tipi'] ?? 'sayfa';
$hedef_url   = $_POST['hedef_url'] ?? null;

/*
|---------------------------------------------------------------------------
| 3) Müdürlük alanları
|---------------------------------------------------------------------------
*/
$ust_mudurluk_id = $_POST['ust_mudurluk_id'] ?? null;
$mudurlukler = $_POST['mudurluk_id'] ?? [];
$mudurluk_csv = !empty($mudurlukler) ? implode(',', array_map('intval', $mudurlukler)) : null;

/*
|---------------------------------------------------------------------------
| 4) Iframe parametreleri
|---------------------------------------------------------------------------
*/
$parametreler = null;
if ($modul_tipi === 'iframe') {
    $secimler = $_POST['iframe_parametreler'] ?? [];
    $filtreliSessionKeyler = [];

    foreach ($secimler as $secimId) {
        $secimId = (int)$secimId;
        if (isset($izinliSessionlar[$secimId])) $filtreliSessionKeyler[] = $izinliSessionlar[$secimId];
    }

    if (!empty($filtreliSessionKeyler)) $parametreler = implode(',', $filtreliSessionKeyler);
}

/*
|---------------------------------------------------------------------------
| 5) Fonksiyon: Insert / Update otomatik
|---------------------------------------------------------------------------
*/
function saveModul($dba, $data, $id = null) {
    $columns = array_keys($data);
    $values = array_values($data);

    if ($id) {
        // UPDATE
        $set = [];
        foreach ($columns as $col) $set[] = "$col = ?";
        $sql = "UPDATE mod_moduller SET ".implode(",", $set)." WHERE id = ?";
        $stmt = $dba->prepare($sql);
        $types = str_repeat('s', count($values)) . 'i';
        $stmt->bind_param($types, ...$values, $id);
        $stmt->execute();
        return $stmt;
    } else {
        // INSERT
        $placeholders = array_fill(0, count($columns), '?');
        $sql = "INSERT INTO mod_moduller (".implode(",", $columns).") VALUES (".implode(",", $placeholders).")";
        $stmt = $dba->prepare($sql);
        $types = str_repeat('s', count($values));
        $stmt->bind_param($types, ...$values);
        $stmt->execute();
        return $stmt;
    }
}

/*
|---------------------------------------------------------------------------
| 6) Veriyi hazırla ve kaydet
|---------------------------------------------------------------------------
*/
$data = [
    'kategori_id'     => $kategori_id,
    'isim'            => $isim,
    'ikon'            => $ikon,
    'yetki'           => $yetki,
    'badge'           => $badge,
    'siralama'        => $siralama,
    'aktif'           => $aktif,
    'modul_tipi'      => $modul_tipi,
    'hedef_url'       => $hedef_url,
    'parametreler'    => $parametreler,
    'style'           => $style,
    'ust_mudurluk_id' => $ust_mudurluk_id,
    'mudurluk_id'     => $mudurluk_csv,
    'update_yetkili'  => $_SESSION['kullanici_id']
];

$action = ($id) ? 'guncelle' : 'ekle';
$stmt = saveModul($dba, $data, $id);

/*
|---------------------------------------------------------------------------
| 7) JSON Response
|---------------------------------------------------------------------------
*/
if ($stmt->errno === 0) {
    echo json_encode([
        'ok' => true,
        'mesaj' => ($action === 'ekle') ? "Modül başarıyla eklendi" : "Modül başarıyla güncellendi"
    ]);
} else {
    echo json_encode([
        'ok' => false,
        'mesaj' => $stmt->error
    ]);
}
