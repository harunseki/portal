<?php
if (empty($_SESSION['yetkili_islemleri']) AND empty($_SESSION['admin']) AND empty($admin)) {
    // Yetkisiz erişim
    http_response_code(403); // 403 Forbidden
    ?>
    <style>
        .error-box {
            background: #fff;
            padding: 40px;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
            text-align: center;
        }
        .error-box h1 {
            font-size: 48px;
            margin-bottom: 10px;
            color: #e74c3c;
        }
        .error-box p {
            font-size: 18px;
            margin-bottom: 20px;
        }
        .error-box a {
            display: inline-block;
            padding: 10px 20px;
            background-color: rgba(6, 90, 40);
            color: #fff;
            text-decoration: none;
            border-radius: 5px;
        }
        .error-box a:hover {
            background-color: rgba(6, 90, 40);;
        }
    </style>
    <div class="error-box">
        <h1>403</h1>
        <p>Bu sayfaya erişim yetkiniz yok.</p>
        <a href="index.php">Ana Sayfaya Dön</a>
    </div>
    <?php
    exit();
}
if ($_SERVER['REQUEST_METHOD'] == "POST") {

    $adi = trim($_POST['adi']);
    $soyadi = trim($_POST['soyadi']);
    $username = trim($_POST['username']);
    $mudurluk = $_POST['mudurluk'];
    $perms = $_POST['perm'] ?? [];  // Yeni sistem için asıl izinler

    $errors = [];
    if (!$adi) $errors[] = "Ad giriniz";
    if (!$soyadi) $errors[] = "Soyad giriniz";
    if (!$mudurluk) $errors[] = "Müdürlük seçiniz";

    if ($errors) {
        alert_danger($errors);
    }
    else {
        if (empty($username)) $username = slugify($adi . $soyadi);
        $q = $dba->prepare("INSERT INTO yetkili (adi, soyadi, username, kayit_yetkili, yetkili_durumu, mudurluk) VALUES (?, ?, ?, '0', '1', ?)");
        $q->bind_param("sssi", $adi, $soyadi, $username, $mudurluk);
        $q->execute();

        $kullanici_id = $dba->insert_id();

        // 2) yetkili_moduller tablosuna izinleri yaz
        foreach ($perms as $yetki_key => $deger) {
            $deger = ($deger == 1 ? 1 : 0);
            $dba->query("INSERT INTO yetkili_moduller (kullanici_id, yetki_key, deger) VALUES ($kullanici_id, $yetki_key, $deger)");
        }
        alert_success("Yetkili başarıyla eklendi.");
    }
}
?>
<div class="row">
    <div class="col-md-12">
        <form role="form" action="<?= htmlspecialchars($_SERVER['REQUEST_URI'], ENT_QUOTES, 'UTF-8') ?>" method="post" enctype="multipart/form-data">
            <div class="box box-success">
                <div class="box-header">
                    <h3 class="box-title">YETKİLİ EKLE</h3>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="box-body">
                            <div id="statusab">
                                <div class="form-group">
                                    <label>Ad</label>
                                    <input type="text" class="form-control" name="adi" id="adi"/>
                                </div>
                                <div class="form-group">
                                    <label>Soyad</label>
                                    <input type="text" class="form-control" name="soyadi" id="soyadi"/>
                                </div>
                            </div>
                            <div class="form-group">
                                <label>LDAP Username</label>
                                <input type="text" class="form-control" name="username" id="username" placeholder="Eğer LDAP Kullanıcı Adı adsoyad Birleşiminden Farklı İse Bu Alana Yazınız, Yoksa Boş Bırakınız.." autocomplete="off"/>
                            </div>
                            <div class="form-group">
                                <label>Çalıştığı Müdürlük</label>
                                <select name="mudurluk" id="mudurluk" class="form-control" required>
                                    <option value="">..:: Seçiniz ::..</option>
                                    <?php
                                    $q=$dba->query("SELECT mudurlukler.id, mudurlukler.mudurluk FROM mudurlukler WHERE mudurlukler.durum='1' Order By mudurluk ASC ");
                                    while ($rowyetkili=$dba->fetch_assoc($q)) { ?>
                                        <option value="<?=strip($rowyetkili['id'])?>"><?=strip($rowyetkili['mudurluk'])?></option>
                                    <?php } ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="box-header">
                            <h3 class="box-title" style="text-decoration: underline">Menü Yetkilerini Seçiniz</h3>
                        </div>
                        <div class="box-body">
                            <?php
                            $q = $dba->query("SELECT * FROM yetki_tanimlari ORDER BY id ASC");
                            while ($r = $dba->fetch_assoc($q)) {
                                ?>
                                <div class="form-group">
                                    <label>
                                        <input type="checkbox" class="perm-checkbox" name="perm[<?= $r['id'] ?>]" value="1"/>
                                        &nbsp;<?= htmlspecialchars($r['yetki_label']) ?>
                                    </label>
                                </div>
                            <?php } ?>
                        </div>
                    </div>
                </div>
                <div class="box-footer">
                    <button type="submit" class="btn btn-success">Kaydet</button>
                </div>
                <script>
                    $(function(){
                        var $admin = $('#adminCheck');
                        var $perms = $('.perm-checkbox');

                        // Admin seçildiğinde tüm alt izinleri seç
                        $admin.on('ifChecked', function(){
                            $perms.iCheck('check');
                        });

                        // Admin kaldırıldığında artık alt izinleri değiştirmiyoruz
                        // sadece admin checkbox işareti kalkacak
                        // $admin.on('ifUnchecked', ...) kısmı kaldırıldı

                        // Alt yetkilerden biri değişince admin durumunu güncelle
                        $perms.on('ifChanged', function(){
                            var allChecked = $perms.filter(':checked').length === $perms.length;
                            if(allChecked){
                                $admin.iCheck('check');
                            } else {
                                $admin.iCheck('uncheck');
                            }
                        });
                    });
                </script>
            </div>
        </div>
        </form>
    </div>
</div>
