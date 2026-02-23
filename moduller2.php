<?php
$file = "mevzuat";
require_once "inc/header.php";
require_once "inc/menu1.php";
$moduller = [
    ["isim" => "Popup Yönetimi", "ikon" => "fa-bullhorn", "dosya" => "popup_yonetim.php", "yetki" => "popup_yonetim"],
    ["isim" => "PDKS Rapor", "ikon" => "fa-chart-bar", "dosya" => "pdks_rapor.php", "yetki" => "pdks_rapor"],
    ["isim" => "QR Oluştur", "ikon" => "fa-qrcode", "dosya" => "qr_olusturucu.php", "yetki" => "qrcode"],

    ["isim" => "Eğitim Bilgisi Sorgula", "ikon" => "fa-school", "dosya" => "yok.php", "yetki" => "yok"],
    ["isim" => "SMS Modülü", "ikon" => "fa-message", "dosya" => "sms.php", "yetki" => "sms"],

    ["isim" => "Tüm Personel Rapor", "ikon" => "fa-users", "dosya" => "personeller.php?x=2", "yetki" => "personel_islemleri"],
    ["isim" => "İzin - Rapor Sorgula", "ikon" => "fa-user-check", "dosya" => "personeller.php", "yetki" => "personel_islemleri"],
    ["isim" => "Müdürlük Bazlı Rapor", "ikon" => "fa-building", "dosya" => "personeller.php?x=3", "yetki" => "personel_islemleri"],

    ["isim" => "Formlar", "ikon" => "fa-file-alt", "dosya" => "formlar.php", "yetki" => "formlar"],
    ["isim" => "Etkinlik / Duyuru Modülü", "ikon" => "fa-bullhorn", "dosya" => "etkinlik_duyuru.php?x=3", "yetki" => "etkinlik_duyuru"],

    ["isim" => "Yemekhane Modülü", "ikon" => "fa-utensils", "dosya" => "yemekhane.php", "yetki" => "yemekhane"],
    ["isim" => "Yetkili İşlemleri", "ikon" => "fa-user-shield", "dosya" => "yetkili.php", "yetki" => "yetkili_islemleri"]
];
?>
<style>
    .mevzuat-box {
        background: #f7f7f7;
        border-radius: 20px;
        padding: 40px 20px;
        text-align: center;
        margin: 10px;
        transition: all 0.3s ease;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        cursor: pointer;
    }
    .mevzuat-box:hover {
        background: #e8f0ff;
        transform: scale(1.05);
        box-shadow: 0 6px 18px rgba(0,0,0,0.2);
    }
    .mevzuat-box .icon {
        font-size: 60px;
        color: #057709;
        margin-bottom: 15px;
    }
    .mevzuat-box h3 {
        font-weight: bold;
        color: #333;
    }
</style>
<aside class="right-side">
    <section class="content-header">
        <h2>Modüller</h2>
    </section>

    <section class="content">
        <div class="row">

            <?php foreach ($moduller as $m): ?>
                <?php if ($_SESSION['admin'] != 1 && ($_SESSION[$m['yetki']] ?? 0) != 1) continue; ?>

                <div class="col-md-3 col-sm-6 col-xs-12">
                    <div class="mevzuat-box" onclick="window.location='<?= $m['dosya'] ?>'">
                        <i class="fa <?= $m['ikon'] ?> icon"></i>
                        <h3><?= $m['isim'] ?></h3>
                    </div>
                </div>
            <?php endforeach; ?>

        </div>
    </section>
</aside>
<?php require_once "inc/footer.php"; ?>
