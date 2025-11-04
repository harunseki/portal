<?php
$file = "formlar";
require_once("inc/header.php");
require_once("inc/menu.php");

// Dosya klasörü
$klasor = __DIR__ . "/backup/formlar";
$webPath = "/backup/formlar";

$dosyalar = [];
if(is_dir($klasor)) {
    foreach(scandir($klasor) as $file) {
        if($file !== "." && $file !== ".."){
            $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
            $dosyalar[] = [
                'isim' => $file,
                'uzanti' => $ext,
                'yol' => $webPath . "/" . $file
            ];
        }
    }
    // Alfabetik sıralama (Türkçe karakterleri normalize et)
    usort($dosyalar, function($a,$b) {
        $turkce = ['ç','Ç','ğ','Ğ','ı','İ','ö','Ö','ş','Ş','ü','Ü'];
        $latin = ['c','C','g','G','i','I','o','O','s','S','u','U'];
        $aName = str_replace($turkce,$latin,$a['isim']);
        $bName = str_replace($turkce,$latin,$b['isim']);
        return strcasecmp($aName,$bName);
    });
}
function jsStyleHash($str) {
    $hash = 0;
    // UTF-8 karakterleri ayır
    $chars = preg_split('//u', $str, -1, PREG_SPLIT_NO_EMPTY);

    foreach ($chars as $char) {
        $code = mb_ord($char, 'UTF-8'); // PHP 7.2+ için
        $hash = (($hash << 5) - $hash) + $code;
        $hash = $hash & 0xFFFFFFFF;
    }

    return $hash;
}
?>
<aside class="right-side">
    <section class="content-header">
        <div class="row">
            <div class="col-xs-6"><h2>Formlar</h2></div>
            <div class="col-xs-6 text-right">
                <div class="input-group" style="width: 250px; margin-top: 20px; float: right;">
                    <input type="text" id="dosyaArama" class="form-control" placeholder="Dosya ara...">
                    <span class="input-group-btn">
                        <button class="btn btn-default" type="button"><i class="fa fa-search"></i></button>
                    </span>
                </div>
                <?php if($_SESSION['formlar']==1 || $_SESSION['admin']==1): ?>
                    <button class="btn btn-success pull-right" data-toggle="modal" data-target="#uploadModal" style="margin:20px;">
                        <i class="glyphicon glyphicon-plus"></i> Yeni Form Ekle
                    </button>
                <?php endif; ?>
            </div>
        </div>
    </section>
    <section class="content">
        <div class="box-body">
            <div class="row" style="margin-top:10px;">
                <?php foreach($dosyalar as $dosya): ?>
                    <div class="col-md-3 file-card" id="file_<?= jsStyleHash($dosya['isim']) ?>" style="margin-bottom:5px;">
                        <div class="box box-success" style="height:110px; display:flex; flex-direction:column; justify-content:space-between;">
                            <div class="box-header">
                                <h3 class="box-title" style="word-break: break-word; padding-right: 10px">
                                    <i class="fa
                                <?php
                                    if($dosya['uzanti']==='pdf') echo 'fa-file-pdf-o';
                                    elseif(in_array($dosya['uzanti'], ['doc','docx'])) echo 'fa-file-word-o';
                                    elseif(in_array($dosya['uzanti'], ['xls','xlsx'])) echo 'fa-file-excel-o';
                                    else echo 'fa-file-o';
                                    ?>"></i>
                                    <?= htmlspecialchars($dosya['isim']) ?>
                                </h3>
                            </div>
                            <div class="card-body" style="padding:10px; display:flex; justify-content:space-between;">
                                <a href="<?= htmlspecialchars($dosya['yol']) ?>" download class="btn btn-success btn-sm">
                                    <i class="fa fa-download"></i> İndir
                                </a>
                                <?php if($_SESSION['formlar']==1 || $_SESSION['admin']==1): ?>
                                    <button class="btn btn-danger btn-sm silBtn" data-name="<?= htmlspecialchars($dosya['isim']) ?>">
                                        <i class="fa fa-trash"></i> Sil
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <div class="text-center">
            <button id="prevPage" class="btn btn-default btn-sm">◀ Önceki</button>
            <span id="pageInfo" style="margin: 0 15px; font-weight: bold;"></span>
            <button id="nextPage" class="btn btn-default btn-sm">Sonraki ▶</button>
        </div>
    </section>
</aside>

<!-- Modal Dosya Yükleme -->
<div class="modal fade" id="uploadModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form id="uploadForm" enctype="multipart/form-data">
                <div class="modal-header">
                    <h4 class="modal-title">Yeni Form Yükle</h4>
                    <button type="button" class="close" data-dismiss="modal" style="margin-top: -25px;">&times;</button>
                </div>
                <div class="modal-body">
                    <input type="file" name="file" class="form-control" required>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">Yükle</button>
                    <button type="button" class="btn btn-default" data-dismiss="modal">Kapat</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    $(document).ready(function(){
        toastr.options = {
            "closeButton": false,
            "debug": false,
            "newestOnTop": true,
            "progressBar": false,
            "positionClass": "toast-bottom-left",
            "preventDuplicates": true,
            "onclick": null,
            "showDuration": "300",
            "hideDuration": "300",
            "timeOut": "2000",
            "extendedTimeOut": "500",
            "showEasing": "swing",
            "hideEasing": "linear",
            "showMethod": "fadeIn",
            "hideMethod": "fadeOut"
        };

        // Silme
        $(".silBtn").on("click", function(){
            if(!confirm("Bu dosyayı silmek istediğinize emin misiniz?")) return;
            var file_name = $(this).data("name");

            $.post("formlar/dosya_sil.php", { file_name: file_name }, function(res){
                if(res.status === "success"){
                    toastr.success(res.message);
                    let filename = res.file_name;

                    // ✅ Log kaydı AJAX ile gönder
                    $.post("class/log_ekle.php", { formlar:1, sil:1, file_name: filename }, function(logRes){
                        console.log("Log sonucu:", logRes);
                    });

                    // Görseli kaldır veya sayfayı yenile
                    $("#file_" + md5(filename)).fadeOut(500,function(){ $(this).remove(); });
                }else{
                    toastr.error(res.message);
                }
            },"json");
        });

        // Ekleme
        $("#uploadForm").on("submit", function(e){
            e.preventDefault();
            var formData = new FormData(this);

            $.ajax({
                url: "formlar/dosya_ekle.php",
                type: "POST",
                data: formData,
                contentType: false,
                processData: false,
                dataType: "json",
                success: function(res){
                    if(res.status === "success"){
                        $("#uploadModal").modal("hide");
                        toastr.success(res.message);
                        let filename = res.file_name;

                        // ✅ Log kaydı AJAX ile gönder
                        $.post("class/log_ekle.php", { formlar:1, sil:0, file_name: filename }, function(logRes){
                            console.log("Log sonucu:", logRes);
                        });

                        setTimeout(function(){ location.reload(); },1000);
                    } else toastr.error(res.message);
                },
                error: function(){
                    toastr.error("Sunucu hatası oluştu.");
                }
            });
        });

        function md5(str) {
            let hash = 0;
            for (const char of str) {
                let code = char.codePointAt(0); // UTF-16 code point
                hash = ((hash << 5) - hash) + code;
                hash |= 0;
            }
            return hash >>> 0; // unsigned 32-bit
        }

    });
    $("#dosyaArama").on("keyup", function() {
        var query = $(this).val().toLowerCase().trim();
        $(".file-card").each(function() {
            var fileName = $(this).find(".box-title").text().toLowerCase();
            if (fileName.includes(query)) {
                $(this).show();
            } else {
                $(this).hide();
            }
        });
    });
    var currentPage = 1;
    var itemsPerPage = 20;

    function showPage(page) {
        var query = $("#dosyaArama").val().toLowerCase().trim();

        // Arama filtresi uygulayarak öğeleri seç
        var filteredItems = $(".file-card").filter(function() {
            var fileName = $(this).find(".box-title").text().toLowerCase();
            return fileName.includes(query);
        });

        var totalItems = filteredItems.length;
        var totalPages = Math.ceil(totalItems / itemsPerPage);

        // Tüm öğeleri gizle
        $(".file-card").hide();

        // Sadece sayfa içindeki öğeleri göster
        var start = (page - 1) * itemsPerPage;
        var end = start + itemsPerPage;
        filteredItems.slice(start, end).show();

        // Sayfa bilgisi
        $("#pageInfo").text("Sayfa " + page + " / " + (totalPages || 1));

        // Buton durumları
        $("#prevPage").prop("disabled", page <= 1);
        $("#nextPage").prop("disabled", page >= totalPages);

        // Eğer geçerli sayfa 0 ise 1 yap
        if(totalPages === 0) currentPage = 1;
    }

    $("#prevPage").click(function() {
        if(currentPage > 1) {
            currentPage--;
            showPage(currentPage);
        }
    });

    $("#nextPage").click(function() {
        var query = $("#dosyaArama").val().toLowerCase().trim();
        var filteredItems = $(".file-card").filter(function() {
            return $(this).find(".box-title").text().toLowerCase().includes(query);
        });
        var totalPages = Math.ceil(filteredItems.length / itemsPerPage);
        if(currentPage < totalPages) {
            currentPage++;
            showPage(currentPage);
        }
    });

    // Arama input
    $("#dosyaArama").on("keyup", function() {
        currentPage = 1; // Arama değişince sayfa 1'den başlasın
        showPage(currentPage);
    });

    // İlk sayfayı göster
    showPage(currentPage);

</script>

<style>
    .file-card { display:flex; flex-direction:column; justify-content:space-between; margin-bottom:20px; }
    .file-card .box-header { white-space:normal; overflow-wrap:break-word; word-break:break-word; }
    #dosyaArama {
        border-radius: 20px;
        padding-left: 12px;
    }
    #pageInfo {
        color: #333;
    }
</style>

<?php require_once "inc/footer.php"; ?>
