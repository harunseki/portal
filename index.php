<?php
require_once("inc/header.php");
require_once("inc/menu1.php");

if (empty($ldap_username) || empty($cn) || empty($personelTC) || $cn === 'Veri yok' || $personelTC === 'Veri yok') {
    echo "<div style='
            position: fixed;
            top: 0; left: 0;
            width: 100%;
            background-color: #e74c3c;
            color: white;
            font-size: 16px;
            font-weight: bold;
            text-align: center;
            padding: 10px 0;
            z-index: 9999;
        '>⚠️ Kullanıcı bilgilerinizde eksiklik var. Lütfen bilgi işlem yazılım birimi(Dahili: 2950) ile iletişime geçiniz.</div>";
}

if (basename($_SERVER['PHP_SELF']) === 'index.php') {
    $now = date('Y-m-d H:i:s');

    // Güvenli prepared statement
    $stmt = $dba->prepare("SELECT * FROM popups 
                           WHERE aktif = 1 AND baslangic_tarihi <= ? AND bitis_tarihi >= ?
                           ORDER BY id ASC");
    $stmt->execute([$now, $now]);
    $popups = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    if (!empty($popups)): ?>
        <style>
            #popupOverlay {
                position: fixed; inset: 0;
                background: rgba(0,0,0,0.6);
                display: flex; justify-content: center; align-items: center;
                z-index: 99999;
                animation: fadeIn .3s ease;
            }
            #popupBox {
                background: #fff;
                padding: 25px 35px;
                border-radius: 15px;
                max-width: 600px;
                width: 90%;
                text-align: center;
                box-shadow: 0 8px 30px rgba(0,0,0,0.4);
                position: relative;
                animation: slideUp .4s ease;
                font-family: "Segoe UI", sans-serif;
            }

            /* Kategori renkleri */
            #popupBox.bilgi  { border-top: 6px solid #3498db; }
            #popupBox.duyuru { border-top: 6px solid #2ecc71; }
            #popupBox.uyari  { border-top: 6px solid #f1c40f; }
            #popupBox.kritik { border-top: 6px solid #e74c3c; }

            #popupTitle {
                font-size: 1.5em;
                margin-bottom: 10px;
                display: flex; align-items: center; justify-content: center;
                gap: 8px;
            }
            #popupContent { font-size: 1.2rem; color: #333; margin-top: 10px; }
            #popupClose {
                margin-top: 20px;
                background: #444; color: #fff;
                border: none; padding: 8px 18px;
                border-radius: 8px; cursor: pointer;
            }
            #popupClose:hover { background: #000; }

            @keyframes fadeIn { from {opacity:0;} to {opacity:1;} }
            @keyframes slideUp { from {transform:translateY(20px);opacity:0;} to {transform:translateY(0);opacity:1;} }
        </style>

        <div id="popupOverlay" style="display:none;">
            <div id="popupBox">
                <h3 id="popupTitle"></h3>
                <div id="popupContent"></div>
                <button id="popupClose">Kapat</button>
            </div>
        </div>

        <script>
            $(function(){
                const popups = <?= json_encode($popups, JSON_UNESCAPED_UNICODE) ?>;
                let index = 0;

                function getCookie(name){
                    const m = document.cookie.match(new RegExp("(^| )" + name + "=([^;]+)"));
                    return m ? m[2] : null;
                }
                function setCookie(name, value, days){
                    const d = new Date();
                    d.setTime(d.getTime() + (days*24*60*60*1000));
                    document.cookie = `${name}=${value};expires=${d.toUTCString()};path=/;SameSite=Lax`;
                }

                function icon(cat){
                    return {
                        bilgi: "ℹ️",
                        duyuru: "📢",
                        uyari: "⚠️",
                        kritik: "❗"
                    }[cat] || "💬";
                }

                function showNext(){
                    if(index >= popups.length) return;

                    const p = popups[index];
                    const cname = "popup_" + p.id + "_seen";

                    if(getCookie(cname)){
                        index++; showNext();
                        return;
                    }

                    $("#popupTitle").html(icon(p.kategori) + " " + p.baslik);

                    // --- RESİM + İÇERİK ENTEGRASYONU ---
                    let html = p.icerik;

                    if (p.image_url && p.image_url !== "") {
                        html = `
                                <div style="margin-bottom:15px;">
                                    <img src="${p.image_url}"
                                         style="max-width:80%; border-radius:10px;
                                         box-shadow:0 4px 15px rgba(0,0,0,0.3);" />
                                </div>
                                <div>${p.icerik}</div>
                            `;
                    }

                    $("#popupContent").html(html);

                    $("#popupBox").attr("class", p.kategori);
                    $("#popupOverlay").fadeIn(200);

                    const scrollY = window.scrollY;
                    document.body.style.position = "fixed";
                    document.body.style.top = `-${scrollY}px`;

                    $("#popupClose").off().on("click", function(){
                        $("#popupOverlay").fadeOut(200, () => {
                            setCookie(cname, "1", 7);
                            document.body.style.position = "";
                            document.body.style.top = "";
                            window.scrollTo(0, scrollY);
                            index++;
                            showNext();
                        });
                    });
                }


                showNext();
            });
        </script>

    <?php
    endif;
}
?>
<aside class="right-side">
    <section class="content">
        <?php
        if ($file) {
            if(empty($x) or $x==1 ) require_once("$file/x1.php");
            else require_once("$file/x". $purifier->purify(rescape((int)$_GET['x'])) .".php");
        }
        else { ?>
            <!-- Döviz Satırı -->
            <div>
                <?php
                $kontrol = $dba->query("SELECT * FROM piyasalar ORDER BY id DESC LIMIT 1");
                $cache = $kontrol->fetch_assoc();

                if (!$needUpdate) {
                    // ---- Servislerden veriyi cURL ile çek ----
                    // Ortak cURL fonksiyonu
                    function fetch_url_with_curl($url) {
                        $ch = curl_init();
                        curl_setopt($ch, CURLOPT_URL, $url);
                        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true); // Güvenli doğrulama
                        $response = curl_exec($ch);
                        curl_close($ch);
                        return $response;
                    }

                    // Finans verisini çek
                    $finans_url = 'https://finans.truncgil.com/today.json';
                    $response = fetch_url_with_curl($finans_url);
                    $finans_data = json_decode($response, true);

                    // Değerleri kontrol et
                    $dolar_satis = $finans_data['USD']['Satış'] ?? 'N/A';
                    $euro_satis = $finans_data['EUR']['Satış'] ?? 'N/A';
                    $gram_altin_satis = $finans_data['gram-altin']['Satış'] ?? 'N/A';

                    // CoinGecko (BTC) verisini cURL ile çek
                    $coingecko_url = 'https://api.coingecko.com/api/v3/simple/price?ids=bitcoin&vs_currencies=try';
                    $ch = curl_init();
                    curl_setopt_array($ch, [
                            CURLOPT_URL => $coingecko_url,
                            CURLOPT_RETURNTRANSFER => true,
                            CURLOPT_SSL_VERIFYPEER => true,
                            CURLOPT_HTTPHEADER => [
                                    'User-Agent: MyAppName/1.0 (contact: admin@siteadi.com)'
                            ],
                            CURLOPT_TIMEOUT => 10,
                    ]);

                    $response = curl_exec($ch);

                    curl_close($ch);

                    $btc_data = json_decode($response, true);

                    $btc_fiyat = isset($btc_data['bitcoin']['try'])
                        ? number_format($btc_data['bitcoin']['try'], 0, ',', '.')
                        : 'N/A';

                    // DB’ye kaydet → varsa güncelle yoksa ekle
                    if ($cache) {
                        $stmt = $dba->prepare("UPDATE piyasalar SET dolar_satis=?, euro_satis=?, altin_satis=?, btc_satis=?, guncellenme_zamani=NOW() WHERE id=?");
                        $stmt->bind_param("ssssi", $dolar_satis, $euro_satis, $gram_altin_satis, $btc_fiyat, $cache['id']);
                        $stmt->execute();
                    } else {
                        $stmt = $dba->prepare("INSERT INTO piyasalar (dolar_satis, euro_satis, altin_satis, btc_satis, guncellenme_zamani) VALUES (?,?,?,?,NOW())");
                        $stmt->bind_param("ssss", $dolar_satis, $euro_satis, $gram_altin_satis, $btc_fiyat);
                        $stmt->execute();
                    }
                } else {
                    // Cache’ten oku
                    $dolar_satis = $cache['dolar_satis'];
                    $euro_satis = $cache['euro_satis'];
                    $gram_altin_satis = $cache['altin_satis'];
                    $btc_fiyat = $cache['btc_satis'];
                }
                $son_guncelleme = $last ? $last['created_at'] : date("Y-m-d H:i:s");
                ?>
                <!-- Son Güncelleme Satırı -->
                <div class="row">
                    <div class="col-xs-12 text-right" style="font-size:11px; color:#4a4a4a;">
                        Son Güncellenme: <?= date("d.m.Y H:i", strtotime($son_guncelleme)) ?>
                    </div>
                </div>
                <div class="row">
                    <?php
                    $boxes = [
                        ['value'=>$dolar_satis, 'label'=>'DOLAR', 'color'=>'radial-gradient(circle at center, #007d32 0%, #00352b 100%)'],
                        ['value'=>$euro_satis, 'label'=>'EURO', 'color'=>'radial-gradient(circle at center, #007d32 0%, #00352b 100%)'],
                        ['value'=>$gram_altin_satis, 'label'=>'ALTIN', 'color'=>'radial-gradient(circle at center, #007d32 0%, #00352b 100%)'],
                        ['value'=>$btc_fiyat, 'label'=>'BTC', 'color'=>'radial-gradient(circle at center, #007d32 0%, #00352b 100%)'],
                        /*['value'=>'', 'label'=>'HAVA DURUMU', 'color'=>'#3d9970', 'weather'=>true]*/
                    ];
                    foreach($boxes as $box):
                        $isWeather = $box['weather'] ?? false;
                        ?>
                        <div class="col-lg-3 col-xs-6">
                            <div class="small-box" style="background:<?= $box['color'] ?>; color:#fff; border-radius:5px; box-shadow:0 2px 5px rgba(0,0,0,0.1);  font-size:14px;">
                                <div class="inner text-center" style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;padding: 5px;">
                                    <strong><?= $box['label'] ?> : <?= $box['value'] ?> TL</strong>
                                </div>
                            </div>
                        </div>
                        <!--<div class="col-lg-<?php /*= $isWeather ? '4':'2' */?> col-xs-6">
                    <div class="small-box" style="background:<?php /*= $box['color'] */?>; color:#fff; border-radius:5px; box-shadow:0 2px 5px rgba(0,0,0,0.1);">
                        <div class="inner text-center" style="padding:10px;">
                            <?php /*if($isWeather): */?>
                                <div class="weather-desc"></div>
                                <div class="weather-temp" style="font-size:24px;">🌡 <?php /*= htmlspecialchars($temp) */?>°C</div>
                                <div class="weather-icon" style="font-size:16px;"><?php /*= $icon ." ". htmlspecialchars($desc) */?></div>
                                <div class="weather-extra">💨 <?php /*= htmlspecialchars($wind) */?> km/h | 💧 Nem: <?php /*= htmlspecialchars($humidity) */?>%</div>
                            <?php /*else: */?>
                                <h3><?php /*= $box['value'] */?> <sup style="font-size:16px">TL</sup></h3>
                                <p><?php /*= $box['label'] */?></p>
                            <?php /*endif; */?>
                        </div>
                    </div>
                </div>-->
                    <?php endforeach; ?>
                </div>
            </div>
            <!-- Alt Taraf -->
            <div class="row">
                <section class="col-lg-7 connectedSortable ui-sortable" style="padding-right: 4px">
                    <!--Haberler-->
                    <div class="box" style="border-top: none !important; margin-bottom: 8px;">
                        <?php
                        $haberler = [];

                        // 1 saat kontrolü (id=1 üzerinden bakıyoruz)
                        $check = $dba->query("SELECT created_at FROM news WHERE id=1")->fetch_assoc();
                        $lastFetch = $check['created_at'] ?? null;
                        $needsUpdate = false;

                        if ($lastFetch) {
                            $diff = time() - strtotime($lastFetch);
                            if ($diff > 7200) { // 2 saatten küçükse API çağrısı yapma
                                $needsUpdate = true;
                            }
                        }

                        if ($needsUpdate) {
                            // API bilgileri
                            $apiUrl = "https://www.cankaya.bel.tr/api/news?per_page=10&sort_by=published_at&sort_direction=desc";
                            $apiKey = "ck_et9nT91y4g1TImI40UDmsvxZFQMMiyosgFgxrmda";

                            try {
                                $ch = curl_init();
                                curl_setopt($ch, CURLOPT_URL, $apiUrl);
                                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                                    "Content-Type: application/json",
                                    "x-api-key: $apiKey"
                                ]);
                                curl_setopt($ch, CURLOPT_TIMEOUT, 5);
                                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
                                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);

                                $response = curl_exec($ch);
                                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                                curl_close($ch);

                                if ($response && $httpCode === 200) {
                                    $result = json_decode($response, true);

                                    if (!empty($result['data']) && is_array($result['data'])) {
                                        $haberler = $result['data'];

                                        // Eski kayıtları sil
                                        $dba->query("TRUNCATE TABLE news");

                                        // Yeni kayıtları ekle
                                        $stmt = $dba->prepare("INSERT IGNORE INTO news (haber_id, title, image_url, published_at) VALUES (?,?,?,?)");
                                        foreach ($haberler as $h) {
                                            $stmt->bind_param("isss", $h['id'], $h['title'], $h['image'], $h['published_at']);
                                            $stmt->execute();
                                        }
                                        $stmt->close();
                                    } else {
                                        // API boş döndüyse tabloya dokunma, mevcut kayıtları kullan
                                    }
                                }
                            } catch (\Exception $e) {
                                // API hatalıysa, db'den devam
                            }
                        }

                        // DB’den oku
                        $res = $dba->query("SELECT * FROM news ORDER BY published_at DESC LIMIT 10");

                        $haberler = [];
                        while ($row = $res->fetch_assoc()) {
                            $haberler[] = $row;
                        }
                        ?>
                        <div class="box-body" style="height: 512px;">
                            <div id="haberCarousel" class="carousel slide carousel-fade" data-ride="carousel" style="height: 492px; border-radius: 4px">
                                <ol class="carousel-indicators" style="bottom: -12px !important;">
                                    <?php foreach($haberler as $i => $haber): ?>
                                        <li data-target="#haberCarousel" data-slide-to="<?= $i ?>" class="<?= $i===0 ? 'active' : '' ?>"></li>
                                    <?php endforeach; ?>
                                </ol>
                                <div class="carousel-inner" style="height:492px;">
                                    <?php foreach($haberler as $i => $haber): ?>
                                        <div class="item <?= $i===0 ? 'active' : '' ?>" style="height:492px;">
                                            <a href="<?= 'https://www.cankaya.bel.tr/haberler/' . slugify($haber['title']) ?>" target="_blank" style="display:block; height:100%;">
                                                <img src="<?= htmlspecialchars($haber['image_url'] ?? 'img/default.png') ?>"
                                                     alt="<?= htmlspecialchars($haber['title'] ?? '') ?>"
                                                     style="width:100%; height:100%; object-fit:cover; display:block;">
                                                <div class="carousel-caption" style="background: rgba(0,0,0,0.4); padding:5px; border-radius:5px;">
                                                    <h4 style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                                        <?= htmlspecialchars($haber['title'] ?? '') ?>
                                                    </h4>
                                                    <p style="max-height: 40px; overflow: hidden; text-overflow: ellipsis;">
                                                        <?= htmlspecialchars($haber['summary'] ?? strip_tags(substr($haber['content'] ?? '', 0, 100))) ?>
                                                    </p>
                                                </div>
                                            </a>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                <a class="left carousel-control" href="#haberCarousel" data-slide="prev" style="width: 8%; border-radius: 4px;">
                                    <span class="glyphicon glyphicon-chevron-left"></span>
                                </a>
                                <a class="right carousel-control" href="#haberCarousel" data-slide="next" style="width: 8%; border-radius: 4px;">
                                    <span class="glyphicon glyphicon-chevron-right"></span>
                                </a>
                            </div>
                            <style>
                                .carousel-fade .carousel-inner .item {
                                    opacity: 0;
                                    transition-property: opacity;
                                }
                                .carousel-fade .carousel-inner .active {
                                    opacity: 1;
                                }
                                .carousel-fade .carousel-inner .active.left,
                                .carousel-fade .carousel-inner .active.right {
                                    left: 0;
                                    opacity: 0;
                                }
                                .carousel-fade .carousel-inner .next.left,
                                .carousel-fade .carousel-inner .prev.right {
                                    opacity: 1;
                                }
                            </style>
                            <script>
                                $(document).ready(function(){
                                    $('#haberCarousel').carousel({
                                        interval: 4000
                                    });
                                });
                            </script>
                        </div>
                    </div>
                    <!--Uygulamalar-->
                    <div class="panel panel-default" style="border:none; box-shadow:none; margin-bottom: 16px;">
                        <div class="panel-heading" style="text-transform: uppercase; display: flex; align-items: center; justify-content: space-between; padding: 10px 15px !important; height: 40px">
                            <h4 class="panel-title" style="display:inline-block; margin:0;">
                                <i class="fa fa-puzzle-piece"></i> &nbsp;UYGULAMALARIMIZ
                            </h4>
                        </div>
                        <style>
                            /* Hover efekti */
                            #appsCarousel .app-card .box {
                                transition: all 0.3s ease;
                            }

                            #appsCarousel .app-card:hover .box {
                                transform: translateY(-10px);
                                box-shadow: 0 6px 15px rgba(0,0,0,0.2);
                            }
                        </style>
                        <?php
                        // DB’den uygulamaları çek
                        $q = $dba->query("SELECT adi, photo, url FROM uygulamalar ORDER BY id");
                        // Tüm satırları bir diziye al
                        $apps = [];
                        while ($row = $dba->fetch_assoc($q)) {
                            $apps[] = $row;
                        }
                        ?>
                        <div class="panel-body" style="padding:0; /*background-color: #f5f5f5;*/">
                            <div id="appsCarousel" class="carousel slide" data-ride="carousel" data-interval="false">
                                <div class="carousel-inner">
                                    <?php
                                    $i = 0;
                                    $total = count($apps);
                                    foreach ($apps as $app):
                                        if ($i % 4 == 0): ?>
                                            <div class="item <?= ($i == 0) ? 'active' : '' ?>">
                                            <div class="row" style="margin:15px 5px;">
                                        <?php endif; ?>
                                        <div class="col-md-3">
                                            <a href="<?= htmlspecialchars($app['url']) ?>" target="_blank" class="app-card">
                                                <div class="box text-center" style="border-radius:10px; padding:20px; background:#fff; box-shadow:0 2px 8px rgba(0,0,0,0.1); height:155px; margin-bottom: 0; border-top: none">
                                                    <div style="height:90px; width:80px; margin:0 auto 10px; display:flex; align-items:center; justify-content:center;">
                                                        <img src="img/icons/<?= strip($app['photo']) ?>" style="max-height:100%; max-width:100%; object-fit:contain; border-radius: 10px">
                                                    </div>
                                                    <h4 style="font-size:14px; font-weight:bold; color:#333; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                                                        <?= htmlspecialchars($app['adi']) ?>
                                                    </h4>
                                                </div>
                                            </a>
                                        </div>

                                        <?php
                                        $i++;
                                        if ($i % 4 == 0 || $i == $total): ?>
                                            </div>
                                            </div>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </div>

                                <?php if ($total>4): ?>
                                    <style>
                                        #appsCarousel .carousel-control {
                                            width: 40px;
                                            background: none !important;
                                            opacity: 1;
                                            top: 50%;
                                            transform: translateY(-50%);
                                        }

                                        #appsCarousel .carousel-control.left {
                                            left: -20px;
                                        }

                                        #appsCarousel .carousel-control.right {
                                            right: -18px;
                                        }

                                        #appsCarousel .carousel-control .glyphicon {
                                            font-size: 28px;
                                            color: #333;
                                        }

                                        #appsCarousel .carousel-inner .row {
                                            margin: 0;
                                        }

                                        #appsCarousel .carousel-inner .col-md-4 {
                                            padding: 10px;
                                        }
                                    </style>
                                    <!-- Carousel Controls -->
                                    <a class="left carousel-control" href="#appsCarousel" data-slide="prev">
                                        <span class="glyphicon glyphicon-chevron-left"></span>
                                    </a>
                                    <a class="right carousel-control" href="#appsCarousel" data-slide="next">
                                        <span class="glyphicon glyphicon-chevron-right"></span>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </section>
                <section class="col-lg-5 connectedSortable ui-sortable" style="padding-left: 4px">
                    <!--Etkinlik ve Duyurular-->
                    <div class="panel panel-success" style="margin-bottom: 8px; border: 0;">
                        <?php

                        /*if ($admin == '1') {
                            echo isset($_SESSION['active_permissions'])
                                ? count($_SESSION['active_permissions'])
                                : 0;
                        }*/
                        $etkinlikler = [];

                        // 1 saat kontrolü (id=1 üzerinden bakıyoruz)
                        $check = $dba->query("SELECT created_at FROM etkinlik_ve_duyuru WHERE id=1")->fetch_assoc();
                        $lastFetch = $check['created_at'] ?? null;
                        $needsUpdate = false;

                        if ($lastFetch) {
                            $diff = time() - strtotime($lastFetch);
                            if ($diff > 7200) { // 2 saatten küçükse API çağrısı yapma
                                $needsUpdate = true;
                            }
                        } else {
                            // tablo boşsa ilk kez doldur
                            $needsUpdate = true;
                        }

                        if ($needsUpdate) {
                            $apiUrl = "https://www.cankaya.bel.tr/api/v1/sliders/active"; // ✅ Yeni servis URL
                            $apiKey = "ck_et9nT91y4g1TImI40UDmsvxZFQMMiyosgFgxrmda";
                            try {
                                $ch = curl_init();
                                curl_setopt($ch, CURLOPT_URL, $apiUrl);
                                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                                    "Content-Type: application/json",
                                    "x-api-key: $apiKey"
                                ]);
                                curl_setopt($ch, CURLOPT_TIMEOUT, 5);
                                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
                                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);

                                $response = curl_exec($ch);
                                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                                curl_close($ch);

                                if ($response && $httpCode === 200) {
                                    $result = json_decode($response, true);

                                    if (!empty($result['data']['data']) && is_array($result['data']['data'])) {
                                        $etkinlikler = $result['data']['data'];

                                        // Eski kayıtları sil
                                        $dba->query("TRUNCATE TABLE etkinlik_ve_duyuru");

                                        // Yeni kayıtları ekle
                                        $stmt = $dba->prepare("INSERT IGNORE INTO etkinlik_ve_duyuru (etk_id, title, direct_link, image_url, publish_at) VALUES (?, ?, ?, ?, ?)");
                                        foreach ($etkinlikler as $e) {
                                            $stmt->bind_param("issss", $e['id'], $e['title'], $e['direct_link'], $e['image']['url'], date("Y-m-d H:i:s", strtotime($e['created_at'])));
                                            $stmt->execute();
                                        }
                                        $stmt->close();
                                    }
                                }
                            } catch (\Exception $e) {
                                // hata durumunda mevcut kayıtlar kullanılacak
                            }
                        }
                        // DB’den oku
                        $res = $dba->query("SELECT * FROM etkinlik_ve_duyuru ORDER BY publish_at DESC LIMIT 2");
                        $etkinlikler = [];
                        while ($row = $res->fetch_assoc()) {
                            $etkinlikler[] = $row;
                        }
                        ?>
                        <div class="panel-heading" style="text-transform: uppercase; display: flex; align-items: center; justify-content: space-between;padding: 5px 5px 5px 10px !important; color: #ffffff; background-color: #454444; border-color: #454444; height: 40px">
                            <h4 class="panel-title" style="display:inline-block; margin:0;">
                                <i class="fa fa-bullhorn"></i> Etkinlik ve Duyurular
                            </h4>

                            <div style="display: flex; gap:5px;">
                                <a href="etkinlik-duyuru" class="btn bg-olive" style="color:white; font-size:10px;">
                                    <i class="fa fa-plus"></i> Tümünü Gör
                                </a>
                            </div>
                            <?php if ($_SESSION['etkinlik_duyuru'] == 1 || $_SESSION['admin'] == 1) { ?>
                            <?php } ?>
                        </div>

                        <div class="panel-body" style="padding: 9px">
                            <div class="row">
                                <?php foreach ($etkinlikler as $etkinlik): ?>
                                    <?php
                                    $image = !empty($etkinlik['image_url']) ? $etkinlik['image_url'] : 'img/default.jpg';
                                    $title = htmlspecialchars($etkinlik['title']);
                                    $slug = slugify($title);
                                    $link = htmlspecialchars($etkinlik['direct_link']);
                                    $id = "etk_" . $etkinlik['id'];
                                    ?>
                                    <div class="col-lg-6 col-md-6 col-sm-6 col-xs-12">
                                        <div class="thumbnail position-relative" style="height:200px; overflow:hidden; cursor:pointer;" data-toggle="modal" data-target="#<?= $id ?>">
                                            <img src="<?= $image ?>" alt="<?= $title ?>" style="width:auto; height:100%; object-fit:cover; border-radius: 5px">
                                            <div class="overlay text-center" style="position:absolute; top:50%; left:50%;
                                transform:translate(-50%, -50%); background:rgba(0,0,0,0.5); color:#fff;
                                font-weight:bold; font-size:16px; padding:5px 10px; border-radius:3px;
                                opacity:0; transition:opacity 0.3s; white-space:nowrap;">
                                                Detay...
                                            </div>

                                            <div class="caption" style="
                                                            width:83%;
                                                            position:absolute;
                                                            bottom:5px;
                                                            left:50%;
                                                            transform:translateX(-50%);
                                                            background:rgba(255,255,255,0.8);
                                                            font-size:12px;
                                                            font-weight:600;
                                                            padding:3px 8px;
                                                            border-radius:3px;
                                                            box-sizing:border-box;
                                                            text-align:center;
                                                            max-width:90%;
                                                            word-wrap: break-word;
                                                            white-space: normal;
                                                            overflow:hidden;">
                                                <?= $title ?>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Modal -->
                                    <div class="modal fade" id="<?= $id ?>" tabindex="-1" role="dialog" aria-hidden="true">
                                        <div class="modal-dialog modal-lg" style="justify-content: center; display: flex; width: 1200px;">
                                            <div class="modal-content">
                                                <div class="modal-header text-right">
                                                    <button type="button" class="btn btn-default btn-sm" data-dismiss="modal">Kapat</button>
                                                </div>
                                                <div class="modal-body text-center" style="padding:10px;">
                                                    <a href="<?= $etkinlik['direct_link'] ?>" target="_blank">
                                                        <img src="<?= $image ?>" alt="<?= $title ?>" style="max-width:100%; max-height:80vh; object-fit:contain; border-radius: 5px">
                                                    </a>
                                                    <div style="margin-top:10px; font-weight:bold;">
                                                        <h4 style="font-weight:bold;"><?=$title?></h4>
                                                        <a href="<?= $etkinlik['direct_link'] ?>" target="_blank" class="btn btn-success btn-sm" style="margin-top:10px;">
                                                            <i class="fa fa-external-link"></i> Detaya Git
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <script>
                            $(document).ready(function(){
                                $('.thumbnail').hover(function(){
                                    $(this).find('.overlay').css('opacity', '1');
                                }, function(){
                                    $(this).find('.overlay').css('opacity', '0');
                                });
                            });
                        </script>
                    </div>
                    <!--Yemek Menüsü-->
                    <div class="panel panel-warning" style="margin-bottom: 8px; border: 0;">
                        <?php
                        $bugun = date('Y-m-d');
                        $turkceGunler = ['Pazartesi','Salı','Çarşamba','Perşembe','Cuma','Cumartesi','Pazar'];
                        $gunAdi = $turkceGunler[date('N') - 1];

                        // Verileri çek
                        $tarih = addslashes($bugun); // özel karakterleri kaçırır
                        $query = $dba->query("SELECT yemek, kalori FROM yemekler WHERE tarih='$tarih'");
                        ?>
                        <div class="panel-heading" style="text-transform: uppercase; display: flex; align-items: center; justify-content: space-between;padding: 5px 5px 5px 10px !important; color: #ffffff; background-color: #454444; border-color: #454444; height: 40px">
                            <h4 class="panel-title" style="display:inline-block; margin:0;">
                                <i class="fa fa-cutlery"></i> &nbsp;Yemek Menüsü - <?= htmlspecialchars(global_date_to_tr($tarih)); ?>
                            </h4>
                            <div style="display: flex; gap:5px;">
                                <?php if ($_SESSION['yemekhane']==1 OR $_SESSION['admin']==1) { ?>
                                    <a href="6-yemekhane" class="btn bg-olive" style="color:white; font-size:10px; /*border-color: white*/">
                                        <i class="fa fa-download"></i> &nbsp;Yemek Listesi Ekle
                                    </a>
                                <?php } ?>
                                <a href="yemek-listesi" class="btn bg-olive" style="color:white; font-size:10px;">
                                    <i class="fa fa-plus"></i> &nbsp;Haftalık Liste
                                </a>
                            </div>
                        </div>
                        <div class="panel-body" style="padding:0;">
                            <table class="table table-striped" style="margin:0;">
                                <tbody>
                                <?php
                                $i = 1;
                                if (!empty($query)):
                                    foreach ($query as $row): ?>
                                        <tr>
                                            <td style="padding: 7px; "><?php echo htmlspecialchars($row['yemek'] ?? ''); ?></td>
                                            <td class="kalori" style="padding: 5px; width:80px; text-align:right;"><?php echo htmlspecialchars($row['kalori'] ?? ''); ?> kcal</td>
                                            <?php if($i==1): ?>
                                                <td rowspan="6" id="toplam_kalori" style="text-align:center;  font-weight:bold; width: 100px"></td>
                                            <?php endif; ?>
                                        </tr>
                                        <?php
                                        $i++;
                                    endforeach;
                                    while ($i <= 5): ?>
                                        <tr>
                                            <td style="padding: 7px; height: 33px;"></td>
                                            <td style="text-align:right;padding: 5px;">&nbsp;</td>
                                        </tr>
                                        <?php
                                        $i++;
                                    endwhile; ?>
                                    <tr style="font-size: 10px">
                                        <td colspan="2" style="padding: 0 7px">
                                            <span> * 1 Adet Roll Ekmek : 128 kcal &nbsp;</span>
                                            <span>&nbsp; * 1 Adet Kepekli Ekmek : 108 kcal </span>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="3" style="text-align:center; color:red;">Kayıt bulunamadı.</td>
                                    </tr>
                                <?php endif; ?>
                                </tbody>

                            </table>
                        </div>
                        <script>
                            document.addEventListener("DOMContentLoaded", function() {
                                let toplam = 0;
                                document.querySelectorAll(".kalori").forEach(td => {
                                    const text = td.textContent.replace("kcal", "").trim();
                                    const value = parseFloat(text);
                                    if (!isNaN(value)) toplam += value;
                                });

                                const target = document.getElementById("toplam_kalori");
                                if (target) {
                                    target.innerHTML = `
                                                        <div style=" display:flex; flex-direction:column;">
                                                            <span style="text-decoration: underline">Toplam Kalori: </span>
                                                            <strong>${toplam.toFixed(0)} kcal</strong>
                                                        </div>
                                                    `;
                                }
                            });
                        </script>

                    </div>
                    <!--Giriş-Çıkış-->
                    <div class="panel panel-danger" style="background-color: #f5f5f5 !important; border: 0;">
                        <?php
                        /*echo $personelTC;*/
                        // 1️⃣ DB'den son 5 günlük veriyi çek
                        $sonuclar = $dba->query("SELECT * FROM pdks_logs WHERE ldap_username='$ldap_username' ORDER BY date DESC");
                        $existingData = $sonuclar->fetch_all(MYSQLI_ASSOC);

                        // 2️⃣ Bugünün tarihi
                        $today = date('Y-m-d');

                        // 3️⃣ Bugün için veri DB'de var mı?
                        $todayExists = false;
                        foreach ($existingData as $row) {
                            if ($row['date'] === $today) {
                                $todayExists = true;
                                break;
                            }
                        }

                        // 4️⃣ Eğer bugün için DB'de veri yoksa, API çağrısı yap
                        if (!$todayExists) {
                            $apiUrl = "https://pdks.cankaya.bel.tr/api/pdksModule/PdksActivity/GetPdksPersonelFirstEnterLastExitLog";
                            $accessToken = "5c84a43b-0afc-49b5-91fd-e647df2b87f8";

                            // Bugün dahil son 5 iş gününü oluştur
                            /*$dateList = [];
                            $current = strtotime('today');
                            $count = 0;
                            while ($count < 5) {
                                $dayOfWeek = date('N', $current);
                                if ($dayOfWeek < 6) { // Pazartesi-Cuma
                                    $dateList[] = date('Y-m-d', $current);
                                    $count++;
                                }
                                $current = strtotime("-1 day", $current);
                            }
                            $dateList = array_reverse($dateList); // Tarihleri küçükten büyüğe sırala

                            $start = $dateList[0];
                            $end = end($dateList);*/

                            // Bugün dahil son 5 günü oluştur
                            $dateList = [];
                            $current = strtotime('today');

                            for ($i = 0; $i < 5; $i++) {
                                $dateList[] = date('Y-m-d', $current);
                                $current = strtotime("-1 day", $current);
                            }

                            $dateList = array_reverse($dateList); // küçükten büyüğe sırala

                            $start = $dateList[0];
                            $end = end($dateList);
                            /*if ($admin==1) {
                                echo "$apiUrl?Start=$start&End=$end&access_token=$accessToken&IncludeNonworkerPersonels=true&personelTckimlikno=$personelTC";
                            }*/

                            $ch = curl_init();
                            curl_setopt($ch, CURLOPT_URL, "$apiUrl?Start=$start&End=$end&access_token=$accessToken&IncludeNonworkerPersonels=true&personelTckimlikno=$personelTC");
                            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
                            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 1);
                            $response = curl_exec($ch);

                            /*if (curl_errno($ch)) {
                                die("cURL Hatası: " . curl_error($ch));
                            }*/
                            curl_close($ch);

                            if ($response !== false) {
                                $data = json_decode($response, true);
                                if (!empty($data) && is_array($data)) {
                                    // 6️⃣ DB'deki tüm eski veriyi sil
                                    $dba->query("DELETE FROM pdks_logs WHERE ldap_username='$ldap_username'");

                                    // 7️⃣ Yeni veriyi toplu olarak DB'ye ekle
                                    $insertedDates = []; // aynı gün 2 kez insert olmasını engelle

                                    foreach ($data as $entry) {
                                        $dateKey = substr($entry['date'], 0, 10);
                                        $enterTime = !empty($entry['enterDate']) ? (new DateTime($entry['enterDate']))->format('H:i:s') : null;
                                        $exitTime = !empty($entry['exitDate']) ? (new DateTime($entry['exitDate']))->format('H:i:s') : null;

                                        // Eğer bu tarih zaten eklendiyse atla
                                        if (in_array($dateKey, $insertedDates)) {
                                            continue;
                                        }

                                        // Eğer giriş ve çıkış saatleri tamamen boşsa atla
                                        if (empty($enterTime) && empty($exitTime)) {
                                            continue;
                                        }

                                        $stmt = $dba->prepare("INSERT INTO pdks_logs (ldap_username, personelSicilNo, date, enterTime, exitTime) VALUES (?,?,?,?,?)");
                                        $stmt->bind_param("sisss", $ldap_username, $personelTC, $dateKey, $enterTime, $exitTime);
                                        $stmt->execute();
                                        $stmt->close();

                                        $insertedDates[] = $dateKey;
                                    }
                                }
                            }
                        }

                        // 8️⃣ DB'den mevcut veriyi al
                        $sonuclar = $dba->query("SELECT * FROM pdks_logs WHERE ldap_username='$ldap_username' ORDER BY date DESC");
                        $existingData = [];
                        while($row = $sonuclar->fetch_assoc()) {
                            $existingData[$row['date']] = $row;
                        }

                        // Son 5 günü oluştur (hafta sonunu dahil et, toplam 5 gün)
                        $today = strtotime('today');
                        $dateList = [];
                        $count = 0;
                        while ($count < 5) {
                            $dateStr = date('Y-m-d', $today);
                            array_unshift($dateList, $dateStr);
                            $today = strtotime("-1 day", $today);
                            $count++;
                        }
                        ?>
                        <div class="panel-heading" style="display: flex; align-items: center; justify-content: space-between;padding: 5px 5px 5px 10px !important; color: #ffffff; background-color: #454444; border-color: #454444; height: 40px">
                            <h4 class="panel-title" style="display:inline-block; text-transform: uppercase; margin:0;">
                                <i class="fa fa-sign-in"></i>&nbsp; Son 5 Günlük Giriş - Çıkış Verileriniz
                            </h4>
                            <div style="display: flex; gap:5px;">
                                <?php if ($_SESSION['admin']==1) { ?>
                                    <button id="deleteTodayBtn" class="btn btn-danger" style="font-size:10px;">
                                        <i class="fa fa-trash"></i> &nbsp;BUGÜNÜ SİL
                                    </button>
                                <?php } ?>
                                <a href="<?=$_SESSION['ldap_username']?>-giris-cikis" class="btn bg-olive" style="color:white; font-size:10px;">
                                    <i class="fa fa-plus"></i> &nbsp;DAHA FAZLA
                                </a>
                            </div>
                        </div>
                        <div id="girisCikisPanel" class="panel-collapse collapse in">
                            <div class="panel-body" style="padding: 0;">
                                <div class="table-responsive">
                                    <table class="table table-borderless" style="margin-bottom:0px !important;">
                                        <!--<thead>
                                        <tr>
                                            <th>Tarih</th>
                                            <th>Gün</th>
                                            <th>Giriş Saati</th>
                                            <th>Çıkış Saati</th>
                                        </tr>
                                        </thead>-->
                                        <tbody>
                                        <?php foreach ($dateList as $date): ?>
                                            <?php
                                            if (isset($existingData[$date])) {
                                                $entry = $existingData[$date];
                                                $enterTime = !empty($entry['enterTime']) ? (new DateTime($entry['enterTime']))->modify('+3 hours')->format('H:i:s') : "-";
                                                $exitTime  = !empty($entry['exitTime'])  ? (new DateTime($entry['exitTime']))->modify('+3 hours')->format('H:i:s') : "-";

                                                /*if ($enterTime !== "-" && $exitTime !== "-") {
                                                    // Giriş saati, çıkış saatinden büyükse yer değiştir
                                                    if (strtotime($enterTime) > strtotime($exitTime)) {
                                                        [$enterTime, $exitTime] = [$exitTime, $enterTime];
                                                    }
                                                }*/
                                            } else {
                                                $enterTime = "-";
                                                $exitTime = "-";
                                            }
                                            // Hafta sonu kontrolü
                                            $dayOfWeek = date('N', strtotime($date));
                                            $rowClass = ($dayOfWeek >= 6) ? 'style="background-color: #efefef;"' : '';
                                            ?>
                                            <tr <?= $rowClass ?>>
                                                <td><?= htmlspecialchars(global_date_to_tr($date)) ?></td>
                                                <td><?= turkceGun($date) ?></td>
                                                <td align="center"><?= htmlspecialchars($enterTime) ?></td>
                                                <td align="center"><?= htmlspecialchars($exitTime) ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                        <script>
                            <?php if ($_SESSION['admin']==1) { ?>
                            $(document).ready(function () {
                                $("#deleteTodayBtn").click(function () {
                                    if (!confirm("Bugüne ait tüm giriş-çıkış kayıtları silinsin mi?")) {
                                        return;
                                    }
                                    $.ajax({
                                        url: "ldap/bugun_sil.php",
                                        type: "POST",
                                        data: { action: "delete_today" },
                                        success: function (response) {
                                            try {
                                                let res = JSON.parse(response);
                                                if (res.status === "success") {
                                                    alert("Bugüne ait kayıtlar silindi.");
                                                    // Tabloyu güncellemek için bugünkü satırı bulup boşaltabiliriz
                                                    $("tbody tr").each(function(){
                                                        if ($(this).find("td:first").text().includes(res.today)) {
                                                            $(this).find("td:nth-child(3)").text("-");
                                                            $(this).find("td:nth-child(4)").text("-");
                                                        }
                                                    });
                                                } else {
                                                    alert("Hata: " + res.message);
                                                }
                                            } catch (e) {
                                                alert("Beklenmeyen cevap: " + response);
                                            }
                                        },
                                        error: function () {
                                            alert("Sunucu hatası oluştu.");
                                        }
                                    });
                                });
                            });
                            <?php } ?>
                        </script>
                    </div>
                </section>
            </div>
        <?php } ?>
    </section>
</aside>
<?php require_once("inc/footer.php"); ?>