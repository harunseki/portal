<?php
// Bugünden itibaren gelecek 5 iş günü
$bugun = strtotime('today');
$gunler = [];
$i = 0;
$turkceGunler = ['Pazartesi','Salı','Çarşamba','Perşembe','Cuma','Cumartesi','Pazar'];

while (count($gunler) < 8) {
    $tarih = strtotime("+$i day", $bugun);
    $haftaGunu = date('N', $tarih); // 1=Pazartesi, 7=Pazar
    if ($haftaGunu < 6) { // Hafta içi
        $gunler[] = [
            'gun'   => $turkceGunler[$haftaGunu - 1],
            'tarih' => date('Y-m-d', $tarih)
        ];
    }
    $i++;
}
?>
<section class="content-header">
    <div class="row">
        <div class="col-xs-6">
            <h2>Yemek Listesi Ekle / Düzenle</h2>
        </div>
        <div class="col-xs-6 text-right">
            <!-- Modal Butonu -->
            <?php if ($_SESSION['yemekhane']==1) { ?>
            <?php } ?>
        </div>
    </div>
</section>
<section class="content">
    <?php
    // Form gönderildiğinde kaydetme işlemi
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        foreach ($_POST['gun'] as $gunIndex => $gun) {
            $gun = addslashes($gun);
            $tarih = addslashes($_POST['tarih'][$gunIndex]);

            $logBasilsin = false;
            $isUpdate = false; // Bu gün için güncelleme mi yapılıyor?

            // Veritabanında bu gün/tarih için kayıt var mı?
            $existing = $dba->query("SELECT COUNT(*) as cnt FROM yemekler WHERE gun='$gun' AND tarih='$tarih'");
            $row = $existing->fetch_assoc();
            if ($row['cnt'] > 0) $isUpdate = true;

            // Önce mevcut kayıtları temizle
            $dba->query("DELETE FROM yemekler WHERE gun='$gun' AND tarih='$tarih'");

            foreach ($_POST['yemek'][$gunIndex] as $i => $yemek) {
                $yemek = addslashes($yemek);
                $kalori = isset($_POST['kalori'][$gunIndex][$i]) && $_POST['kalori'][$gunIndex][$i] !== ''
                    ? (int)$_POST['kalori'][$gunIndex][$i]
                    : 0;

                if (!empty($yemek) || $kalori !== 0) {
                    $dba->query("INSERT INTO yemekler (gun, tarih, yemek, kalori) VALUES ('$gun', '$tarih', '$yemek', $kalori)");
                    $logBasilsin = true;
                }
            }

            // Log işlemi
            if ($logBasilsin) {
                if ($isUpdate) {
                    $dba->addLog($ip, $ldap_username, $personelTC, "update", "$tarih Yemek Listesi Güncellendi.");
                } else {
                    $dba->addLog($ip, $ldap_username, $personelTC, "create", "$tarih Yemek Listesi Eklendi.");
                }
            }
        }

        alert_success("Yemekler kaydedildi!");
    }

    $gunYemekleri = [];
    $startDate = $gunler[0]['tarih'];
    $endDate   = end($gunler)['tarih'];
    $res = $dba->query("SELECT * FROM yemekler WHERE tarih BETWEEN '$startDate' AND '$endDate' ORDER BY tarih, id");
    while ($row = $res->fetch_assoc()) {
        $gunYemekleri[$row['tarih']][] = $row;
    }
    ?>
    <form method="post">
        <div class="box-body">
            <div class="row" style="margin-top:10px;">
                <?php foreach ($gunler as $gi => $g): ?>
                    <div class="col-md-3">
                        <div class="box box-success">
                            <div class="box-header">
                                <h3 class="box-title"><?php echo $g['gun'] . " - " . global_date_to_tr($g['tarih']); ?></h3>
                            </div>

                            <div class="box-body">
                                <h4 class="mt-4"></h4>
                                <input type="hidden" name="gun[<?php echo $gi; ?>]" value="<?php echo $g['gun']; ?>">
                                <input type="hidden" name="tarih[<?php echo $gi; ?>]" value="<?php echo $g['tarih']; ?>">

                                <?php for ($i = 0; $i < 5; $i++):
                                    $yemekValue = isset($gunYemekleri[$g['tarih']][$i]['yemek']) ? $gunYemekleri[$g['tarih']][$i]['yemek'] : '';
                                    $kaloriValue = isset($gunYemekleri[$g['tarih']][$i]['kalori']) ? $gunYemekleri[$g['tarih']][$i]['kalori'] : '';
                                    ?>
                                    <div class="row" style="margin-bottom: 5px">
                                        <div class="col-md-8">
                                            <input type="text" class="form-control" name="yemek[<?php echo $gi; ?>][<?php echo $i; ?>]" placeholder="Yemek adı" value="<?php echo htmlspecialchars($yemekValue); ?>">
                                        </div>
                                        <div class="col-md-4">
                                            <input type="text" class="form-control" name="kalori[<?php echo $gi; ?>][<?php echo $i; ?>]" placeholder="Kalori" step="1" min="0" value="<?php echo htmlspecialchars($kaloriValue); ?>">
                                        </div>
                                    </div>
                                <?php endfor; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <div class="box-footer">
            <button type="submit" class="btn btn-success">Kaydet</button>
        </div>
    </form>
</section>
