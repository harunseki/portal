<?php
require_once "class/mysql.php";
$departments = [];
$q = $dba->query("SELECT id, name, price FROM carddepartment WHERE durum=1 ORDER BY name ASC");
while ($row = $dba->fetch_assoc($q)) {
    $departments[] = $row;
}
?>
<section class="content-header">
    <h2><i class="fa fa-building"></i> Departman Tanımlama</h2>
</section>
<section class="content">
    <div class="row" style="margin-top: 10px">
        <!-- LİSTE -->
        <div class="col-md-7">
            <div class="box box-success">
                <div class="box-header">
                    <h3 class="box-title"><i class="fa fa-list"></i> Departmanlar</h3>
                </div>
                <div class="box-body table-responsive">
                    <table class="table table-bordered table-hover">
                        <thead>
                        <tr>
                            <th>#</th>
                            <th>Adı</th>
                            <th>Ücret</th>
                            <th>İşlem</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php $i=0; foreach ($departments as $d): $i++; ?>
                            <tr>
                                <td><strong><?= $i ?></strong></td>
                                <td><?= htmlspecialchars($d['name']) ?></td>
                                <td><?= number_format($d['price'],2) ?> TL</td>
                                <td>
                                <td>
                                    <button class="btn btn-xs btn-warning editBtn"
                                            data-id="<?= $d['id'] ?>"
                                            data-name="<?= htmlspecialchars($d['name']) ?>"
                                            data-price="<?= $d['price'] ?>">
                                        <i class="fa fa-edit"></i> Düzenle
                                    </button>

                                    <button class="btn btn-xs btn-danger deleteBtn"
                                            data-id="<?= $d['id'] ?>"
                                            data-name="<?= htmlspecialchars($d['name']) ?>">
                                        <i class="fa fa-trash"></i> Sil
                                    </button>
                                </td>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <!-- FORM -->
        <div class="col-md-5">
            <div class="box box-success">
                <div class="box-header">
                    <h3 class="box-title"><i class="fa fa-plus"></i> Departman Ekle / Güncelle</h3>
                </div>
                <div class="box-body">
                    <form id="departmentForm">
                        <input type="hidden" name="id" id="dept_id">
                        <div class="form-group">
                            <label>Departman Adı</label>
                            <input type="text" name="name" id="dept_name" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>Yemek Ücreti (TL)</label>
                            <input type="number" step="0.01" name="price" id="dept_price" class="form-control" required>
                        </div>
                        <button type="submit" class="btn btn-success" id="saveBtn">
                            <i class="fa fa-save"></i> Kaydet
                        </button>
                        <button type="button" class="btn btn-default" id="resetBtn">
                            <i class="fa fa-eraser"></i> Temizle
                        </button>
                    </form>
                </div>
            </div>
        </div>
        <script>
            function notify(type, message, title = '') {
                toastr[type](message, title);
            }
            document.addEventListener('DOMContentLoaded', () => {
                document.querySelectorAll('.editBtn').forEach(btn => {
                    btn.addEventListener('click', () => {
                        document.getElementById('dept_id').value = btn.dataset.id;
                        document.getElementById('dept_name').value = btn.dataset.name;
                        document.getElementById('dept_price').value = btn.dataset.price;

                        notify('info', 'Güncelleme Modundasınız')
                    });
                });

                document.getElementById('resetBtn').addEventListener('click', () => {
                    document.getElementById('departmentForm').reset();
                    document.getElementById('dept_id').value = '';

                    notify('warning', 'Form temizlendi')
                });

                document.getElementById('departmentForm').addEventListener('submit', e => {
                    e.preventDefault();
                    const formData = new FormData(e.target);

                    fetch('yemekhane/save_department.php', {
                        method: 'POST',
                        body: new URLSearchParams(formData)
                    })
                        .then(r => r.json())
                        .then(res => {
                            if (res.status === 'success') {
                                notify('success', res.message);
                                setTimeout(() => location.reload(), 800);
                            } else {
                                notify('error', res.message);
                            }
                        });
                });

                document.querySelectorAll('.deleteBtn').forEach(btn => {

                    btn.addEventListener('click', () => {

                        const id = btn.dataset.id;
                        const name = btn.dataset.name;

                        Swal.fire({
                            title: 'Emin misiniz?',
                            html: `<b>${name}</b> silinecek.`,
                            icon: 'warning',
                            showCancelButton: true,
                            confirmButtonText: 'Evet, Sil',
                            cancelButtonText: 'Vazgeç',
                            confirmButtonColor: '#d33',
                            cancelButtonColor: '#6c757d'
                        }).then((result) => {

                            if (!result.isConfirmed) return;

                            fetch('yemekhane/delete_department.php', {
                                method: 'POST',
                                headers: {'Content-Type':'application/x-www-form-urlencoded'},
                                body: 'id=' + encodeURIComponent(id)
                            })
                                .then(r => r.json())
                                .then(res => {
                                    if (res.status === 'success') {
                                        toastr.success(res.message, 'Başarılı');

                                        // Satırı animasyonla kaldır
                                        btn.closest('tr').style.opacity = '0.4';
                                        setTimeout(() => {
                                            btn.closest('tr').remove();
                                        }, 400);

                                    } else {
                                        toastr.error(res.message, 'Hata');
                                    }
                                })
                                .catch(() => toastr.error('Sunucu hatası', 'Hata'));

                        });

                    });

                });
            });
        </script>
    </div>
</section>