<!--Önümüzdeki 9 günü gösterir-->
<?php
/*$bugun = date('Y-m-d');
$gunler = [];
$i = 0;
$turkceGunler = ['Pazartesi','Salı','Çarşamba','Perşembe','Cuma','Cumartesi','Pazar'];

while (count($gunler) < 9) { // ✅ 9 kutu için 9 yaptım
    $tarih = strtotime("+$i day");
    $haftaGunu = date('N', $tarih);
    if ($haftaGunu < 6) { // sadece hafta içi
        $gunler[] = [
            'gun'   => $turkceGunler[$haftaGunu - 1],
            'tarih' => date('Y-m-d', $tarih)
        ];
    }
    $i++;
}
*/?>
<!--Sadece bu haftayı gösterir-->
<?php
$bugun = date('Y-m-d');
$gunler = [];
$turkceGunler = ['Pazartesi','Salı','Çarşamba','Perşembe','Cuma','Cumartesi','Pazar'];

$i = 0;
while (true) {
    $tarih = strtotime("+$i day");
    $haftaGunu = date('N', $tarih); // 1= Pazartesi ... 7=Pazar

    // Sadece hafta içi günleri ekle
    if ($haftaGunu < 6) {
        $gunler[] = [
            'gun'   => $turkceGunler[$haftaGunu - 1],
            'tarih' => date('Y-m-d', $tarih)
        ];
    }

    // Cuma günü geldiğinde dur
    if ($haftaGunu == 5) { // 5 = Cuma
        break;
    }
    $i++;
}
?>
<section class="content-header">
    <div class="row">
        <div class="col-xs-6">
            <h2>Haftalık Yemek Listesi</h2>
        </div>
        <div class="col-xs-6 text-right">
            <!-- Modal Butonu -->
            <?php if ($_SESSION['yemekhane']==1 OR $_SESSION['admin']==1) { ?>
                <a href="yemek.php?x=1" class="btn btn-success" style="color: white; margin-top: 20px;"><i class="fa fa-download"></i> Yemek Listesi Ekle</a>
            <?php } ?>
        </div>
    </div>
</section>
<section class="content">
    <div class="box-body">
        <div class="row" style="margin-top:10px;">
            <?php foreach ($gunler as $g):
                $isToday = ($g['tarih'] === $bugun);
                ?>
                <div class="col-md-4">
                    <div class="box <?= $isToday ? 'box-today' : 'box-success' ?>" style="border-top: 0">
                        <div class="box-header">
                            <h3 class="box-title">
                                <strong>
                                    <?= $g['gun'] . " - " . global_date_to_tr($g['tarih']) ?>
                                    <?php if($isToday): ?> (Bugün)<?php endif; ?>
                                </strong>
                            </h3>
                        </div>
                        <div class="card-body">
                            <table class="table table-bordered table-sm">
                                <tbody>
                                <?php
                                $tarih = addslashes($g['tarih']);
                                $sql = "SELECT yemek, kalori FROM yemekler WHERE tarih='" .$tarih."'";
                                $query = $dba->query($sql);

                                $satirlar = [];
                                if ($query && $query->num_rows > 0) {
                                    while ($row = $query->fetch_assoc()) {
                                        $satirlar[] = $row;
                                    }
                                    for ($i=0; $i<5; $i++) {
                                        if (isset($satirlar[$i])) {
                                            echo "<tr>";
                                            echo "<td width='400px'>" . htmlspecialchars($satirlar[$i]['yemek']) . "</td>";
                                            echo "<td>" . htmlspecialchars($satirlar[$i]['kalori']) . "</td>";
                                            echo "</tr>";
                                        } else {
                                            echo "<tr><td width='400px'>&nbsp;</td><td>&nbsp;</td></tr>";
                                        }
                                    }
                                } else {
                                    echo "<tr><td class='text-center text-muted'>Henüz veri girilmemiştir</td></tr>";
                                    for ($i=1; $i<5; $i++) {
                                        echo "<tr><td width='400px'>&nbsp;</td><td>&nbsp;</td></tr>";
                                    }
                                }
                                ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<style>
    .box.box-today .box-header {
        background: #2e7d32;
        color: white;
        border-top-color: #00a65a !important;
    }
</style>

<script>
    $(document).ready(function(){
        $(".col-md-4").hide();
        $(".col-md-4").each(function(index){
            $(this).delay(200*index).fadeIn(750);
        });
    });
</script>