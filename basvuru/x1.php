<?php
$ldap_username = $_SESSION['ldap_username'] ?? '';
$port = (int)($_GET['port'] ?? 0);

// iframe url
$iframe_url = "http://10.1.1.66:" . $port . "/?samaccountname=" . urlencode($ldap_username);

// Modül başlığını çek
$stmt = $dba->prepare("SELECT isim FROM mod_moduller WHERE dosya LIKE CONCAT('%', ?, '%') LIMIT 1");
$stmt->bind_param("s", $port);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();

$baslik = $row['isim'] ?? '';
?>
<section class="content-header clearfix">
    <div class="pull-left">
        <h2><?= htmlspecialchars($modul['isim']) ?></h2>
    </div>
</section>
<section class="content" style="padding: 0;">
    <iframe src="<?= htmlspecialchars($iframe_url) ?>" style="width:100%; height:83.5vh; border:none;" sandbox="allow-scripts allow-same-origin allow-forms allow-popups allow-popups-to-escape-sandbox allow-downloads">
    </iframe>
</section>