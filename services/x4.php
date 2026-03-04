<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/autoload.php';


$manager = new ServiceManager($dba);
$manager->checkServices();

$result = $dba->query("SELECT name,last_status,last_response_time,last_checked FROM services WHERE is_active=1 ORDER BY name");
$services = $result->fetch_all(MYSQLI_ASSOC);
?>
<style>
    table { width:100%; background:#fff; border-collapse: collapse; }
    th,td { padding:10px; border:1px solid #ddd; text-align:center; }
    .up { color:green; font-weight:bold; }
    .down { color:red; font-weight:bold; }
    .slow { color:orange; font-weight:bold; }
</style>
<section class="content-header">
    <div class="row">
        <div class="col-xs-6">
            <h2>API Durum</h2>
        </div>
        <div class="col-xs-6 text-right">
            <?php if ($_SESSION['admin'] == 1): ?>
                <div class="pull-right" style="margin: 20px 10px 0">
                    <a href="2-services" class="btn btn-success">
                        <i class="fa fa-plus"></i> Yeni Servis Ekle
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>
<section class="content">
    <div class='row' style="margin-top:20px;">
        <div class='col-md-12'>
            <div class='box box-success'>
                <div class='box-header'>
                    <h3 class='box-title'></h3>
                    <!--<div style="display:flex;justify-content:space-between;align-items:center;margin:10px; float: right;">
                        <span id="lastRefresh" style="margin-right: 5px"></span>
                        <button onclick="refreshPage()" class="btn btn-success">
                            Yenile
                        </button>
                    </div>-->
                </div>
                <div class='box-body'>
                    <table>
                        <tr>
                            <th>Servis</th>
                            <th>Durum</th>
                            <th>Süre (ms)</th>
                            <th>Son Kontrol</th>
                        </tr>
                        <?php foreach ($services as $s): ?>
                            <tr>
                                <td><?= htmlspecialchars($s['name']) ?></td>
                                <td>
                                    <?php
                                    if ($s['last_status']) {
                                        if ($s['last_response_time'] > 2000) {
                                            echo "<span class='slow'>SLOW</span>";
                                        } else {
                                            echo "<span class='up'>UP</span>";
                                        }
                                    } else {
                                        echo "<span class='down'>DOWN</span>";
                                    }
                                    ?>
                                </td>
                                <td><?= $s['last_response_time'] ?></td>
                                <td><?= $s['last_checked'] ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </table>
                </div>
            </div>
        </div>
    </div>
</section>
<script>
    function refreshPage() {
        location.reload();
    }

    document.getElementById("lastRefresh").innerText =
        "Son Güncelleme: " + new Date().toLocaleString();
</script>