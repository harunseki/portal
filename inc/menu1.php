<aside class="left-side sidebar-offcanvas" style="padding-top: 150px;">
    <section class="sidebar">
        <div class="user-panel">
            <div class="pull-left info">
                <a href="index.php"><p style="font-size: 14px"> Hoşgeldin, <?= $cn ?></p>
                    <i class="fa fa-circle text-success"></i> Çevrim İçi</a>
            </div>
        </div>
        <ul class="sidebar-menu">
            <?php
            $menus = getMenuTree($dba);
            renderMenu($menus, $file);
            ?>
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