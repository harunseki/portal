<?php
$dataRows = [];
$start = $_POST["start"] ?? "";
$kaynakTuru = $_POST["kaynakTuru"] ?? ""; // Personel Türü filtresi

if ($_SERVER["REQUEST_METHOD"] === "POST" && !empty($start)) {

    //$yil = date("Y", strtotime($start));
    $sirketVerileri = [];
    $memurVerileri = [];

    // ---------------------------------------------------------------------
    // 1️⃣ ŞİRKET PERSONELİ (MEVCUT SERVİS) İÇİN ÇAĞRI
    // Sadece 'sirket' veya 'tumu' seçiliyse çalışır
    // ---------------------------------------------------------------------
    if ($kaynakTuru === "" || $kaynakTuru === "sirket") {

        // 1.a Token Al (Mevcut Servis) - KODUNUZDAN ALINAN BİLGİLER
        $tokenUrl = "https://cnkerp.cankaya.bel.tr/User/AccessKey";
        $tokenPayload = json_encode([
            "userEmail" => "erp@cankaya.bel.tr",
            "password"  => "ErpCankayaBelTr653.."
        ]);

        $ch = curl_init($tokenUrl);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => ["Content-Type: application/json"],
            CURLOPT_POSTFIELDS => $tokenPayload,
            CURLOPT_SSL_VERIFYPEER => false, // Gerekirse SSL doğrulamasını kapatın
        ]);

        $tokenResponse = curl_exec($ch);
        $tokenError = curl_error($ch);
        curl_close($ch);
        $tokenJson = json_decode($tokenResponse, true);

        if ($tokenJson && isset($tokenJson["bearerToken"])) {
            $bearer = $tokenJson["bearerToken"];

            // 1.b İzinleri Çek (Mevcut Servis)
            $url = "https://cnkerp.cankaya.bel.tr/PersonelYillikIzin/IzinleriveRaporlarıGetir?" . http_build_query([
                    "organizasyonId" => 846,
                    "sorguBaslangicTarih" => $start // Başlangıç Tarihi parametresini kullanıyoruz
                ]);

            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => ["Authorization: Bearer $bearer"],
                CURLOPT_SSL_VERIFYPEER => false, // Gerekirse SSL doğrulamasını kapatın
            ]);

            $apiResponse = curl_exec($ch);
            curl_close($ch);
            $json = json_decode($apiResponse, true);

            if (is_array($json)) {
                $sirketVerileri = array_map(function($row) {
                    $row["kaynak"] = "Şirket"; // Kaynak bilgisini ekle
                    return $row;
                }, $json);
            } else {
                echo "<div class='alert alert-warning'>Şirket Personeli API dönüşü okunamadı veya boş döndü.</div>";
            }
        } else {
            echo "<div class='alert alert-danger'>Şirket Personeli için Token alınamadı! Hata: " . htmlspecialchars($tokenError) . "</div>";
        }
    }

    // ---------------------------------------------------------------------
    // 2️⃣ MEMUR PERSONELİ (YENİ SERVİS - Postman Dosyasından) İÇİN ÇAĞRI
    // Sadece 'memur' veya 'tumu' seçiliyse çalışır
    // ---------------------------------------------------------------------
    if ($kaynakTuru === "" || $kaynakTuru === "memur") {

        $url = "https://ybscanli.cankaya.bel.tr/FlexCityUi/rest/json/sis/RestDataset";
        $headers = [
            "Authorization: applicationkey=PRONETA,requestdate=2025-05-02T15:02:47+03:00,md5hashcode=9ead7fa09a505ba912689a4deffb55ab",
            "Content-Type: application/json",
        ];
        $start=global_date_to_tr($start);
        $postData = "datasetName=KULLANILAN_IZINLER&parameters={TARIH :'{$start}'}";

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $postData,
        ]);
        $response = curl_exec($ch);
        $apiErrorYeni = curl_error($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200 || !$response) {
            echo json_encode(['status' => 'error', 'message' => "Servis HTTP $httpCode hatası"]);
            exit;
        }
        $jsonYeni = json_decode($response, true);

        // Yeni JSON yapısı: {"success": true, "resultSet": [...]}
        if (isset($jsonYeni["success"]) && $jsonYeni["success"] === true && is_array($jsonYeni["resultSet"])) {

            // Veri Eşleştirme (Mapping)
            $memurVerileri = array_map(function($yeniRow) {

                // TC - TCKN
                $tc = $yeniRow["TCKN"] ?? '-';
                // Ad Soyad
                $ad = $yeniRow["ADI"] ?? '-';
                $soyad = $yeniRow["SOYADI"] ?? '-';
                // İzin Türü - IZIN_TURU
                $izinTuru = $yeniRow["IZIN_TURU"] ?? ($yeniRow["IZIN_TURU_ENUM"] ?? '-');
                // Başlangıç - BASLANGIC_TARIHI (DD.MM.YYYY formatında)
                $baslangic = $yeniRow["BASLANGIC_TARIHI"] ?? '-';
                // Bitiş - BITIS_TARIHI (DD.MM.YYYY formatında)
                $bitis = $yeniRow["BITIS_TARIHI"] ?? '-';

                // Ortak Çıktı Yapısı: Mevcut servisin çıktısı ile uyumlu hale getiriyoruz
                return [
                    "tcKimlikNo"      => $tc,
                    "personelAd"      => $ad,
                    "personelSoyad"   => $soyad,
                    "izinTuru"        => $izinTuru,
                    "baslangicTarih"  => $baslangic,
                    "bitisTarih"      => $bitis,
                    "kaynak"          => "Memur"
                ];
            }, $jsonYeni["resultSet"]);

        } else {
            echo "<div class='alert alert-danger'>Memur Personeli API'sinden veri alınamadı. Hata: " . htmlspecialchars($apiErrorYeni ?: ($jsonYeni["mesaj"] ?? 'Bilinmeyen Hata')) . "</div>";
        }
    }

    // 3️⃣ TÜM VERİLERİ BİRLEŞTİR VE FİLTRELE
    $dataRows = array_merge($sirketVerileri, $memurVerileri);

    // İzin Türü Filtresini Uygula
    $filterIzin = $_POST["izinTuru"] ?? "";

    if ($filterIzin !== "") {
        // categorizeIzinTuru fonksiyonunun tanımlı olduğunu varsayıyorum.
        $dataRows = array_filter($dataRows, function($row) use ($filterIzin) {
            // Fonksiyon varsa kullan, yoksa ham izin türü alanını kullan
            $izinAdi = $row["izinTuru"] ?? "";
            $kategori = function_exists('categorizeIzinTuru') ? categorizeIzinTuru($izinAdi) : $izinAdi;
            return $kategori === $filterIzin;
        });
    }
    $dba->addLog($ip, $ldap_username, $personelTC, "create", "İzin Bilgi Sorgulama yapıldı: ".$start." tarihi ".($kaynakTuru ? ' - '.$kaynakTuru: ' - Tüm personel ' ).($filterIzin ?  " - ".$filterIzin : ' - Tüm izin türleri' ));
}
$start=global_date_to_tr($_POST["start"]);
?>
<section class="content-header">
    <h2>İzin Bilgi Sorgulama</h2>
</section>
<section class="content">
    <div class="box-body">
        <div class="row" style="margin-top:10px;">
            <div class="col-md-12">
                <div class="box box-success" style="margin-bottom: 0">
                    <div class="box-body">
                        <form method="POST" style="margin-bottom:20px;">
                            <div class="row">
                                <div class="col-md-3">
                                    <label>Başlangıç Tarihi</label>
                                    <input type="date" name="start" class="form-control" required value="<?= htmlspecialchars(tr_to_global_date($start)) ?>">
                                </div>

                                <div class="col-md-3">
                                    <label>Personel Türü</label>
                                    <select name="kaynakTuru" class="form-control">
                                        <option value="" <?= ($kaynakTuru == '' ? 'selected' : '') ?>>Tümü (Şirket + Memur)</option>
                                        <option value="sirket" <?= ($kaynakTuru == 'sirket' ? 'selected' : '') ?>>Şirket</option>
                                        <option value="memur" <?= ($kaynakTuru == 'memur' ? 'selected' : '') ?>>Memur</option>
                                    </select>
                                </div>

                                <div class="col-md-3">
                                    <label>İzin Türü</label>
                                    <select name="izinTuru" class="form-control">
                                        <option value="">Tümü</option>
                                        <option value="raporlu" <?= (($_POST['izinTuru'] ?? '') == 'raporlu' ? 'selected' : '') ?>>Raporlu</option>
                                        <option value="yillik" <?= (($_POST['izinTuru'] ?? '') == 'yillik' ? 'selected' : '') ?>>Yıllık İzin</option>
                                        <option value="diger" <?= (($_POST['izinTuru'] ?? '') == 'diger' ? 'selected' : '') ?>>Diğer</option>
                                    </select>
                                </div>

                                <div class="col-md-3">
                                    <label>&nbsp;</label>
                                    <button class="btn btn-success btn-block">Sorgula</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <?php if (!empty($dataRows)): ?>
            <div class="row" style="margin-top:15px;">
                <div class="col-md-12">
                    <div class="box box-success">
                        <div class="box-header" style="margin-top: 5px">
                            <h3 class="box-title"><?= $start . " tarihinden itibaren - " ?> İzin ve Rapor Verileri (<?= count($dataRows) ?> Kayıt)</h3>
                            <button id="exportExcel" class="btn btn-success pull-right">Excel Olarak İndir</button>
                        </div>
                        <div class="box-body table-responsive">
                            <table id="pdksTable" class="table table-bordered table-striped">
                                <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Kaynak</th> <th>TC</th>
                                    <th>Ad Soyad</th>
                                    <th>İzin Türü</th>
                                    <th>Başlangıç</th>
                                    <th>Bitiş</th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php $i=1; foreach ($dataRows as $r): ?>
                                    <tr>
                                        <td><?= $i ?></td>
                                        <td><?= htmlspecialchars($r["kaynak"] ?? 'Bilinmiyor') ?></td> <td><?= htmlspecialchars($r["tcKimlikNo"] ?? '-') ?></td>
                                        <td><?= htmlspecialchars($r["personelAd"] ?? '-')." ".htmlspecialchars($r["personelSoyad"] ?? '-') ?></td>
                                        <td><?= htmlspecialchars($r["izinTuru"] ?? '-') ?></td>
                                        <td><?= formatDate($r["baslangicTarih"] ?? null) ?></td>
                                        <td><?= formatDate($r["bitisTarih"] ?? null) ?></td>
                                    </tr>
                                    <?php $i++; endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <?php if ($_SERVER["REQUEST_METHOD"] === "POST" && !empty($start)): ?>
                <div class="alert alert-info" style="margin-top: 15px;">Seçtiğiniz kritere uygun veri bulunamamıştır.</div>
            <?php endif; ?>
        <?php endif; ?>

        <script>
            document.getElementById('exportExcel').addEventListener('click', function() {
                let table = document.getElementById('pdksTable');
                let rows = Array.from(table.querySelectorAll('tr'));
                // Başlıklar ve satır verileri için virgülle ayrılmış CSV oluşturma
                let csv = rows.map(r => Array.from(r.querySelectorAll('th,td')).map(cell => `"${cell.innerText.replace(/"/g, '""')}"`).join(",")).join("\n");

                let blob = new Blob(["\ufeff" + csv], { type: 'text/csv;charset=utf-8;' }); // UTF-8 BOM ekledim
                let url = URL.createObjectURL(blob);

                let a = document.createElement('a');
                a.href = url;
                a.download = 'izin_raporlari.csv';
                a.click();
                URL.revokeObjectURL(url);
            });
        </script>

    </div>
</section>