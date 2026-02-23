<?php
$tc       = safePost('tc');
$durumu   = safePost('durumu');
$username = safePost('username');
$altturu  = safePost('altturu');
$statuTuru  = safePost('statuTuru');

// --- MÜDÜRLÜK ÇOKLU POST ALMA ---
$mudurluk = isset($_POST['mudurluk']) && is_array($_POST['mudurluk']) ? $_POST['mudurluk'] : [];

$dataRows = [];

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    // --- Çoklu müdürlük API formatı (1,5,7...) ---
    $mudurlukStr = "";
    if (!empty($mudurluk)) {
        $mudurlukStr = implode(",", array_map('intval', $mudurluk));
    }

    $postData = "datasetName=PRONETA_PERSONEL_YAZILIM&parameters="
        . "{TCKIMLIKNO:'" . addslashes($tc)
        . "',DURUMU:'" . addslashes($durumu)
        . "',USERNAME:'" . addslashes($username)
        . "',ALTTURU:'" . addslashes($altturu)
        . "',MUDURLUK:'" . addslashes($mudurlukStr)
        . "',STATU_TURU:'" . addslashes($statuTuru)
        . "'}";

    $url = "https://ybscanli.cankaya.bel.tr/FlexCityUi/rest/json/sis/RestDataset";
    $headers = [
        "Authorization: applicationkey=PRONETA,requestdate=2025-05-02T15:02:47+03:00,md5hashcode=9ead7fa09a505ba912689a4deffb55ab",
        "Content-Type: application/json",
    ];

    $ch3 = curl_init($url);
    curl_setopt_array($ch3, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $postData,
        CURLOPT_TIMEOUT => 30,
    ]);

    $response = curl_exec($ch3);
    $httpCode = curl_getinfo($ch3, CURLINFO_HTTP_CODE);
    $curlErr = curl_error($ch3);
    curl_close($ch3);

    if ($response && $httpCode === 200) {
        $json = json_decode($response, true);
        if ($json && !empty($json['resultSet']) && is_array($json['resultSet'])) {
            foreach ($json['resultSet'] as $m) {
                $tcVal = $m['TCKN'] ?? ($m['TC'] ?? '');
                $ad = $m['ADI'] ?? '';
                $soyad = $m['SOYADI'] ?? '';
                $gorev_yaptigi_mudurluk = $m['GOREV_YAPTIGI_MUDURLUK'] ?? '';
                $personel_statu_turu = $m['STATU_TURU'] ?? '';

                $dataRows[] = [
                    'tcKimlikNo' => $tcVal,
                    'personelAd' => $ad,
                    'personelSoyad' => $soyad,
                    'gorev_yaptigi_mudurluk' => $gorev_yaptigi_mudurluk,
                    'personel_statu_turu' => $personel_statu_turu
                ];
            }
        }
    }

    // Aynı TC’den sadece birini al
    $uniqueMap = [];
    foreach ($dataRows as $r) {
        $tc = $r['tcKimlikNo'];
        if ($tc === '') continue;
        if (!isset($uniqueMap[$tc])) {
            $uniqueMap[$tc] = $r;
        }
    }

    $uniqueRows = array_values($uniqueMap);

    // Ad soyad sıralama
    $collator = new Collator('tr_TR');
    usort($uniqueRows, function($a, $b) use ($collator) {
        return $collator->compare(
            $a['personelAd'] . ' ' . $a['personelSoyad'],
            $b['personelAd'] . ' ' . $b['personelSoyad']
        );
    });

    // --- Müdürlük isimlerini başlıkta göstermek için ---
    $selectedMudAdi = "Tüm Müdürlükler";
    if (!empty($mudurluk)) {
        $ids = implode(",", array_map('intval', $mudurluk));
        $m = $dba->query("SELECT mudurluk FROM mudurlukler WHERE flexy_id IN ($ids)");
        $adlar = [];
        while ($row = $m->fetch_assoc()) {
            $adlar[] = $row['mudurluk'];
        }
        if (!empty($adlar)) {
            $selectedMudAdi = implode(", ", $adlar);
        }
    }

    $dba->addLog($ip, $ldap_username, $personelTC, "create",
        "Personel Sorgulama yapıldı: " . $selectedMudAdi
    );
}
?>
<section class="content-header">
    <h2>Tüm Personel Giriş-Çıkış Sorgulama</h2>
</section>
<section class="content">
    <div class="box-body">
        <div class="row" style="margin-top:10px;">
            <div class="col-md-12">
                <div class="box box-success">
                    <div class="box-body">
                        <form method="POST">
                            <div class="row">
                                <div class="col-md-2">
                                    <label>Başkan Yardımcısı</label>
                                    <select id="baskan" name="baskan" class="form-control">
                                        <option value="">Tümü</option>
                                        <?php
                                        $res = $dba->query("SELECT id, ad FROM baskan_yardimcilari ORDER BY ad");
                                        while ($b = $res->fetch_assoc()) {
                                            echo '<option value="'.$b['id'].'">'.$b['ad'].'</option>';
                                        }
                                        ?>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label>Müdürlük</label>
                                    <select name="mudurluk[]" id="mudurluk" multiple>
                                        <?php
                                        $result = $dba->query("SELECT flexy_id, mudurluk FROM mudurlukler ORDER BY mudurluk ASC");
                                        while ($m = $result->fetch_assoc()) {
                                            $id = $m['flexy_id'];
                                            $safe = htmlspecialchars($m['mudurluk'], ENT_QUOTES, 'UTF-8');
                                            $selected = in_array($id, $mudurluk) ? 'selected' : '';

                                            echo "<option value=\"$id\" $selected>$safe</option>";
                                        }
                                        ?>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label>TC Kimlik No</label>
                                    <input type="text" name="tc" class="form-control"
                                           value="<?= htmlspecialchars($preserve['tc'] ?? '') ?>">
                                </div>
                                <div class="col-md-2">
                                    <label>Personel Türü</label>
                                    <select name="statuTuru" class="form-control">
                                        <option value="" <?= ($statuTuru == '' ? 'selected' : '') ?>>Tümü (Memur + Şirket)</option>
                                        <option value="MEMUR" <?= ($statuTuru == 'MEMUR' ? 'selected' : '') ?>>Memur</option>
                                        <option value="HIZMET_ALIMI" <?= ($statuTuru == 'HIZMET_ALIMI' ? 'selected' : '') ?>>Şirket</option>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label>DURUMU</label>
                                    <select name="durumu" class="form-control">
                                        <option value="1" <?= ($durumu == '1' ? 'selected' : '') ?>>Tümü</option>
                                        <option value="AKTIF" <?= (($durumu == 'AKTIF' OR $durumu == '') ? 'selected' : '') ?>>Aktif</option>
                                        <option value="PASIF" <?= ($durumu == 'PASIF' ? 'selected' : '') ?>>Pasif</option>
                                        <option value="UCRETSIZ_IZINDE" <?= ($durumu == 'UCRETSIZ_IZINDE' ? 'selected' : '') ?>>Ücretsiz İzinde</option>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label>&nbsp;</label>
                                    <button class="btn btn-success btn-block">Sorgula</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <?php if (!empty($uniqueRows)): ?>
            <div class="row" style="margin-top:10px;">
                <div class="col-md-12">
                    <div class="box box-success">
                        <div class="box-header">
                            <h3 class="box-title">
                                <?= htmlspecialchars($selectedMudAdi) ?> - Personel Verileri (<?= count($uniqueRows) ?> Kayıt)
                            </h3>
                            <button id="exportExcel" class="btn btn-success pull-right" style="margin: 10px 10px 0">Excel Olarak İndir</button>
                        </div>
                        <div class="box-body table-responsive">
                            <table id="pdksTable" class="table table-bordered table-striped">
                                <thead>
                                <tr>
                                    <th>#</th>
                                    <th>TC</th>
                                    <th>Ad Soyad</th>
                                    <th>Tür</th>
                                    <th>Müdürlük</th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php $i=1; foreach ($uniqueRows as $r): ?>
                                    <tr>
                                        <td><?= $i ?></td>
                                        <td><?= htmlspecialchars($r["tcKimlikNo"]) ?></td>
                                        <td><?= htmlspecialchars($r["personelAd"]." ".$r["personelSoyad"]) ?></td>
                                        <td><?= htmlspecialchars(str_replace('_',' ',$r["personel_statu_turu"])) ?></td>
                                        <td><?= htmlspecialchars($r["gorev_yaptigi_mudurluk"]) ?></td>
                                    </tr>
                                    <?php $i++; endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</section>
<link href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js"></script>

<script>
    var mudurlukSelect = new TomSelect("#mudurluk", {
        plugins: ['remove_button', 'checkbox_options'],
        maxItems: null,
        hideSelected: true,
        closeAfterSelect: false,
        valueField: 'flexy_id',
        labelField: 'mudurluk',
        searchField: 'mudurluk',
    });

    // BAŞKAN DEĞİŞİNCE
    document.getElementById('baskan').addEventListener('change', function () {
        let baskan_id = this.value;

        fetch('personeller/ajax_mudurluk_by_baskan.php?baskan=' + baskan_id)
            .then(res => res.json())
            .then(list => {

                // 1) Mevcut seçenekleri temizle
                mudurlukSelect.clearOptions();
                mudurlukSelect.clear(true); // seçili değerleri de sil

                // 2) Yeni müdürlük listesini ekle
                mudurlukSelect.addOptions(list);

                // 3) Gelen müdürlükleri değer olarak seç
                let selectedValues = list.map(i => i.flexy_id);
                mudurlukSelect.setValue(selectedValues, true); // otomatik selected yap

                mudurlukSelect.refreshOptions(false);
            })
            .catch(err => console.error("HATA:", err));
    });

</script>

