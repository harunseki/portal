<?php
$tckn = $_SESSION['personelTC'];
$totalCount  = 0;
$usedCount   = 0;
$unusedCount = 0;
$futureCount = 0;

$sql = "SELECT startDate, finishDate FROM cardmealallowement
        WHERE tckn = ? 
        ORDER BY startDate DESC LIMIT 1";
$stmt = $dba->prepare($sql);
$stmt->bind_param("s", $tckn);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();

$events = [];

if ($order) {
    /* Kullanılan günler */
    $usedDays = [];
    $q = $dba->prepare("SELECT DATE(date) d FROM cardmovement 
                        WHERE tckn = ?");
    $q->bind_param("s", $tckn);
    $q->execute();
    $res = $q->get_result();

    while ($r = $res->fetch_assoc()) {
        $usedDays[$r['d']] = true;
    }

    $start = new DateTime($order['startDate']);
    $end   = new DateTime($order['finishDate']);
    $testDate = $_GET['test_date'] ?? null;

    $today = new DateTime($testDate ?: date('Y-m-d'));
    $jsToday = $today->format('Y-m-d');

    while ($start <= $end) {
        // Haftasonu yok
        if (in_array($start->format('N'), [6,7])) {
            $start->modify('+1 day');
            continue;
        }
        $dateStr = $start->format('Y-m-d');

        if (isset($usedDays[$dateStr])) {
            $status = 'used';
            $usedCount++;
        } elseif ($start < $today) {
            $status = 'unused';
            $unusedCount++;
        } elseif ($start == $today) {
            $status = 'today';
            $unusedCount++;
        } else {
            $status = 'future';
            $futureCount++;
        }
        $events[] = [
                'date'   => $dateStr,
                'status' => $status
        ];
        $totalCount++;
        $start->modify('+1 day');
    }
}
$remainingCount = $futureCount;
?>
<style>
    body { background:#f4f6f9; }
    .box {
        background:#fff;
        padding:20px;
        border-radius:6px;
        margin-top:20px;
    }
    .status-past { color:#999; }
    .status-today { color:#00a65a; font-weight:bold; }
    .status-future { color:#0073b7; }
    .legend span {
        margin-right:15px;
        font-size:13px;
    }
</style>
<section class="content-header">
    <h2> 🍽️ Yemek Haklarım</h2>
</section>
<section class="content">
    <div class="box box-success" style="margin-top:20px;">
        <div class="row">
            <!-- TAKVİM -->
            <div class="col-md-7">
                <div id="mealHeader" style="text-align:center;margin-bottom:10px;">
                    <?php if($admin==1): ?>
                    <div style="text-align:center;margin-bottom:10px;">
                        <h3 id="periodTitle"></h3>
                        <div style="margin-top:8px;">
                            <label style="font-size:13px;">Test Tarihi:</label>
                            <input type="date" id="testDateInput"
                                   value="<?= htmlspecialchars($_GET['test_date'] ?? '') ?>">
                            <button onclick="applyTestDate()">Uygula</button>
                            <button onclick="clearTestDate()">Temizle</button>
                        </div>
                    </div>
                    <?php else: ?>
                    <h3 id="periodTitle"></h3>
                    <?php endif; ?>
                    <div style="display:flex;justify-content:center;gap:30px;font-size:14px;">
                        <div>📊 <strong>Toplam Hak:</strong> <?= $totalCount ?></div>
                        <div>✅ <strong>Kullanılan:</strong> <?= $usedCount ?></div>
                        <div>❌ <strong>Kullanılmayan:</strong> <?= $unusedCount ?></div>
                        <div>🟢 <strong>Kalan:</strong> <?= $remainingCount ?></div>
                    </div>
                </div>
                <div id="calendar"></div>
            </div>
            <!-- LİSTE -->
            <div class="col-md-5">
                <div class="box">
                    <h4>Detaylı Liste</h4>
                    <ul class="list-group" id="mealList"></ul>
                    <h4><i class="fa fa-history"></i> Geçmiş Yemek Hakları</h4>
                    <div id="mealHistory" class="table-responsive" style="max-height:458px; overflow:auto;">
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
    function applyTestDate() {
        const d = document.getElementById('testDateInput').value;
        if (!d) return;

        const url = new URL(window.location.href);
        url.searchParams.set('test_date', d);
        window.location.href = url.toString();
    }

    function clearTestDate() {
        const url = new URL(window.location.href);
        url.searchParams.delete('test_date');
        window.location.href = url.toString();
    }

    function loadMealHistory(cardUserId) {
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
                            <td>${(i+1)}</td>
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

    function isWeekend(dateStr) {
        const d = new Date(dateStr);
        const day = d.getDay(); // 0 = Pazar, 6 = Cumartesi
        return day === 0 || day === 6;
    }

    function toYMD(date) {
        return new Intl.DateTimeFormat('tr-TR', {
            year: 'numeric',
            month: '2-digit',
            day: '2-digit'
        }).format(date).split('.').reverse().join('-');
    }

    function getMealPeriod() {
        const today = new Date("<?= $jsToday ?>T00:00:00");

        const year  = today.getFullYear();
        const month = today.getMonth();
        const day   = today.getDate();

        let start, end;

        if (day >= 15) {
            // 15 Ocak → 14 Şubat
            start = new Date(year, month, 15);
            end   = new Date(year, month + 1, 14); // 15 Şubat (EXCLUSIVE)
        } else {
            // 15 Aralık → 14 Ocak
            start = new Date(year, month - 1, 15);
            end   = new Date(year, month, 14);     // 15 Ocak (EXCLUSIVE)
        }

        return {
            start: toYMD(start),
            end:   toYMD(end)
        };
    }

    function formatPeriodTitle(startStr, endStr) {
        const months = [
            'Ocak','Şubat','Mart','Nisan','Mayıs','Haziran',
            'Temmuz','Ağustos','Eylül','Ekim','Kasım','Aralık'
        ];

        const start = new Date(startStr);
        const end   = new Date(endStr);

        const startText = `${start.getDate()} ${months[start.getMonth()]}`;
        const endText   = `${end.getDate()} ${months[end.getMonth()]}`;

        return `Yemek Dönemi: ${startText} – ${endText}`;
    }

    document.addEventListener('DOMContentLoaded', function () {

        loadMealHistory(<?=$tckn?>);

        const mealEvents = <?= json_encode($events); ?>;

        /* FullCalendar eventleri */
        const calendarEvents = mealEvents.map(e => ({
            title:
                e.status === 'used'   ? 'Kullanıldı' :
                    e.status === 'unused' ? 'Kullanılmadı' :
                        e.status === 'today'  ? 'Bugün Henüz Kullanılmadı' :
                            'Gelecek',
            start: e.date,
            color:
                e.status === 'used'   ? '#999999' :
                    e.status === 'unused' ? '#f39c12' :
                        e.status === 'today'  ? '#00a65a' :
                            '#0073b7'
        }));

        /* Tarih sınırları */
        const mealPeriod = getMealPeriod();

        // Başlıkları bas
        document.getElementById('periodTitle').innerText = formatPeriodTitle(mealPeriod.start, mealPeriod.end);

        const calendar = new FullCalendar.Calendar(
            document.getElementById('calendar'),
            {
                initialView: 'dayGridMonth',
                locale: 'tr',
                firstDay: 1, // Pazartesi
                height: 'auto',
                weekends:false,
                validRange: {
                    start: mealPeriod.start,
                    end:   mealPeriod.end
                },
                headerToolbar: {
                    left: 'prev',
                    center: 'title',
                    right: 'next'
                },
                events: calendarEvents
            }
        );
        calendar.render();
    });
</script>