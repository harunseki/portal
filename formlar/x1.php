<section class="content-header">
    <div class="row">
        <div class="col-xs-6"><h2>Formlarx1</h2></div>
        <div class="col-xs-6 text-right">
            <?php if($_SESSION['formlar']==1 || $_SESSION['admin']==1 || $_SESSION['admin']==1): ?>
                <button class="btn btn-success pull-right" data-toggle="modal" data-target="#uploadModal" style="margin-top:20px;">
                    <i class="glyphicon glyphicon-plus"></i> Yeni Form Ekle
                </button>
            <?php endif; ?>
        </div>
    </div>
</section>

<section class="content">
    <div class="box-body">
        <div class="row" style="margin-top:10px;">
            <?php
            // Dosya klasörü
            $klasor = __DIR__ . "/backup/formlar";
            $webPath = "/backup/formlar";
            $dosyalar = [];
            if(is_dir($klasor)){
                foreach(scandir($klasor) as $file){
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
                usort($dosyalar, function($a,$b){
                    $turkce = ['ç','Ç','ğ','Ğ','ı','İ','ö','Ö','ş','Ş','ü','Ü'];
                    $latin = ['c','C','g','G','i','I','o','O','s','S','u','U'];
                    $aName = str_replace($turkce,$latin,$a['isim']);
                    $bName = str_replace($turkce,$latin,$b['isim']);
                    return strcasecmp($aName,$bName);
                });
            }
            ?>
            <?php foreach($dosyalar as $dosya): ?>
                <div class="col-md-3 file-card" id="file_<?= md5($dosya['isim']) ?>" style="margin-bottom:15px;">
                    <div class="box box-success" style="height:110px; display:flex; flex-direction:column; justify-content:space-between;">
                        <div class="box-header">
                            <h3 class="box-title" style="word-break: break-word;">
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
    <div class="text-center" style="margin: 20px 0;">
        <button id="prevPage" class="btn btn-default btn-sm">◀ Önceki</button>
        <span id="pageInfo" style="margin: 0 15px; font-weight: bold;"></span>
        <button id="nextPage" class="btn btn-default btn-sm">Sonraki ▶</button>
    </div>
</section>

<!-- Modal Dosya Yükleme -->
<div class="modal fade" id="uploadModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form id="uploadForm" enctype="multipart/form-data">
                <div class="modal-header">
                    <h4 class="modal-title">Yeni Dosya Yükle</h4>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
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
        // Silme
        $(".silBtn").on("click", function(){
            if(!confirm("Bu dosyayı silmek istediğinize emin misiniz?")) return;
            var file_name = $(this).data("name");

            $.post("formlar/dosya_sil.php", { file_name: file_name }, function(res){
                if(res.status === "success"){
                    toastr.success(res.message);
                    $("#file_" + md5(file_name)).fadeOut(500,function(){ $(this).remove(); });
                    setTimeout(function(){ location.reload(); },1000);
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
                        setTimeout(function(){ location.reload(); },1000);
                    } else toastr.error(res.message);
                },
                error: function(){
                    toastr.error("Sunucu hatası oluştu.");
                }
            });
        });

        function md5(str){
            return str.split("").reduce(function(a,b){ a=((a<<5)-a)+b.charCodeAt(0); return a & a; },0);
        }
    });
</script>

<style>
    .file-card { height:120px; display:flex; flex-direction:column; justify-content:space-between; margin-bottom:20px; }
    .file-card .box-header { white-space:normal; overflow-wrap:break-word; word-break:break-word; }
    #pageInfo {
        color: #333;
    }
</style>