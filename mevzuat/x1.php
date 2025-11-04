<section class="content-header">
    <div class="row">
        <div class="col-xs-6">
            <h2>Mevzuat</h2>
        </div>
    </div>
</section>

<section class="content">
    <!-- Ana kutular -->
    <div id="kategoriKutular" class="d-flex justify-content-center align-items-center" style="height:70vh;/* display: flex; justify-content: center; align-items: center*/">
        <div class="row" style="width:100%; max-width:900px; justify-content:center; margin-top: 10px">
            <div class="col-md-4 col-sm-6 col-xs-12">
                <div class="mevzuat-box" data-kategori="genelgeler">
                    <i class="fa fa-file-text-o icon"></i>
                    <h3>Genelgeler</h3>
                </div>
            </div>
            <div class="col-md-4 col-sm-6 col-xs-12">
                <div class="mevzuat-box" data-kategori="yonergeler">
                    <i class="fa fa-book icon"></i>
                    <h3>Yönergeler</h3>
                </div>
            </div>

            <div class="col-md-4 col-sm-6 col-xs-12">
                <div class="mevzuat-box" data-kategori="yonetmelikler">
                    <i class="fa fa-gavel icon"></i>
                    <h3>Yönetmelikler</h3>
                </div>
            </div>
        </div>
    </div>

    <!-- Dosya listesi -->
    <div id="dosyaListeAlani" style="display:none; margin-top: 10px" >
        <button id="geriBtn" class="btn btn-default"><i class="fa fa-arrow-left"></i> Geri</button>
        <h3 id="kategoriBaslik" style="margin-top:15px;"></h3>
        <div class="row" id="dosyaListesi"></div>
    </div>
</section>

<script>
    $(document).ready(function(){
        function temizleMetin(str) {
            return str.replace(/^\s+|\s+$/g, "");
        }
        // 📂 Kategori seçimi
        $(".mevzuat-box").click(function(){
            var kategori = $(this).data("kategori");
            $("#kategoriBaslik").text(kategori.charAt(0).toUpperCase() + kategori.slice(1));
            $("#kategoriKutular").hide();
            $("#dosyaListeAlani").fadeIn();

            $.getJSON("mevzuat/x2.php", { kategori: kategori }, function(res){
                var html = "";
                if(res.length > 0){
                    res.forEach(function(d){
                        var icon = "fa-file-o";
                        if(d.uzanti === "pdf") icon = "fa-file-pdf-o";
                        else if(["doc","docx"].includes(d.uzanti)) icon = "fa-file-word-o";
                        else if(["xls","xlsx"].includes(d.uzanti)) icon = "fa-file-excel-o";

                        html += `
                        <div class="col-md-3 file-card" style="margin-bottom:15px;">
                            <div class="box box-success" style="height:130px; display:flex; flex-direction:column; justify-content:space-between;">
                                <div class="box-header">
                                    <h3 class="box-title" style="word-break: break-word;">
                                         ${d.isim}
                                    </h3>
                                </div>
                                <div class="card-body" style="padding:10px; display:flex; justify-content:space-between;">
                                    <a href="${d.yol}" download class="btn btn-success btn-sm">
                                        <i class="fa fa-download"></i> İndir
                                    </a>
                                </div>
                            </div>
                        </div>`;
                    });
                } else {
                    html = `<div class="col-md-12 text-center"><div class="alert alert-info">Bu kategoride dosya bulunamadı.</div></div>`;
                }
                $("#dosyaListesi").html(html);
            });
        });

        // 🔙 Geri dön
        $("#geriBtn").click(function(){
            $("#dosyaListeAlani").hide();
            $("#kategoriKutular").fadeIn();
        });
    });
</script>

<style>
    .mevzuat-box {
        background: #f7f7f7;
        border-radius: 20px;
        padding: 50px 20px;
        text-align: center;
        margin: 10px;
        transition: all 0.3s ease;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        cursor: pointer;
    }
    .mevzuat-box:hover {
        background: #e8f0ff;
        transform: scale(1.08);
        box-shadow: 0 6px 18px rgba(0,0,0,0.2);
    }
    .mevzuat-box .icon {
        font-size: 70px;
        color: #057709;
        margin-bottom: 15px;
    }
    .mevzuat-box h3 {
        font-weight: bold;
        color: #333;
    }
    /* 🔹 Her dosya kutusu sabit yükseklikte */
    .file-card {
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        margin-bottom: 20px;
        min-height: 150px; /* sabit yükseklik */
    }

    /* 🔹 Başlık alanı taşmaları düzgün göstersin */
    .file-card .box-header {
        /*white-space: nowrap*/;
        overflow: hidden;
        text-overflow: ellipsis;
        padding-right: 10px;
    }

    /* 🔹 Dosya adı yazısı düzgün hizalansın */
    .file-card .box-title {
        font-size: 14px;
        font-weight: normal;
        line-height: 1.2em;
        margin: 0;
    }

    /* 🔹 Uzun dosya adları kaymasın */
    .file-card .box-title i {
        margin-right: 5px;
    }

    .file-card .card-body {
        padding: 10px;
        display: flex;
        justify-content: space-between;
    }

    .file-card a.btn,
    .file-card button.btn {
        font-size: 12px;
        padding: 5px 10px;
    }
    @media (max-width: 768px) {
        .mevzuat-box {
            padding: 40px 10px;
            margin-bottom: 20px;
        }
        .mevzuat-box .icon {
            font-size: 55px;
        }
    }
</style>
