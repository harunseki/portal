<?php
// Dahili listeleme sayfası

$total = 0;
$perPage = 12;

// Müdürlükleri çek
$mudurlukQuery = $dba->query("SELECT * FROM mudurlukler WHERE durum=1 ORDER BY mudurluk ASC");
$mudurlukler = [];
while ($row = $dba->fetch_assoc($mudurlukQuery)) {
    $mudurlukler[] = $row;
}

// Seçili müdürlük ve birim
$selectedMudurluk = $_GET['mudurluk'] ?? '';
$selectedBirim = $_GET['birim'] ?? '';
?>
<section class="content-header">
    <div class="row">
        <div class="col-xs-6">
            <h2>Birimler ve Dahili Telefonlar</h2>
        </div>
    </div>
</section>
<style>
    .fancy-label {
        display: inline-block;
        font-weight: 600;
        font-size: 1.8rem;
        color: #333;
        margin-bottom: 5px;
        position: relative;
    }
    .fancy-label::after {
        content: '';
        display: block;
        width: 40px;
        height: 3px;
        background: #00a65a;
        border-radius: 2px;
        margin-top: 3px;
        box-shadow: 0 2px 5px rgba(0,0,0,0.2);
    }
    .box.box-success {
        min-height: 100px; /* içerik uzun olabilir diye min-height tercih ettim */
        display: flex;
        flex-direction: column;
        justify-content: space-between; /* başlık ve içerik arası dengeli */
    }
</style>
<section class="content">
    <form id="dahiliForm" method="GET" onsubmit="return false;">
        <div class="row" style="margin: 10px 0;">
            <div class="col-md-4">
                <label class="fancy-label">Müdürlük Seçiniz</label>
                <select name="mudurluk" id="mudurluk" class="form-control">
                    <option value="">.:: Seçiniz ::.</option>
                    <?php foreach($mudurlukler as $m): ?>
                        <option value="<?= $m['id'] ?>" <?= $selectedMudurluk == $m['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($m['mudurluk']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-4">
                <label class="fancy-label">Birim Seçiniz</label>
                <select name="birim" id="birim" class="form-control">
                    <option value="">.:: İsteğe bağlı ::.</option>
                </select>
            </div>

            <!--<div class="col-md-4 d-flex align-items-end" style="margin-top: 35px;">
                <button type="submit" id="getirBtn" class="btn btn-success btn-lg">Getir</button>
            </div>-->
        </div>
    </form>

    <div id="dahiliSonuc" class="row" style="margin: 10px 0px;"></div>
</section>

<!--<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>-->
<script>
    $(function(){
        // Müdürlük seçildiğinde birimleri getir ve dahili listesini yükle
        $('#mudurluk').on('change', function(){
            const mudurlukId = $(this).val();
            $('#birim').html('<option>Yükleniyor...</option>');
            $('#dahiliSonuc').empty();

            if(!mudurlukId){
                $('#birim').html('<option value="">-- İsteğe bağlı --</option>');
                return;
            }

            $.get('dahili/ajax_birimler.php', {mudurluk_id: mudurlukId}, function(data){
                let html = '<option value="all">Tüm Birimler</option>';
                data.forEach(b => {
                    html += `<option value="${b.id}">${b.baslik}</option>`;
                });
                $('#birim').html(html);
                getDahiliList(); // Müdürlük seçildiğinde otomatik liste getir
            }, 'json');
        });

        // Birim seçilince otomatik liste getir
        $('#birim').on('change', function(){
            getDahiliList();
        });

        // Buton tıklanırsa da elle liste getir
        $('#getirBtn').on('click', function(){
            getDahiliList();
        });

        // AJAX ile dahili listesi getiren fonksiyon
        function getDahiliList(){
            const mudurluk = $('#mudurluk').val();
            const birim = $('#birim').val();

            if(!mudurluk){
                $('#dahiliSonuc').html('<p style="padding:10px;">Lütfen önce bir müdürlük seçiniz.</p>');
                return;
            }

            $('#dahiliSonuc').html('<p style="padding:10px;">Yükleniyor...</p>');

            $.get('dahili/ajax_dahili_listesi.php', {mudurluk, birim}, function(data){
                if(!data.length){
                    $('#dahiliSonuc').html('<p style="padding:10px;">Kayıt bulunamadı.</p>');
                    return;
                }

                let html = '';
                data.forEach(item => {
                    const nums = item.dahili.split(',').map(n => n.trim()).filter(Boolean);
                    const numHTML = nums.map(n => `
                    <div style="display:inline-block; margin:3px; padding:4px 8px; background:#f5f5f5; color:#333; border-radius:6px; font-size:16px;">
                        ${n}
                    </div>
                `).join('');
                    html += `
                    <div class="col-md-3">
                        <div class="box box-success" style="height:120px;">
                            <div class="box-header">
                                <h3 class="box-title">${item.baslik}</h3>
                            </div>
                            <div class="box-body">${numHTML}</div>
                        </div>
                    </div>`;
                });

                $('#dahiliSonuc').hide().html(html).slideDown(700);
            }, 'json');
        }
    });
</script>