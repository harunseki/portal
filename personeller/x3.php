<?php
$dataRows = [];
$start = $_POST["start"] ?? "";
$kaynakTuru = $_POST["kaynakTuru"] ?? "";
$selectedMudurluk = $_POST['mudurluk'] ?? '';

if ($_SERVER["REQUEST_METHOD"] === "POST" && !empty($start)) {

    $sirketVerileri = [];
    $memurVerileri  = [];

    // --------------------- ŞİRKET SERVİSİ ---------------------
    if ($kaynakTuru === "" || $kaynakTuru === "sirket") {

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
            CURLOPT_SSL_VERIFYPEER => false,
        ]);

        $tokenResponse = curl_exec($ch);
        curl_close($ch);
        $tokenJson = json_decode($tokenResponse, true);

        if ($tokenJson && isset($tokenJson["bearerToken"])) {
            $bearer = $tokenJson["bearerToken"];

            $url = "https://cnkerp.cankaya.bel.tr/PersonelYillikIzin/IzinleriveRaporlarıGetir?" . http_build_query([
                    "organizasyonId" => 846,
                    "sorguBaslangicTarih" => $start
                ]);

            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => ["Authorization: Bearer $bearer"],
                CURLOPT_SSL_VERIFYPEER => false,
            ]);

            $apiResponse = curl_exec($ch);
            curl_close($ch);
            $json = json_decode($apiResponse, true);

            if (is_array($json)) {
                $sirketVerileri = array_map(fn($row) => $row + ["kaynak" => "Şirket"], $json);

                /* ✅ TEMİZLEME İŞLEMİ BURAYA EKLENDİ */
                foreach ($sirketVerileri as &$row) {
                    if (!empty($row['gorevlendirildigiMudurluk'])) {
                        $row['gorevlendirildigiMudurluk'] = preg_replace(
                            '/^DOĞRUDAN TEMİN\s+/iu',
                            '',
                            $row['gorevlendirildigiMudurluk']
                        );
                    }
                }
                unset($row);
                /* ✅ TEMİZLEME SONU */
            }
        }
    }


    // --------------------- MEMUR SERVİSİ ---------------------
    if ($kaynakTuru === "" || $kaynakTuru === "memur") {

        $url = "https://ybscanli.cankaya.bel.tr/FlexCityUi/rest/json/sis/RestDataset";
        $headers = [
            "Authorization: applicationkey=PRONETA,requestdate=2025-05-02T15:02:47+03:00,md5hashcode=9ead7fa09a505ba912689a4deffb55ab",
            "Content-Type: application/json",
        ];

        $startTr = global_date_to_tr($start);
        $postData = "datasetName=KULLANILAN_IZINLER&parameters={TARIH :'{$startTr}'}";

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $postData,
        ]);
        $response = curl_exec($ch);
        curl_close($ch);

        if ($response) {
            $jsonYeni = json_decode($response, true);
            if (isset($jsonYeni["success"]) && $jsonYeni["success"] === true && is_array($jsonYeni["resultSet"])) {
                $memurVerileri = array_map(function($row){
                    return [
                        "tcKimlikNo" => $row["TCKN"] ?? null,
                        "personelAd" => $row["ADI"] ?? '-',
                        "personelSoyad" => $row["SOYADI"] ?? '-',
                        "izinTuru" => $row["IZIN_TURU"] ?? '-',
                        "baslangicTarih" => $row["BASLANGIC_TARIHI"] ?? '-',
                        "bitisTarih" => $row["BITIS_TARIHI"] ?? '-',
                        "gorevlendirildigiMudurluk" => buyuk_harfe_cevir($row["GOREV_YAPTIGI_MUDURLUK"]) ?? null,
                        "kaynak" => "Memur"
                    ];
                }, $jsonYeni["resultSet"]);
            }
        }
    }

    // --------------------- Verileri Birleştir ---------------------
    $dataRows = array_merge($sirketVerileri, $memurVerileri);

    // --------------------- TC Bazlı Tekilleştirme ve Gün Toplama ---------------------
    $tmp = [];
    foreach ($dataRows as $r) {
        $tc = $r['tcKimlikNo'] ?? null;
        if (!$tc) continue;

        $days = gunFarki($r['baslangicTarih'], $r['bitisTarih'], true);
        $cat  = categorizeIzinTuru($r['izinTuru']);

        if (!isset($tmp[$tc])) {
            $tmp[$tc] = [
                'tcKimlikNo' => $tc,
                'personelAd' => $r['personelAd'],
                'personelSoyad' => $r['personelSoyad'],
                'gorevlendirildigiMudurluk' => $r['gorevlendirildigiMudurluk'],
                'yillik' => 0,
                'rapor' => 0,
                'diger' => 0,
                'kaynak' => $r['kaynak']
            ];
        }

        if ($cat === 'raporlu')      $tmp[$tc]['rapor']  += $days;
        elseif ($cat === 'yillik')   $tmp[$tc]['yillik'] += $days;
        else                         $tmp[$tc]['diger']  += $days;
    }
    $dataRows = array_values($tmp);

    // --------------------- Müdürlük Bazlı Stats ---------------------
    $stats = [];
    $mudurlukStmt = $dba->query("SELECT mudurluk FROM mudurlukler ORDER BY mudurluk ASC");
    $mudurlukMap = [];
    while ($m = $mudurlukStmt->fetch_assoc()) {
        $norm = mudurlukNormalize($m['mudurluk']);
        $mudurlukMap[$norm] = $m['mudurluk'];
    }

    foreach ($dataRows as $r) {
        $mud = $r['gorevlendirildigiMudurluk'] ?? null;
        $normMud = mudurlukNormalize($mud);
        if (!isset($mudurlukMap[$normMud])) continue;
        $finalMud = $mudurlukMap[$normMud];

        $tc = $r['tcKimlikNo'];
        if (!isset($stats[$finalMud][$tc])) {
            $stats[$finalMud][$tc] = [
                'ad' => $r['personelAd'],
                'soyad' => $r['personelSoyad'],
                'yillik' => $r['yillik'],
                'rapor' => $r['rapor'],
                'diger' => $r['diger']
            ];
        }
    }

    $dba->addLog($ip, $ldap_username, $personelTC, "create", "Müdürlük Bazlı İzin Sorgulama yapıldı: ".$start." tarihi ".($kaynakTuru ? ' - '.$kaynakTuru: ' - Tüm personel ' ).($_POST['mudurluk'] ?  " - ".$_POST['mudurluk'] : ' - Tüm Müdürlükler' ));
}
?>
<section class="content-header"><h2>Müdürlük Bazlı İzin-Rapor Sorgulama</h2></section>
<section class="content">
    <div class="box-body" style="margin-top: 10px">
        <form method="POST" style="margin-bottom:15px;">
            <div class="row">
                <div class="col-md-3">
                    <label>Başlangıç Tarihi</label>
                    <input type="date" name="start" class="form-control" required value="<?=htmlspecialchars($start)?>">
                </div>

                <div class="col-md-3">
                    <label>Müdürlük</label>
                    <select name="mudurluk" class="form-control">
                        <option value="" <?= $selectedMudurluk === '' ? 'selected' : '' ?>>Tümü</option>
                        <?php
                        $result = $dba->query("SELECT mudurluk FROM mudurlukler ORDER BY mudurluk ASC");
                        if (!$result) {
                            echo "<option value=''>MÜDÜRLÜK SORGUSU HATALI!</option>";
                        } else {
                            while ($m = $result->fetch_assoc()) {
                                $mudurluk = $m['mudurluk'];
                                $safe = htmlspecialchars($mudurluk, ENT_QUOTES, 'UTF-8');

                                // Hem görünen metin hem value aynı olsun
                                $selected = ($selectedMudurluk === $mudurluk) ? 'selected' : '';

                                echo "<option value=\"$safe\" $selected>$safe</option>";
                            }
                        }
                        ?>
                    </select>
                </div>

                <div class="col-md-3"><label>Personel Türü</label>
                    <select name="kaynakTuru" class="form-control">
                        <option value="">Tümü</option>
                        <option value="sirket">Şirket</option>
                        <option value="memur">Memur</option>
                    </select>
                </div>

                <div class="col-md-3">
                    <label>&nbsp;</label>
                    <button class="btn btn-success btn-block">Sorgula</button>
                </div>
            </div>
        </form>
        <?php if (!empty($stats)):
            ksort($stats, SORT_NATURAL | SORT_FLAG_CASE); ?>
            <div class="box">
                <div class="box-header">
                    <h3 class="box-title"><strong>Müdürlük Toplam Özet</strong></h3>
                    <button id="exportExcel2" class="btn btn-success pull-right">Excel Olarak İndir</button>
                </div>
                <div class="box-body table-responsive">
                    <table id="example2" class="table table-bordered table-striped">
                        <thead><tr><th>Müdürlük</th><th>Personel Sayısı</th><th>Σ Yıllık</th><th>Σ Rapor</th><th>Σ Diğer</th><th>Genel Σ</th></tr></thead>
                        <tbody>
                        <?php foreach ($stats as $mud => $p):
                            if ($selectedMudurluk && $mud !== $selectedMudurluk) continue;
                            $count = count($p);
                            $sumY = array_sum(array_column($p,'yillik'));
                            $sumR = array_sum(array_column($p,'rapor'));
                            $sumD = array_sum(array_column($p,'diger'));
                            $gen  = $sumY+$sumR+$sumD;
                            ?>
                            <tr>
                                <td><?=$mud?></td>
                                <td><?=$count?></td>
                                <td><?=$sumY?></td>
                                <td><?=$sumR?></td>
                                <td><?=$sumD?></td>
                                <td style="font-weight: bold"><?=$gen?></td>
                            </tr>
                        <?php endforeach; ?>
                        <tbody>
                    </table>
                </div>
            </div>
            <div class="box">
                <div class="box-header">
                    <h3 class="box-title"><strong>Personel Bazlı İçerik Özet</strong></h3>
                    <button id="exportExcel1" class="btn btn-success pull-right">Excel Olarak İndir</button>
                </div>
                <div class="box-body table-success">
                    <table id="example1" class="table table-bordered table-striped">
                        <thead>
                        <tr>
                            <th>Müdürlük</th>
                            <th>TC</th>
                            <th>Ad Soyad</th>
                            <th>Yıllık</th>
                            <th>Rapor</th>
                            <th>Diğer</th>
                            <th>Toplam</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($stats as $mud => $p):
                            if ($selectedMudurluk && $mud !== $selectedMudurluk) continue;
                            foreach ($p as $tc => $s):
                                $top = $s['yillik'] + $s['rapor'] + $s['diger'];
                                ?>
                                <tr>
                                    <td><?=htmlspecialchars($mud)?></td>
                                    <td><?=htmlspecialchars($tc)?></td>
                                    <td><?=htmlspecialchars($s['ad'] . " " . $s['soyad'])?></td>
                                    <td><?=$s['yillik']?></td>
                                    <td><?=$s['rapor']?></td>
                                    <td><?=$s['diger']?></td>
                                    <td style="font-weight: bold"><?=$top?></td>
                                </tr>
                            <?php endforeach; endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>
        <script>
            document.getElementById('exportExcel2')?.addEventListener('click', function() {
                let table = document.getElementById('example2');
                if (!table) return;
                let rows = Array.from(table.querySelectorAll('tr'));
                let csv = rows.map(r => Array.from(r.querySelectorAll('th,td')).map(cell => `"${cell.innerText.replace(/"/g, '""')}"`).join(",")).join("\n");
                let blob = new Blob(["\ufeff" + csv], { type: 'text/csv;charset=utf-8;' });
                let url = URL.createObjectURL(blob);
                let a = document.createElement('a');
                a.href = url;
                a.download = 'mudurluk_ozet.csv';
                a.click();
                URL.revokeObjectURL(url);
            });
            document.getElementById('exportExcel1')?.addEventListener('click', function() {
                let table = document.getElementById('example1');
                if (!table) return;
                let rows = Array.from(table.querySelectorAll('tr'));
                let csv = rows.map(r => Array.from(r.querySelectorAll('th,td')).map(cell => `"${cell.innerText.replace(/"/g, '""')}"`).join(",")).join("\n");
                let blob = new Blob(["\ufeff" + csv], { type: 'text/csv;charset=utf-8;' });
                let url = URL.createObjectURL(blob);
                let a = document.createElement('a');
                a.href = url;
                a.download = 'mudurluk_ozet.csv';
                a.click();
                URL.revokeObjectURL(url);
            });
        </script>
    </div>
</section>