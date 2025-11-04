<?php
if(empty($_SESSION['kbld_web_site_yetkili_id']) or empty($_SESSION['kbld_web_site_yetkili_name'])){
    exit();
}
if(empty($_GET['edit']) and empty($_GET['insert_photo'])){
?>
    <div class="box box-danger">
        <div class="box-header" style="padding-bottom:0px;">
            <h4 class="box-title">
                <a data-toggle="collapse" data-parent="#accordion" href="#collapseOne">ARAMA </a>
            </h4>
        </div>
        <div id="collapseOne" class="panel-collapse collapse <?php if(!empty($purifier->purify(rescape($_GET['ara'])))){ ?> in<?php } ?>">
            <div class="box-body" id="collapseOne">
                <div class="row">
                    <div style="margin-left:15px;">
                        <form role="form" action="<?=htmlspecialchars($_SERVER['REQUEST_URI'], ENT_QUOTES, 'UTF-8' )?>" method="GET" enctype="multipart/form-data">
                            <input type="hidden" name="x" value="<?=strip((int)$_GET['x'])?>" />

                            <div class="arama_div">
                                <label>Dosya Adı</label>
                                <input type="text" class="form-control" id="adi" name="adi" >
                            </div>

                            <div class="arama_div">
                                <button type="submit" class="btn btn-danger" name="ara" value="ara" style="margin-top:25px;">Ara</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div><!-- /.box-body -->
        </div>
    </div>

    <div class="row">
        <div class="col-xs-12">
            <div id="sil_uyari"></div>
            <div class="box">
                <div class="box-header">
                    <h3 class="box-title">Dosya Düzenle</h3>
                </div><!-- /.box-header -->
                <div class="box-body table-responsive no-padding">
                    <table class="table table-bordered">
                        <tr>
                            <th>Adı</th>
                            <th>Dosya</th>
                            <th align="right"></th>
                        </tr>
                        <?php

                        $adi = $purifier->purify(rescape($_GET['adi']));

                        if(!empty($adi)){
                            $sql_adi = " AND adi LIKE '%$adi%' ";
                        }

                        $q=$dba->query("SELECT * FROM files WHERE id!='' $sql_adi Order By id DESC ");
                        while($row=$dba->fetch_assoc($q)){
                            ?>
                            <tr id="sil_tr_<?=strip((int)$row['id'])?>">
                                <td><?=strip($row['adi'])?></td>
                                <td><a href="../images/files/<?=strip($row['file'])?>" target="_blank"><img src="img/document-32.png"></a></td>
                                <td align="right">
                                    <a class="btn btn-default" href="<?=strip($file)?>.php?x=<?=strip((int)$_GET['x'])?>&edit=<?=strip((int)$row['id'])?>" title="Düzenle"><img src="img/edit.png"></a>
                                    <button type="button" class="btn btn-default" data-toggle="modal" data-target="#sil_modal" data-id="<?=strip((int)$row['id'])?>"><img src="img/delete.png"></button>
                                </td>
                            </tr>
                            <?php
                        }
                        ?>
                    </table>

                    <div class="modal fade" tabindex="-1" role="dialog" aria-labelledby="sil_modalLabel" aria-hidden="true" id="sil_modal">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                                    <h4 class="modal-title">Sil</h4>
                                </div>
                                <div class="modal-body">
                                    <p>Silmek istediğinize eminmisiniz ?</p>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-primary" data-dismiss="modal" id="delete_banner" >Evet</button>
                                    <button type="button" class="btn btn-danger" data-dismiss="modal">Hayır</button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <script>
                        var Sil_Banner;
                        $(document).ready(function(){
                            Sil_Banner = function(id)
                            {
                                var hr = new XMLHttpRequest();
                                var url = "etkinlik_duyuru/dosya_sil.php";
                                var vars = "id="+id;
                                hr.open("POST", url, true);
                                hr.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
                                hr.onreadystatechange = function () {
                                    if (hr.readyState == 4 && hr.status == 200) {
                                        var return_data = hr.responseText;
                                        if(return_data==1){
                                            document.getElementById("sil_uyari").innerHTML='<div class="alert alert-info alert-dismissable">\
                                                            <i class="fa fa-ban"></i>\
                                                            <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>\
                                                            <p>Dosya başarıyla silinmiştir.</p>\
                                                            </div>';
                                            $("#sil_tr_"+id).animate({ opacity: 'hide' }, "slow");
                                        }
                                        else {
                                            document.getElementById("sil_uyari").innerHTML='<div class="alert alert-danger alert-dismissable">\
                                                            <i class="fa fa-ban"></i>\
                                                            <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>\
                                                            <p>Teknik bir arıza oluştu. Lütfen tekrar deneyiniz.</p>\
                                                            </div>';
                                        }
                                    }
                                }
                                hr.send(vars);
                            }
                        });
                    </script>
                </div>
            </div>
        </div>
    </div>

    <script>
        $('#sil_modal').on('show.bs.modal', function (event) {
            var button = $(event.relatedTarget); // Button that triggered the modal
            var id = button.data('id'); // Extract info from data-* attributes
            var modal = $(this);

            document.getElementById("delete_banner").addEventListener("click", handler);
            function handler(e) {
                e.target.removeEventListener(e.type, arguments.callee);
                Sil_Banner(id);
            }
        });
    </script>

<?php }else if(!empty($_GET['edit'])){

    if($_SERVER['REQUEST_METHOD']=="POST"){

        $adi = $purifier->purify(rescape($_POST['adi']));

        if(empty($adi)){
            $hata[]="<p>Dosya Adı giriniz</p>";
        }

        if(!empty($_FILES['file']['name'])) {

            $valid_mime_types = valid_mime_types();
            $mime = mime_content_type($_FILES['file']['tmp_name']);
            $ext = pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION);

            if ($valid_mime_types[strtolower($ext)] != strtolower($mime) and !empty($ext) and !empty($mime)) {
                $hata[] = "<p>Lütfen geçerli bir dosya eki seçiniz pdf veya fotograf</p>";
            }
        }


        if(sizeof($hata)>0){
            alert_danger($hata);
        }else{

            $qu=$dba->query("UPDATE files SET adi='$adi', update_yetkili='".yetkili_id()."' WHERE id='".$purifier->purify(rescape((int)$_GET['edit']))."' ");
            if($dba->affected_rows()>0){
                alert_success("Dosya başarıyla güncellenmiştir");
            }


            $insertid=$_GET['edit'];

            if(!empty($_FILES['file']['name'])){
                $uzanti=end(explode(".", $_FILES['file']['name']));
                $isim=chtext(strip($adi)).'_'.$insertid.".".$uzanti;
                move_uploaded_file($_FILES['file']['tmp_name'],"../images/files/".$isim);
                $qup=$dba->query("UPDATE files SET file ='$isim' WHERE id='$insertid' ");
                alert_success("Dosya Eki başarıyla güncellenmiştir");
            }

        }


    }

    $q=$dba->query("select * from files WHERE id='".$purifier->purify(rescape((int)$_GET['edit']))."' ");
    $row=$dba->fetch_assoc($q);
    ?>
    <div class='row'>
        <div class='col-md-12'>
            <div class='box box-info'>
                <div class='box-header'>
                    <h3 class='box-title'>Dosya Düzenle</h3>
                </div><!-- /.box-header -->
                <div class='box-body'>
                    <form action="<?=htmlspecialchars($_SERVER['REQUEST_URI'], ENT_QUOTES, 'UTF-8' )?>" method="post" enctype="multipart/form-data" >

                        <div class="form-group">
                            <label class="kirmizi">Dosya Adı</label>
                            <input type="text" class="form-control" id="adi" name="adi" value="<?=strip($row['adi'])?>" placeholder="Dosya Adı" />
                        </div>

                        <div class="form-group">
                            <label>Dosya</label>
                            <input type="file" name="file" id="file">
                        </div>

                        <div class="form-group">
                            <a href="../images/files/<?=strip($row['file'])?>" target="_blank">Dosya Eki</a>
                        </div>

                        <div class="form-group">
                            <button class="btn btn-primary" type="submit">Kaydet</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
<?php } ?>