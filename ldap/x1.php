<?php
if (isset($_GET['q'])) {
    $search = $_GET['q'] ?? '';
    $includeDisabled = isset($_GET['includeDisabled']) ? (bool)$_GET['includeDisabled'] : false;

    $dba->addLog($ip, $ldap_username, $personelTC, "create",
        "LDAP kullanıcıları içinde arama yapıldı: \"".$search."\" (Disabled dahil: " . ($includeDisabled ? 'evet' : 'hayır') . ")"
    );

    $ldap_host = "ldap://10.1.1.21";
    $ldap_port = 389;
    $ldap_user = "cankaya\\smsadmin";
    $ldap_pass = "Telefon01*";

    // 🔹 Arama yapılacak OU’lar:
    $ou_list = ["OU=Cankaya Belediyesi,DC=cankaya,DC=bel,DC=tr"];
    $ou_list[] = "OU=Sirketler,DC=cankaya,DC=bel,DC=tr";
    $filter = "(&(|(displayName=*$search*)(samaccountname=*$search*))(|(userAccountControl=512)(userAccountControl=66048)))";

    if ($includeDisabled && ($_SESSION['sifre']==1 || $_SESSION['admin']==1)) {
        $ou_list[] = "OU=Disabled,DC=cankaya,DC=bel,DC=tr";
        $filter = "(&(|(displayName=*$search*)(samaccountname=*$search*))(|(userAccountControl=512)(userAccountControl=514)(userAccountControl=66048)))";
    }

    $ldap = ldap_connect($ldap_host, $ldap_port);
    if (!$ldap) die("LDAP sunucusuna bağlanılamadı!");

    ldap_set_option($ldap, LDAP_OPT_PROTOCOL_VERSION, 3);
    ldap_set_option($ldap, LDAP_OPT_REFERRALS, 0);

    if (!@ldap_bind($ldap, $ldap_user, $ldap_pass)) {
        die("LDAP Bind Başarısız! Hata: " . ldap_error($ldap));
    }

    $data = [];

    foreach ($ou_list as $ldap_dn) {
        $result = @ldap_search($ldap, $ldap_dn, $filter, [
            "displayname", "mail", "samaccountname", "dn", "telephonenumber", "ipphone", "info", "department"
        ]);

        if ($result) {
            $entries = ldap_get_entries($ldap, $result);
            for ($i = 0; $i < $entries["count"]; $i++) {
                $data[] = [
                    "displayname" => $entries[$i]["displayname"][0] ?? "",
                    "username" => $entries[$i]["samaccountname"][0] ?? "",
                    "mail" => $entries[$i]["mail"][0] ?? "",
                    "telephone" => $entries[$i]["telephonenumber"][0] ?? "",
                    "ipPhone" => $entries[$i]["ipphone"][0] ?? "",
                    "info" => $entries[$i]["info"][0] ?? "",
                    "department" => $entries[$i]["department"][0] ?? "",
                    "sourceOU" => strpos($ldap_dn, "Disabled") !== false ? "Disabled" : "Cankaya Belediyesi"
                ];
            }
        } else {
            error_log("LDAP araması başarısız ({$ldap_dn}): " . ldap_error($ldap));
        }
    }
    /*if ($ldap_username=="harunseki"){
        print_r($data);
    }*/

    ldap_unbind($ldap);

    usort($data, function($a, $b) {
        return strcasecmp($a['displayname'], $b['displayname']);
    });

    // --- SAYFALAMA AYARLARI ---
    $perPage = ($_SESSION['sifre']==1 || $_SESSION['admin']==1) ? 12 : 20;
    $total = count($data);
    $totalPages = ceil($total / $perPage);
    $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
    $start = ($page - 1) * $perPage;
    $pagedData = array_slice($data, $start, $perPage);
}
?>
<section class="content-header">
    <div class="row">
        <div class="col-xs-6">
            <h2>Personel Rehberi</h2>
        </div>
    </div>
</section>
<section class="content" style="min-height: 725px">
    <div style="display: flex; align-items: center; flex-wrap: wrap; gap: 0.8rem; margin: 1rem 0;">
        <input type="text" id="searchInput" placeholder="Personel Bilgisi Giriniz..." style="width: 390px; padding: 0.5rem 1rem; border-radius: 0.5rem; border: 1px solid #ccc; box-shadow: 0 2px 5px rgba(0,0,0,0.1); font-size: 1.8rem;" onkeypress="if(event.key === 'Enter'){ doSearch(); }" autocomplete="off" value="<?= htmlspecialchars($search ?? '') ?>">
        <button onclick="doSearch()" style="padding: 0.5rem 1.2rem; border-radius: 0.5rem; background-color: #007bff; color: white; border: none; font-size: 1.8rem; cursor: pointer; box-shadow: 0 2px 5px rgba(0,0,0,0.1);"> Ara </button>

        <?php if ($_SESSION['sifre']==1 || $_SESSION['admin']==1): ?>
            <label style="display: flex; align-items: center; gap: 0.5rem; font-size: 1.6rem; margin-left: 1rem;">
                <input type="checkbox" id="includeDisabled" <?= $includeDisabled ? 'checked' : '' ?> onchange="doSearch()">
                Pasif Kullanıcıları Dahil Et
            </label>
        <?php endif; ?>

        <?php if (isset($total)): ?>
            <span style="margin-left: auto; font-size: 1.6rem; color: #555;">
                Toplam <strong><?= $total ?></strong> sonuç bulundu.
            </span>
        <?php endif; ?>
    </div>

    <div id="results" class="row">
        <?php if (!empty($pagedData)): ?>
            <?php foreach ($pagedData as $user): ?>
                <div class="col-md-3">
                    <div class="box box-success" style="box-shadow: 0 2px 6px rgba(0,0,0,0.1);">
                        <div class="box-header">
                            <h3 class="box-title"><strong><?= htmlspecialchars($user['displayname']) ?></strong><?php if ($user['sourceOU'] === 'Disabled'): ?>
                                    <span style="color:#a00; font-size:1.3rem;">(Pasif Kullanıcı)</span>
                                <?php endif; ?></h3>
                        </div>
                        <div class="card-body" style="padding: 0 10px 10px">
                            <p class="card-text">
                                <?php if ($_SESSION['sifre']==1 || $_SESSION['admin']==1): ?>
                                    <strong>TC: </strong> <?= htmlspecialchars($user['info']) ?><br>
                                    <strong>Kullanıcı Adı: </strong> <?= htmlspecialchars($user['username']) ?><br>
                                    <strong>Telefon: </strong> <?= htmlspecialchars($user['telephone'] ?: '-') ?><br>
                                    <strong>Müdürlük: </strong> <?= buyuk_harfe_cevir(htmlspecialchars($user['department'] ?: '-')) ?><br>
                                <?php endif; ?>
                                <strong>E-posta: </strong> <?= htmlspecialchars($user['mail'] ?: '-') ?><br>
                                <strong>Dahili: </strong> <?= htmlspecialchars($user['ipPhone'] ?: '-') ?><br>

                            </p>
                            <?php if ($_SESSION['sifre']==1 || $_SESSION['admin']==1): ?>
                                <div style="display:flex; justify-content:space-between; margin-top:10px;">
                                    <button class="btn btn-success" onclick="resetPass('<?= $user['username'] ?>')">Şifre Sıfırla</button>
                                    <a href="yetkili.php?x=3&edit=<?= $user['username'] ?>&ldap=1<?php if ($user['sourceOU'] === 'Disabled'): ?>&includeDisabled=<?php echo $includeDisabled; endif; ?>" class="btn btn-warning">Bilgileri Güncelle</a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <?php if (!isset($_GET['q'])): ?>
                <!-- Henüz arama yapılmadı -->
            <?php else: ?>
                <p style="margin:20px;">Sonuç bulunamadı.</p>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <?php if (!empty($totalPages) && $totalPages > 1): ?>
        <div style="text-align:center;">
            <?php if ($page > 1): ?>
                <a href="?q=<?= urlencode($search) ?>&page=<?= $page - 1 ?>&includeDisabled=<?= $includeDisabled ? '1' : '0' ?>" class="btn btn-default">← Önceki</a>
            <?php endif; ?>

            <span style="margin: 0 10px;">Sayfa <?= $page ?> / <?= $totalPages ?></span>

            <?php if ($page < $totalPages): ?>
                <a href="?q=<?= urlencode($search) ?>&page=<?= $page + 1 ?>&includeDisabled=<?= $includeDisabled ? '1' : '0' ?>" class="btn btn-default">Sonraki →</a>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <script>
        function doSearch() {
            const q = document.getElementById("searchInput").value.trim();
            const includeDisabled = document.getElementById("includeDisabled")?.checked ? 1 : 0;
            if (!q) return alert("Lütfen arama terimi girin.");
            window.location.href = "ldap.php?q=" + encodeURIComponent(q) + "&includeDisabled=" + includeDisabled;
        }

        function resetPass(username) {
            if (!confirm(username + " için şifre sıfırlansın mı?")) return;

            fetch("ldap/reset.php?user=" + encodeURIComponent(username))
                .then(response => response.text())
                .then(msg => {
                    toastr.success(msg);
                    $.post("class/log_ekle.php", { sifre: 1, username: username }, function(res){
                        console.log("Log sonucu:", res);
                    });
                })
                .catch(err => {
                    toastr.error("Hata oluştu: " + err);
                });
        }
    </script>
</section>
