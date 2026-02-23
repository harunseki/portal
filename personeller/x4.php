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
                        <button id="exportExcelPDKS" class="btn btn-success pull-right" style="display: none; margin-top: 5px; margin-right: 15px;">
                            <i class="fa fa-file-excel-o"></i> Excel İndir
                        </button>
                    </div>
                    <div class="box-body">
                    <table id="pdksTable" class="table table-bordered table-striped">
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
    var table;

    $(document).ready(function() {
        table = $('#pdksTable1').DataTable({
            "paging": true,
            "lengthChange": true,
            "searching": true,
            "ordering": true,
            "info": true,
            "autoWidth": false,
            "language": {
                "url": "//cdn.datatables.net/plug-ins/1.13.6/i18n/tr.json" // Türkçe dil desteği
            }
        });
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

                    // jQuery objesini alıyoruz (lowercase 'd')
                    var dt = $('#pdksTable').dataTable();

                    // 1. Tabloyu Temizle (fnClearTable - Legacy Method)
                    dt.fnClearTable();

                    if (data.length > 0) {
                        $('#exportExcelPDKS').show(); // Veri varsa göster
                    } else {
                        $('#exportExcelPDKS').hide(); // Veri yoksa gizle
                        alert("Kayıt bulunamadı."); // Bilgilendirme
                    }

                    // Başlığı güncelle
                    $('#tableTitle').text((tc || 'Tüm Personel') + " – Log Kayıtları (" + data.length + " kayıt)");

                    if (data.length > 0) {
                        // Veriyi hazırla (rows.add formatı yerine fnAddData formatı)
                        var rows = data.map(function(row, i) {
                            var tarihStr = '-';

                            // Tarih kontrolü
                            if (row.Tarih) {
                                // Tarih nesne gelirse stringe çeviriyoruz
                                tarihStr = (typeof row.Tarih === 'object' && row.Tarih.date) ? row.Tarih.date : row.Tarih;
                            }

                            // fnAddData, ham dizi (array of arrays) bekler
                            return [
                                i + 1,
                                row.Tckimlikno || '-',
                                row.AdSoyad || '-',
                                row.HareketTuru || '-',
                                row.LogType || '-',
                                tarihStr || '-'
                            ];
                        });

                        // 2. Satırları Ekle (fnAddData - Legacy Method)
                        dt.fnAddData(rows);
                    }

                    // 3. Tabloyu Çiz (fnDraw - Legacy Method)
                    dt.fnDraw();

                    $('html, body').animate({
                        scrollTop: $('#tableTitle').offset().top - 50 // Başlığın 50 piksel üstüne kaydır
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
        document.getElementById('exportExcelPDKS')?.addEventListener('click', function() {
            let table = document.getElementById('pdksTable');
            if (!table) return;

            // DataTables'dan çekilen tüm satırları al
            let rows = Array.from(table.querySelectorAll('tr'));

            // CSV formatına çevir (Başlıklar ve Satırlar)
            let csv = rows.map(r =>
                Array.from(r.querySelectorAll('th,td'))
                    .map(cell => `"${cell.innerText.replace(/"/g, '""')}"`)
                    .join(",")
            ).join("\n");

            // Dosya oluşturma ve indirme
            let blob = new Blob(["\ufeff" + csv], { type: 'text/csv;charset=utf-8;' });
            let url = URL.createObjectURL(blob);
            let a = document.createElement('a');
            a.href = url;
            a.download = 'pdks_loglari.csv'; // Dosya Adı
            a.click();
            URL.revokeObjectURL(url);
        });
    });
</script>