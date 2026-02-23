<?php
/*if(empty($_SESSION['bim_takip_admin_id']) or empty($_SESSION['bim_takip_admin_name']) ){
    exit();
}*/

/*error_reporting(E_ALL);
ini_set("display_errors", 1);*/

require_once("class/mysql.php");
require_once("../class/functions.php");
require_once("../class/resize-class.php");

require_once("inc/header.php");
require_once("inc/menu1.php");

$id=$purifier->purify(rescape((int)$_GET['edit']));
if (empty($purifier->purify(rescape($_GET['tablo'])))) $tablo="galeri";
else $tablo=$purifier->purify(rescape($_GET['tablo']));
?>
<aside class="right-side" xmlns:margin-bottom="http://www.w3.org/1999/xhtml">
    <section class="content-header">
        <?php
        $qak=$dba->query("SELECT * FROM ayarlar");
        echo $dba->error();
        $rowak=$dba->fetch_assoc($qak);
        ?>
        <h1> <?=$rowak['site_baslik']?> </h1>
    </section>
    <section class="content">
        <div id="sil_uyari"></div>
        <div class="row">
            <div class="col-md-12">
                <div class="box box-danger">
                    <div class="nav-tabs-custom">
                        <div class="box-header box1">
                            <div id="sil_uyari"></div>
                            <h4> GALERİ RESİMLERİ <?php
                            if ($tablo=="galeri" OR $tablo=="kategoriler") echo "(550x325px)";
                            else echo "(359x296px)";; ?> </h4>
                            <div class="row" style="margin-top: 20px">
                                <?php
                                $qak=$dba->query("SELECT galeri.image, galeri.id, galeri.aciklama FROM galeri WHERE tablo_adi='$tablo' AND tablo_id='$id' ");
                                echo $dba->error();
                                while ($rowak=$dba->fetch_assoc($qak)) { ?>
                                <div class="col-md-2" id="sil_div_<?=strip($rowak['id'])?>" style="margin-bottom: 1cm">
                                    <img src="../img/<?= $tablo ?>/<?=strip($rowak['image'])?>" style="max-height: 150px; margin-bottom: 0.3cm" alt="">
                                    <p><?= strip($rowak['aciklama']) ?></p>
                                    <div> <button type="button" class="btn btn-danger" data-toggle="modal" data-target="#sil_modal" data-id="<?=strip((int)$rowak['id'])?>">Sil</button> </div>
                                </div>
                                <?php
                                }
                                if($_SERVER['REQUEST_METHOD']=="POST") {
                                    $kategori_id = 2;
                                    $aciklama = rescape($purifier->purify($_POST['aciklama']));

                                    $q = $dba->query("INSERT INTO galeri (tablo_adi, tablo_id, aciklama, kayit_yetkili) VALUES ('$tablo', '$id', '$aciklama', '".admin_name()."') ");
                                    echo $dba->error();
                                    $insert_id = $dba->insert_id();

                                    if ($dba->affected_rows() > 0) {
                                        if(!empty($_FILES['image']['name'])){
                                            /*$valid_mime_types = valid_mime_types_image();
                                            $mime = mime_content_type($_FILES['image']['tmp_name']);
                                            $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);*/
                                            $path = "../img/$tablo/"; // Upload directory

                                            /*if ($valid_mime_types[strtolower($ext)] != strtolower($mime) and !empty($ext) and !empty($mime)) alert_danger("Fotoğraf eklenememiştir. Lütfen geçerli bir fotoğraf yükleyiniz...!");
                                            else {*/
                                                $uzanti=end(explode(".", $_FILES['image']['name']));
                                                $isim=substr(correctlink($aciklama),0,5)."_".time()."_".$id.".".$uzanti;

                                                list($width, $height) = getimagesize($_FILES['image']['tmp_name']);

                                                if(move_uploaded_file($_FILES["image"]["tmp_name"], $path.$isim)) {
                                                    /*$resizeObj = new resize($path.$isim); //*** 1) Initialise / load image
                                                    $resizeObj->resizeImage(359*3, 296*3, 'crop');
                                                    $resizeObj -> saveImage($path.$isim);// *** 3) Save image*/
                                                    $qup=$dba->query("UPDATE galeri SET image='$isim' WHERE id='$insert_id' ");
                                                    echo $dba->error();
                                                    if($dba->affected_rows()>0) {
                                                        alert_success("Resim başarıyla eklenmiştir");
                                                        ?>
                                                        <meta http-equiv="refresh" content="0; url=galeri.php?tablo=<?=$tablo?>&edit=<?=$id?>">
                                                        <?php
                                                    }
                                                }
                                            /*}*/
                                        }
                                    }
                                }
                                ?>
                                <form role="form" action="<?=htmlspecialchars($_SERVER['REQUEST_URI'], ENT_QUOTES, 'UTF-8' )?>" method="post" enctype="multipart/form-data">
                                    <div class="box-body">
                                        <div class="form-group">
                                            <input type="file" class="form-control" name="image" id="image" required/>
                                        </div>
                                        <div class="form-group">
                                            <label class="kirmizi">Resin Açıklama *</label>
                                            <input type="text" class="form-control" name="aciklama" id="aciklama" placeholder="Açıklama giriniz..." autocomplete="off" autocomplete="off"/>
                                        </div>

                                        <!--<div class="form-group">
                                            <label class="kirmizi">Kategori  *</label>
                                            <select class="form-control" name="kategori_id" id="kategori_id" required>
                                                <?php
/*                                                $kat_id = $tablo=="faaliyetler" ? '1':'2';
                                                $q = $dba->query("SELECT id, baslik, alt_kategori FROM kategoriler WHERE id!='' AND alt_kategori='$kat_id' ORDER BY alt_kategori DESC, baslik ASC ");
                                                while($row=$dba->fetch_assoc($q)) { */?>
                                                <option value="<?/*= $row['id'] */?>" <?/*= $row['id']==$id ? 'selected':'' */?>> <?/*= $row['baslik'] */?> </option>
                                                <?php /*} */?>
                                            </select>
                                        </div>-->
                                        <div style="clear:both;"></div>
                                        <button type="submit" class="btn btn-primary">Resim Ekle</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

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
                        <button type="button" class="btn btn-primary" data-dismiss="modal" id="delete_photo">Evet </button>
                        <button type="button" class="btn btn-danger" data-dismiss="modal">Hayır</button>
                    </div>
                </div>
            </div>
        </div>
        <script>
            var Sil_photo;
            $(document).ready(function(){
                Sil_photo = function(id){
                    var hr = new XMLHttpRequest();
                    var url = "../sec/galeri_foto_sil.php";
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
                                                            <p>Resim başarıyla silinmiştir.</p>\
                                                            </div>';
                                $("#sil_div_"+id).animate({ opacity: 'hide' }, "slow");
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
    </section>
</aside>
<?php require_once("inc/footer.php"); ?>