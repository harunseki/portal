<?php
$file = "yok";
require_once "inc/header.php";
require_once "inc/menu1.php";

// GET parametresi al
$x = isset($_GET['x']) ? (int) $_GET['x'] : 1;
$x = $purifier->purify(rescape($x));

// Dahil edilecek dosya yolu
$pageFile = sprintf("%s/x%d.php", $file, $x);

// Varsayılan sayfa
if ($x <= 1 || !file_exists($pageFile)) {
    $pageFile = "$file/x1.php";
}
?>
<aside class="right-side">
    <?php require_once $pageFile; ?>
</aside>
<?php require_once "inc/footer.php"; ?>
