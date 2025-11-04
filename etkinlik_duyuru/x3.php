<?php
// --- SAYFALAMA AYARLARI ---
$limit = 12; // sayfa başına 8 etkinlik
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $limit;

// --- TOPLAM KAYIT SAYISI ---
$totalQuery = $dba->query("SELECT COUNT(*) as total FROM etkinlik_ve_duyuru");
$totalRows = $totalQuery->fetch_assoc()['total'];
$totalPages = ceil($totalRows / $limit);

// --- VERİLERİ ÇEK ---
$sonuclar = $dba->query("SELECT * FROM etkinlik_ve_duyuru ORDER BY publish_at DESC LIMIT $limit OFFSET $offset");
?>

<section class="content-header">
    <div class="row">
        <div class="col-xs-6">
            <h2>Etkinlik ve Duyurular</h2>
        </div>
    </div>
</section>

<style>
    /* Thumbnail kutucukları */
    .thumbnail {
        min-width: 320px;
        height: 210px;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        text-align: center;
        padding: 10px;
        border-radius: 10px;
        overflow: hidden;
        transition: box-shadow 0.3s ease;
    }
    .thumbnail img {
        width: 100%;
        height: 190px;
        object-fit: cover;
        border-radius: 5px;
        cursor: pointer;
        border: 1px solid #ddd;
    }

    /* Caption */
    .thumbnail .caption {
        padding: 5px;
        background: rgba(240,240,240,0.8);
        border-radius: 3px;
        min-height: 60px;
    }

    /* Pagination */
    .pagination {
        display: flex;
        justify-content: center;
        align-items: center;
        /*margin-top: 25px;*/
        margin: 10px 0;
    }

    /* Modal responsive */
    .modal-dialog {
        max-width: 90%;
        margin: 30px auto;
    }
    .modal-content {
        border-radius: 8px;
    }
    .modal-body {
        text-align: center;
        padding: 15px;
    }
    .modal-body img {
        max-width: 100%;
        max-height: 80vh;
        object-fit: contain;
        border-radius: 6px;
    }
</style>

<section class="content">
    <div class="container" style="display: contents;">
        <div class="row" style="margin-top:10px;">
            <?php if ($sonuclar->num_rows > 0): ?>
                <?php while($row = $sonuclar->fetch_assoc()):
                    $image = htmlspecialchars($row['image_url']);
                    $title = htmlspecialchars($row['title']);
                    $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $title), '-'));
                    $link = htmlspecialchars($row['direct_link']);
                    $id = "etk_" . $row['id'];
                    ?>
                    <div class="col-md-3 col-sm-6" id="resim_<?=$row['id']?>">
                        <div class="thumbnail" data-toggle="modal" data-target="#<?=$id?>">
                            <img src="<?=$image?>" alt="<?=$title?>" >
                            <!--<div class="caption text-center">
                                <h4><?php /*=$title*/?></h4>
                            </div>-->
                        </div>
                    </div>

                    <!-- Modal -->
                    <div class="modal fade" id="<?=$id?>" tabindex="-1" role="dialog" aria-hidden="true">
                        <div class="modal-dialog" role="document"  style="justify-content: center; display: flex; width: 1200px;">
                            <div class="modal-content">
                                <div class="modal-header text-right">
                                    <button type="button" class="btn btn-default btn-sm" data-dismiss="modal">Kapat</button>
                                </div>
                                <div class="modal-body">
                                    <a href="<?= $link ?>" target="_blank">
                                        <img src="<?=$image?>" alt="<?=$title?>">
                                    </a>
                                    <div style="margin-top:15px;">
                                        <h4 style="font-weight:bold;"><?=$title?></h4>
                                        <a href="<?= $link ?>" target="_blank" class="btn btn-success btn-sm" style="margin-top:10px;">
                                            <i class="fa fa-external-link"></i> Detay Git
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                <?php endwhile; ?>
            <?php else: ?>
                <div class="col-md-12 text-center">
                    <p style="font-size:1.5rem; color:#777;">Henüz etkinlik veya duyuru bulunmamaktadır.</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- SAYFALAMA -->
        <?php if ($totalPages > 1): ?>
            <nav>
                <ul class="pagination">
                    <li class="<?=($page <= 1 ? 'disabled' : '')?>">
                        <a href="?x=3&page=<?=max(1, $page - 1)?>">&laquo; Önceki</a>
                    </li>
                    <?php for($i = 1; $i <= $totalPages; $i++): ?>
                        <li class="<?=($i == $page ? 'active' : '')?>">
                            <a href="?x=3&page=<?=$i?>"><?=$i?></a>
                        </li>
                    <?php endfor; ?>
                    <li class="<?=($page >= $totalPages ? 'disabled' : '')?>">
                        <a href="?x=3&page=<?=min($totalPages, $page + 1)?>">Sonraki &raquo;</a>
                    </li>
                </ul>
            </nav>
        <?php endif; ?>
    </div>
</section>
