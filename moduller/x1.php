<?php
$YENI_GUN = 7;
$now = new DateTime();
$kategoriler = [];

$sql = "SELECT k.id AS kategori_id, k.kod, k.baslik, k.renk, k.collapse, m.id AS mod_id, m.isim AS mod_isim, m.label, m.ikon, m.hedef_url, m.yetki, m.kayit_tarihi, m.modul_tipi, m.hedef_url, m.parametreler FROM mod_kategori k
        LEFT JOIN mod_moduller m ON m.kategori_id = k.id
        WHERE m.aktif=1
        ORDER BY k.baslik, m.isim";
$result = $dba->query($sql);

while ($row = $result->fetch_assoc()) {
    $key = $row['kod'];
    if (!isset($kategoriler[$key])) {
        $kategoriler[$key] = [
            "baslik"   => $row['baslik'],
            "renk"     => $row['renk'],
            "collapse"     => $row['collapse'],
            "moduller" => []
        ];
    }
    if (!empty($row['mod_id'])) {
        // --- YENİ BADGE HESABI ---
        $badge = null;
        if (!empty($row['kayit_tarihi'])) {
            $kayit = new DateTime($row['kayit_tarihi']);
            if ($kayit->diff($now)->days <= $YENI_GUN) {
                $badge = 'YENİ';
            }
        }

        $kategoriler[$key]["moduller"][] = [
                "id"          => $row['mod_id'],
                "isim"        => $row['mod_isim'],
                "ikon"        => $row['ikon'],
                "hedef_url"   => $row['hedef_url'],
                "yetki"       => $row['yetki'],
                "badge"       => $badge,
                "modul_tipi"  => $row['modul_tipi']
        ];
    }
}
?>
<style>
    .modul-kutu {
        background: #fff;
        border-radius: 12px;
        padding: 15px 8px;
        text-align: center;
        margin: 6px;
        transition: .3s;
        cursor: pointer;
        box-shadow: 0 2px 10px rgba(0,0,0,.1);
        position: relative;
    }

    .modul-kutu:hover {
        transform: scale(1.05);
        box-shadow: 0 4px 16px rgba(0,0,0,.2);
    }

    .modul-kutu .icon {
        font-size: 40px;
        color: #444;
        margin-bottom: 6px;
    }

    .modul-kutu h4 {
        font-weight: bold;
        color: #222;
        font-size: 15px;
        line-height: 1.2;
    }

    .badge-custom {
        position: absolute;
        top: 8px;
        right: 10px;
        background: #e74c3c;
        color: #fff;
        padding: 2px 7px;
        font-size: 10px;
        border-radius: 6px;
        font-weight: bold;
    }
    .kategori-baslik {
        user-select: none;
    }
    .kategori-baslik .toggle-icon {
        transition: transform .25s ease;
    }
    .kategori-baslik.collapsed .toggle-icon {
        transform: rotate(-90deg);
    }
</style>
<section class="content-header clearfix">
    <div class="pull-left">
        <h2>Modüller</h2>
    </div>
    <?php if ($_SESSION['admin'] == 1): ?>
        <div class="pull-right" style="margin: 20px 10px 0">
            <a href="2-moduller" class="btn btn-success">
                <i class="fa fa-plus"></i> Yeni Modül Ekle
            </a>
        </div>
    <?php endif; ?>
</section>
<section class="content">
    <?php foreach ($kategoriler as $kat): ?>
        <?php
        // --- Kategori içinde yetkisi olan modül var mı? ---
        $gorunen_moduller = [];
        foreach ($kat['moduller'] as $m) {
            if ($_SESSION['admin'] == 1 || ($_SESSION[$m['yetki']] ?? 0) == 1) {
                $gorunen_moduller[] = $m;
            }
        }

        if (count($gorunen_moduller) == 0) continue; // Bu kategori tamamen gizlenir

        $isCollapsed   = ((int)$kat['collapse'] === 1 AND $_SESSION['admin']===1);
        $collapseClass = $isCollapsed ? '' : 'in';
        $ariaExpanded  = $isCollapsed ? 'false' : 'true';
        $headerClass   = $isCollapsed ? 'collapsed' : '';
        ?>
        <h3 class="kategori-baslik <?= $headerClass ?>" data-toggle="collapse" data-target="#kat_<?= md5($kat['baslik']) ?>" aria-expanded="<?= $ariaExpanded ?>" style="color: <?= $kat['renk'] ?>; margin-top:15px; cursor:pointer; font-size: 22px">
            <i class="fa fa-folder-open"></i>
            <?= $kat['baslik'] ?>
            <i class="fa fa-chevron-down toggle-icon"></i>
        </h3>
        <div class="row collapse <?= $collapseClass ?>" id="kat_<?= md5($kat['baslik']) ?>">
            <?php foreach ($gorunen_moduller as $m): ?>
                <?php
                if ($m['modul_tipi'] == 'iframe') {
                    $link = $m['id']."-".$m['modul_tipi'];
                } else {
                    $link = $m['hedef_url'];
                }
                ?>
                <div class="col-lg-2 col-md-2 col-sm-3 col-xs-4">
                    <div class="modul-kutu" onclick="window.location='<?= $link ?>'" style="min-height:120px; border-top:4px solid <?= $kat['renk'] ?>">
                        <i class="fa <?= $m['ikon'] ?> icon"></i>
                        <h4 style="margin-bottom:0"><?= $m['isim'] ?></h4>
                        <?php if (!empty($m['badge'])): ?>
                            <span class="badge-custom" style="background: <?= $kat['renk'] ?>"> YENİ </span>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endforeach; ?>
</section>