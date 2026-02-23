<section class="content-header">
    <div class="row">
        <div class="col-xs-6">
            <h2>Pop-Up Yönetim Ekranı</h2>
        </div>
        <div class="col-xs-6 text-right"></div>
    </div>
</section>
<section class="content">
    <div class='row' style="margin-top:10px;">
        <div class='col-md-12'>
            <div class='box box-success'>
                <div class='box-header'>
                    <h3 class='box-title'>Pop-Up Ekle / Düzenle</h3>
                </div>
                <div class='box-body'>
                    <?php
                    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                        $kategori = trim($_POST['kategori'] ?? '');
                        $baslik = trim($_POST['baslik'] ?? '');
                        $icerik = trim($_POST['icerik'] ?? '');
                        $baslangic = $_POST['baslangic_tarihi'] ?? '';
                        $bitis = $_POST['bitis_tarihi'] ?? '';
                        $aktif = isset($_POST['aktif']) ? 1 : 0;
                        $cookie_mode = $_POST['cookie_mode'] ?? 'none'; // ✔ ÇEREZ EKLENDİ
                        $id = $_POST['id'] ?? '';

                        // ✔ RESİM YÜKLEME KODU
                        $image_sql = "";
                        if (!empty($_FILES['popup_image']['name'])) {

                            $targetDir = "img/popups/";
                            if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);

                            $fileName = time() . "_" . basename($_FILES["popup_image"]["name"]);
                            $targetFile = $targetDir . $fileName;

                            if (move_uploaded_file($_FILES["popup_image"]["tmp_name"], $targetFile)) {
                                $image_sql = ", image_url='$targetFile'";
                            }
                        }

                        if ($kategori == '' || $baslik == '' || $icerik == '') {
                            echo "<div class='alert alert-danger'>Kategori, başlık ve içerik boş olamaz.</div>";
                        } else {
                            if ($id) {
                                $stmt = $dba->prepare("UPDATE popups SET kategori=?, baslik=?, icerik=?, baslangic_tarihi=?, bitis_tarihi=?, aktif=?, cookie_mode=? $image_sql WHERE id=?");
                                $stmt->execute([$kategori, $baslik, $icerik, $baslangic, $bitis, $aktif, $cookie_mode, $id]);
                                echo "<div class='alert alert-success'>Pop-up güncellendi.</div>";
                                $dba->addLog($ip, $ldap_username, $personelTC, "update", "Pop-up güncellendi: ".$icerik);
                            } else {
                                $stmt = $dba->prepare("INSERT INTO popups (kategori, baslik, icerik, baslangic_tarihi, bitis_tarihi, aktif, cookie_mode, image_url) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                                $stmt->execute([$kategori, $baslik, $icerik, $baslangic, $bitis, $aktif, $cookie_mode, ($image_sql ? $targetFile : null)]);
                                echo "<div class='alert alert-success'>Yeni pop-up eklendi.</div>";
                                $dba->addLog($ip, $ldap_username, $personelTC, "create", "Yeni pop-up eklendi: ".$icerik);
                            }
                        }
                    }

                    // --- SİL ---
                    if (isset($_GET['sil'])) {
                        $id = intval($_GET['sil']);
                        $dba->query("DELETE FROM popups WHERE id=$id");
                        echo "<div class='alert alert-warning'>Pop-up silindi.</div>";
                        $dba->addLog($ip, $ldap_username, $personelTC, "delete", "Pop-up silindi.");
                    }

                    // --- DÜZENLE ---
                    $duzenle = null;
                    if (isset($_GET['duzenle'])) {
                        $id = intval($_GET['duzenle']);
                        $duzenle = $dba->query("SELECT * FROM popups WHERE id=$id")->fetch_assoc();
                    }
                    ?>

                    <form method="post" enctype="multipart/form-data"> <!-- ✔ RESİM İÇİN GEREKLİ -->
                        <input type="hidden" name="id" value="<?= htmlspecialchars($duzenle['id'] ?? '') ?>">

                        <div class="form-group">
                            <label class="kirmizi">Kategori</label>
                            <select name="kategori" class="form-control">
                                <option value="bilgi"  <?= ($duzenle['kategori'] ?? '') === 'bilgi'  ? 'selected' : '' ?>>Bilgilendirme</option>
                                <option value="duyuru" <?= ($duzenle['kategori'] ?? '') === 'duyuru' ? 'selected' : '' ?>>Duyuru</option>
                                <option value="uyari"  <?= ($duzenle['kategori'] ?? '') === 'uyari'  ? 'selected' : '' ?>>Uyarı</option>
                                <option value="kritik" <?= ($duzenle['kategori'] ?? '') === 'kritik' ? 'selected' : '' ?>>Kritik</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="kirmizi">Başlık</label>
                            <input type="text" class="form-control" name="baslik" required
                                   value="<?= htmlspecialchars($duzenle['baslik'] ?? '') ?>"/>
                        </div>

                        <div class="form-group">
                            <label class="kirmizi">İçerik</label>
                            <textarea class="form-control" name="icerik" rows="4"
                                      required><?= htmlspecialchars($duzenle['icerik'] ?? '') ?></textarea>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <label class="kirmizi">Başlangıç Tarihi</label>
                                <input type="datetime-local" class="form-control" name="baslangic_tarihi"
                                       value="<?= isset($duzenle['baslangic_tarihi']) ? date('Y-m-d\TH:i', strtotime($duzenle['baslangic_tarihi'])) : '' ?>" required/>
                            </div>
                            <div class="col-md-6">
                                <label class="kirmizi">Bitiş Tarihi</label>
                                <input type="datetime-local" class="form-control" name="bitis_tarihi"
                                       value="<?= isset($duzenle['bitis_tarihi']) ? date('Y-m-d\TH:i', strtotime($duzenle['bitis_tarihi'])) : '' ?>" required/>
                            </div>
                        </div>

                        <!-- ✔ RESİM YÜKLEME ALANI -->
                        <div class="form-group" style="margin-top:10px;">
                            <label>Pop-up Resmi</label>
                            <input type="file" name="popup_image" class="form-control">

                            <?php if (!empty($duzenle['image_url'])): ?>
                                <img src="<?= $duzenle['image_url'] ?>" style="max-width:200px;margin-top:10px;border:1px solid #ccc;">
                            <?php endif; ?>
                        </div>

                        <!-- ✔ ÇEREZ AYARI -->
                        <div class="form-group">
                            <label>Çerez Gösterim Ayarı</label>
                            <select name="cookie_mode" class="form-control">
                                <option value="none"  <?= ($duzenle['cookie_mode'] ?? '') == 'none'  ? 'selected' : '' ?>>Çerez Kullanma (Her zaman göster)</option>
                                <option value="daily" <?= ($duzenle['cookie_mode'] ?? '') == 'daily' ? 'selected' : '' ?>>Günde 1 Kez Göster</option>
                                <option value="once"  <?= ($duzenle['cookie_mode'] ?? '') == 'once'  ? 'selected' : '' ?>>Sadece 1 Kez Göster</option>
                            </select>
                        </div>

                        <div class="form-group" style="margin-top:10px;">
                            <label>
                                <input type="checkbox" name="aktif" <?= isset($duzenle['aktif']) && $duzenle['aktif'] ? 'checked' : '' ?>> Aktif
                            </label>
                        </div>

                        <div class="box-footer">
                            <button class="btn btn-success" type="submit">💾 Kaydet</button>
                            <?php if ($duzenle): ?>
                                <a href="popup_yonetim.php" class="btn btn-default">İptal</a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>

            <div class='box box-primary'>
                <div class='box-header'>
                    <h3 class='box-title'>Kayıtlı Pop-Up Listesi</h3>
                </div>
                <div class='box-body'>
                    <?php
                    $popups = $dba->query("SELECT * FROM popups ORDER BY id DESC")->fetch_all(MYSQLI_ASSOC);
                    if (count($popups) > 0): ?>
                        <table class="table table-bordered table-striped">
                            <thead>
                            <tr>
                                <th>#</th>
                                <th>Kategori</th>
                                <th>Başlık</th>
                                <th>Başlangıç</th>
                                <th>Bitiş</th>
                                <th>Durum</th>
                                <th>İşlem</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($popups as $p): ?>
                                <tr>
                                    <td><?= $p['id'] ?></td>
                                    <td><?= htmlspecialchars($p['kategori']) ?></td>
                                    <td><?= htmlspecialchars($p['baslik']) ?></td>
                                    <td><?= date('d.m.Y H:i', strtotime($p['baslangic_tarihi'])) ?></td>
                                    <td><?= date('d.m.Y H:i', strtotime($p['bitis_tarihi'])) ?></td>
                                    <td><?= $p['aktif'] ? '<span class="label label-success">Aktif</span>' : '<span class="label label-default">Pasif</span>' ?></td>
                                    <td>
                                        <a href="?duzenle=<?= $p['id'] ?>" class="btn btn-xs btn-warning">Düzenle</a>
                                        <a href="?sil=<?= $p['id'] ?>" class="btn btn-xs btn-danger" onclick="return confirm('Silinsin mi?')">Sil</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <div class="alert alert-info">Henüz kayıtlı bir pop-up bulunmamaktadır.</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</section>