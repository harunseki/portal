<?php
// ----------------------
// FİLTRE PARAMETRELERİ
// ----------------------
$start  = $_GET['start']  ?? date('Y-m-01');
$end    = $_GET['end']    ?? date('Y-m-d');
$source = $_GET['source'] ?? '';
$package = $_GET['package'] ?? '';

function e($str) {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

// ----------------------
// ÖZET BİLGİLER
// ----------------------

// Aktif hak sayısı
$stmt = $dba->prepare("
    SELECT COUNT(*) 
    FROM cardmealallowement
    WHERE CURDATE() BETWEEN startDate AND finishDate
");
$stmt->execute();
$stmt->bind_result($activeCount);
$stmt->fetch();
$stmt->close();

// Bu ay gelir
$stmt = $dba->prepare("
    SELECT IFNULL(SUM(amount),0)
    FROM orders
    WHERE status = 'payment_success'
    AND MONTH(createdAt)=MONTH(CURRENT_DATE())
    AND YEAR(createdAt)=YEAR(CURRENT_DATE())
");
$stmt->execute();
$stmt->bind_result($monthlyIncome);
$stmt->fetch();
$stmt->close();

// ----------------------
// GİRİŞ ÇIKIŞ RAPORU
// ----------------------
$logQuery = "
SELECT cardNumber, description, creationDate
FROM cardmovement
WHERE creationDate BETWEEN ? AND ?
ORDER BY creationDate DESC
LIMIT 500
";

$stmt = $dba->prepare($logQuery);
$stmt->bind_param("ss", $start, $end);
$stmt->execute();
$logs = $stmt->get_result();
$stmt->close();

// ----------------------
// PAKET SATIŞ RAPORU
// ----------------------

$where = "WHERE kayitTarihi BETWEEN ? AND ?";
$params = [$start, $end];
$types = "ss";

if (!empty($source)) {
    $where .= " AND source = ?";
    $params[] = $source;
    $types .= "s";
}

if (!empty($package)) {
    $where .= " AND package = ?";
    $params[] = $package;
    $types .= "i";
}

$sql = "
SELECT package, source, COUNT(*) as total
FROM cardmealallowement
$where
GROUP BY package, source
ORDER BY package
";

$stmt = $dba->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$packageReport = $stmt->get_result();
$stmt->close();
?>
<style>
    .card { background:#fff; padding:20px; margin-bottom:20px; border-radius:8px; box-shadow:0 4px 8px rgba(0,0,0,0.08); }
    .flex { display:flex; gap:20px; }
    .stat { flex:1; text-align:center; }
    table { width:100%; border-collapse:collapse; }
    table th, table td { padding:10px; border-bottom:1px solid #ddd; text-align:left; }
    th { background:#f0f0f0; }
    .filter { margin-bottom:20px; }
    input, select { padding:6px; }
    button { padding:6px 12px; background:#065a28; color:#fff; border:none; }
</style>
<section class="content-header">
    <div class="row">
        <div class="col-xs-6">
            <h2>Rapor Paneli</h2>
        </div>
        <div class="col-xs-6 text-right">
            <!-- Modal Butonu -->
            <?php if ($_SESSION['yemekhane']==1) { ?>
            <?php } ?>
        </div>
    </div>
</section>

<div class="card filter">
    <form method="GET">
        Başlangıç: <input type="date" name="start" value="<?=e($start)?>">
        Bitiş: <input type="date" name="end" value="<?=e($end)?>">

        Source:
        <select name="source">
            <option value="">Tümü</option>
            <option value="online" <?= $source=='online'?'selected':'' ?>>Online</option>
            <option value="admin" <?= $source=='admin'?'selected':'' ?>>Admin</option>
        </select>

        Paket:
        <input type="number" name="package" value="<?=e($package)?>" placeholder="örn 30">

        <button type="submit">Filtrele</button>
    </form>
</div>

<!-- ÖZET -->
<div class="flex">
    <div class="card stat">
        <h3>Aktif Hak</h3>
        <h2><?=e($activeCount)?></h2>
    </div>
    <div class="card stat">
        <h3>Bu Ay Gelir</h3>
        <h2><?=number_format($monthlyIncome,2)?> ₺</h2>
    </div>
</div>

<!-- GİRİŞ ÇIKIŞ -->
<div class="card">
    <h3>Giriş / Çıkış Logları (Son 500 kayıt)</h3>
    <table>
        <tr>
            <th>Kart</th>
            <th>Tip</th>
            <th>Tarih</th>
        </tr>
        <?php while($row = $logs->fetch_assoc()): ?>
            <tr>
                <td><?=e($row['cardNumber'])?></td>
                <td><?=e($row['description'])?></td>
                <td><?=e($row['creationDate'])?></td>
            </tr>
        <?php endwhile; ?>
    </table>
</div>

<!-- PAKET RAPOR -->
<div class="card">
    <h3>Paket Satış Raporu</h3>
    <table>
        <tr>
            <th>Paket Gün</th>
            <th>Source</th>
            <th>Satış Adedi</th>
        </tr>
        <?php while($row = $packageReport->fetch_assoc()): ?>
            <tr>
                <td><?=e($row['package'])?></td>
                <td><?=e($row['source'])?></td>
                <td><?=e($row['total'])?></td>
            </tr>
        <?php endwhile; ?>
    </table>
</div>
