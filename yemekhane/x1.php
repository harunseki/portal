<?php
require_once("class/mysql.php");

// Departman listesini çek
$departments = [];
$q = $dba->query("SELECT id, name, price FROM carddepartment ORDER BY name ASC");
while ($row = $dba->fetch_assoc($q)) {
        $departments[] = $row;
}
?>
<style>
    /* Görsel iyileştirmeler */
    .fade-in { animation: fadeIn 0.5s ease forwards; opacity: 0; }
    @keyframes fadeIn { to { opacity: 1; } }

    .status-bar {
        height: 4px;
        background-color: #ccc;
        margin-top: 5px;
        border-radius: 3px;
        transition: background-color 0.4s;
    }
    .status-bar.active { background-color: #28a745; }
    .status-bar.warning { background-color: #ffc107; }
    .status-bar.error { background-color: #dc3545; }

    .box .box-header h3 i {
        color: #28a745;
        margin-right: 6px;
    }

    #mealHistory table th, #mealHistory table td {
        text-align: center;
        vertical-align: middle;
    }
    #mealHistory tr.highlight {
        background: #d4edda !important;
        font-weight: bold;
    }
</style>

<section class="content-header">
    <h2><i class="fa fa-id-card"></i> Kart Kullanıcı İşlemleri</h2>
</section>

<section class="content">

    <!-- Kart Okutma Alanı -->
    <div class="box box-success" style="margin-top:20px;">
        <div class="box-header"><h3 class="box-title"><i class="fa fa-credit-card"></i> Kart Okut</h3></div>
        <div class="box-body">
            <input type="text" id="cardInput" class="form-control" placeholder="Kart okuyucuya kartı okutun, elle giriş yapmayınız." autofocus>
            <div id="cardStatus" style="margin-top:10px;"></div>
            <div class="status-bar" id="statusBar"></div>
        </div>
    </div>

    <div class="row">
        <!-- Sol sütun: Kullanıcı Bilgileri -->
        <div class="col-md-6 fade-in" id="kullanici_bilgileri" style="display:none;">
            <div class="box box-success">
                <div class="box-header"><h3 class="box-title"><i class="fa fa-user"></i> Kullanıcı Bilgileri</h3></div>
                <div class="box-body">
                    <form id="cardForm">
                        <input type="hidden" name="cardNumber" id="cardNumber">

                        <div class="form-group">
                            <label>TCKN</label>
                            <input type="text" name="TCKN" id="TCKN" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>Adı</label>
                            <input type="text" name="adi" id="adi" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>Soyadı</label>
                            <input type="text" name="soyadi" id="soyadi" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>Sicil No</label>
                            <input type="text" name="sicilNo" id="sicilNo" class="form-control">
                        </div>
                        <div class="form-group">
                            <label>Birim / Departman</label>
                            <select name="cardDepartment" id="cardDepartment" class="form-control" required>
                                <option value="">Seçiniz...</option>
                                <?php foreach ($departments as $d): ?>
                                    <option value="<?= $d['id'] ?>">
                                        <?= htmlspecialchars($d['name']) ?> (<?= number_format($d['price'],2) ?> TL)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <button type="submit" id="saveUserBtn" class="btn btn-success"><i class="fa fa-save"></i> Bilgileri Kaydet</button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Sağ sütun: Yemek Hakkı -->
        <div class="col-md-6 fade-in" id="yemek_hakki_tanimlama" style="display:none;">
            <div class="box box-success">
                <div class="box-header"><h3 class="box-title"><i class="fa fa-utensils" ></i> Yemek Hakkı Tanımlama</h3></div>
                <div class="box-body">
                    <form id="mealAllowanceForm">
                        <input type="hidden" name="cardUserId" id="cardUserId">
                        <input type="hidden" name="cardUserTCKN" id="cardUserTCKN">

                        <div class="form-group">
                            <label>Yemek Süresi</label>
                            <select id="mealDays" name="mealDays" class="form-control" required>
                                <option value="">.:: Seçiniz ::.</option>
                                <option value="15">15 Günlük</option>
                                <option value="30">30 Günlük</option>
                            </select>
                        </div>

                        <button type="submit" id="saveMealBtn" class="btn btn-success"><i class="fa fa-plus-circle"></i> Yemek Hakkı Ekle</button>
                    </form>

                    <div id="mealAllowanceStatus" style="margin-top:10px;"></div>

                    <hr>
                    <h4><i class="fa fa-history"></i> Son Yemek Hakları</h4>
                    <div id="mealHistory" class="table-responsive" style="max-height:150px; overflow:auto;">
                        <table class="table table-bordered table-sm">
                            <thead>
                            <tr>
                                <th>#</th>
                                <th>Başlangıç</th>
                                <th>Bitiş</th>
                            </tr>
                            </thead>
                            <tbody>
                            <tr><td colspan="3" class="text-center text-muted">Henüz kayıt yok</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const cardInput = document.getElementById('cardInput');
        const cardStatus = document.getElementById('cardStatus');
        const fields = ['cardNumber','TCKN','adi','soyadi','sicilNo','cardDepartment'];
        let debounceTimer;
        let inputTimer;

        const userDiv = document.getElementById('kullanici_bilgileri');
        const mealDiv = document.getElementById('yemek_hakki_tanimlama');

        // Başlangıçta gizle
        userDiv.style.display = 'none';
        mealDiv.style.display = 'none';

        cardInput.focus();

        // Kart okutma
        cardInput.addEventListener('input', function() {
            clearTimeout(inputTimer);
            inputTimer = setTimeout(() => {
                this.value = '';
            }, 700);
            const cardNumber = this.value.trim();

            // 5 karakterden az ise alt divleri gizle ve fetch yapma
            if(cardNumber.length < 7){
                userDiv.style.display = 'none';
                mealDiv.style.display = 'none';
                return;
            }

            // 5 karakter ve üzeri ise alt divleri göster
            userDiv.style.display = 'block';
            mealDiv.style.display = 'block';

            // Debounce ile fetch
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(() => {
                fetch('yemekhane/check_card.php', {
                    method: 'POST',
                    headers: {'Content-Type':'application/x-www-form-urlencoded'},
                    body: 'cardNumber=' + encodeURIComponent(cardNumber)
                })
                    .then(r => r.json())
                    .then(data => {
                        if(data.status === 'found'){
                            const d = data.data;
                            fields.forEach(f=>{
                                if(f==='cardDepartment') document.getElementById(f).value = d[f];
                                else document.getElementById(f).value = d[f]??'';
                            });

                            document.getElementById('cardUserId').value = d.id;
                            document.getElementById('cardUserTCKN').value = d.TCKN;
                            cardStatus.innerHTML = '<div class="alert alert-success">Kayıt bulundu.</div>';

                            loadMealHistory(d.id);

                        } else {
                            cardStatus.innerHTML = '<div class="alert alert-warning">Yeni kart kaydı oluşturabilirsiniz.</div>';
                            document.getElementById('cardNumber').value = cardNumber;
                            fields.forEach(f=>{
                                if(f!=='cardNumber') document.getElementById(f).value = '';
                            });
                            document.querySelector('#mealHistory tbody').innerHTML = '<tr><td colspan="3" class="text-center text-muted">Henüz kayıt yok</td></tr>';
                        }
                        cardInput.focus();
                    })
                    .catch(err=>{
                        console.error(err);
                        cardStatus.innerHTML = '<div class="alert alert-danger">Sunucu hatası</div>';
                        cardInput.focus();
                    });
            }, 250);
        });

        // Kullanıcı kaydet
        document.getElementById('cardForm').addEventListener('submit', e=>{
            e.preventDefault();
            const formData = new FormData(e.target);
            fetch('yemekhane/save_card.php', {
                method: 'POST',
                body: new URLSearchParams(formData)
            })
                .then(r=>r.json())
                .then(data=>{
                    if(data.status==='success'||data.status==='updated'){
                        cardStatus.innerHTML = `<div class="alert alert-success">${data.message}</div>`;
                    } else {
                        cardStatus.innerHTML = `<div class="alert alert-danger">${data.message}</div>`;
                    }
                    cardInput.focus();
                });
        });

        // Yemek hakkı ekle
        document.getElementById('mealAllowanceForm').addEventListener('submit', e=>{
            e.preventDefault();
            const formData = new FormData(e.target);
            fetch('yemekhane/save_meal_allowance.php',{
                method:'POST',
                body: new URLSearchParams(formData)
            })
                .then(r=>r.json())
                .then(data=>{
                    const statusDiv = document.getElementById('mealAllowanceStatus');
                    if(data.status==='success'){
                        statusDiv.innerHTML = `<div class="alert alert-success">${data.message}</div>`;

                        // tabloyu yenile
                        const cardUserId = document.getElementById('cardUserId').value;
                        loadMealHistory(cardUserId);

                    } else {
                        statusDiv.innerHTML = `<div class="alert alert-danger">${data.message}</div>`;
                    }
                });
        });

        // Tabloyu güncelleyen fonksiyon
        function loadMealHistory(cardUserId){
            fetch('yemekhane/get_meal_history.php', {
                method: 'POST',
                headers: {'Content-Type':'application/x-www-form-urlencoded'},
                body: 'cardUserId=' + encodeURIComponent(cardUserId)
            })
                .then(r => r.json())
                .then(hist => {
                    const tbody = document.querySelector('#mealHistory tbody');
                    tbody.innerHTML = '';
                    if (hist.status === 'success' && hist.data.length > 0) {
                        hist.data.forEach((row, i) => {
                            const isLast = i === 0 ? ' style="background:#dff0d8;font-weight:bold;"' : '';
                            tbody.insertAdjacentHTML('beforeend', `
                        <tr${isLast}>
                            <td>${row.id}</td>
                            <td>${row.startDate}</td>
                            <td>${row.finishDate}</td>
                        </tr>
                    `);
                        });
                    } else {
                        tbody.innerHTML = '<tr><td colspan="3" class="text-center text-muted">Kayıt bulunamadı</td></tr>';
                    }
                });
        }

        // TCKN alanına girişte otomatik doldurma
        $('#TCKN').on('input', function() {
            const tckn = $(this).val().trim();
            const cardNumber = $('#cardNumber').val().trim();
            const mealDays = $('#mealDays').val();

            if (tckn.length === 11) {
                fetch('yemekhane/get_personel_by_tckn.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: 'tckn=' + encodeURIComponent(tckn) + '&cardNumber=' + encodeURIComponent(cardNumber)
                })
                    .then(res => res.json())
                    .then(data => {
                        if (data.status === 'ok' || data.status === 'found_local') {
                            $('#adi').val(data.adi);
                            $('#soyadi').val(data.soyadi);
                            $('#sicilNo').val(data.sicilNo);
                            $('#cardDepartment').val(data.cardDepartment);

                            if (data.cardNumber) {
                                $('#cardNumber').val(data.cardNumber);
                                $('#cardInput').val(data.cardNumber);
                                userDiv.style.display = 'block';
                                mealDiv.style.display = 'block';
                            }

                            $('#cardUserId').val(data.cardUserId || data.newUserId || '');
                            $('#cardUserTCKN').val(tckn);

                            const msg = data.status === 'found_local'
                                ? 'Kullanıcı sistemde kayıtlı, bilgiler getirildi.'
                                : 'Servisten bilgiler getirildi.';
                            $('#cardStatus').html(`<div class="alert alert-success">${msg}</div>`);

                            if (data.cardUserId || data.newUserId) loadMealHistory(data.cardUserId || data.newUserId);

                            if (mealDays && (data.newUserId || data.cardUserId)) {
                                const uid = data.newUserId || data.cardUserId;
                                const formData = new URLSearchParams({
                                    cardUserId: uid,
                                    cardUserTCKN: tckn,
                                    mealDays: mealDays
                                });
                                fetch('yemekhane/save_meal_allowance.php', {
                                    method: 'POST',
                                    body: formData
                                })
                                    .then(r => r.json())
                                    .then(resp => {
                                        $('#mealAllowanceStatus').html(`<div class="alert alert-${resp.status === 'success' ? 'success' : 'danger'}">${resp.message}</div>`);
                                        if (resp.status === 'success') loadMealHistory(uid);
                                    });
                            }
                        } else {
                            $('#cardStatus').html(`<div class="alert alert-warning">${data.message}</div>`);
                        }
                    })
                    .catch(err => {
                        console.error(err);
                        $('#cardStatus').html('<div class="alert alert-danger">Servis bağlantı hatası.</div>');
                    });
            }
        });

        // MealDays seçimi değiştiğinde periodType göster/gizle
        $('#mealDays').on('change', function() {
            if ($(this).val() == '15') {
                $('#periodTypeDiv').show();
            } else {
                $('#periodTypeDiv').hide();
            }
        });

    });
</script>
