<?php
require_once("inc/header.php");
require_once("inc/menu1.php");

$start = $_POST['start'] ?? null;
$end   = $_POST['end'] ?? null;
$tc    = $_POST['tc'] ?? null;
$baskanId = $_POST['baskan'] ?? null;
$selectedMudurlukler = $_POST['mudurluk'] ?? [];
$enterMin = isset($_POST['enter_min']) ? $_POST['enter_min'] : null;
$enterMax = isset($_POST['enter_max']) ? $_POST['enter_max'] : null;
$exitMin  = isset($_POST['exit_min']) ? $_POST['exit_min'] : null;
$exitMax  = isset($_POST['exit_max']) ? $_POST['exit_max'] : null;

$response = null;

// API yapılandırması
$apiUrl = "https://pdks.cankaya.bel.tr/api/pdksModule/PdksActivity/GetPdksPersonelFirstEnterLastExitLog";
$accessToken = "5c84a43b-0afc-49b5-91fd-e647df2b87f8";
?>
<aside class="right-side">
    <section class="content-header">
        <h2>PDKS Bilgi Sorgulama</h2>
    </section>
    <section class="content">
        <div class="box box-success">
            <div class="box-body">
                <link rel="stylesheet" href="assets/css/select2.min.css">
                <script src="assets/js/select2.min.js"></script>
                <script src="assets/js/pdks_rapor.js"></script>
                <form method="POST" class="mb-3">
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
                                $result = $dba->query("SELECT flexy_id, mudurluk FROM mudurlukler ORDER BY mudurluk");
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
                        <div class="col-md-4">
                            <div style="display:flex; gap:20px; margin-bottom:20px;">
                                <div>
                                    <label>Giriş Saati (min)</label>
                                    <input type="time" name="enter_min" id="enter_min" class="form-control"
                                           value="<?php echo htmlspecialchars($enterMin); ?>">
                                </div>
                                <div>
                                    <label>Giriş Saati (max)</label>
                                    <input type="time" name="enter_max" id="enter_max" class="form-control"
                                           value="<?php echo htmlspecialchars($enterMax); ?>">
                                </div>
                                <div>
                                    <label>Çıkış Saati (min)</label>
                                    <input type="time" name="exit_min" id="exit_min" class="form-control"
                                           value="<?php echo htmlspecialchars($exitMin); ?>">
                                </div>
                                <div>
                                    <label>Çıkış Saati (max)</label>
                                    <input type="time" name="exit_max" id="exit_max" class="form-control"
                                           value="<?php echo htmlspecialchars($exitMax); ?>">
                                </div>
                            </div>
                        </div>
                        <div class="col-md-1">
                            <label>&nbsp;</label>
                            <button class="btn btn-success btn-block">Sorgula</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        <div class="box box-success mt-3">
            <div class="box-header">
                <h3 class="box-title">PDKS Verileri</h3>
                <button id="exportExcel" class="btn btn-success pull-right" style="margin: 10px 10px 0">Excel Olarak İndir</button>
            </div>
            <div class="box-body table-responsive">
                <table id="pdksTable" class="table table-bordered table-striped">
                    <thead>
                    <tr>
                        <th>S.No</th>
                        <th>Ad Soyad</th>
                        <th>TC</th>
                        <th>Birim</th>
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

                        // Başkan seçili ama müdürlük seçilmemişse -> başkana bağlı müdürlükleri al
                        if ($baskanId && empty($selectedMudurlukler)) {
                            $q = "SELECT m.mudurluk 
                                  FROM mudurlukler m
                                  INNER JOIN baskan_yardimcilari_mudurlukler b 
                                    ON b.mudurluk_id = m.flexy_id
                                  WHERE b.baskan_id = ?";
                            $stmt = $dba->prepare($q);
                            $stmt->bind_param("i", $baskanId);
                            $stmt->execute();
                            $res = $stmt->get_result();

                            while ($r = $res->fetch_assoc()) {
                                $selectedMudurlukler[] = $r['mudurluk'];
                            }
                        }

                        while ($currentStart <= $finalEnd) {
                            $periodStart = date('Y-m-d', $currentStart);
                            $periodEnd   = date('Y-m-d', min(strtotime("+".($chunkDays-1)." days", $currentStart), $finalEnd));

                            $url = "$apiUrl?Start=$periodStart&End=$periodEnd&access_token=$accessToken&IncludeNonworkerPersonels=true";
                            if ($tc) $url .= "&personelTckimlikno=$tc";

                            $ch = curl_init();
                            curl_setopt($ch, CURLOPT_URL, $url);
                            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                            curl_setopt($ch, CURLOPT_TIMEOUT, 15);
                            $response = curl_exec($ch);
                            curl_close($ch);

                            $chunkData = json_decode($response, true);

                            if (!empty($chunkData)) {
                                // Sıralama: Müdürlük > Ad Soyad > Giriş Saati
                                usort($chunkData, function ($a, $b) {
                                    $deptCompare = strcmp($a['personelPersonelDepartmentName'], $b['personelPersonelDepartmentName']);
                                    if ($deptCompare !== 0) return $deptCompare;

                                    $nameCompare = strcmp($a['personelName'], $b['personelName']);
                                    if ($nameCompare !== 0) return $nameCompare;

                                    /*$enterA = strtotime($a['enterDate']);
                                    $enterB = strtotime($b['enterDate']);
                                    return $enterA <=> $enterB;*/
                                });

                                foreach ($chunkData as $entry) {
                                    // Müdürlük filtresi
                                    if (!empty($selectedMudurlukler) &&
                                        !in_array($entry['personelPersonelDepartmentName'], $selectedMudurlukler)) {
                                        continue;
                                    }

                                    // Giriş saat filtresi
                                    $entryEnterTime = !empty($entry['enterDate']) ? date('H:i', strtotime($entry['enterDate'])) : null;
                                    if (($enterMin && $entryEnterTime < $enterMin) || ($enterMax && $entryEnterTime > $enterMax)) {
                                        continue;
                                    }

                                    // Çıkış saat filtresi
                                    $entryExitTime = !empty($entry['exitDate']) ? date('H:i', strtotime($entry['exitDate'])) : null;
                                    if (($exitMin && $entryExitTime < $exitMin) || ($exitMax && $entryExitTime > $exitMax)) {
                                        continue;
                                    }

                                    // Tabloya veri ekleme
                                    echo "<tr>
                                            <td>{$counter}</td>
                                            <td>{$entry['personelName']}</td>
                                            <td>{$entry['personelTckimlikno']}</td>
                                            <td>{$entry['personelPersonelDepartmentName']}</td>
                                            <td>" . date('d.m.Y', strtotime($entry['date'])) . "</td>
                                            <td>" . (!empty($entry['enterDate']) ? date('H:i:s', strtotime($entry['enterDate'])) : '-') . "</td>
                                            <td>" . (!empty($entry['exitDate']) ? date('H:i:s', strtotime($entry['exitDate'])) : '-') . "</td>
                                          </tr>";

                                    $counter++;
                                }

                            }

                            $currentStart = strtotime("+$chunkDays days", $currentStart);
                        }
                    }
                    ?>
                    </tbody>
                </table>
            </div>
        </div>
    </section>
</aside>
<?php require_once("inc/footer.php"); ?>