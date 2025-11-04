<?php
require_once "inc/header.php";
require_once "inc/menu.php";

$file = "uygulamalar";
?>
<aside class="right-side">
    <?php
    // DB’den uygulamaları çek
    $q = $dba->query("SELECT adi, photo, url FROM uygulamalar ORDER BY adi");
    // Tüm satırları bir diziye al
    $apps = [];
    while ($row = $dba->fetch_assoc($q)) {
        $apps[] = $row;
    }
    ?>
    <section class="content-header">
        <h1>Uygulamalar</h1>
    </section>
    <section class="content" style="padding-top: 20px">
        <div class="row">
            <?php if (!empty($apps)): ?>
                <?php foreach ($apps as $app): ?>
                    <div class="col-md-2 col-sm-4 col-xs-6">
                        <a href="<?= htmlspecialchars($app['url']) ?>" target="_blank" class="app-card">
                            <div class="box text-center" style="border-radius:10px; padding:20px; transition: all 0.3s; height:180px;">
                                <div style="height:80px; width:80px; margin:0 auto 10px; display:flex; align-items:center; justify-content:center;">
                                    <img src="img/icons/<?= strip($app['photo']) ?>"
                                         style="max-height:100%; max-width:100%; object-fit:contain;">
                                </div>
                                <h4 style="font-size:16px; font-weight:bold; color:#333; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                                    <?= htmlspecialchars($app['adi']) ?>
                                </h4>
                            </div>
                        </a>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p>Henüz uygulama eklenmedi.</p>
            <?php endif; ?>
        </div>
    </section>
    <style>
        .app-card .box:hover {
            background: #f0f0f0;
            transform: translateY(-5px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
    </style>
</aside>
<?php require_once "inc/footer.php"; ?>
