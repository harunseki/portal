<?php
$start = $_POST['start'] ?? null;
$end   = $_POST['end'] ?? null;
$tc    = $_POST['tc'] ?? null;
$baskanId = $_POST['baskan'] ?? null;
$selectedMudurlukler = $_POST['mudurluk'] ?? [];
$enterMin = $_POST['enter_min'] ?? null;
$enterMax = $_POST['enter_max'] ?? null;
$exitMin  = $_POST['exit_min'] ?? null;
$exitMax  = $_POST['exit_max'] ?? null;

$totalRecords = 0;

// API yapılandırması
$apiUrl = "https://pdks.cankaya.bel.tr/api/pdksModule/PdksActivity/GetPdksPersonelFirstEnterLastExitLog";
$accessToken = "5c84a43b-0afc-49b5-91fd-e647df2b87f8";
?>

<section class="content-header">
    <h2>PDKS Bilgi Sorgulama</h2>
</section>

<section class="content">

    <!-- FİLTRE ALANI -->
    <div class="box box-success">
        <div class="box-body">
            <link rel="stylesheet" href="assets/css/select2.min.css">
            <script src="assets/js/select2.min.js"></script>
            <script src="assets/js/pdks_rapor.js"></script>

            <form method="POST">
                <div class="row">

                    <div class="col-md-3">
                        <label>Başkan Yardımcısı</label>
                        <select name="baskan" id="baskan" class="form-control select2">
                            <option value="">Tümü</option>
                            <?php
                            $result = $dba->query("SELECT id, ad FROM baskan_yardimcilari ORDER BY ad");
                            while ($b = $result->fetch_assoc()) {
                                $sel = ($baskanId == $b['id']) ? "selected" : "";
                                echo "<option value='{$b['id']}' {$sel}>{$b['ad']}</option>";
                            }
                            ?>
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label>Müdürlük (çoklu)</label>
                        <select name="mudurluk[]" id="mudurluk" multiple class="form-control select2">
                            <?php
                            $result = $dba->query("SELECT mudurluk FROM mudurlukler ORDER BY mudurluk");
                            while ($m = $result->fetch_assoc()) {
                                $sel = in_array($m['mudurluk'], $selectedMudurlukler) ? "selected" : "";
                                echo "<option value='{$m['mudurluk']}' {$sel}>{$m['mudurluk']}</option>";
                            }
                            ?>
                        </select>
                    </div>

                    <div class="col-md-2">
                        <label>Başlangıç Tarihi</label>
                        <input type="date" name="start" class="form-control" required value="<?= htmlspecialchars($start ?? date('Y-m-d')) ?>">
                    </div>

                    <div class="col-md-2">
                        <label>Bitiş Tarihi</label>
                        <input type="date" name="end" class="form-control" required value="<?= htmlspecialchars($end ?? date('Y-m-d')) ?>">
                    </div>

                    <div class="col-md-2">
                        <label>TC Kimlik No</label>
                        <input type="text" name="tc" class="form-control" value="<?= htmlspecialchars($tc ?? '') ?>">
                    </div>

                    <div class="col-md-12" style="margin-top:15px; display:flex; gap:15px;">
                        <div>
                            <label>Giriş (Min)</label>
                            <input type="time" name="enter_min" class="form-control" value="<?= htmlspecialchars($enterMin) ?>">
                        </div>
                        <div>
                            <label>Giriş (Max)</label>
                            <input type="time" name="enter_max" class="form-control" value="<?= htmlspecialchars($enterMax) ?>">
                        </div>
                        <div>
                            <label>Çıkış (Min)</label>
                            <input type="time" name="exit_min" class="form-control" value="<?= htmlspecialchars($exitMin) ?>">
                        </div>
                        <div>
                            <label>Çıkış (Max)</label>
                            <input type="time" name="exit_max" class="form-control" value="<?= htmlspecialchars($exitMax) ?>">
                        </div>
                        <div style="align-self:end;">
                            <button class="btn btn-success">Sorgula</button>
                        </div>
                    </div>

                </div>
            </form>
        </div>
    </div>

    <!-- TABLO -->
    <div class="box box-success">
        <div class="box-header">
            <h3 class="box-title">
                PDKS Verileri
                <?php if ($totalRecords > 0): ?>
                    <small style="margin-left:10px; color:#666;">
                        (Toplam: <?= number_format($totalRecords, 0, ',', '.') ?> kayıt)
                    </small>
                <?php endif; ?>
            </h3>

            <button id="exportExcel" class="btn btn-success pull-right" style="margin-left:10px">Excel</button>
            <button id="exportPdf" class="btn btn-success pull-right">PDF</button>
        </div>

        <div class="box-body table-responsive">
            <table id="pdksTable" class="table table-bordered table-striped">
                <thead>
                <tr>
                    <th>#</th>
                    <th>Ad Soyad</th>
                    <th>TC</th>
                    <th>Müdürlük</th>
                    <th>Tarih</th>
                    <th>Giriş</th>
                    <th>Çıkış</th>
                </tr>
                </thead>
                <tbody>

                <?php
                if ($_SERVER['REQUEST_METHOD'] === 'POST' && $start && $end) {
                    $currentStart = strtotime($start);
                    $finalEnd = strtotime($end);
                    $chunkDays = 7;
                    $counter = 1;
                    while ($currentStart <= $finalEnd) {
                        $periodStart = date('Y-m-d', $currentStart);
                        $periodEnd   = date('Y-m-d', min(strtotime("+6 days", $currentStart), $finalEnd));

                        $url = "$apiUrl?Start=$periodStart&End=$periodEnd&access_token=$accessToken&IncludeNonworkerPersonels=true";
                        if ($tc) $url .= "&personelTckimlikno=$tc";

                        $ch = curl_init($url);
                        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                        curl_setopt($ch, CURLOPT_TIMEOUT, 20);
                        $response = curl_exec($ch);
                        curl_close($ch);

                        $data = json_decode($response, true);

                        if (!empty($data)) {

                            usort($data, function ($a, $b) {
                                return [$a['personelPersonelDepartmentName'], $a['personelName'], $a['enterDate']]
                                    <=> [$b['personelPersonelDepartmentName'], $b['personelName'], $b['enterDate']];
                            });

                            foreach ($data as $row) {

                                if ($selectedMudurlukler && !in_array($row['personelPersonelDepartmentName'], $selectedMudurlukler)) continue;

                                $enter = !empty($row['enterDate']) ? date('H:i', strtotime($row['enterDate'])) : null;
                                $exit  = !empty($row['exitDate'])  ? date('H:i', strtotime($row['exitDate']))  : null;

                                if (($enterMin && $enter < $enterMin) || ($enterMax && $enter > $enterMax)) continue;
                                if (($exitMin && $exit < $exitMin) || ($exitMax && $exit > $exitMax)) continue;

                                echo "<tr>
                                    <td>{$counter}</td>
                                    <td>{$row['personelName']}</td>
                                    <td>{$row['personelTckimlikno']}</td>
                                    <td>{$row['personelPersonelDepartmentName']}</td>
                                    <td>".date('d.m.Y', strtotime($row['date']))."</td>
                                    <td>".($enter ? $enter : '-')."</td>
                                    <td>".($exit ? $exit : '-')."</td>
                                </tr>";
                                $counter++;
                            }
                        }
                        $currentStart = strtotime("+7 days", $currentStart);
                    }

                    $totalRecords = $counter - 1;
                }
                ?>

                </tbody>
            </table>
        </div>
    </div>

</section>

<script>
    document.getElementById("exportPdf").onclick = function () {
        window.print();
    };
</script>