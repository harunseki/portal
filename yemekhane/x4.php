<?php
if (empty($_GET['payment']) OR empty($_GET['msg']) ) {
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
        <p>Bu sayfaya erişim yetkiniz bulunmamaktadır.</p>
        <a href="index.php">Ana Sayfaya Dön</a>
    </div>
    <script>
        setTimeout(function () {
            window.location.href = "index.php";
        }, 2000);
    </script>
    <?php
    exit();
}
$payment = $_GET['payment'] ?? 'fail';
$msg     = $_GET['msg'] ?? 'Teknik bir hata oluştu.';

$payment = strtolower($payment);

/* varsayılanlar */
$title       = 'Ödeme Başarısız';
$icon        = '❌';
$redirectUrl = '/3-yemekhane';
$delay       = 4000;

/* duruma göre ayarla */
switch ($payment) {
    case 'success':
        $title       = 'Ödeme Başarılı';
        $icon        = '✅';
        $redirectUrl = '/3-yemekhane';
        break;

    case 'warning':
        $title       = 'Ödeme Alındı';
        $icon        = '⚠️';
        $redirectUrl = '/5-yemekhane';
        break;

    case 'fail':
    default:
        break;
}

$msg = htmlspecialchars(urldecode($msg), ENT_QUOTES, 'UTF-8');
?>
<style>
    .box {
        text-align:center;
    }
    .content{
        justify-content: center;
        display: flex;
    }
    .icon {
        font-size:60px;
        margin-bottom:20px;
    }
    h1 {
        margin:10px 0;
    }
    p {
        color:#555;
        margin:15px 0;
    }
    .countdown {
        font-size:14px;
        color:#888;
    }
</style>
<section class="content-header">
    <h2><i class="fa fa-id-card"></i> Ödeme Sonucu</h2>
</section>
<section class="content">
    <div class="box box-success" style="margin-top:20px;">
        <div class="icon"><?= $icon ?></div>
        <h1><?= $title ?></h1>
        <p><?= $msg ?></p>
        <div class="countdown">
            Birkaç saniye içinde yönlendirileceksiniz...
        </div>
    </div>
    <script>
        setTimeout(function () {
            window.location.href = "<?= $redirectUrl ?>";
        }, <?= $delay ?>);
    </script>
</section>