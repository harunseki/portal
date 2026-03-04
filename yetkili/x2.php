<?php
$tablo="yetkili";
if(isset($_GET['delete'])){
    $delete=$purifier->purify(rescape((int)$_GET['delete']));
    $q=$dba->query("UPDATE $tablo SET yetkili_durumu=0 WHERE id='$delete' ");

    if($dba->affected_rows()>0) alert_success("Yetkili sistemden kaldırılmıştır.");
}

$kategoriYetkiler = [];
$sql = "SELECT k.id   AS kategori_id, k.baslik AS kategori_adi, m.id   AS modul_id, m.isim AS modul_adi
        FROM mod_kategori k
        JOIN mod_moduller m ON m.kategori_id = k.id
        WHERE m.aktif > 0
        ORDER BY k.siralama, m.isim ";

$q = $dba->query($sql);
while ($r = $dba->fetch_assoc($q)) {
    $kategoriYetkiler[$r['kategori_id']]['baslik'] = $r['kategori_adi'];
    $kategoriYetkiler[$r['kategori_id']]['moduller'][] = [
        'id'   => $r['modul_id'],
        'isim' => $r['modul_adi']
    ];
}?>
<div class="row">
    <div class="col-md-12">
        <div class="box box-success">
            <div class="box-header">
                <h3 class="box-title">Yetkili Düzenle</h3>
                <div style="float: right; margin: 10px">
                    <a href="1-<?= strip($file) ?>" class="btn btn-success" style="color: white">
                        <i class="fa fa-plus"></i> <strong>Yetkili Ekle</strong>
                    </a>
                </div>
            </div>
            <div class="box-body">
                <table id="example1" class="table table-bordered table-striped datatable">
                    <thead>
                    <tr>
                        <th>Adı</th>
                        <th>Soyadı</th>
                        <th>Username</th>
                        <th>Yetkiler</th>
                        <th>İşlem</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php
                    $q = $dba->query("SELECT * FROM yetkili WHERE yetkili_durumu=1 ORDER BY username ASC");
                    while ($row = $dba->fetch_assoc($q)) {
                        // Kullanıcının yetkileri
                        $uyet = [];
                        $qy = $dba->query("SELECT yetki_key, deger FROM yetkili_moduller WHERE kullanici_id = ".(int)$row['id']);
                        while ($yy = $dba->fetch_assoc($qy)) {
                            $uyet[(int)$yy['yetki_key']] = (int)$yy['deger'];
                        }
                        ?>
                        <tr data-id="<?= (int)$row['id'] ?>">
                            <td><?= strip($row['adi']) ?></td>
                            <td><?= strip($row['soyadi']) ?></td>
                            <td><?= strip($row['username']) ?></td>
                            <td>
                                <button class="btn btn-primary btn-sm yetki-ac"
                                        data-user="<?= (int)$row['id'] ?>"
                                        data-adi="<?= strip($row['adi']) ?> <?= strip($row['soyadi']) ?>">
                                    <i class="fa fa-lock"></i> Yetkiler
                                </button>
                            </td>
                            <td>
                                <a href="<?= strip($file) ?>.php?x=2&edit=<?= (int)$row['id'] ?>" class="btn btn-default">
                                    <img src="img/edit.png" />
                                </a>
                                <a href="<?= strip($file) ?>.php?x=2&delete=<?= (int)$row['id'] ?>"
                                   class="btn btn-default"
                                   onclick="return confirm('Silmek istediğinize emin misiniz?');">
                                    <img src="img/delete1.png" style="height:15px" />
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
<style>
    .yetki-modal-xl {
        width: 55%;
        max-width: 1400px;
    }

    .scroll-container {
        width: 100%;
        overflow-x: auto;
        position: relative;
    }
    .myTable th,
    .myTable td {
        white-space: nowrap;
    }
    .myTable th:nth-child(1),
    .myTable td:nth-child(1),
    .myTable th:nth-child(2),
    .myTable td:nth-child(2),
    .myTable th:nth-child(3),
    .myTable td:nth-child(3) {
        position: sticky;
        left: 0;
        z-index: 3;
        background: #fff;
    }
    .myTable th:nth-child(2),
    .myTable td:nth-child(2) {
        /*left: 120px*/;
    }
    .myTable th:nth-child(3),
    .myTable td:nth-child(3) {
        left: 240px;
    }
    .myTable th:last-child,
    .myTable td:last-child {
        position: sticky;
        right: 0;
        z-index: 3;
        background: #fff;
    }
</style>
<div class="modal fade" id="yetkiModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg yetki-modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">
                    <i class="fa fa-lock"></i>
                    Yetkiler – <span id="yetkiKullaniciAdi"></span>
                </h4>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <ul class="nav nav-tabs">
                    <?php $i = 0; foreach ($kategoriYetkiler as $katId => $kat): ?>
                        <li class="<?= $i === 0 ? 'active' : '' ?>">
                            <a href="#kat<?= $katId ?>" data-toggle="tab">
                                <?= strip($kat['baslik']) ?>
                            </a>
                        </li>
                    <?php $i++; endforeach; ?>
                </ul>
                <div class="tab-content" style="margin-top:15px">
                    <?php $i = 0; foreach ($kategoriYetkiler as $katId => $kat): ?>
                        <div class="tab-pane <?= $i === 0 ? 'active' : '' ?>" id="kat<?= $katId ?>">
                            <div class="row">
                                <?php foreach ($kat['moduller'] as $m): ?>
                                    <div class="col-md-3">
                                        <div class="well well-sm">
                                            <strong><?= strip($m['isim']) ?></strong>
                                            <div class="pull-right">
                                                <button class="btn btn-xs yetki-toggle btn-danger" data-yetki-id="<?= (int)$m['id'] ?>" data-status="0">
                                                    PASİF
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php $i++; endforeach; ?>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">
                    Kapat
                </button>
            </div>
        </div>
    </div>
</div>
<script>
    $(document).on('click', '.yetki-ac', function () {
        const userId = $(this).data('user');
        const ad = $(this).data('adi');

        $('#yetkiKullaniciAdi').text(ad);
        $('#yetkiModal').data('user-id', userId);

        // Önce tüm butonları PASİF yap
        $('.yetki-toggle')
            .removeClass('btn-success')
            .addClass('btn-danger')
            .text('PASİF')
            .data('status', 0);

        $.post('yetkili/yetkiler_getir.php', {
            user_id: userId
        }, function (resp) {
            if (resp.success) {
                $.each(resp.yetkiler, function (yetkiId, deger) {
                    if (deger == 1) {
                        const btn = $('.yetki-toggle[data-yetki-id="'+yetkiId+'"]');
                        btn
                            .removeClass('btn-danger')
                            .addClass('btn-success')
                            .text('AKTİF')
                            .data('status', 1);
                    }
                });
            }
            $('#yetkiModal').modal('show');
        }, 'json');
    });

    $(document).on('click', '.yetki-toggle', function () {
        const btn = $(this);
        const yetki_id = btn.data('yetki-id');
        const user_id = $('#yetkiModal').data('user-id');

        $.post('yetkili/yetki_toggle.php', {
            id: user_id,
            yetki_id: yetki_id
        }, function (resp) {

            if (resp.success) {
                const aktif = resp.yeni_durum == 1;

                btn
                    .removeClass('btn-success btn-danger')
                    .addClass(aktif ? 'btn-success' : 'btn-danger')
                    .text(aktif ? 'AKTİF' : 'PASİF');

                toastr[aktif ? 'success' : 'warning'](
                    aktif ? 'Yetki aktif edildi' : 'Yetki pasif edildi'
                );
            }
        }, 'json');
    });

    $(function() {
        $('.yetki-btn').on('click', function() {
            const el = $(this);
            const user_id = el.closest('tr').data('id');         // kullanıcı
            const yetki_id = el.data('yetki-id');               // ilgili yetki
            const ldap_username = "<?= $ldap_username ?>";
            const ip = "<?= $ip ?>";
            const personelTC = "<?= $personelTC ?>";

            $.post('yetkili/yetki_toggle.php', {
                id: user_id,
                yetki_id: yetki_id,
                ldap_username: ldap_username,
                ip: ip,
                personelTC: personelTC
            }, function(resp) {
                if(resp.success) {
                    const aktif = resp.yeni_durum == 1;
                    const renk  = aktif ? '#28a745' : '#dc3545';
                    const yazi  = aktif ? 'AKTİF' : 'PASİF';

                    el.css('background', renk).text(yazi);

                    if(aktif){
                        toastr.success('Yetki aktif edildi.');
                    } else {
                        toastr.warning('Yetki pasif hale getirildi.');
                    }
                } else {
                    alert('Hata: ' + resp.message);
                }
            }, 'json');
        });
    });
</script>