<aside class="left-side sidebar-offcanvas" style="padding-top: 150px;">
    <section class="sidebar">
        <div class="user-panel">
            <div class="pull-left info">
                <a href="index.php"><p style="font-size: 14px"> Hoşgeldin, <?= $cn ?></p>
                <i class="fa fa-circle text-success"></i> Çevrim İçi</a>
            </div>
        </div>
        <ul class="sidebar-menu">
            <li class="<?= ($file=="") ? '' : '' ?>">
                <a href="index.php" style="<?= ($file=="") ? 'font-weight: bold' : '' ?>">
                    <i class="fa fa-home"></i> <span>Ana Sayfa</span>
                </a>
            </li>
            <?php if ($_SESSION['admin']==1 OR $_SESSION['sifre']==1): ?>
            <?php endif;  ?>

            <li class="treeview <?= ($file=="dahili" OR $file=="ldap") ? 'active' : '' ?>">
                    <a href="javascript:void(0);">
                        <i class="fa fa-group"></i> <span>REHBER</span>
                        <i class="fa fa-angle-left pull-right"></i>
                    </a>
                    <ul class="treeview-menu" <?= ($file=="bgys") ? 'style="display:block;"' : '' ?>>
                        <li><a href="ldap.php"><i class="fa fa-angle-double-right"></i> Personel Rehberi</a></li>
                        <li><a href="dahili.php"><i class="fa fa-angle-double-right"></i> Müdürlük Rehberi</a></li>
                        <?php if ($_SESSION['admin']==1 OR $ldap_username=="selcukkarabulut"): ?>
                            <li><a href="ldap.php?x=2"><i class="fa fa-angle-double-right"></i> SMS Gönder</a></li>
                        <?php endif;  ?>
                    </ul>
                </li>
            <!--<li class="<?php /*= ($file=="ldap") ? '' : '' */?>">
                <a href="ldap.php" style=" text-transform: uppercase; <?php /*= ($file=="ldap") ? 'font-weight: bold' : '' */?>">
                    <i class="fa fa-group"></i> <span>Personel Rehberi </span>
                </a>
            </li>-->
            <li class="<?= ($file=="etkinlik_duyuru") ? '' : '' ?>">
                <a href="etkinlik_duyuru.php?x=3" style="<?= ($file=="etkinlik_duyuru") ? 'font-weight: bold' : '' ?>;">
                    <i class="fa fa-bullhorn"></i> <span>Duyuru ve Etkinlikler</span>
                </a>
            </li>
            <?php if ($_SESSION['qr_code']==1 OR $_SESSION['admin']==1) { ?>
            <li class="<?= ($file=="qr_olusturucu") ? '' : '' ?>">
                <a href="qr_olusturucu.php" style="<?= ($file=="qr_olusturucu") ? 'font-weight: bold' : '' ?>">
                    <i class="fa fa-qrcode"></i> <span>QR Oluştur</span>
                </a>
            </li>
            <?php } ?>
            <?php if ($_SESSION['popup_yonetim']==1 OR $_SESSION['admin']==1) { ?>
            <li class="<?= ($file=="popup_yonetim") ? '' : '' ?>">
                <a href="popup_yonetim.php" style="<?= ($file=="popup_yonetim") ? 'font-weight: bold' : '' ?>">
                    <i class="fa fa-qrcode"></i> <span>Popup Yönetimi</span>
                </a>
            </li>
            <?php } ?>
            <!--Formlar-->
            <li class="<?= ($file=="formlar") ? '' : '' ?>">
                <a href="formlar.php" style="<?= ($file=="formlar") ? 'font-weight: bold' : '' ?>">
                    <i class="fa fa-file-text-o"></i> <span>Formlar</span>
                </a>
            </li>
            <!--Mevzuat-->
            <li class="">
                <a href="mevzuat.php">
                    <i class="fa fa-balance-scale icon"></i> <span>Mevzuat</span>
                </a>
            </li>
            <!--Eczaneler-->
            <li class="">
                <a href="https://www.cankaya.bel.tr/nobetci-eczaneler" target="_blank">
                    <i class="fa fa-medkit"></i> <span>Nöbetçi Eczaneler</span>
                </a>
            </li>
            <!--Belediyemiz-->
            <li class="treeview <?= ($file=="belediyemiz") ? '' : '' ?>">
                <a href="javascript:void(0);">
                    <i class="fa fa-landmark"></i> <span>Belediyemiz</span>
                    <i class="fa fa-angle-left pull-right"></i>
                </a>
                <ul class="treeview-menu" <?= ($file=="bgys") ? 'style="display:block;"' : '' ?>>
                    <li><a href="https://www.cankaya.bel.tr/baskan" target="_blank"><i class="fa fa-angle-double-right"></i> Başkan</a></li>
                    <li><a href="https://www.cankaya.bel.tr/kurumsal/baskan-yardimcilari" target="_blank"><i class="fa fa-angle-double-right"></i> Başkan Yardımcıları</a></li>
                    <li><a href="https://www.cankaya.bel.tr/kurumsal/mudurlukler" target="_blank"><i class="fa fa-angle-double-right"></i> Müdürlükler </a></li>
                    <li><a href="https://www.cankaya.bel.tr/uploads/documents/CANKAYA_BELEDIYE_STRATEJIK_PLAN_1749735050_INzqyckN.pdf" target="_blank"><i class="fa fa-angle-double-right"></i> Stratejik Plan 2025-2029 </a></li>
                    <li><a href="https://www.cankaya.bel.tr/iletisim" target="_blank"><i class="fa fa-angle-double-right"></i> İletişim</a></li>
                </ul>
            </li>
            <!--BGYS-->
            <!--<li class="treeview <?php /*= ($file=="bgys") ? '' : '' */?>">
                <a href="javascript:void(0);">
                    <i class="fa fa-folder"></i> <span>BGYS-KVKK</span>
                    <i class="fa fa-angle-left pull-right"></i>
                </a>
                <ul class="treeview-menu" <?php /*= ($file=="bgys") ? 'style="display:block;"' : '' */?>>
                    <li><a href="../backup/bgys/ÇB-BGYS-PL-02%20Bilgi%20Güvenliği%20Alt%20Politikaları.docx" target="_blank"><i class="fa fa-angle-double-right"></i> Alt Politikalar</a></li>
                    <li><a href="../backup/video/BGYS%20Farkındalık%20Eğitimi.mp4" target="_blank"><i class="fa fa-angle-double-right"></i> BGYS Eğitimi</a></li>
                    <li><a href="../backup/bgys/Bilgi%20Güvenliği%20Politikamız.docx" target="_blank"><i class="fa fa-angle-double-right"></i> Bilgi Güvenliği</a></li>
                    <li><a href="../backup/bgys/ÇB-BGYS-FR-19%20Güvenlik%20Duvarı%20Erişim%20Talep%20Formu.docx" target="_blank"><i class="fa fa-angle-double-right"></i> Güvenlik Duvarı Talep Formu</a></li>
                    <li><a href="../backup/bgys/ÇB-BGYS-FR-04%20Güvenlik%20İhlalleri%20Bildirim%20Formu.docx" target="_blank"><i class="fa fa-angle-double-right"></i> İhlal Bildirim Formu</a></li>
                    <li><a href="../backup/bgys/KVKK%20Kılavuzu.pdf" target="_blank"><i class="fa fa-angle-double-right"></i> KVKK Kılavuz</a></li>
                    <li><a href="../backup/bgys/KPS%20TAAHÜTNAME.pdf" target="_blank"><i class="fa fa-angle-double-right"></i> KPS Taahhütname</a></li>
                    <li><a href="../backup/video/KVKK%20Farkındalık%20Eğitimi.mp4" target="_blank"><i class="fa fa-angle-double-right"></i> KVKK Eğitimi</a></li>
                    <li><a href="../backup/bgys/Tapu%20Takbis%20Taahhütname.docx" target="_blank"><i class="fa fa-angle-double-right"></i> Tapu Taahhütname</a></li>
                    <li><a href="../backup/bgys/ÇB-BGYS-FR-13%20Personel%20VPN%20Erişim%20Talep%20Formu.docx" target="_blank"><i class="fa fa-angle-double-right"></i> VPN Talep Formu</a></li>
                    <li><a href="../backup/bgys/ÇB-BGYS-FR-14%20Yetki%20Talep%20Formu.docx" target="_blank""><i class="fa fa-angle-double-right"></i> Yetki Talep Formu</a></li>
                </ul>
            </li>-->
            <!--Yetkili-->
            <?php if ($_SESSION['yetkili_islemleri']==1  OR $_SESSION['admin']==1 ) { ?>
            <li class="treeview <?= ($file=="yetkili") ? '' : '' ?>">
                <a href="#" style="<?= ($file=="yetkili") ? 'font-weight: bold' : '' ?>">
                    <i class="fa fa-edit"></i> <span>Yetkili İşlemleri</span>
                    <i class="fa fa-angle-left pull-right"></i>
                </a>
                <ul class="treeview-menu">
                    <li><a href="yetkili.php" style="<?= ($file=="yetkili" AND ($x==1 OR empty($x))) ? 'font-weight: bold' : '' ?>"><i class="fa fa-angle-double-right"></i> Yetkili Ekle</a></li>
                    <li><a href="yetkili.php?x=2" style="<?= ($file=="yetkili" AND $x==2) ? 'font-weight: bold' : '' ?>"><i class="fa fa-angle-double-right"></i> Yetkili Düzenle</a></li>
                </ul>
            </li>
            <?php } ?>
        </ul>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
        <div class="sidebar-social">
            <div class="social-badge" style="margin-bottom: 20px">
                <img src="img/bim_logo_son.png" alt="Portalımız" loading="lazy" width="100" height="auto">
            </div>
            <div class="social-icons">
                <a href="https://www.facebook.com/cankayabelediye/" class="social-icon" target="_blank" style="color: white;"><i class="fa-brands fa-facebook-f"></i></a>
                <a href="https://www.x.com/cankayabelediye" class="social-icon" target="_blank" style="color: white;"><i class="fa-brands fa-x-twitter"></i></a>
                <a href="https://www.instagram.com/cankayabelediye" class="social-icon" target="_blank" style="color: white;"><i class="fa-brands fa-instagram"></i></a>
                <a href="https://www.youtube.com/@%C3%87ankaya_Belediyesi" class="social-icon" target="_blank" style="color: white;"><i class="fa-brands fa-youtube"></i></a>
            </div>
        </div>
        <style>
            .skin-blue .sidebar > .sidebar-menu > li > a:hover, .skin-blue .sidebar > .sidebar-menu > li.active > a {
                 color: #ffffff;
                 background: transparent;
            }
            /* === Sidebar genel yapı === */
            .left-side.sidebar-offcanvas {
                position: fixed;
                top: 0;
                left: 0;
                height: 100vh;
                width: 217px; /* Sidebar genişliği */
                background: #222; /* Arka plan rengi */
                overflow: hidden; /* Dışarı taşmayı engelle */
                z-index: 10;
            }

            /* Sidebar içeriği (menü bölümü) */
            .sidebar {
                position: relative;
                height: 100%;
                overflow-y: hidden; /* Menü çoksa sadece bu kısım kayar */
                overflow-x: hidden;
                padding-bottom: 90px; /* Sosyal medya bloğu için boşluk bırak */
            }

            /* Menü altındaki sosyal medya bölümü */
            .sidebar-social {
                position: absolute;
                bottom: 0;
                left: 0;
                width: 100%;
                text-align: center;
                padding: 10px 0 25px;
                background: #222; /* Sidebar’la aynı renk */
                z-index: 5;
            }

            /* Sosyal medya başlığı */
            .sidebar-social .social-title {
                color: #fff;
                font-weight: bold;
                margin-bottom: 8px;
                font-size: 14px;
            }

            /* Sosyal medya ikonları */
            .sidebar-social .social-icons {
                display: flex;
                justify-content: center;
                gap: 10px;
            }

            .sidebar-social .social-icon {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                width: 32px;
                height: 32px;
                border-radius: 50%;
                background: #000;
                color: #fff;
                font-size: 15px;
                transition: all 0.3s ease;
            }

            .sidebar-social .social-icon:hover {
                background: #444;
                transform: scale(1.1);
            }

            /* Menü alt başlıkları (treeview) */
            .treeview-menu {
                max-height: 290px;
                /*overflow-y: auto;*/
            }

            .treeview-menu li a {
                display: inline-block;
                width: 210px;
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
                font-size: 13px;
            }
            .social-badge { margin-bottom: 5px; display:flex; justify-content:center; align-items:center; }
            .social-badge img { display:block; max-width:100%; height:auto; border-radius:8px; box-shadow:0 6px 18px rgba(0,0,0,0.15); }
        </style>


    </section>
</aside>