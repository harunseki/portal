<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $kategori_id = $_POST['kategori_id'];
    $isim        = $_POST['isim'];
    $ikon        = $_POST['ikon'];
    $dosya       = $_POST['dosya'];
    $yetki       = $_POST['yetki'];
    $badge       = $_POST['badge'];
    $siralama    = $_POST['siralama'];
    $aktif       = $_POST['aktif'];

    $stmt = $dba->prepare("INSERT INTO mod_moduller (kategori_id, isim, ikon, dosya, yetki, badge, siralama, aktif) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("isssssii", $kategori_id, $isim, $ikon, $dosya, $yetki, $badge, $siralama, $aktif);

    if ($stmt->execute()) {
        echo '<div class="alert alert-success" style="margin-top: 5px">Modül başarıyla eklendi.</div>';
    } else {
        echo '<div class="alert alert-danger" style="margin-top: 5px">Hata oluştu: ' . $stmt->error . '</div>';
    }
}

// Kategorileri çek
$kategoriler = $dba->query("SELECT id, baslik FROM mod_kategori ORDER BY siralama");
?>
<section class="content-header">
    <h2>Yeni Modül Ekle</h2>
</section>
<section class="content">
    <div class='row' style="margin-top:10px;">
        <div class='col-md-12'>
            <div class='box box-success'>
                <div class='box-header'>
                    <h3 class='box-title'>Pop-Up Ekle / Düzenle</h3>
                </div>
                <div class='box-body'>
                    <form action="#" method="POST">
                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label>Kategori</label>
                        <select name="kategori_id" class="form-control" required>
                            <option value="">Seçiniz...
                                <?php while($k = $kategoriler->fetch_assoc()) { ?>
                            <option value="<?= $k['id'] ?>"><?= $k['baslik'] ?></option>
                            <?php } ?></option>
                            <?php
                            // Örnek kategori listeleme
                            // $kategoriler = $db->query("SELECT id, baslik FROM mod_kategori ORDER BY siralama");
                            // while ($k = $kategoriler->fetch_assoc()) {
                            //     echo '<option value="'.$k['id'].'">'.$k['baslik'].'</option>';
                            // }
                            ?>
                        </select>
                    </div>
                    <div class="form-group col-md-6">
                        <label>Modül Adı</label>
                        <input type="text" name="isim" class="form-control" required>
                    </div>

                </div>

                <div class="form-row">

                    <div class="form-group col-md-4">
                        <label>İkon</label>
                        <input type="text" name="ikon" placeholder="fa-users" class="form-control">
                    </div>

                    <div class="form-group col-md-4">
                        <label>Dosya</label>
                        <input type="text" name="dosya" placeholder="personeller.php" class="form-control" required>
                    </div>

                    <div class="form-group col-md-4">
                        <label>Yetki Anahtarı</label>
                        <input type="text" name="yetki" placeholder="personel_islemleri" class="form-control" required>
                    </div>

                </div>

                <div class="form-row">

                    <div class="form-group col-md-4">
                        <label>Badge</label>
                        <select name="badge" class="form-control">
                            <option value="">Yok</option>
                            <option value="NEW">NEW</option>
                            <option value="UPDATE">UPDATE</option>
                            <option value="PRO">PRO</option>
                        </select>
                    </div>

                    <div class="form-group col-md-4">
                        <label>Sıralama</label>
                        <input type="number" name="siralama" value="0" class="form-control">
                    </div>

                    <div class="form-group col-md-4">
                        <label>Aktif</label>
                        <select name="aktif" class="form-control">
                            <option value="1">Aktif</option>
                            <option value="0">Pasif</option>
                        </select>
                    </div>

                </div>

                <button class="btn btn-success btn-block"><i class="fa fa-save"></i> Kaydet</button>

            </form>
                </div>
            </div>
        </div>
    </div>
</section>
