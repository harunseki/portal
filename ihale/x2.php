<?php
// Kullanıcının yetkili olduğu ihale dosyalarını çek
$stmt = $dba->prepare("
    SELECT d.id, d.baslik, d.dosya_adi, d.created_at
    FROM ihale_dosyalar d
    INNER JOIN ihale_dosya_personel dp ON d.id = dp.dosya_id
    WHERE dp.personelTC = ?
    ORDER BY d.created_at DESC
");
$stmt->bind_param("s", $personelTC);
$stmt->execute();
$result = $stmt->get_result();

$dosyalar = [];
while ($row = $result->fetch_assoc()) {
    $dosyalar[] = $row;
}
$stmt->close();

if (empty($_GET['edit'])) { ?>
    <section class="content-header">
        <div class="row">
            <div class="col-xs-6">
                <h2>İhale Dosyalarım</h2>
            </div>
        </div>
    </section>
    <section class="content">
        <div class="row" style="margin-top:10px;">
            <?php if (count($dosyalar) > 0): ?>
                <?php foreach ($dosyalar as $d):
                    // uzantıya göre ikon seç
                    $ext = strtolower(pathinfo($d['dosya_adi'], PATHINFO_EXTENSION));
                    $icon = 'fa-file-o';
                    if ($ext === 'pdf') $icon = 'fa-file-pdf-o';
                    elseif (in_array($ext, ['doc','docx'])) $icon = 'fa-file-word-o';
                    elseif (in_array($ext, ['xls','xlsx'])) $icon = 'fa-file-excel-o';

                    $filePath = "backup/ihaleler/" . urlencode($d['dosya_adi']);
                    ?>
                    <div class="col-md-3 file-card" id="file_<?= md5($d['id']) ?>" style="margin-bottom:15px;">
                        <div class="box box-success" style="height:130px; display:flex; flex-direction:column; justify-content:space-between;">
                            <div class="box-header">
                                <h3 class="box-title" style="word-break: break-word; font-size:14px;">
                                    <i class="fa <?= $icon ?>"></i>
                                    <?= htmlspecialchars($d['baslik']) ?>
                                </h3>
                            </div>
                            <div class="card-body" style="padding:10px; display:flex; justify-content:space-between; align-items:center;">
                                <a href="<?= $filePath ?>" download class="btn btn-success btn-sm">
                                    <i class="fa fa-download"></i> İndir
                                </a>
                                <?php if($_SESSION['formlar']==1 || $_SESSION['admin']==1): ?>
                                    <button class="btn btn-danger btn-sm silBtn" data-id="<?= $d['id'] ?>" data-name="<?= htmlspecialchars($d['baslik']) ?>">
                                        <i class="fa fa-trash"></i> Sil
                                    </button>
                                <?php endif; ?>
                            </div>
                            <small style="color:#888; padding:0 10px 5px;">
                                Yüklendi: <?= htmlspecialchars($d['created_at']) ?>
                            </small>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-md-12"><p>Hiç yetkili dosya yok.</p></div>
            <?php endif; ?>
        </div>
    </section>
    <?php
}
else if (!empty($_GET['edit'])) {
    $id=$purifier->purify(rescape((int)$_GET['edit']));

    if ($_SERVER['REQUEST_METHOD'] == "POST") {
        $adi = $purifier->purify(rescape($_POST['adi']));
        $soyadi = $purifier->purify(rescape($_POST['soyadi']));
        $username = $purifier->purify(rescape($_POST['username']))?:correctlink($adi.$soyadi);
        $cep_telefonu = $purifier->purify(rescape($_POST['cep_telefonu']));
        $mudurluk = $purifier->purify(rescape($_POST['mudurluk']));
        $yetkili_durumu = $purifier->purify(rescape($_POST['yetkili_durumu'])) ? 1 : 0;
        $admin = $purifier->purify(rescape($_POST['admin'])) ? 1 : 0;
        $sifre = $purifier->purify(rescape($_POST['sifre'])) ? 1 : 0;
        $yemekhane = $purifier->purify(rescape($_POST['yemekhane'])) ? 1 : 0;
        $etkinlik_duyuru = $purifier->purify(rescape($_POST['etkinlik_duyuru'])) ? 1 : 0;
        $yetkili_islemleri = $purifier->purify(rescape($_POST['yetkili_islemleri'])) ? 1 : 0;
        $qrcode = $purifier->purify(rescape($_POST['qrcode'])) ? 1 : 0;
        $formlar = $purifier->purify(rescape($_POST['formlar'])) ? 1 : 0;
        /*print_r($_POST);
        exit();*/

        $hata = [];
        if (empty($adi)) $hata[] = "<p>Yetkili adı giriniz</p>";
        if (empty($soyadi)) $hata[] = "<p>Yetkili soyadı giriniz</p>";
        if (empty($mudurluk)) $hata[] = "<p>Çalıştığı müdürlük seçiniz</p>";

        if (!empty($hata)) alert_danger($hata);
        else {
            $q = $dba->query("UPDATE yetkili SET adi='$adi', soyadi='$soyadi',cep_telefonu='$cep_telefonu', mudurluk='$mudurluk', yetkili_durumu='$yetkili_durumu', admin='$admin', sifre='$sifre', yemekhane='$yemekhane', etkinlik_duyuru='$etkinlik_duyuru', yetkili_islemleri='$yetkili_islemleri', qrcode='$qrcode', formlar='$formlar' WHERE id='$id' ");

            if ($dba->affected_rows() > 0) {
                alert_success("Yetkili başarıyla güncellenmiştir.");
                $dba->addLog($ip, $ldap_username, $personelTC, "create", "Yetkili bilgileri güncellendi : ".$username);
            }
        }
    }
    $q = $dba->query("SELECT * FROM yetkili WHERE id='$id' ");
    $row = $dba->fetch_assoc($q);
    ?>
    <div class="row">
        <div class="col-md-12">
            <div class="box box-success">
                <div class="box-header">
                    <h3 class="box-title">YETKİLİ DÜZENLE</h3>
                </div>
                <form role="form" action="<?= htmlspecialchars($_SERVER['REQUEST_URI'], ENT_QUOTES, 'UTF-8') ?>" method="post" enctype="multipart/form-data">
                    <div class="box-body">
                        <div id="statusab">
                            <div class="form-group">
                                <label>Adı</label>
                                <input type="text" class="form-control" name="adi" id="adi"
                                       value="<?= strip($row['adi']) ?>"/>
                            </div>
                            <div class="form-group">
                                <label>Soyadi</label>
                                <input type="text" class="form-control" name="soyadi" id="soyadi"
                                       value="<?= strip($row['soyadi']) ?>"/>
                            </div>
                            <div class="form-group">
                                <label>LDAP Kullanıcı Adı</label>
                                <input type="text" class="form-control" name="username" id="username"
                                       value="<?= strip($row['username']) ?>"/>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Çalıştığı Müdürlük</label>
                            <select name="mudurluk" id="mudurluk" class="form-control chosen-select">
                                <option value="">..:: Seçiniz ::..</option>
                                <?php
                                $q=$dba->query("SELECT mudurlukler.id, mudurlukler.mudurluk FROM mudurlukler WHERE mudurlukler.durum='1' Order By mudurluk ASC ");
                                while ($rowyetkili=$dba->fetch_assoc($q)) { ?>
                                    <option value="<?=strip($rowyetkili['id'])?>" <?= strip($rowyetkili['id'])==strip($row['mudurluk']) ? 'selected':'' ?>><?=strip($rowyetkili['mudurluk'])?></option>
                                <?php } ?>
                            </select>
                        </div>
                        <hr>
                        <div class="form-group">
                            <label>Yetkili Durumu</label>
                            <select class="form-control" name="yetkili_durumu">
                                <option value="">..:: Yetkili Durumu Seçiniz ::..</option>
                                <?php
                                $qyetkili_durumu = $dba->query("SELECT * FROM yetkili_durumu");
                                while ($rowyetkili_durumu = $dba->fetch_assoc($qyetkili_durumu)) { ?>
                                    <option value="<?= strip($rowyetkili_durumu['id']) ?>" <?php if ($rowyetkili_durumu['id'] == $row['yetkili_durumu']) { ?> selected="selected" <?php } ?> ><?= strip($rowyetkili_durumu['yetkili_durumu']) ?></option>
                                <?php }  ?>
                            </select>
                        </div>
                    </div>
                    <div class="box-header">
                        <h3 class="box-title" style="text-decoration: underline">Menü Yetkilerini Seçiniz</h3>
                    </div>
                    <div class="box-body">
                        <div class="box-body">
                            <!-- Admin Checkbox -->
                            <div class="form-group">
                                <label>
                                    <input type="checkbox" id="adminCheck" name="admin" value="1" <?= ($row['admin']==1 ? 'checked' : '') ?>/> &nbsp;Admin Yetkisi
                                </label>
                            </div>
                            <hr style="margin: 5px">

                            <!-- Alt Yetkiler -->
                            <div class="form-group">
                                <label>
                                    <input type="checkbox" name="sifre" class="perm-checkbox" value="1" <?= strip($row['sifre'])==1 ? 'checked':'' ?>/> &nbsp;LDAP Şifre Sıfırlama Yetkisi
                                </label>
                            </div>
                            <div class="form-group">
                                <label>
                                    <input type="checkbox" name="yemekhane" class="perm-checkbox" value="1" <?= strip($row['yemekhane'])==1 ? 'checked':'' ?>/> &nbsp;Yemek Listesi Ekleme Yetkisi
                                </label>
                            </div>
                            <div class="form-group">
                                <label>
                                    <input type="checkbox" name="etkinlik_duyuru" class="perm-checkbox" value="1" <?= strip($row['etkinlik_duyuru'])==1 ? 'checked':'' ?>/> &nbsp;Etkinlik-Duru Ekleme-Silme Yetkisi
                                </label>
                            </div>
                            <div class="form-group">
                                <label>
                                    <input type="checkbox" name="yetkili_islemleri" class="perm-checkbox" value="1" <?= strip($row['yetkili_islemleri'])==1 ? 'checked':'' ?>/> &nbsp;Yetkili İşlemleri Yetkisi
                                </label>
                            </div>
                            <div class="form-group">
                                <label>
                                    <input type="checkbox" name="qrcode" class="perm-checkbox" value="1" <?= strip($row['qrcode'])==1 ? 'checked':'' ?>/> &nbsp;QR İşlemleri Yetkisi
                                </label>
                            </div>
                            <div class="form-group">
                                <label>
                                    <input type="checkbox" name="formlar" class="perm-checkbox" value="1" <?= strip($row['formlar'])==1 ? 'checked':'' ?>/> &nbsp;Form Ekleme Yetkisi
                                </label>
                            </div>
                        </div>
                    </div>
                    <div class="box-footer">
                        <button type="submit" class="btn btn-success">Kaydet</button>
                    </div>
                </form>
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
    </div>
<?php } ?>