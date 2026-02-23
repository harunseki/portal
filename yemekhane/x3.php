<?php
//$dayOfWeek = 6;
$dayOfWeek = date('N');
$isWeekend = ($dayOfWeek == 6 || $dayOfWeek == 7);

if ($isWeekend) {
    // Yetkisiz erişim
    http_response_code(403); // 403 Forbidden
    ?>
    <style>
        .error-box {
            background: #fff;
            padding: 200px;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
            text-align: center;
        }
        .error-box h1 {
            font-size: 48px;
            margin-bottom: 10px;
            color: #e74c3c;
        }
        .error-box p {
            font-size: 18px;
            margin-bottom: 20px;
        }
        .error-box a {
            display: inline-block;
            padding: 10px 20px;
            background-color: rgba(6, 90, 40);
            color: #fff;
            text-decoration: none;
            border-radius: 5px;
        }
        .error-box a:hover {
            background-color: rgba(6, 90, 40);;
        }
        .warning-triangle {
            width: 0;
            height: 0;
            border-left: 40px solid transparent;
            border-right: 40px solid transparent;
            border-bottom: 70px solid #fa1d1d;
            margin: 0 auto 20px;
            position: relative;
        }
        .warning-triangle::after {
            content: "!";
            position: absolute;
            left: -6px;
            top: 18px;
            font-size: 36px;
            font-weight: bold;
            color: #fff;
        }
    </style>
    <div class="error-box">
        <div class="warning-triangle"></div>
        <p>Hafta sonu yemek kartı şatışı yapılmamaktadır. Hafta içi ilk iş günü tekrar deneyiniz.</p>
        <p>Anlayışınız için teşekkürler...</p>
        <a href="index">Ana Sayfaya Dön</a>
    </div>
    <script>
        setTimeout(function () {
            window.location.href = "index.php";
        }, 10000);
    </script>
    <?php
    exit();
}
$cn = $cn ?? '';
$personelTC = $_SESSION['personelTC'] ?? '';
$department = $_SESSION['department'] ?? '';
$cardNumber = $_SESSION['cardNumber'] ?? '';
if (empty($personelTC) || empty($cardNumber) || $cardNumber === 'Veri yok' || $personelTC === 'Veri yok') {
    echo "<div style='
            position: fixed;
            top: 0; left: 0;
            width: 100%;
            background-color: #e74c3c;
            color: white;
            font-size: 16px;
            font-weight: bold;
            text-align: center;
            padding: 10px 0;
            z-index: 9999;
        '>⚠️ Kullanıcı bilgilerinizde eksiklik var. Lütfen bilgi işlem yazılım birimi(Dahili: 2950) ile iletişime geçiniz.</div>";
}
?>
<div id="weekendWarning" style="
    display:none;
    background:#e74c3c;
    color:#fff;
    padding:12px;
    text-align:center;
    font-weight:bold;
    margin-bottom:10px;
">
    ⚠️ Hafta sonu yemek kartı satışı yapılamamaktadır.
    Lütfen işleminizi hafta içi günlerde gerçekleştiriniz.
</div>
<section class="content-header">
    <h2><i class="fa fa-utensils"></i> Yemek Kartı Satın Alma</h2>
</section>
<style>
    .package-card {
        border: 2px solid #ddd;
        border-radius: 8px;
        padding: 20px;
        cursor: pointer;
        transition: all .2s;
        background: #fff;
    }

    .package-card:hover {
        border-color: #3c8dbc;
        box-shadow: 0 0 10px rgba(60,141,188,.3);
    }

    .package-card.active {
        border-color: #00a65a;
        background: #f0fff6;
    }

    .package-card .price-info {
        font-size: 13px;
        color: #666;
        margin-top: 10px;
    }
</style>
<section class="content" style="margin-top: 10px">
    <div class="box box-success">
        <div class="box-header">
            <h3 class="box-title"><i class="fa fa-calendar"></i> Paket Seçimi</h3>
        </div>
        <div class="box-body">
            <div class="row text-center" id="packageArea" style=" display: flex; justify-content: center"></div>
        </div>
        <div class="box-header">
            <h3 class="box-title"><i class="fa fa-info-circle"></i> Satın Alma Özeti</h3>
        </div>
        <div class="box-body">
            <table class="table">
                <tr>
                    <th>Paket</th>
                    <td id="summaryPackage">-</td>
                </tr>
                <tr>
                    <th>Başlangıç Tarihi</th>
                    <td id="summaryStart">-</td>
                </tr>
                <tr>
                    <th>Bitiş Tarihi</th>
                    <td id="summaryEnd">-</td>
                </tr>
                <tr>
                    <th>Toplam Tutar</th>
                    <td id="summaryPrice">-</td>
                </tr>
            </table>
        </div>
        <div class="box-body text-center">
            <label>
                <input type="checkbox" id="agree">
                Kullanım şartlarını kabul ediyorum
            </label>
            <br><br>
            <div id="buyBtnWrapper" style="display:inline-block">
                <button class="btn btn-success btn-lg" id="buyBtn" disabled>
                    <i class="fa fa-credit-card"></i> Satın Al
                </button>
            </div>
        </div>
    </div>
    <!-- Kullanım Şartları Modal -->
    <div class="modal fade" id="termsModal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">

                <div class="modal-header">
                    <h4 class="modal-title">Kullanım Şartları</h4>
                    <button type="button" class="close" data-dismiss="modal">
                        &times;
                    </button>
                </div>

                <div class="modal-body" style="max-height:60vh; overflow:auto">
                    <p>
                        Buraya kullanım şartlarını yazacaksın…
                    </p>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">
                        Vazgeç
                    </button>
                    <button type="button" class="btn btn-success" id="approveTerms">
                        Kabul Ediyorum
                    </button>
                </div>

            </div>
        </div>
    </div>
</section>
<script>
    let CARD_DEPARTMENT = null;
    let MONTHLY_PRICE   = null;
    let DAILY_PRICE     = 0;
    let selectedDays    = null;

    const agreeCheckbox = document.getElementById('agree');
    const buyBtn = document.getElementById('buyBtn');

    document.getElementById('buyBtnWrapper').addEventListener('click', function () {

        if (!calculateDatesByPackage) {
            toastr.warning('Lütfen bir paket seçiniz');
            return;
        }

        if (!$('#agree').on('ifChanged')) {
            toastr.warning('Devam etmek için kullanım şartlarını kabul etmelisiniz');
            return;
        }

    });

    /* ===============================
       TEK TARİH KAYNAĞI
    =============================== */
    const TEST_MODE = false; // 🔴 PROD'DA FALSE YAP

    const TEST_DATE = {
        year: 2026,
        month: 1,
        day: 15
    };

    function todayInfo() {
        if (TEST_MODE) {
            return new Date(
                TEST_DATE.year,
                TEST_DATE.month,
                TEST_DATE.day
            );
        }
        return new Date();
    }
    if (TEST_MODE) {
        const t = todayInfo();
        alert( '⚠ TEST TARİHİ AKTİF' );
    }

    function formatDate(d) {
        return d.toLocaleDateString('tr-TR');
    }

    function isWeekend(d) {
        const day = d.getDay(); // 0 Pazar - 6 Cumartesi
        return day === 0 || day === 6;
    }

    function nextMonday(d) {
        const date = new Date(d);
        while (isWeekend(date)) {
            date.setDate(date.getDate() + 1);
        }
        return date;
    }

    function isSameDate(a, b) {
        return a.getFullYear() === b.getFullYear()
            && a.getMonth() === b.getMonth()
            && a.getDate() === b.getDate();
    }

    /* ===============================
       TARİH HESAPLAMA (MERKEZ)
    =============================== */
    function calculateDatesByPackage(days) {

        const today = todayInfo();
        const day   = today.getDate();
        const month = today.getMonth(); // 0-11
        const year  = today.getFullYear();

        let start = null;
        let end   = null;

        // 1 Günlük
        if (days === 1) {
            start = new Date(year, month, day);
            end   = new Date(year, month, day);
        }

        // 15 Günlük
        if (days === 15) {

            const first  = new Date(year, month, 1);
            const fifteenth = new Date(year, month, 15);

            const validStartDays = [];

            // 1'i
            validStartDays.push(
                isWeekend(first) ? nextMonday(first) : first
            );

            // 15'i
            validStartDays.push(
                isWeekend(fifteenth) ? nextMonday(fifteenth) : fifteenth
            );

            const todayMidnight = new Date(year, month, day);

            const isValidDay = validStartDays.some(d =>
                isSameDate(d, todayMidnight)
            );

            if (!isValidDay) {
                <?php if ($_GET['payment'] == ''): ?>
                toastr.warning('15 günlük paket ayın 1’i veya 15’i (hafta sonuysa ilk iş günü) alınabilir');
                <?php endif; ?>
                return null;
            }

            // Tarih aralığı DEĞİŞMEZ
            if (day <= 15) {
                start = new Date(year, month, 1);
                end   = new Date(year, month, 14);
            } else {
                start = new Date(year, month, 15);
                end   = new Date(year, month + 1, 0);
            }
        }

        // 30 Günlük
        if (days === 30) {
            if (day < 15 || day > 20) {
                <?php if ($_GET['payment'] == ''): ?>
                toastr.warning('30 günlük paket sadece ayın 15–20 tarihleri arasında alınabilir');
                <?php endif; ?>
                return null;
            }

            start = new Date(year, month, 15);
            end   = new Date(year, month + 1, 14);
        }

        return { start, end };
    }

    /* ===============================
       PAKET KARTI
    =============================== */
    function createPackage(days, title, desc, validText) {
        return `
            <div class="col-md-4">
                <div class="package-card" data-days="${days}">
                    <h4>${title}</h4>
                    <p>${desc}</p>
                    <div class="price-info">
                        <i class="fa fa-calendar"></i> ${validText}
                    </div>
                </div>
            </div>
        `;
    }

    /* ===============================
       PAKETLERİ ÇİZ
    =============================== */
    function renderPackages() {

        const area = document.getElementById('packageArea');
        area.innerHTML = '';

        const today = todayInfo();

        if (isWeekend(today)) {

            document.getElementById('weekendWarning').style.display = 'block';

            toastr.warning('Hafta sonu yemek kartı satışı kapalıdır');

            buyBtn.disabled = true;
            agreeCheckbox.disabled = true;

            return; // 🔴 HİÇBİR PAKET ÇİZİLMEZ
        }

        // ⬇️ Hafta içiyse normal akış
        document.getElementById('weekendWarning').style.display = 'none';

        // 1 Günlük (her zaman)
        const d1 = calculateDatesByPackage(1);
        area.innerHTML += createPackage(
            1,
            '1 Günlük',
            'Günlük yemek hakkı',
            formatDate(d1.start)
        );

        // 15 Günlük
        const d15 = calculateDatesByPackage(15);
        if (d15) {
            area.innerHTML += createPackage(
                15,
                '15 Günlük',
                'Yarım aylık paket',
                `${formatDate(d15.start)} - ${formatDate(d15.end)}`
            );
        }

        // 30 Günlük
        const d30 = calculateDatesByPackage(30);
        if (d30) {
            area.innerHTML += createPackage(
                30,
                '30 Günlük',
                'Aylık paket',
                `${formatDate(d30.start)} - ${formatDate(d30.end)}`
            );
        }
    }

    /* ===============================
       ÖZET HESAPLAMA
    =============================== */
    function calculateSummary(days) {

        if (!MONTHLY_PRICE && days !== 1) {
            toastr.warning('Fiyat bilgisi henüz yüklenmedi');
            return;
        }

        const dates = calculateDatesByPackage(days);
        if (!dates) return;

        let price = 0;
        if (days === 1)  price = DAILY_PRICE;
        if (days === 15) price = MONTHLY_PRICE / 2;
        if (days === 30) price = MONTHLY_PRICE;

        document.getElementById('summaryPackage').textContent =
            days + '  Günlük Paket';

        document.getElementById('summaryStart').textContent =
            formatDate(dates.start);

        document.getElementById('summaryEnd').textContent =
            formatDate(dates.end);

        document.getElementById('summaryPrice').textContent =
            price.toLocaleString('tr-TR') + ' TL';
    }

    function toSqlDate(d) {
        return d.getFullYear() + '-' +
            String(d.getMonth()+1).padStart(2,'0') + '-' +
            String(d.getDate()).padStart(2,'0') + ' ' +
            String(d.getHours()).padStart(2,'0') + ':' +
            String(d.getMinutes()).padStart(2,'0') + ':00';
    }

    function updateBuyBtn() {
        const agreeChecked = $('#agree').iCheck('update')[0].checked;
        buyBtn.disabled =
            !(selectedDays && agreeChecked);
    }

    /* ===============================
       FİYATLAR
    =============================== */
    function loadDepartmentPrices(deptId) {

        fetch('yemekhane/get_daily_price.php')
            .then(r => r.json())
            .then(data => {
                if (data.status !== 'ok') {
                    toastr.error(data.message);
                    return;
                }
                DAILY_PRICE = data.daily_price;
            });

        fetch('yemekhane/get_department_prices.php', {
            method: 'POST',
            headers: {'Content-Type':'application/x-www-form-urlencoded'},
            body: 'departmentId=' + deptId
        })
            .then(r => r.json())
            .then(data => {
                if (data.status !== 'ok') {
                    toastr.error(data.message);
                    return;
                }
                MONTHLY_PRICE = parseFloat(data.prices.price);
            });
    }
    /* ===============================
       EVENTLER
    =============================== */
    buyBtn.addEventListener('click', function () {

        const today = todayInfo();

        if (isWeekend(today)) {
            toastr.error('Hafta sonu satın alma yapılamaz');
            return;
        }

        if (!selectedDays) {
            toastr.warning('Paket seçiniz');
            return;
        }

        if (!selectedDays) {
            toastr.warning('Paket seçiniz');
            return;
        }

        const dates = calculateDatesByPackage(selectedDays);
        if (!dates) return;

        const payload = {
            tckn: "<?= $personelTC ?>",
            cardNumber: "<?= $cardNumber ?>",
            startDate: toSqlDate(dates.start),
            finishDate: toSqlDate(dates.end)
        };

        // 🔴 1) ÇAKIŞMA KONTROLÜ
        fetch('yemekhane/check_card_conflict.php', {
            method: 'POST',
            headers: {'Content-Type':'application/x-www-form-urlencoded'},
            body: new URLSearchParams(payload)
        })
            .then(r => r.json())
            .then(res => {

                if (res.status === 'conflict') {
                    toastr.error(res.message);
                    return;
                }

                if (res.status !== 'ok') {
                    toastr.error('Kontrol sırasında hata oluştu');
                    return;
                }

                // ✅ 2) POS SUCCESS (TEST)
                fetch('yemekhane/pos/start_payment.php', {
                    method: 'POST',
                    headers: {'Content-Type':'application/x-www-form-urlencoded'},
                    body: new URLSearchParams({
                        tckn: "<?= $personelTC ?>",
                        cardNumber: "<?= $cardNumber ?>",
                        packageDays: selectedDays,
                        departmentId: CARD_DEPARTMENT
                    })
                })
                    .then(r => r.json())
                    .then(res => {

                        if (res.status !== 'redirect') {
                            toastr.error(res.message || 'Ödeme başlatılamadı');
                            return;
                        }

                        // ⬇ BANKAYA GERÇEK POST
                        const form = document.createElement('form');
                        form.method = 'POST';
                        form.action = res.bankUrl;

                        for (const k in res.payload) {
                            const i = document.createElement('input');
                            i.type = 'hidden';
                            i.name = k;
                            i.value = res.payload[k];
                            form.appendChild(i);
                        }

                        document.body.appendChild(form);
                        form.submit();
                    });

            })
            .catch(() => toastr.error('Sunucuya ulaşılamadı'));
    });

    document.addEventListener('DOMContentLoaded', function () {

        <?php if ($_GET['payment'] === 'success'): ?>
        toastr.success('<?=$_GET['msg']?>');
        <?php endif; ?>
        <?php if ($_GET['payment'] === 'fail'): ?>
        toastr.error('<?=$_GET['msg']?>');
        <?php endif; ?>

        renderPackages();

        document.getElementById('packageArea').addEventListener('click', function (e) {

            const card = e.target.closest('.package-card');
            if (!card) return;

            document.querySelectorAll('.package-card')
                .forEach(c => c.classList.remove('active'));

            card.classList.add('active');

            selectedDays = parseInt(card.dataset.days);
            calculateSummary(selectedDays);
            updateBuyBtn();
        });

        $('#agree').on('ifChanged', function () {
            updateBuyBtn();
            if (this.checked) {
                // checkbox'ı geçici olarak geri al
                this.checked = false;
                $('#termsModal').modal('show');
            } else {
                buyBtn.disabled = true;
            }
        });

        document.getElementById('approveTerms').addEventListener('click', function () {
            agreeCheckbox.checked = true;
            buyBtn.disabled = false;
            $('#termsModal').modal('hide');
        });

        fetch('yemekhane/get_personel_by_tckn.php', {
            method: 'POST',
            headers: {'Content-Type':'application/x-www-form-urlencoded'},
            body: 'tckn=<?= $personelTC ?>'
        })
            .then(r => r.json())
            .then(data => {

                if (data.status !== 'ok' && data.status !== 'found_local') {
                    toastr.error(data.message);
                    return;
                }

                CARD_DEPARTMENT = data.cardDepartment;
                loadDepartmentPrices(CARD_DEPARTMENT);
            });
    });
</script>