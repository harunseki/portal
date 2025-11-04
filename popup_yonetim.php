<?php
$file = "popup_yonetim";
require_once "inc/header.php";
if (empty($_SESSION['admin']) AND empty($_SESSION['popup_yonetim'])) {
    // Yetkisiz erişim
    http_response_code(403); // 403 Forbidden
    ?>
    <style>
        .error-box {
            background: #fff;
            padding: 40px;
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
    </style>
    <div class="error-box">
        <h1>403</h1>
        <p>Bu sayfaya erişim yetkiniz yok.</p>
        <a href="index.php">Ana Sayfaya Dön</a>
    </div>
    <?php
    exit();
}
require_once "inc/menu.php";

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