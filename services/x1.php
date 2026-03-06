<?php
require_once __DIR__ . '/autoload.php';

$result = $dba->query("
SELECT 
    s.id,
    s.name,
    s.last_status,
    s.last_response_time,
    s.last_checked,
    s.category_id,
    s.is_primary,
    c.name AS category_name
FROM services s
LEFT JOIN service_categories c ON c.id = s.category_id
WHERE s.is_active=1
ORDER BY 
    c.sort_order,
    s.category_id,
    s.is_primary DESC,
    s.name
");

$services = $result->fetch_all(MYSQLI_ASSOC);

$grouped = [];
$noCategory = [];

foreach ($services as $s) {
    if (empty($s['category_id'])) {
        $noCategory[] = $s;
    } else {
        $grouped[$s['category_id']]['name'] = $s['category_name'];
        $grouped[$s['category_id']]['services'][] = $s;
    }
}
?>
<style>
    table {
        width:100%;
        background:#fff;
        border-collapse: collapse;
        font-size:14px;
    }
    thead th {
        border-bottom: 1px solid #000;
    }
    th {
        background:#f4f6f9;
        text-transform:uppercase;
        font-size:16px;
        font-weight: bold;
        letter-spacing:.5px;
    }
    th,td {
        padding:12px;
        border:1px solid #eee;
        text-align:center;
    }
    tr:hover {
        background:#fafafa;
    }
    .category-row {
        background:#f9fbff;
        cursor:pointer;
        font-weight:600;
    }
    .category-row:hover {
        background:#eef3ff;
    }
    .child-row {
        background:#fcfcfc;
        transition: all .2s ease;
    }
    .badge {
        padding:4px 8px;
        border-radius:20px;
        font-size:12px;
        font-weight:600;
    }
    .up { background:#e6f9f0; color:#1e7e34; }
    .down { background:#fdecea; color:#c82333; }
    .slow { background:#fff4e5; color:#e67e22; }
    .arrow {
        display:inline-block;
        transition:transform .2s ease;
        margin-right:6px;
    }
    .arrow.open {
        transform:rotate(90deg);
    }
    .small-count {
        background:#ddd;
        padding:2px 6px;
        border-radius:12px;
        font-size:11px;
        margin-left:6px;
    }
    .primary-badge {
        font-size: 11px;
        background: #4f46e5;
        color: #fff;
        padding: 2px 6px;
        border-radius: 12px;
        margin-left: 8px;
    }
    .refresh-btn {
        border: none;
        background: transparent;
        cursor: pointer;
        font-size: 16px;
        transition: 0.2s;
    }

    .refresh-btn:hover {
        transform: rotate(90deg);
    }

    .refresh-btn.loading {
        opacity: 0.5;
        pointer-events: none;
    }
</style>
<section class="content-header">
    <div class="row">
        <div class="col-xs-6">
            <h2>API Monitoring Dashboard</h2>
        </div>
        <div class="col-xs-6 text-right">
            <?php if ($_SESSION['admin'] == 1): ?>
                <div class="pull-right" style="margin:20px 10px 0">
                    <a href="2-services" class="btn btn-success">
                        Yeni Servis Ekle
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>
<section class="content">
    <div class="row" style="margin-top:20px;">
        <div class="col-md-12">
            <div class="box box-success">
                <div class="box-body">
                    <table id="serviceTable">
                        <thead>
                        <tr>
                            <th>API</th>
                            <th>Durum</th>
                            <th>Süre (ms)</th>
                            <th>Son Kontrol</th>
                            <th style="width:50px;"></th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($noCategory as $s): ?>
                            <tr id="service-<?= $s['id'] ?>" class="single-service">
                                <td style="padding-left:45px; width: 500px; text-align: justify;"><?= htmlspecialchars($s['name']) ?></td>
                                <td class="status"></td>
                                <td class="response_time"></td>
                                <td class="last_checked"></td>
                                <td>
                                    <button class="refresh-btn" data-service-id="<?= $s['id'] ?>">
                                        ⟳
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php foreach ($grouped as $catId => $cat):
                            $primary = null;

                            foreach ($cat['services'] as $srv) {
                                if ($srv['is_primary']) {
                                    $primary = $srv;
                                    break;
                                }
                            }
                            ?>
                            <!-- KATEGORİ SATIRI -->
                            <tr class="category-row" data-category="<?= $catId ?>" id="service-<?= $primary['id'] ?>">
                                <td style=" width: 500px; text-align: justify;">
                                    <span class="arrow">▶</span>
                                    <strong><?= htmlspecialchars($cat['name']) ?></strong>
                                </td>
                                <td class="status"></td>
                                <td class="response_time"></td>
                                <td class="last_checked"></td>
                                <td>
                                    <!--<button class="refresh-btn" data-service-id="<?php /*= $primary['id'] */?>">
                                        ⟳
                                    </button>-->
                                </td>
                            </tr>
                            <!-- ALT SATIRLAR -->
                            <?php foreach ($cat['services'] as $srv): ?>
                                <tr class="child-row child-of-<?= $catId ?>" data-service-id="<?= $srv['id'] ?>" data-loaded="0" style="display:none;">
                                    <td style="padding-left:45px; width: 500px; text-align: justify;">
                                        <?= htmlspecialchars($srv['name']) ?>
                                        <?php if($srv['is_primary']): ?>
                                            <span class="primary-badge">PRIMARY</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="status"></td>
                                    <td class="response_time"></td>
                                    <td class="last_checked"></td>
                                    <td>
                                        <button class="refresh-btn" data-service-id="<?= $srv['id'] ?>">
                                            ⟳1
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</section>
<script>
    document.addEventListener("DOMContentLoaded", function() {

        /* CHECK EDİLECEK SATIRLAR */
        const rows = document.querySelectorAll(
            "#serviceTable tbody tr.single-service, #serviceTable tbody tr.category-row"
        );

        rows.forEach(row => {
            const serviceId = row.id.split('-')[1];

            fetch(`services/service_status.php?id=${serviceId}`)
                .then(res => res.json())
                .then(data => {

                    const statusCell = row.querySelector(".status");
                    const timeCell = row.querySelector(".response_time");
                    const checkedCell = row.querySelector(".last_checked");

                    if(data.status) {
                        if(data.response_time > 2000)
                            statusCell.innerHTML = "<span class='badge slow'>SLOW</span>";
                        else
                            statusCell.innerHTML = "<span class='badge up'>UP</span>";
                    } else {
                        statusCell.innerHTML = "<span class='badge down'>DOWN</span>";
                    }

                    timeCell.textContent = data.response_time ?? 0;
                    checkedCell.textContent = data.last_checked ?? '';

                })
                .catch(err => console.error(err));
        });

        /* ACCORDION */
        document.querySelectorAll(".category-row").forEach(row => {
            row.addEventListener("click", function() {

                const catId = this.dataset.category;
                const arrow = this.querySelector(".arrow");

                arrow.classList.toggle("open");

                const children = document.querySelectorAll(".child-of-" + catId);

                children.forEach(child => {

                    const isHidden = child.style.display === "none";

                    child.style.display = isHidden ? "table-row" : "none";

                    // SADECE ilk açılışta check et
                    if (isHidden && child.dataset.loaded === "0") {

                        const serviceId = child.dataset.serviceId;

                        fetch(`services/service_status.php?id=${serviceId}`)
                            .then(res => res.json())
                            .then(data => {

                                const cells = child.querySelectorAll("td");

                                let statusHtml = "";
                                if(data.status) {
                                    if(data.response_time > 2000)
                                        statusHtml = "<span class='badge slow'>SLOW</span>";
                                    else
                                        statusHtml = "<span class='badge up'>UP</span>";
                                } else {
                                    statusHtml = "<span class='badge down'>DOWN</span>";
                                }

                                child.innerHTML = `<td style="padding-left:45px; width: 500px; text-align: justify;">
                                                        ${cells[0].innerText}
                                                    </td>
                                                    <td class="status">${statusHtml}</td>
                                                    <td class="response_time">${data.response_time ?? 0}</td>
                                                    <td class="last_checked">${data.last_checked ?? ''}</td>
                                                    <td>
                                                        <button class="refresh-btn" data-service-id="${data.id}">
                                                            ⟳
                                                        </button>
                                                    </td> `;
                                child.dataset.loaded = "1";
                            })
                            .catch(err => console.error(err));
                    }
                });
            });
        });

        /* REFRESH BUTON */
        document.addEventListener("click", function(e){

            const btn = e.target.closest(".refresh-btn");
            if(!btn) return;

            const serviceId = btn.dataset.serviceId;
            const row = btn.closest("tr");

            btn.classList.add("loading");

            fetch(`services/service_status.php?id=${serviceId}&refresh=1`)
                .then(res => res.json())
                .then(data => {
                    const statusCell = row.querySelector(".status");
                    const responseCell = row.querySelector(".response_time");
                    const checkedCell = row.querySelector(".last_checked");

                    let statusHtml = "";

                    if(data.status) {
                        if(data.response_time > 2000)
                            statusHtml = "<span class='badge slow'>SLOW</span>";
                        else
                            statusHtml = "<span class='badge up'>UP</span>";
                    } else {
                        statusHtml = "<span class='badge down'>DOWN</span>";
                    }
                    statusCell.innerHTML = statusHtml;
                    responseCell.innerText = data.response_time ?? 0;
                    checkedCell.innerText = data.last_checked ?? "";
                })
                .catch(err => console.error(err))
                .finally(() => {
                    btn.classList.remove("loading");
                });
        });
    });
</script>