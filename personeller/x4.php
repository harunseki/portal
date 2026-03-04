<?php
$tc = $_POST['tc'] ?? '';
?>
<?php
// personeller.php
?>
<section class="content-header">
    <h2>PDKS Personel Log Sorgulama</h2>
</section>

<section class="content">
    <div class="box-body">
        <!-- Arama Formu -->
        <div class="row" style="margin-top:10px;">
            <div class="col-md-12">
                <div class="box box-success" style="margin-top:10px;">
                    <div class="box-body">
                        <form id="formPersonel">
                            <div class="row">
                                <div class="col-md-3">
                                    <label>TC Kimlik No</label>
                                    <input type="text" name="tc" class="form-control" placeholder="Boş bırakılırsa tümü gelir">
                                </div>

                                <div class="col-md-3">
                                    <label>&nbsp;</label>
                                    <button class="btn btn-success btn-block">Sorgula</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <div class="row" style="margin-top:10px;">
            <div class="col-md-12">
                <div class="box box-success" style="margin-top:10px;">
                    <div class="box-header with-border">
                        <h3 id="tableTitle" class="box-title"></h3>
                        <button data-table="pdksTable4" class="btn btn-success pull-right export-btn" style="display: none; margin-top: 5px; margin-right: 15px;">
                            <i class="fa fa-file-excel-o"></i> Excel İndir
                        </button>
                    </div>
                    <div class="box-body">
                    <table id="pdksTable4" class="table table-bordered table-striped">
                        <thead>
                        <tr>
                            <th>#</th>
                            <th>TC</th>
                            <th>Ad Soyad</th>
                            <th>Hareket Türü</th>
                            <th>Log Type</th>
                            <th>Tarih</th>
                        </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
<script>
    $(document).ready(function() {

        // Form submit
        $('#formPersonel').on('submit', function(e){
            e.preventDefault();
            var tc = $('input[name="tc"]').val();

            var btn = $(this).find('button');
            var originalText = btn.text();
            btn.prop('disabled', true).text('Sorgulanıyor...');

            $.ajax({
                url: 'personeller/ajax_personel.php',
                type: 'POST',
                data: {tc: tc},
                dataType: 'json',
                success: function(data){

                    var tableEl = $('#pdksTable4');

                    // DataTable legacy 1.9.4 settings
                    var oSettings = tableEl.dataTable().fnSettings();
                    if (!oSettings) {
                        console.warn("DataTable initialize edilmemiş:", 'pdksTable4');
                        btn.prop('disabled', false).text(originalText);
                        return;
                    }

                    // Tabloyu temizle
                    tableEl.dataTable().fnClearTable();

                    // Export butonu görünürlüğü
                    if (data.length > 0) {
                        $('.export-btn[data-table="pdksTable4"]').show();
                    } else {
                        $('.export-btn[data-table="pdksTable4"]').hide();
                        alert("Kayıt bulunamadı.");
                    }

                    // Başlığı güncelle
                    $('#tableTitle').text((tc || 'Tüm Personel') + " – Log Kayıtları (" + data.length + " kayıt)");

                    if (data.length > 0) {
                        // Satırları ekle
                        var rows = data.map(function(row, i) {
                            var tarihStr = '-';
                            if (row.Tarih) {
                                tarihStr = (typeof row.Tarih === 'object' && row.Tarih.date) ? row.Tarih.date : row.Tarih;
                            }

                            return [
                                i + 1,
                                row.Tckimlikno || '-',
                                row.AdSoyad || '-',
                                row.HareketTuru || '-',
                                row.LogType || '-',
                                tarihStr || '-'
                            ];
                        });

                        tableEl.dataTable().fnAddData(rows);
                    }

                    tableEl.dataTable().fnDraw();

                    // Scroll
                    $('html, body').animate({
                        scrollTop: $('#tableTitle').offset().top - 50
                    }, 500);

                    btn.prop('disabled', false).text(originalText);
                },
                error: function(xhr, status, error){
                    console.error("Hata Detayı:", xhr.responseText);
                    alert("Veri alınamadı. Lütfen konsolu (F12) kontrol edin.");
                    btn.prop('disabled', false).text(originalText);
                }
            });
        });

    });
</script>