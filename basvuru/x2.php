<?php
$ldap_username = $_SESSION['ldap_username'] ?? '';
$port = (int)($_GET['port'] ?? 0);
$x = (int)($_GET['x'] ?? 0);

// iframe url
$iframe_url = "https://yeep.cankaya.bel.tr/login?samaccountname=" . urlencode($ldap_username);

// Modül başlığını çek
$stmt = $dba->prepare("SELECT isim FROM mod_moduller WHERE dosya LIKE CONCAT('%', ?, '%') LIMIT 1");
$stmt->bind_param("s", $port);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();

$baslik = $row['isim'] ?? 'Başvurular';
?>
<section class="content-header clearfix">
    <div class="pull-left">
        <h2><?=htmlspecialchars($baslik)?>  Başvuru Modülü</h2>
    </div>
</section>
<section class="content" style="padding: 0;">
    <iframe src="<?= htmlspecialchars($iframe_url) ?>" style="width:100%; height:83.5vh; border:none;" sandbox="allow-scripts allow-same-origin allow-forms allow-popups allow-popups-to-escape-sandbox allow-downloads">
    </iframe>
</section>