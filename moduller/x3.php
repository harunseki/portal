<?php
$kullanici_id = 1;
$_SESSION['kullanici_mudurluk'] = $user['mudurluk'];
$_SESSION['kullanici_id'] = $kullanici_id;

$q = $dba->prepare("
    SELECT 
        yt.id AS yetki_id,
        yt.yetki AS yetki_key,
        yt.isim  AS yetki_label,
        COALESCE(ym.deger,0) AS deger
    FROM mod_moduller yt
    LEFT JOIN yetkili_moduller ym 
        ON ym.yetki_key = yt.id 
        AND ym.kullanici_id = ?
    ORDER BY yt.isim ASC
");
$q->bind_param("i", $kullanici_id);
$q->execute();
$result = $q->get_result();

$_SESSION['permissions'] = [];
$_SESSION['active_permissions'] = [];

while ($row = $result->fetch_assoc()) {

    $item = [
        'id'    => (int)$row['yetki_id'],
        'key'   => $row['yetki_key'],
        'label' => $row['yetki_label'],
        'value' => (int)$row['deger']
    ];

    $_SESSION['permissions'][$row['yetki_id']] = $item;

    // Eski sistem uyumluluğu
    $_SESSION[$row['yetki_key']] = (int)$row['deger'];

    if ($item['value'] === 1) {
        $_SESSION['active_permissions'][$row['yetki_id']] = $item;
    }
}

$kategoriler = [];

$sql = "SELECT k.id AS kategori_id, k.kod, k.baslik, k.renk,m.id AS mod_id, m.isim AS mod_isim, m.ikon, m.dosya, m.yetki, m.badge FROM mod_kategori k
        LEFT JOIN mod_moduller m ON m.kategori_id = k.id
        ORDER BY k.siralama, m.siralama";

$result = $dba->query($sql);

while ($row = $result->fetch_assoc()) {
    $key = $row['kod'];
    if (!isset($kategoriler[$key])) {
        $kategoriler[$key] = [
            "baslik"   => $row['baslik'],
            "renk"     => $row['renk'],
            "moduller" => []
        ];
    }

    if (!empty($row['mod_id'])) {
        $kategoriler[$key]["moduller"][] = [
            "isim"  => $row['mod_isim'],
            "ikon"  => $row['ikon'],
            "dosya" => $row['dosya'],
            "yetki" => $row['yetki'],
            "badge" => $row['badge']
        ];
    }
}
?>
<style>
    .modul-kutu {
        background: #fff;
        border-radius: 16px;
        padding: 25px 15px;
        text-align: center;
        margin: 10px;
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
        font-size: 45px;
        color: #444;
        margin-bottom: 10px;
    }

    .modul-kutu h4 {
        font-weight: bold;
        color: #222;
        font-size: 15px;
    }

    .badge-custom {
        position: absolute;
        top: 8px;
        right: 10px;
        background: crimson;
        color: #fff;
        padding: 3px 7px;
        font-size: 10px;
        border-radius: 6px;
        font-weight: bold;
    }
</style>
<section class="content-header">
    <h2>Modüller</h2>
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
        if (count($gorunen_moduller) == 0) {
            continue; // Bu kategori tamamen gizlenir
        }
        ?>
        <h3 style="color: <?= $kat['renk'] ?>; margin-top:25px;">
            <i class="fa fa-folder-open"></i> <?= $kat['baslik'] ?>
        </h3>
        <div class="row">
            <?php foreach ($gorunen_moduller as $m): ?>
                <div class="col-md-2 col-sm-4 col-xs-12">
                    <div class="modul-kutu" onclick="window.location='<?= $m['dosya'] ?>'" style="border-top:4px solid <?= $kat['renk'] ?>">
                        <i class="fa <?= $m['ikon'] ?> icon"></i>
                        <h4><?= $m['isim'] ?></h4>
                        <?php if (!empty($m['badge'])): ?>
                            <span class="badge-custom"><?= $m['badge'] ?></span>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endforeach; ?>
</section>