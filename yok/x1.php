<?php
if (empty($_SESSION['yok']) && empty($_SESSION['admin'])) {
    http_response_code(403);
    ?>
    <style>
        .error-box {
            background: #fff;
            padding: 40px;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
            text-align: center;
        }
        .error-box h1 { font-size: 48px; color:#e74c3c; }
        .error-box a {
            padding:10px 20px;
            background:rgba(6,90,40);
            color:#fff;
            border-radius:5px;
            text-decoration:none;
        }
    </style>
    <div class="error-box">
        <h1>403</h1>
        <p>Bu sayfaya erişim yetkiniz yok.</p>
        <a href="index.php">Ana Sayfaya Dön</a>
    </div>
    <?php
    exit;
}

// API sonuç alanları
$responseList = [];
$error = null;

// CACHE klasörünü kontrol et yoksa oluştur
$cacheDir = __DIR__ . "/cache";
if (!is_dir($cacheDir)) {
    mkdir($cacheDir, 0777, true);
}

$cacheTime = 600; // 10 dakika

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if ($_POST["tc"] != '10586023836') {
        $tc = trim($_POST["tc"]);
        $endpoints = $_POST["endpoint"] ?? [];

        if (!is_numeric($tc) || strlen($tc) < 8) {
            $error = "Lütfen geçerli bir TC Kimlik No girin.";
        } elseif (empty($endpoints)) {
            $error = "En az 1 sorgu türü seçmelisiniz.";
        } else {
            foreach ($endpoints as $endpoint) {

                /*$cacheFile = "$cacheDir/{$endpoint}_{$tc}.json";

                // --- Cache varsa ve süresi dolmamışsa dosyadan oku ---
                if (file_exists($cacheFile) && (time() - filemtime($cacheFile)) < $cacheTime) {
                    $responseList[$endpoint] = file_get_contents($cacheFile);
                    $dba->addLog($ip, $ldap_username, $tc, "cache", "Cache'den okundu: " . $endpoint);
                    continue;
                }*/

                // --- Cache yok veya süresi dolmuş → API çağır ---
                if ($endpoint === "egitim") {
                    $token = "Zz91fE3Ixr9vAQL";
                    $url   = "https://yazilim.ankara.bel.tr/api/EgoOgrenci/sorgula";
                    $body  = json_encode(["tckn" => $tc]);
                } elseif ($endpoint === "mezun") {
                    $token = "R1Tip0v804N4";
                    $url   = "https://yazilim.ankara.bel.tr/api/YOK/EgitimBilgisiMezun";
                    $body  = json_encode(["TCKimlikNo" => $tc]);
                } elseif ($endpoint === "denklik") {
                    $token = "R1Tip0v804N4";
                    $url   = "https://yazilim.ankara.bel.tr/api/YOK/DenklikBilgisiMezun";
                    $body  = json_encode(["TCKimlikNo" => $tc]);
                } else {
                    continue;
                }

                $ch = curl_init($url);
                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                        "Content-Type: application/json",
                        "token: $token"
                ]);
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

                $apiResponse = curl_exec($ch);
                curl_close($ch);

                // API cevabını kaydet
                $responseList[$endpoint] = $apiResponse;

                // --- API sonucu cache'e yaz ---
                /*file_put_contents($cacheFile, $apiResponse);*/
                $dba->addLog($ip, $ldap_username, $tc, "api", "API'den alındı ve cache'e yazıldı: " . $endpoint);
            }
        }
    }
    else {
        alert_danger("Bu bilgilerle sorgulama yapılamaz..!");
    }
}
?>
<link href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.css" rel="stylesheet">
<section class="content-header">
    <h2>YÖK Bilgi Sorgulama</h2>
</section>
<section class="content">
    <div class="box box-success" style="margin-top: 10px">
        <div class="box-body">
            <form method="POST">
                <div class="form-group">
                    <label>TC Kimlik No:</label>
                    <input type="text" name="tc" class="form-control" maxlength="11" required placeholder="TC Kimlik Numarası">
                </div>
                <div class="form-group">
                    <label>Sorgu Türleri:</label>
                    <select name="endpoint[]" id="endpoint" multiple>
                        <option value="egitim" selected>🎓 Aktif Öğrenci</option>
                        <?php if ($admin): ?>
                            <option value="mezun" selected>📜 Mezuniyet</option>
                            <option value="denklik" selected>✅ Denklik</option>
                        <?php endif; ?>
                    </select>
                </div>
                <button type="submit" class="btn btn-success btn-block">
                    <i class="fa fa-search"></i> Seçilenleri Sorgula
                </button>
            </form>
            <?php if ($error): ?>
                <div class="alert alert-danger" style="margin-top:15px;"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            <div class="row" style="margin-top:20px;">
                <?php foreach ($responseList as $type => $resp):

                    $json = json_decode($resp, true);
                    $title = "";
                    $icon  = "";

                    if ($type === "egitim") {
                        $title = "Aktif Öğrenci Bilgisi";
                        $icon  = "🎓";
                        $ogr = $json["aktifOgrenimler"] ?? [];
                    }
                    elseif ($type === "mezun") {
                        $title = "Mezuniyet Bilgisi";
                        $icon  = "📜";
                        $ogr = $json["egitimBilgisiMezunKayit"] ?? [];
                    }
                    elseif ($type === "denklik") {
                        $title = "Denklik Bilgisi";
                        $icon  = "✅";
                        $ogr = $json["denklikBilgisiMezunKayit"] ?? [];
                    }
                    ?>
                    <div class="col-md-4">
                        <div class="card box-success" style="border:1px solid #28a745; border-radius:12px; min-height:220px; padding:15px;">
                            <h3><?=$icon." ".$title?></h3>
                            <hr>
                            <?php if (!empty($ogr) && is_array($ogr)): ?>
                                <?php foreach ($ogr as $o): ?>
                                    <?php if ($type === "egitim"): ?>
                                        <div class="alert alert-success">
                                            <b><?= htmlspecialchars($o["universiteAdi"]) ?></b><br>
                                            Öğrenim Süresi: <?= intval($o["ogrenimSuresi"]) ?> yıl<br>
                                            Tür: <?= htmlspecialchars($o["ogrenimTuru"]) ?><br>
                                        </div>
                                        <hr>
                                    <?php elseif ($type === "mezun"):
                                        $k = $o["kisiselBilgi"] ?? null;
                                        $m = $o["mezunBilgi"] ?? null;
                                        $b = $o["birimBilgi"] ?? null;
                                        ?>
                                        <div class="alert alert-success">
                                            <b><?= htmlspecialchars($b["birimAdi"]) ?></b><br>
                                            Ad Soyad: <?= htmlspecialchars($k["adi"]." ".$k["soyadi"]) ?><br>
                                            Diploma No: <?= htmlspecialchars($m["diplomaNo"]) ?><br>
                                            GANO: <?= htmlspecialchars($m["gano"]["not"] ?? "-") ?><br>
                                        </div>
                                        <hr>
                                    <?php else: // denklik ?>
                                        <div class="alert alert-success">
                                            <b><?= htmlspecialchars($o["universiteAdi"] ?? "Denklik Kaydı") ?></b><br>
                                            Tür: <?= htmlspecialchars($o["ogrenimTuru"] ?? "-") ?><br>
                                        </div>
                                        <hr>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="alert alert-warning">Kayıt bulunamadı...</div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</section>
<script src="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js"></script>
<script>
    new TomSelect("#endpoint",{
        plugins:['remove_button','checkbox_options'],
        maxItems:10,
    });
</script>