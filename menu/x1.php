<?php
$menus = getMenuTree($dba, null, 0);

function renderMenuAdmin(array $menus, int $level = 0, int &$topIndex = 0): string
{
    $html = '';

    foreach ($menus as $menu) {

        if ($level === 0) {
            $topIndex++;
            $prefix = $topIndex . '.';
        } else {
            $prefix = str_repeat('—', $level);
        }

        $html .= '<tr data-id="'.$menu['id'].'" data-parent="'.($menu['parent_id'] ?? 0).'">';
        $html .= '<td class="order-col left">' . $prefix . '</td>';
        $html .= '<td>' . htmlspecialchars($menu['title']) . '</td>';
        $html .= '<td>' . htmlspecialchars($menu['link']) . '</td>';
        $html .= '<td class="text-center">' . ($menu['is_active'] ? '✔' : '✖') . '</td>';
        $html .= '<td class="text-center">
            <button class="btn btn-sm btn-primary editMenu" data-id="'.$menu['id'].'">
                <i class="fa fa-edit"></i>
            </button>
            <button class="btn btn-sm btn-danger deleteMenu" data-id="'.$menu['id'].'">
                <i class="fa fa-trash"></i>
            </button>
        </td>';
        $html .= '</tr>';

        if (!empty($menu['children'])) {
            $html .= renderMenuAdmin($menu['children'], $level + 1, $topIndex);
        }
    }

    return $html;
}

$mudurlukler = $dba->query("SELECT id, mudurluk FROM mudurlukler WHERE durum = 1 ORDER BY mudurluk")->fetch_all(MYSQLI_ASSOC);
?>
<link rel="stylesheet" href="assets/css/select2.min.css">
<script src="assets/js/select2.min.js"></script>
<section class="content-header">
    <div class="row">
        <div class="col-xs-6">
            <h2>Menü Yönetimi</h2>
        </div>
        <div class="col-xs-6 text-right">
            <button class="btn btn-success" id="addMenu" style="margin: 20px 10px 0">
                <i class="fa fa-plus"></i> Yeni Menü Ekle
            </button>
        </div>
    </div>
</section>
<style>
    tbody tr {
        cursor: move;
    }
</style>
<section class="content" style="margin-top: 20px">
    <table class="table table-bordered table-hover">
        <thead>
        <tr>
            <th style="width:60px">#</th>
            <th>Menü</th>
            <th>Link</th>
            <th class="text-center">Aktif</th>
            <th class="text-center" width="120">İşlem</th>
        </tr>
        </thead>
        <tbody>
        <?= renderMenuAdmin($menus); ?>
        </tbody>
    </table>
</section>
<!-- MODAL -->
<div class="modal fade" id="menuModal">
    <div class="modal-dialog modal-lg">
        <form id="menuForm">
            <input type="hidden" name="id" id="menu_id">

            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">Menü</h4>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>Menü Başlığı</label>
                        <input type="text" name="title" id="title" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Link</label>
                        <input type="text" name="link" id="link" class="form-control">
                    </div>
                    <div class="form-group">
                        <label>Üst Menü</label>
                        <select name="parent_id" id="parent_id" class="form-control">
                            <option value="">Ana Menü</option>
                            <?= renderParentOptions($menus); ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Görebilecek Müdürlükler</label>
                        <select name="allowed_mudurluk[]" id="allowed_mudurluk"
                                class="form-control select2" multiple>
                            <?php foreach ($mudurlukler as $m): ?>
                                <option value="<?= $m['id'] ?>">
                                    <?= htmlspecialchars($m['mudurluk']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="checkbox">
                        <label>
                            <input type="checkbox" name="target_blank" value="1">
                            Yeni sekmede aç
                        </label>
                    </div>
                    <div class="checkbox">
                        <label>
                            <input type="checkbox" name="requires_any_permission" value="1">
                            Herhangi bir uygulama yetkisi varsa göster
                        </label>
                    </div>
                    <div class="checkbox">
                        <label>
                            <input type="checkbox" name="is_active" value="1">
                            Aktif
                        </label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-success">Kaydet</button>
                    <button type="button" class="btn btn-default" data-dismiss="modal">Kapat</button>
                </div>
            </div>
        </form>
    </div>
</div>

<script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script>
<script>
    $('#allowed_mudurluk').select2({
        width: '100%'
    });
    function refreshOrderNumbers(parentId) {
        let counter = 1;

        $('tr[data-parent="' + parentId + '"]').each(function () {
            $(this).find('.order-col').text(counter++);
        });
    }
    $('tbody').sortable({
        items: 'tr',
        axis: 'y',
        cursor: 'move',

        start: function (e, ui) {
            ui.item.data('start-parent', ui.item.data('parent'));
        },

        update: function (e, ui) {
            const movedRow = ui.item;
            const parentId = movedRow.data('parent');
            const oldParent = movedRow.data('start-parent');

            if (parentId !== oldParent) {
                toastr.error('Menüler sadece kendi üst menüsü altında taşınabilir');
                $(this).sortable('cancel');
                return;
            }

            const order = [];

            $('tr[data-parent="' + parentId + '"]').each(function (index) {
                order.push({
                    id: $(this).data('id'),
                    sort_order: index
                });
            });

            $.ajax({
                url: 'menu/menu_sort.php',
                method: 'POST',
                dataType: 'json',
                data: { order: order },
                success: function (res) {
                    if (res.success) {
                        toastr.success(res.message);
                        refreshOrderNumbers(parentId);
                    } else {
                        toastr.error(res.message);
                    }
                },
                error: function () {
                    toastr.error('Sunucu ile iletişim kurulamadı');
                }
            });
        }
    });
</script>
<script>
    /* === Yeni Menü === */
    $('#addMenu').on('click', function () {
        $('#menuForm')[0].reset();
        $('#menu_id').val('');
        $('#menuModal').modal('show');
    });

    /* === Düzenle === */
    $(document).on('click', '.editMenu', function () {
        const id = $(this).data('id');

        function setICheck(name, value) {
            const el = $('input[name="' + name + '"]');
            el.iCheck(value === 1 ? 'check' : 'uncheck');
        }

        $.get('menu/menu_get.php', {id}, function (res) {
            $('#menu_id').val(res.id);
            $('#title').val(res.title);
            $('#link').val(res.link);
            $('#parent_id').val(res.parent_id);
            $('#allowed_mudurluk').val(res.allowed_mudurluk).trigger('change');

            setICheck('is_active', res.is_active);
            setICheck('target_blank', res.target_blank);
            setICheck('requires_any_permission', res.requires_any_permission);

            $('#menuModal').modal('show');
        }, 'json');
    });

    /* === Kaydet === */
    $('#menuForm').on('submit', function (e) {
        e.preventDefault();

        $.post('menu/menu_save.php', $(this).serialize(), function () {
            location.reload();
        });
    });

    /* === Sil === */
    $(document).on('click', '.deleteMenu', function () {
        if (!confirm('Bu menü ve varsa alt menüleri silinecek. Emin misiniz?')) {
            return;
        }

        const id = $(this).data('id');

        $.post('menu/menu_delete.php', {id}, function () {
            location.reload();
        });
    });

    /* === Modal reset === */
    $('#menuModal').on('hidden.bs.modal', function () {
        $('#menuForm')[0].reset();
        $('#menu_id').val('');
    });
</script>