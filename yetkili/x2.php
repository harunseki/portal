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
$tablo="yetkili";
$tablo1="5257debfbc904d2dbec03afd4e971f1cb69c6b3c";
$tablo2="1700728637";
if(isset($_GET['delete'])){
    $delete=$purifier->purify(rescape((int)$_GET['delete']));
    $q=$dba->query("UPDATE $tablo SET yetkili_durumu=0 WHERE id='$delete' ");

    if($dba->affected_rows()>0) alert_success("Yetkili sistemden kaldırılmıştır.");
}
if (empty($_GET['edit'])) { ?>
    <div class="row">
        <div class="col-md-12">
            <div class="box box-success">
                <div class="box-header">
                    <h3 class="box-title">Yetkili Düzenle</h3>
                    <div style="float: right; margin: 10px">
                        <a href="<?= strip($file) ?>.php" class="btn btn-danger" style="color: white">
                            <i class="fa fa-plus"></i> <strong>Yetkili Ekle</strong>
                        </a>
                    </div>
                </div>
                <div class="box-body table-responsive">
                    <table id="example1" class="table table-bordered table-striped text-center align-middle">
                        <thead>
                        <tr>
                            <th>Adı</th>
                            <th>Soyadı</th>
                            <th>LDAP Kullanıcı Adı</th>
                            <th>Admin</th>
                            <th>Şifre</th>
                            <th>Yemekhane</th>
                            <th>Etkinlik</th>
                            <th>Yetkili İşl.</th>
                            <th>QR Kod</th>
                            <th>Formlar</th>
                            <th>Pop-up</th>
                            <th>İşlemler</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php
                        $q = $dba->query("SELECT * FROM yetkili WHERE yetkili_durumu=1 ORDER BY adi ASC");
                        while ($row = $dba->fetch_assoc($q)) { ?>
                            <tr data-id="<?= (int)$row['id'] ?>">
                                <td style="align-content: center;"><?= strip($row['adi']) ?></td>
                                <td style="align-content: center;"><?= strip($row['soyadi']) ?></td>
                                <td style="align-content: center;"><?= strip($row['username']) ?></td>

                                <?php
                                $yetki_alanlari = ['admin','sifre','yemekhane','etkinlik_duyuru','yetkili_islemleri','qrcode','formlar','popup_yonetim'];
                                foreach ($yetki_alanlari as $alan):
                                    $aktif = $row[$alan] ? 'aktif' : 'pasif';
                                    $renk = $row[$alan] ? '#28a745' : '#dc3545';
                                    ?>
                                    <td style="align-content: center;">
                                        <div class="yetki-btn"
                                             data-field="<?= $alan ?>"
                                             data-status="<?= $aktif ?>"
                                             style="cursor:pointer; background:<?= $renk ?>; color:white; border-radius:5px; padding:5px 8px; font-size: 12px; font-weight:600;">
                                            <?= $row[$alan] ? 'AKTİF' : 'PASİF' ?>
                                        </div>
                                    </td>
                                <?php endforeach; ?>

                                <td>
                                    <a href="<?= strip($file) ?>.php?x=2&edit=<?= strip((int)$row['id']) ?>" class="btn btn-default">
                                        <img src="img/edit.png" />
                                    </a>
                                    <a href="<?= strip($file) ?>.php?x=2&delete=<?= strip((int)$row['id']) ?>"
                                       class="btn btn-default"
                                       onclick="return confirm('Silmek istediğinize emin misiniz?');">
                                        <img src="img/delete1.png" style="height: 15px"/>
                                    </a>
                                </td>
                            </tr>
                        <?php } ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(function(){
            $('.yetki-btn').on('click', function(){
                const el = $(this);
                const field = el.data('field');
                const id = el.closest('tr').data('id');
                const ldap_username = "<?= $ldap_username ?>";
                const ip = "<?= $ip ?>";
                const personelTC = "<?= $personelTC ?>";

                $.post('yetkili/yetki_toggle.php', { id, field, ldap_username, ip, personelTC }, function(resp){
                    if(resp.success){
                        const yeniRenk = resp.yeni_durum == 1 ? '#28a745' : '#dc3545';
                        const yeniYazi = resp.yeni_durum == 1 ? 'AKTİF' : 'PASİF';
                        el.css('background', yeniRenk).text(yeniYazi);

                        if(resp.yeni_durum == 1){
                            toastr.success(field.toUpperCase() + ' yetkisi aktif edildi.');
                        } else {
                            toastr.warning(field.toUpperCase() + ' yetkisi pasif hale getirildi.');
                        }
                    } else {
                        alert('Hata: ' + resp.message);
                    }
                }, 'json');
            });
        });
    </script>
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
        $popup_yonetim = $purifier->purify(rescape($_POST['popup_yonetim'])) ? 1 : 0;
        /*print_r($_POST);
        exit();*/

        $hata = [];
        if (empty($adi)) $hata[] = "<p>Yetkili adı giriniz</p>";
        if (empty($soyadi)) $hata[] = "<p>Yetkili soyadı giriniz</p>";
        if (empty($mudurluk)) $hata[] = "<p>Çalıştığı müdürlük seçiniz</p>";

        if (!empty($hata)) alert_danger($hata);
        else {
            $q = $dba->query("UPDATE yetkili SET adi='$adi', soyadi='$soyadi',cep_telefonu='$cep_telefonu', mudurluk='$mudurluk', yetkili_durumu='$yetkili_durumu', admin='$admin', sifre='$sifre', yemekhane='$yemekhane', etkinlik_duyuru='$etkinlik_duyuru', yetkili_islemleri='$yetkili_islemleri', qrcode='$qrcode', formlar='$formlar', popup_yonetim='$popup_yonetim' WHERE id='$id' ");

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
                    <h3 class="box-title">PROFİLİM</h3>
                </div>
                <form role="form" action="<?= htmlspecialchars($_SERVER['REQUEST_URI'], ENT_QUOTES, 'UTF-8') ?>" method="post" enctype="multipart/form-data">
                    <div class="row">
                        <div class="col-md-6">
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
                        </div>
                        <div class="col-md-6">
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
                                    <div class="form-group">
                                        <label>
                                            <input type="checkbox" name="popup_yonetim" class="perm-checkbox" value="1" <?= strip($row['popup_yonetim'])==1 ? 'checked':'' ?>/> &nbsp;Popup Yönetim Yetkisi
                                        </label>
                                    </div>
                                </div>
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