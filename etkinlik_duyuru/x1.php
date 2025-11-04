<?php
// --- SAYFALAMA AYARLARI ---
$limit = 12; // Sayfa başına 10 thumbnail
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $limit;

// --- TOPLAM KAYIT SAYISI ---
$totalQuery = $dba->query("SELECT COUNT(*) as total FROM files");
$totalRows = $totalQuery->fetch_assoc()['total'];
$totalPages = ceil($totalRows / $limit);

// --- VERİLERİ ÇEK ---
$sonuclar = $dba->query("SELECT * FROM files ORDER BY id DESC LIMIT $limit OFFSET $offset");
?>
<section class="content-header">
    <div class="row">
        <div class="col-xs-6">
            <h2>Etkinlik ve Duyurular</h2>
        </div>
        <div class="col-xs-6 text-right">
            <?php if ($_SESSION['etkinlik_duyuru']==1 OR $_SESSION['admin']==1) { ?>
                <button class="btn btn-success pull-right" data-toggle="modal" data-target="#etkinlikModal" style="margin-top: 20px;">
                    <i class="glyphicon glyphicon-plus"></i> Yeni Etkinlik Ekle
                </button>
            <?php } ?>
        </div>
    </div>
</section>

<?php if ($_SESSION['etkinlik_duyuru']==1 OR $_SESSION['admin']==1) { ?>
    <div class="modal fade" id="etkinlikModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <form id="etkinlikForm" method="post" enctype="multipart/form-data">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal">&times;</button>
                        <h4 class="modal-title">Yeni Etkinlik Ekle</h4>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label>Dosya Adı</label>
                            <input type="text" name="adi" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>Dosya Seç</label>
                            <input type="file" name="file" class="form-control" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-primary">Kaydet</button>
                        <button type="button" class="btn btn-default" data-dismiss="modal">Kapat</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
<?php } ?>
<style>
    .thumbnail {
        height: 320px; /* Sabit yükseklik */
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        text-align: center;
        padding: 10px;
    }
    .thumbnail img {
        width: 100%;
        height: 200px;
        object-fit: cover;
        border-radius: 5px;
    }
    .thumbnail h4 {
        font-size: 14px;
        margin-top: 8px;
        min-height: 40px;
        line-height: 1.2em;
        overflow: hidden;
    }
    .pagination {
        display: flex;
        justify-content: center;
        align-items: center;
        margin-top: 25px;
    }
    /* Thumbnail hover büyüme efekti */
    .thumbnail {
        transition: transform 0.3s ease, box-shadow 0.3s ease;
        border-radius: 10px;
        overflow: hidden;
    }

    .thumbnail img {
        transition: transform 0.3s ease;
        border-radius: 10px;
    }

    /* Hover olduğunda öne çıkma efekti */
    .thumbnail:hover {
        transform: scale(1.05);
        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.3);
        z-index: 10;
    }

    /* Görselin kendisini hafif büyütelim */
    .thumbnail:hover img {
        transform: scale(1.08);
    }

    /* Başlık ve butonlar da aynı kalır */
    .thumbnail .caption {
        transition: opacity 0.3s ease;
    }

</style>
<section class="content">
    <div class="container">
        <div class="row" style="margin-top:10px;">
            <?php if ($sonuclar->num_rows > 0): ?>
                <?php while($row = $sonuclar->fetch_assoc()):
                    $dosya = "img/files/" . $row['file'];
                    $ext = strtolower(pathinfo($dosya, PATHINFO_EXTENSION));
                    $id = "modal_" . $row['id'];
                    ?>
                    <div class="col-md-2 col-sm-4" id="resim_<?=$row['id']?>" style="margin-bottom:20px;">
                        <div class="thumbnail">
                            <?php if (in_array($ext, ['jpg','jpeg','png','gif'])): ?>
                                <img src="<?=$dosya?>" alt="<?=htmlspecialchars($row['adi'])?>" data-toggle="modal" data-target="#<?=$id?>">
                            <?php elseif ($ext == 'pdf'): ?>
                                <a href="<?=$dosya?>" target="_blank" class="btn btn-danger" style="margin-top:80px;">📄 PDF Görüntüle</a>
                            <?php else: ?>
                                <a href="<?=$dosya?>" download class="btn btn-default" style="margin-top:80px;">📂 Dosya İndir</a>
                            <?php endif; ?>

                            <div class="caption text-center" style="padding:2px; background: rgba(227,227,227,0.5);border-radius: 3px; transition: opacity 0.3s;">
                                <h4 style="margin-bottom: 2px; font-size: 13px"><?=htmlspecialchars($row['adi'])?></h4>
                                <?php if ($_SESSION['etkinlik_duyuru']==1 OR $_SESSION['admin']==1) { ?>
                                    <button type="button" class="btn btn-danger btn-sm silBtn" data-id="<?=$row['id']?>">Sil</button>
                                <?php } ?>
                            </div>
                        </div>
                    </div>

                    <?php if (in_array($ext, ['jpg','jpeg','png','gif'])): ?>
                    <div class="modal fade" id="<?=$id?>" tabindex="-1" role="dialog">
                        <div class="modal-dialog modal-lg" role="document">
                            <div class="modal-content">
                                <div class="modal-header" style="border-bottom: none !important;">
                                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                                </div>
                                <div class="modal-body text-center">
                                    <img src="<?=$dosya?>" class="img-responsive center-block" alt="<?=htmlspecialchars($row['adi'])?>" style="max-height:80vh; margin:auto;">
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
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
                        <a href="?page=<?=max(1, $page - 1)?>">&laquo; Önceki</a>
                    </li>
                    <?php for($i = 1; $i <= $totalPages; $i++): ?>
                        <li class="<?=($i == $page ? 'active' : '')?>">
                            <a href="?page=<?=$i?>"><?=$i?></a>
                        </li>
                    <?php endfor; ?>
                    <li class="<?=($page >= $totalPages ? 'disabled' : '')?>">
                        <a href="?page=<?=min($totalPages, $page + 1)?>">Sonraki &raquo;</a>
                    </li>
                </ul>
            </nav>
        <?php endif; ?>

        <script>
            var silinecekId = 0;

            $(document).ready(function() {
                // ✅ Etkinlik ekleme
                $("#etkinlikForm").on("submit", function(e){
                    e.preventDefault();
                    var formData = new FormData(this);

                    $.ajax({
                        url: "etkinlik_duyuru/dosya_ekle.php",
                        type: "POST",
                        data: formData,
                        contentType: false,
                        processData: false,
                        dataType: "json",
                        success: function(res){
                            if(res.status === "success"){
                                toastr.success(res.message);
                                let filename = res.file_name;

                                // ✅ Log kaydı AJAX ile gönder
                                $.post("class/log_ekle.php", { etkinlik_duyuru:1, sil:0, file_name: filename }, function(logRes){
                                    console.log("Log sonucu:", logRes);
                                });
                                $("#etkinlikModal").modal("hide");
                                setTimeout(function(){
                                    location.reload();
                                }, 2000);
                            } else {
                                toastr.error("Hata: " + res.message);
                            }
                        },
                        error: function(){
                            toastr.error("Sunucuya ulaşılamadı veya 500 hatası oluştu.");
                        }
                    });
                });

                // ✅ Silme butonuna tıklandığında modal aç
                $(".silBtn").on("click", function(e){
                    e.preventDefault();
                    e.stopPropagation();

                    silinecekId = $(this).data("id");
                    $("#confirmSilModal").modal("show");
                });

                // ✅ Modalda Evet'e basınca silme işlemi
                $("#confirmSilBtn").on("click", function() {
                    if(silinecekId <= 0) return;

                    $.ajax({
                        url: "etkinlik_duyuru/dosya_sil.php",
                        type: "POST",
                        data: {id: silinecekId},
                        dataType: "json",
                        success: function(res){
                            if(res.status === "success"){
                                toastr.success(res.message);
                                let filename = res.file_name;

                                // ✅ Log kaydı AJAX ile gönder
                                $.post("class/log_ekle.php", { etkinlik_duyuru:1, sil:1, file_name: filename }, function(logRes){
                                    console.log("Log sonucu:", logRes);
                                });
                                $("#resim_" + silinecekId).slideUp(500, function() {
                                    $(this).remove();
                                });
                            } else {
                                toastr.error("Hata: " + res.message);
                            }
                            $("#confirmSilModal").modal("hide");
                            silinecekId = 0;
                        },
                        error: function(xhr){
                            toastr.error("Sunucu hatası: " + xhr.responseText);
                            $("#confirmSilModal").modal("hide");
                            silinecekId = 0;
                        }
                    });
                });
            });
        </script>

        <!-- Silme Modal (sayfada bir kez) -->
        <?php if (empty($_SESSION['etkinlik_duyuru']) OR empty($_SESSION['admin']) ) { ?>
            <div class="modal fade" id="confirmSilModal" tabindex="-1" role="dialog">
                <div class="modal-dialog modal-sm" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h4 class="modal-title">Dosyayı Sil</h4>
                        </div>
                        <div class="modal-body">
                            <p>Bu dosyayı silmek istediğinize emin misiniz?</p>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-default" data-dismiss="modal">Hayır</button>
                            <button type="button" class="btn btn-danger" id="confirmSilBtn">Evet</button>
                        </div>
                    </div>
                </div>
            </div>
        <?php } ?>
    </div>
</section>
