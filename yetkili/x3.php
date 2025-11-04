<?php
$tablo="yetkili";

if(isset($_GET['delete'])){
    $delete=$purifier->purify(rescape((int)$_GET['delete']));
    $q=$dba->query("UPDATE $tablo SET yetkili_durumu=0 WHERE id='$delete' ");

    if($dba->affected_rows()>0) alert_success("Yetkili sistemden kaldırılmıştır.");
}
if (!empty($_GET['edit'])) {
    $edit_ldap_username=$purifier->purify(rescape($_GET['edit']));
    $includeDisabled=$purifier->purify(rescape($_GET['includeDisabled']));

    if ($_SERVER['REQUEST_METHOD'] == "POST") {
        $name = $purifier->purify(rescape($_POST['name']));
        $mail = $purifier->purify(rescape($_POST['mail']));
        $telephonenumber = $purifier->purify(rescape($_POST['telephonenumber']));
        $ipphone = $purifier->purify(rescape($_POST['ipphone']));
        $info = $purifier->purify(rescape($_POST['info']));
        $department = $purifier->purify(rescape($_POST['department']));

        $hata = [];
        /*if (empty($ldap_username)) $hata[] = "<p>Kullanıcı adını giriniz</p>";*/
        /*if (empty($telephonenumber)) $hata[] = "<p>Cep Telefonunu giriniz</p>";*/
        /*if (empty($mail)) $hata[] = "<p>Eposta adresini giriniz</p>";*/
        /*if (empty($ipphone)) $hata[] = "<p>Dahili no giriniz</p>";*/

        if (!empty($hata)) alert_danger($hata);
        else {
            $ldap_host = "ldap://10.1.1.21";
            $ldap_port = 389;
            $ldap_user = "cankaya\\smsadmin";
            $ldap_pass = "Telefon01*";
            $ldap_dn   = "DC=cankaya,DC=bel,DC=tr";

            // LDAP bağlan
            $ldap = ldap_connect($ldap_host, $ldap_port);
            if (!$ldap) die("LDAP sunucusuna bağlanılamadı.");
            ldap_set_option($ldap, LDAP_OPT_PROTOCOL_VERSION, 3);
            ldap_set_option($ldap, LDAP_OPT_REFERRALS, 0);

            if (!@ldap_bind($ldap, $ldap_user, $ldap_pass)) {
                die("LDAP Bind Başarısız! Hata: " . ldap_error($ldap));
            }

            // Kullanıcıyı bul
            $filter = "(sAMAccountName=$edit_ldap_username)";
            $result = ldap_search($ldap, $ldap_dn, $filter);
            $entries = ldap_get_entries($ldap, $result);
            if ($entries["count"] == 0) die("Kullanıcı bulunamadı.");

            $user_dn = $entries[0]["dn"];

            $update = [
                "info" => $info,
                "mail" => $mail,
                "telephoneNumber" => $telephonenumber,
                "ipPhone" => $ipphone,
                "department" => $department
            ];
            if (!ldap_mod_replace($ldap, $user_dn, $update)) {
                die("LDAP Güncelleme Hatası: " . ldap_error($ldap));
            }
            else {
                alert_success("Bilgiler başarıyla güncellenmiştir.");
                $dba->addLog($ip, $ldap_username, $personelTC, "update", "Yetkili ldap bilgileri güncellendi : ".$edit_ldap_username);
                if (isset($_GET['ldap']) && $_GET['ldap'] == 1) {
                    header("Refresh: 2; url=ldap.php?q=$edit_ldap_username&includeDisabled=$includeDisabled"); // yönlendirmek istediğin sayfa
                }
            }
        }
    }
    if (isset($_GET['ldap']) && $_GET['ldap'] == 1) {
        $ldap_host = "ldap://10.1.1.21";
        $ldap_port = 389;
        $ldap_user = "cankaya\\smsadmin"; // DOMAIN\username
        $ldap_pass = "Telefon01*";
        $ldap_dn = "DC=cankaya,DC=bel,DC=tr"; // Tüm kullanıcılar için üst seviye DN

        $filter = "(&(samaccountname=*$edit_ldap_username*)(|(userAccountControl=512)(userAccountControl=514)(userAccountControl=66048)))";

        $ldap = ldap_connect($ldap_host, $ldap_port);
        if (!$ldap) die("LDAP sunucusuna bağlanılamadı!");

        // LDAP seçenekleri
        ldap_set_option($ldap, LDAP_OPT_PROTOCOL_VERSION, 3);
        ldap_set_option($ldap, LDAP_OPT_REFERRALS, 0);

        // Bind
        if (!@ldap_bind($ldap, $ldap_user, $ldap_pass)) {
            die("LDAP Bind Başarısız! Hata: " . ldap_error($ldap));
        }

        // Arama (facsimileTelephoneNumber dahil)
        $result = @ldap_search($ldap, $ldap_dn, $filter, ["name", "facsimileTelephoneNumber", "mail", "telephonenumber", "ipphone", "info", "department"]);

        if (!$result) die("LDAP Araması Başarısız! Hata: " . ldap_error($ldap));

        $entries = ldap_get_entries($ldap, $result);
        if ($entries["count"] > 0) {
            $cn = $entries[0]["name"][0] ?? 'Veri yok';
            $personelSicilNo = $entries[0]["facsimiletelephonenumber"][0] ?? 'Veri yok';
            $mail = $entries[0]["mail"][0] ?? 'Veri yok';
            $telephonenumber = $entries[0]["telephonenumber"][0] ?? 'Veri yok';
            $ipphone = $entries[0]["ipphone"][0] ?? 'Veri yok';
            $personelTC = $entries[0]["info"][0] ?? 'Veri yok';
            $department = $entries[0]["department"][0] ?? 'Veri yok';
        }
    }
    ?>
    <div class="row">
        <div class="col-md-12">
            <div class="box box-success" style="margin-top: 10px">
                <div class="box-header">
                    <h3 class="box-title">YETKİLİ DÜZENLE</h3>
                </div>
                <form role="form" action="<?= htmlspecialchars($_SERVER['REQUEST_URI'], ENT_QUOTES, 'UTF-8') ?>" method="post" enctype="multipart/form-data">
                    <input type="hidden" name="ldap" value="<?= $_GET['ldap'] ?>">
                    <div class="box-body">
                        <div class="form-group">
                            <label>Adı Soyadı</label>
                            <input type="text" class="form-control" name="name" id="name" value="<?= $cn ?>" readonly/>
                        </div>
                        <div class="form-group">
                            <label>LDAP Kullanıcı Adı</label>
                            <input type="text" class="form-control" name="ldap_username" id="ldap_username"
                                   value="<?= $edit_ldap_username ?>" readonly/>
                        </div>
                        <div class="form-group">
                            <label>TC</label>
                            <input type="text" class="form-control" name="info" id="info"
                                   value="<?= $personelTC ?>" <?= ($_SESSION['admin']==1) ?: 'readonly' ?>/>
                        </div>
                        <div class="form-group">
                            <label>Müdürlük</label>
                            <input type="text" class="form-control" name="department" id="department"
                                   value="<?= $department ?>" <?= ($_SESSION['admin']==1) ?: 'readonly' ?>/>
                        </div>
                        <div class="form-group">
                            <label>E-Posta</label>
                            <input type="text" class="form-control" name="mail" id="mail"
                                   value="<?= $mail ?>" <?= ($_SESSION['admin']==1) ?: 'readonly' ?>/>
                        </div>
                        <div class="form-group">
                            <label>Cep Telefonu</label>
                            <input type="text" class="form-control" name="telephonenumber" id="telephonenumber"
                                   value="<?= $telephonenumber ?>" <?= ($_SESSION['admin']==1) ?: 'readonly' ?>/>
                        </div>
                        <div class="form-group">
                            <label>Dahili Telefon</label>
                            <input type="text" class="form-control" name="ipphone" id="ipphone"
                                   value="<?= $ipphone ?>" <?= ($_SESSION['admin']==1) ?: 'readonly' ?>/>
                        </div>
                    </div>
                    <?php if ($_SESSION['admin']==1):?>
                    <div class="box-footer">
                        <button type="submit" class="btn btn-success">Kaydet</button>
                    </div>
                    <?php endif; ?>
                </form>
            </div>
        </div>
    </div>
<?php } ?>