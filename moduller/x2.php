<?php
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
// Mevcut modül bilgisi
$stmt = $dba->prepare("SELECT * FROM mod_moduller WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$modul = $stmt->get_result()->fetch_assoc();
// Kategori listesi
$kategoriler = $dba->query("SELECT id, baslik FROM mod_kategori ORDER BY siralama");

$izinli_iframe_sessionlar = [];
$q = $dba->query("SELECT id, session_key, aciklama FROM session_parameters WHERE aktif = 1 ");

while ($r = $q->fetch_assoc()) {
    $izinli_iframe_sessionlar[] = $r;
}

$ust_mudurlukler = [];
$q = $dba->query("SELECT id, mudurluk FROM mudurlukler WHERE durum=1 ORDER BY mudurluk");

while ($r = $q->fetch_assoc()) {
    $ust_mudurlukler[] = $r;
}
?>
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet"/>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<section class="content-header">
    <h2>Modül Düzenle</h2>
</section>
<section class="content">
    <div class='row' style="margin-top:10px;">
        <div class='col-md-12'>
            <div class='box box-warning'>
                <div class='box-header'>
                    <div class="row">
                        <div class="col-xs-6"><h3 class='box-title'><i class="fa fa-list"></i> Modül Yönetimi</h3></div>
                        <div class="col-xs-6 text-right">
                            <button class="btn btn-success btn-sm" data-toggle="modal" data-target="#modulModal" style="margin: 10px 10px 0" onclick="yeniModul()"> <i class="fa fa-plus"></i> Yeni Modül Ekle
                            </button>
                        </div>
                    </div>
                </div
                <div class='box-body'>
                    <div class="card shadow-sm">
                        <div class="card-body">
                            <table class="table table-bordered table-striped" id="modulTable"  style="margin-top:10px;">
                                <thead class="thead-dark">
                                    <tr>
                                        <th>S.No</th>
                                        <th>Modül Adı</th>
                                        <th>Modül Tipi</th>
                                        <th>Kategori</th>
                                        <th>Hedef Url</th>
                                        <th>Yetki</th>
                                        <th>Aktif</th>
                                        <th>İşlemler</th>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php
                                $i = 0;
                                $q = $dba->query("SELECT m.*, k.baslik AS kategori FROM mod_moduller m 
                                                  LEFT JOIN mod_kategori k ON k.id = m.kategori_id
                                                  WHERE m.aktif NOT IN (0)
                                                  ORDER BY m.aktif, k.baslik, m.isim");
                                while ($row = $q->fetch_assoc()) { $i++; ?>
                                    <tr>
                                        <td><?= $i ?></td>
                                        <td><i class="fa <?= $row['ikon'] ?>"></i> <?= $row['isim'] ?></td>
                                        <td><?= $row['kategori'] ?></td>
                                        <td><?= ($row['modul_tipi']) ?></td>
                                        <td><?= ($row['hedef_url']) ?></td>
                                        <td><?= $row['yetki'] ?></td>
                                        <td><?= $row['aktif']==1 ? "Aktif" : "Pasif" ?></td>
                                        <td>
                                            <button class="btn btn-primary btn-sm" onclick="duzenle(<?= $row['id'] ?>)">
                                                <i class="fa fa-edit"></i>
                                            </button>
                                            <button class="btn btn-danger btn-sm" onclick="sil(<?= $row['id'] ?>)">
                                                <i class="fa fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                <?php } ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- MODAL -->
        <div class="modal fade" id="modulModal" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header bg-info text-white">
                        <h5 class="modal-title">Modül Ekle / Düzenle</h5>
                        <button class="close text-white" data-dismiss="modal">&times;</button>
                    </div>
                    <form id="modulForm">
                        <div class="modal-body">
                            <input type="hidden" name="id" id="modul_id">
                            <div class="row">
                                <div class="form-group col-md-6">
                                    <label>Kategori</label>
                                    <select name="kategori_id" id="kategori_id" class="form-control" required>
                                        <option value="">Seçiniz...</option>
                                        <?php
                                        $kats = $dba->query("SELECT id, baslik FROM mod_kategori ORDER BY siralama");
                                        while ($k = $kats->fetch_assoc()) {
                                            echo '<option value="'.$k['id'].'">'.$k['baslik'].'</option>';
                                        }
                                        ?>
                                    </select>
                                </div>
                                <div class="form-group col-md-6">
                                    <label>Modül Adı</label>
                                    <input type="text" name="isim" id="isim" class="form-control" required>
                                </div>
                            </div>
                            <div class="row">
                                <div class="form-group col-md-6">
                                    <label>Üst Müdürlük</label>
                                    <select name="ust_mudurluk_id" id="ust_mudurluk_id" class="form-control select2">
                                        <option value="">Seçiniz...</option>
                                        <?php foreach($ust_mudurlukler as $u): ?>
                                            <option value="<?= $u['id'] ?>">
                                                <?= htmlspecialchars($u['mudurluk']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="form-group col-md-6">
                                    <label>Müdürlük</label>
                                    <select name="mudurluk_id[]" id="mudurluk_id" class="form-control select2" multiple>
                                        <?php foreach($ust_mudurlukler as $u): ?>
                                            <option value="<?= $u['id'] ?>">
                                                <?= htmlspecialchars($u['mudurluk']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <small class="text-muted">
                                        Birden fazla müdürlük seçebilirsiniz
                                    </small>
                                </div>
                            </div>
                            <div class="row">
                                <div class="form-group col-md-4">
                                    <label>İkon(fa fa-*)</label>
                                    <input type="text" name="ikon" id="ikon" class="form-control">
                                </div>
                                <div class="form-group col-md-4">
                                    <label>Yetki Tanımı</label>
                                    <input type="text" name="yetki" id="yetki" class="form-control" required>
                                </div>
                            </div>
                            <div class="row">
                                <div class="form-group col-md-4">
                                    <label>Badge</label>
                                    <select name="badge" id="badge" class="form-control">
                                        <option value="">Yok</option>
                                        <option value="NEW">NEW</option>
                                        <option value="UPDATE">UPDATE</option>
                                        <option value="PRO">PRO</option>
                                    </select>
                                </div>
                                <div class="form-group col-md-4">
                                    <label>Sıralama</label>
                                    <input type="number" name="siralama" id="siralama" class="form-control" value="0">
                                </div>
                                <div class="form-group col-md-4">
                                    <label>Aktif</label>
                                    <select name="aktif" id="aktif" class="form-control">
                                        <option value="1">Aktif</option>
                                        <option value="2">Pasif</option>
                                    </select>
                                </div>
                            </div>
                            <div class="row">
                                <div class="form-group col-md-4">
                                    <label>Modül Tipi</label>
                                    <select name="modul_tipi" id="modul_tipi" class="form-control">
                                        <option value="sayfa">Yerel Sayfa</option>
                                        <option value="iframe">Iframe</option>
                                        <option value="redirect">Yönlendirme</option>
                                    </select>
                                </div>
                                <div class="form-group col-md-4">
                                    <label>Hedef URL</label>
                                    <input type="text" name="hedef_url" id="hedef_url" class="form-control"
                                           placeholder="Iframe veya redirect adresi">
                                </div>
                                <div class="form-group col-md-4">
                                    <label>Style</label>
                                    <input type="text" name="style" id="style" class="form-control"
                                           placeholder="Style veriniz">
                                </div>
                                <div class="form-group col-md-12" id="iframe_parametre_alani" style="display:none;">
                                    <label>Iframe Session Parametreleri</label>
                                    <div style="max-height:150px;overflow:auto;border:1px solid #ddd;padding:10px;border-radius:6px;">
                                        <?php foreach ($izinli_iframe_sessionlar as $s): ?>
                                            <label style="display:block;">
                                                <input type="checkbox" name="iframe_parametreler[]" value="<?= $s['id'] ?>"> <?= htmlspecialchars($s['aciklama']) ?> -> <small class="text-muted"><?= htmlspecialchars($s['session_key']) ?></small>
                                            </label>
                                        <?php endforeach; ?>
                                    </div>
                                    <small class="text-muted">
                                        Seçilen session değişkenleri hedef URL'e otomatik parametre olarak eklenecektir.
                                    </small>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="submit" class="btn btn-success">
                                <i class="fa fa-save"></i> Kaydet
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>
<script>
    $(document).ready(function(){

        $('.select2').select2({
            placeholder: "Müdürlük seçiniz",
            allowClear: true,
            closeOnSelect:false,
            width: '100%'
        });

    });
    /** Yeni Modül **/
    function yeniModul() {
        $('#mudurluk_id').val(null).trigger('change');
        $('#modulForm')[0].reset();
        $('#modul_id').val('');

        $('#modul_tipi').val('sayfa');
        $('#hedef_url').val('');
        $('#style').val('');

        $('input[name="iframe_parametreler[]"]').prop('checked', false);

        modulTipiKontrol();
    }

    /** Düzenle **/
    function duzenle(id) {

        $.ajax({
            url: 'moduller/modul_getir.php',
            data: { id: id },
            method: 'POST',
            dataType: 'json',

            success: function(res) {

                if (!res || (res.status && res.status === 'error')) {
                    toastr.error(res.message || 'Modül getirilemedi.');
                    return;
                }

                var m = res.data ? res.data : res;

                $('#modul_id').val(m.id);
                $('#kategori_id').val(m.kategori_id);
                $('#isim').val(m.isim);
                $('#ikon').val(m.ikon);
                $('#yetki').val(m.yetki);
                $('#badge').val(m.badge);
                $('#siralama').val(m.siralama);
                $('#aktif').val(m.aktif);
                $('#modul_tipi').val(m.modul_tipi);
                $('#hedef_url').val(m.hedef_url);
                $('#style').val(m.style);

                if (m.ust_mudurluk_id) {
                    $('#ust_mudurluk_id').val(m.ust_mudurluk_id).trigger('change');
                } else {
                    $('#ust_mudurluk_id').val(null).trigger('change');
                }
                if(m.mudurluk_id) {
                    let secili = m.mudurluk_id.split(',');
                    $('#mudurluk_id').val(secili).trigger('change');
                }else {
                    $('#mudurluk_id').val(null).trigger('change');
                }
                // 🔥 ÖNCE tip kontrolü
                modulTipiKontrol();

                $('#modulModal').modal('show');

                // 🔥 Küçük gecikme ile işaretle
                setTimeout(function() {

                    $('input[name="iframe_parametreler[]"]').prop('checked', false);

                    if (m.parametreler && m.parametreler.length > 0) {
                        let secili = m.parametreler.split(',');
                        secili.forEach(function (id) {
                            id = id.trim();
                            $('input[name="iframe_parametreler[]"][value="' + id + '"]')
                                .iCheck('check');
                        });
                    }
                }, 1000);
            }
        });
    }
    /** KAYDET **/
    $("#modulForm").on("submit", function(e) {
        e.preventDefault();

        $.ajax({
            url: "moduller/modul_kaydet.php",
            method: "POST",
            data: $("#modulForm").serialize(),
            dataType: "json",

            success: function(res) {
                if (res.ok) {
                    toastr.success(res.mesaj);
                    $("#modulModal").modal("hide");

                    setTimeout(() => location.reload(), 1000);
                } else {
                    toastr.error(res.mesaj);
                }
            },

            error: function() {
                toastr.error("Sunucu hatası!");
            }
        });
    });
    /** SİLME **/
    function sil(id) {
        if (!confirm("Bu modülü silmek istiyor musunuz?")) return;

        $.post("moduller/modul_sil.php", { id: id }, function(d) {
            toastr.success("Modül başarı silindi");
            setTimeout(() => location.reload(), 800);
        });
    }

    function modulTipiKontrol() {

        let tip = $('#modul_tipi').val();
        let styleInput = $('#style');

        if (tip !== 'iframe') {
            $('#iframe_parametre_alani').hide();
            styleInput.closest('.form-group').hide();
            styleInput.prop('required', false);

        } else {
            $('#iframe_parametre_alani').show();
            styleInput.closest('.form-group').show();
            styleInput.prop('required', true);
        }
    }

    $('#modul_tipi').on('change', function () {
        modulTipiKontrol();
    });
</script>