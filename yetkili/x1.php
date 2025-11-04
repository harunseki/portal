<?php
if (empty($_SESSION['yetkili_islemleri']) AND empty($_SESSION['admin']) ) {
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
    $adi = $purifier->purify(rescape($_POST['adi']));
    $soyadi = $purifier->purify(rescape($_POST['soyadi']));
    $username = $purifier->purify(rescape($_POST['username']))?:correctlink($adi.$soyadi);
    $cep_telefonu = $purifier->purify(rescape($_POST['cep_telefonu']));
    $mudurluk = $purifier->purify(rescape($_POST['mudurluk']));
    $admin = $purifier->purify(rescape($_POST['admin'])) ? 1 : 0;
    $sifre = $purifier->purify(rescape($_POST['sifre'])) ? 1 : 0;
    $yemekhane = $purifier->purify(rescape($_POST['yemekhane'])) ? 1 : 0;
    $etkinlik_duyuru = $purifier->purify(rescape($_POST['etkinlik_duyuru'])) ? 1 : 0;
    $yetkili_islemleri = $purifier->purify(rescape($_POST['yetkili_islemleri'])) ? 1 : 0;
    $qrcode = $purifier->purify(rescape($_POST['qrcode'])) ? 1 : 0;
    $formlar = $purifier->purify(rescape($_POST['formlar'])) ? 1 : 0;
    $popup_yonetim = $purifier->purify(rescape($_POST['popup_yonetim'])) ? 1 : 0;

    $hata = [];
    $qc = $dba->query("SELECT id FROM yetkili WHERE username='$username' ");
    /*if ($dba->num_rows($qc) > 0) {
        $rowc = $dba->fetch_assoc($qc);
        $hata[] = "<p>Kullanıcı sistemde kayıtlı bulunmaktadır.</p>";
        $hata[] = "<a href='index.php?tablo=yetkili&x=2&edit=".$rowc['id']."'></a>";
    }*/
    if (empty($adi)) $hata[] = "<p>Yetkili adı giriniz</p>";
    if (empty($soyadi)) $hata[] = "<p>Yetkili soyadı giriniz</p>";
    if (empty($mudurluk)) $hata[] = "<p>Çalıştığı müdürlük seçiniz</p>";

    if (!empty($hata)) alert_danger($hata);
    else {
        $q = $dba->query("INSERT INTO yetkili (adi, soyadi, username, kayit_yetkili, yetkili_durumu, mudurluk, admin, sifre, yemekhane, etkinlik_duyuru, yetkili_islemleri, qrcode, formlar, popup_yonetim) VALUES ('$adi', '$soyadi', '$username','0', '1', '$mudurluk', '$admin', '$sifre', '$yemekhane', '$etkinlik_duyuru', '$yetkili_islemleri', '$qrcode', '$formlar', '$popup_yonetim') ");
        if ($dba->affected_rows() > 0) {
            alert_success("Yetkili başarıyla eklenmiştir.");

            $dba->addLog($ip, $ldap_username, $personelTC, "create", "Yeni yetkili eklendi : ".$username);
        }
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
                            <div class="form-group">
                                <label><input type="checkbox" id="adminCheck" name="admin" value="1"/> &nbsp;Admin </label>
                            </div><hr style="margin: 5px">
                            <div class="form-group">
                                <label><input type="checkbox" class="perm-checkbox" name="sifre" value="1"/> &nbsp;LDAP Şifre Sıfırlama Yetkisi </label>
                            </div>
                            <div class="form-group">
                                <label><input type="checkbox" class="perm-checkbox" name="yemekhane" value="1"/> &nbsp;Yemek Listesi Ekleme Yetkisi </label>
                            </div>
                            <div class="form-group">
                                <label><input type="checkbox" class="perm-checkbox" name="etkinlik_duyuru" value="1"/> &nbsp;Etkinlik-Duyuru Ekleme-Silme Yetkisi </label>
                            </div>
                            <div class="form-group">
                                <label><input type="checkbox" class="perm-checkbox" name="yetkili_islemleri" value="1"/> &nbsp;Yetkili İşlemleri Yetkisi </label>
                            </div>
                            <div class="form-group">
                                <label><input type="checkbox" class="perm-checkbox" name="qrcode" value="1"/> &nbsp;QR Oluşturma Yetkisi </label>
                            </div>
                            <div class="form-group">
                                <label><input type="checkbox" class="perm-checkbox" name="formlar" value="1"/> &nbsp;Form Ekleme Yetkisi </label>
                            </div>
                            <div class="form-group">
                                <label><input type="checkbox" class="perm-checkbox" name="popup_yonetim" value="1"/> &nbsp;Popup Yönetim Yetkisi </label>
                            </div>
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
