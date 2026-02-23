<?php
/*
error_reporting(E_ALL);
ini_set("display_errors", 1);*/
$qi = $dba->query("SELECT * FROM sabit_icerik WHERE id='121' ");
$rowi = $dba->fetch_assoc($qi);
$title = strip($rowi['baslik']);

$id = $purifier->purify(rescape($_POST['id']));
require_once("inc/header.php");
?>
    <section class="wf100 subheader">
        <div class="container">
            <h2 title="<?= strip($rowi['baslik']) ?>"><?= strip($rowi['baslik']) ?></h2>
            <ul>
                <li><a href="index.html">Ana Sayfa</a></li>
                <li><a href="#"> <?= strip($rowi['baslik']) ?> </a></li>
            </ul>
        </div>
    </section>
    <div class="main-content p60">
        <div class="news-details">
            <div class="container">
                <div class="row">
                    <div class="col-md-12">
                        <div class="event-details">
                            <?php
                            if ($_SERVER['REQUEST_METHOD'] == "POST") {
                                if (isset($_POST['csrf_token']) && $_POST['csrf_token'] == get_csrf_token()) {
                                    $salon_id = $purifier->purify(rescape($_POST['salon_id']));
                                    $tiyatro_oyunu = $purifier->purify(rescape($_POST['tiyatro_oyunu']));
                                    $oyun_tarihi = $purifier->purify(rescape($_POST['oyun_tarihi']));
                                    $seans = $purifier->purify(rescape($_POST['seans']));
                                    $koltuk1 = $purifier->purify(rescape($_POST['koltuk1']));
                                    $koltuk2 = $purifier->purify(rescape($_POST['koltuk2']));
                                    $koltuk3 = $purifier->purify(rescape($_POST['koltuk3']));
                                    $koltuk4 = $purifier->purify(rescape($_POST['koltuk4']));
                                    $ebeveyn_tc = $purifier->purify(rescape($_POST['ebeveyn_tc']));
                                    $ebeveyn_adi_soyadi = $purifier->purify(rescape(buyuk_harfe_cevir($_POST['ebeveyn_adi_soyadi'])));
                                    $ebeveyn_telefonu = $purifier->purify(rescape(buyuk_harfe_cevir($_POST['ebeveyn_telefonu'])));
                                    $ebeveyn_dogum_tarihi = $purifier->purify(rescape(tr_to_global_date($_POST['ebeveyn_dogum_tarihi'])));
                                    $kisi_tc = $purifier->purify(rescape($_POST['kisi_tc']));
                                    $kisi_adi_soyadi = $purifier->purify(rescape(buyuk_harfe_cevir($_POST['kisi_adi_soyadi'])));
                                    $kisi_telefonu = $purifier->purify(rescape(buyuk_harfe_cevir($_POST['kisi_telefonu'])));
                                    $kisi_dogum_tarihi = $purifier->purify(rescape(tr_to_global_date($_POST['kisi_dogum_tarihi'])));
                                    $cocuk_adi_soyadi_1 = $purifier->purify(rescape($_POST['cocuk_adi_soyadi_1']));
                                    if(!empty($koltuk3)) $cocuk_adi_soyadi_2 = $purifier->purify(rescape($_POST['cocuk_adi_soyadi_2']));
                                    if(!empty($koltuk4)) $cocuk_adi_soyadi_3 = $purifier->purify(rescape($_POST['cocuk_adi_soyadi_3']));
                                    $guvenlik_kodu = $purifier->purify(rescape($_POST['guvenlik_kodu']));

                                    $ebeveyn_tc=$kisi_tc;
                                    $ebeveyn_adi_soyadi=$kisi_adi_soyadi;
                                    $ebeveyn_dogum_tarihi=$kisi_dogum_tarihi;
                                    $ebeveyn_telefonu=$kisi_telefonu;

                                    $qc=$dba->query("SELECT id FROM tiyatro_oyunu_basvurusu WHERE tiyatro_oyunu='$tiyatro_oyunu' AND ebeveyn_tc='$ebeveyn_tc' AND oyun_tarihi='$oyun_tarihi'  ");
                                    if($dba->num_rows($qc)) {
                                        $rowc = $dba->fetch_assoc($qc);

                                        $hata1[]="<p>Bu oyuna daha öncede başvurunuz bulunmaktadır.</p>";
                                        alert_danger($hata1);
                                        $qsonuc=$dba->query("SELECT
                                                                    tiyatro_oyunu_basvurusu.id,
                                                                    tiyatro_oyunu.oyun_adi,
                                                                    tiyatro_oyunu_basvurusu.oyun_tarihi,
                                                                    tiyatro_oyunu_seans.seans,
                                                                    tiyatro_oyunu_koltuk_sira.sira,
                                                                    tiyatro_oyunu_koltuk_konumu.koltuk_konumu,
                                                                    tiyatro_oyunu_basvurusu.ebeveyn_tc,
                                                                    tiyatro_oyunu_basvurusu.ebeveyn_telefonu,
                                                                    tiyatro_oyunu_basvurusu.ebeveyn_adi_soyadi,
                                                                    tiyatro_oyunu_basvurusu.ebeveyn_dogum_tarihi,
                                                                    tiyatro_oyunu_basvurusu.in_time
                                                                  FROM
                                                                    tiyatro_oyunu_basvurusu
                                                                    LEFT JOIN tiyatro_oyunu ON tiyatro_oyunu_basvurusu.tiyatro_oyunu = tiyatro_oyunu.id
                                                                    LEFT JOIN tiyatro_oyunu_seans ON tiyatro_oyunu_basvurusu.seans = tiyatro_oyunu_seans.id
                                                                    LEFT JOIN tiyatro_oyunu_koltuk_sira ON tiyatro_oyunu_basvurusu.koltuk1 = tiyatro_oyunu_koltuk_sira.id
                                                                    LEFT JOIN tiyatro_oyunu_koltuk_konumu ON tiyatro_oyunu_koltuk_konumu.id = tiyatro_oyunu_koltuk_sira.tiyatro_oyunu_koltuk_konumu
                                                                    LEFT JOIN tiyatro_oyunu_koltuk_sira AS toks2 ON tiyatro_oyunu_basvurusu.koltuk2 = toks2.id
                                                                    LEFT JOIN tiyatro_oyunu_koltuk_sira AS toks3 ON tiyatro_oyunu_basvurusu.koltuk3 = toks3.id 
                                                                    LEFT JOIN tiyatro_oyunu_koltuk_sira AS toks4 ON tiyatro_oyunu_basvurusu.koltuk4 = toks4.id 
                                                                    WHERE tiyatro_oyunu_basvurusu.id='". strip($rowc['id']) ."'
                                                                  ORDER BY
                                                                    tiyatro_oyunu_basvurusu.id ASC ");
                                        $rowsonuc=$dba->fetch_assoc($qsonuc);
                                        ?>
                                        <div class="box-body table-responsive no-padding">
                                            <div ><strong>Başvuru Bilgileriniz aşağıdaki gibidir...</strong></div>
                                            <table class="table table-bordered">
                                                <tr>
                                                    <th>Oyun Adı</th>
                                                    <th>Oyun Tarihi</th>
                                                    <th>Seans</th>
                                                    <th>Koltuk</th>
                                                    <th>TC</th>
                                                    <th>Ad Soyad</th>
                                                    <th>Başvuru Tarihi</th>
                                                </tr>
                                                <tr>
                                                    <td><?=strip($rowsonuc['oyun_adi'])?></td>
                                                    <td><?=strip(global_date_to_tr($rowsonuc['oyun_tarihi']))?></td>
                                                    <td><?=strip($rowsonuc['seans'])?></td>
                                                    <td><?=strip($rowsonuc['koltuk_konumu'])." - ".strip($rowsonuc['sira'])?></td>
                                                    <td><?=strip($rowsonuc['ebeveyn_tc'])?></td>
                                                    <td><?=strip($rowsonuc['ebeveyn_adi_soyadi'])?></td>
                                                    <td><?=strip(($rowsonuc['in_time']))?></td>
                                                </tr>
                                            </table>
                                        </div>
                                        <?php
                                    }

                                    $qk=$dba->query("SELECT cocuk FROM tiyatro_oyunu WHERE tiyatro_oyunu='$tiyatro_oyunu'  ");
                                    $rowk=$dba->fetch_assoc($qk);


                                    if(empty($ebeveyn_tc)) $hata[]="<p> TC Numaranızı giriniz</p>";
                                    if(empty($ebeveyn_adi_soyadi)) $hata[]="<p> Adınızı soyadınızı giriniz</p>";
                                    if(empty($ebeveyn_dogum_tarihi)) $hata[]="<p> Doğum tarihinizi giriniz</p>";
                                    if(empty($ebeveyn_telefonu)) $hata[]="<p> Telefon numaanızı giriniz</p>";
                                    if(empty($tiyatro_oyunu)) $hata[]="<p>Tiyatro oyunu seçiniz</p>";
                                    if(empty($oyun_tarihi)) $hata[]="<p>Oyun tarihi seçiniz</p>";
                                    if(empty($seans)) $hata[]="<p>Seans seçiniz</p>";
                                    if(empty($koltuk1)) $hata[]="<p> Koltuğu Seçiniz </p>";

                                    $sql="SELECT * FROM tiyatro_oyunu_basvurusu WHERE salon_id='$salon_id' AND tiyatro_oyunu='$tiyatro_oyunu' AND oyun_tarihi='$oyun_tarihi' AND seans='$seans' AND koltuk1='$koltuk1' ";
                                    $qc=$dba->query($sql);
                                    $rowc=$dba->num_rows($qc);

                                    if($rowc>0){
                                        $hata[]="<p>Sectiginiz koltuk daha once alinmis, lutfen baska koltuk seciniz.</p>";
                                    }

                                    if ($rowk['cocuk']==1) {
                                        if($tiyatro_oyunu!='4') {
                                            if(empty($cocuk_adi_soyadi_1)) $hata[]="<p>1. Çocuk Adı & Soyadı giriniz</p>";
                                            if(empty($koltuk2)) $hata[]="<p> 1. Çoçuk Koltuğu Seçiniz </p>";
                                            if(empty($ebeveyn_tc)) $hata[]="<p> Ebeveyn TC No giriniz </p>";
                                            if(empty($ebeveyn_adi_soyadi)) $hata[]="<p> Ebeveyn Adı Soyadı giriniz </p>";
                                            if(empty($ebeveyn_dogum_tarihi)) $hata[]="<p> Ebeveyn Doğum Tarihi giriniz </p>";
                                        }
                                        else {
                                            $ebeveyn_tc=$kisi_tc;
                                            $ebeveyn_adi_soyadi=$kisi_adi_soyadi;
                                            $ebeveyn_dogum_tarihi=$kisi_dogum_tarihi;
                                            $ebeveyn_telefonu=$kisi_telefonu;

                                            if(empty($kisi_tc)) $hata[]="<p> TC Numaranızı giriniz</p>";
                                            if(empty($kisi_adi_soyadi)) $hata[]="<p> Adınızı soyadınızı giriniz</p>";
                                            if(empty($kisi_dogum_tarihi)) $hata[]="<p> Doğum tarihinizi giriniz</p>";
                                        }
                                    }

                                    if (empty($guvenlik_kodu)) $hata[] = "<p>Güvenlik Kodunu giriniz</p>";
                                    if($guvenlik_kodu!=$_SESSION['guvenlik_kodu']) $hata[] = "<p>Lütfen güvenlik kodunu doğru giriniz</p>";

                                    if (sizeof($hata) > 0 OR sizeof($hata1) > 0) {
                                        if (sizeof($hata) > 0) alert_danger($hata);
                                    }
                                    else {
                                        $ebeveyn_dogum_tarihinvi= str_replace('.','-', global_date_to_tr($ebeveyn_dogum_tarihi));
                                        $bilgiler = kan_bankasi_yas_kontol($ebeveyn_tc, $ebeveyn_dogum_tarihinvi);
                                        $kisi_bilgileri_adi_soyadi = $bilgiler[0] . ' ' . $bilgiler[1];
                                        if ($ebeveyn_adi_soyadi != $kisi_bilgileri_adi_soyadi) $hata[] = "<div>Kimlik bilgileriniz uyuşmuyor.</div>";

                                        if (sizeof($hata) > 0) alert_danger($hata);
                                        else {
                                            $q=$dba->query("INSERT INTO tiyatro_oyunu_basvurusu (salon_id, tiyatro_oyunu, oyun_tarihi, seans, koltuk1, koltuk2, koltuk3, ebeveyn_tc, ebeveyn_adi_soyadi, ebeveyn_dogum_tarihi, cocuk_adi_soyadi_1, cocuk_adi_soyadi_2, ebeveyn_telefonu, koltuk4, cocuk_adi_soyadi_3) VALUES ('$salon_id', '$tiyatro_oyunu', '$oyun_tarihi', '$seans', '$koltuk1', '$koltuk2', '$koltuk3', '$ebeveyn_tc', '$ebeveyn_adi_soyadi', '$ebeveyn_dogum_tarihi', '$cocuk_adi_soyadi_1', '$cocuk_adi_soyadi_2', '$ebeveyn_telefonu', '$koltuk4', '$cocuk_adi_soyadi_3' ) ");
                                            if($dba->affected_rows()>0){
                                                $insert_id=$dba->insert_id();
                                                alert_success("Başvurunuz başarıyla kaydedilmiştir.");
                                                /*alert_success("Başvurunuz başarıyla kaydedilmiştir. Başvuru bilgileriniz SMS ile gönderilmiştir.")*/;
                                                $qsonuc=$dba->query("SELECT
                                                                    tiyatro_oyunu_basvurusu.id,
                                                                    tiyatro_oyunu.oyun_adi,
                                                                    tiyatro_oyunu_basvurusu.oyun_tarihi,
                                                                    tiyatro_oyunu_seans.seans,
                                                                    tiyatro_oyunu_koltuk_sira.sira,
                                                                    tiyatro_oyunu_koltuk_konumu.koltuk_konumu,
                                                                    toks2.sira AS cocuk_koltuk_1,
                                                                    toks3.sira AS cocuk_koltuk_2,
                                                                    toks4.sira AS cocuk_koltuk_3,
                                                                    tiyatro_oyunu_basvurusu.ebeveyn_tc,
                                                                    tiyatro_oyunu_basvurusu.ebeveyn_telefonu,
                                                                    tiyatro_oyunu_basvurusu.ebeveyn_adi_soyadi,
                                                                    tiyatro_oyunu_basvurusu.ebeveyn_dogum_tarihi,
                                                                    tiyatro_oyunu_basvurusu.in_time,
                                                                    tiyatro_oyunu_basvurusu.cocuk_adi_soyadi_1,
                                                                    tiyatro_oyunu_basvurusu.cocuk_adi_soyadi_2,
                                                                    tiyatro_oyunu_basvurusu.cocuk_adi_soyadi_3
                                                                  FROM
                                                                    tiyatro_oyunu_basvurusu
                                                                    LEFT JOIN tiyatro_oyunu ON tiyatro_oyunu_basvurusu.tiyatro_oyunu = tiyatro_oyunu.id
                                                                    LEFT JOIN tiyatro_oyunu_seans ON tiyatro_oyunu_basvurusu.seans = tiyatro_oyunu_seans.id
                                                                    LEFT JOIN tiyatro_oyunu_koltuk_sira ON tiyatro_oyunu_basvurusu.koltuk1 = tiyatro_oyunu_koltuk_sira.id
                                                                    LEFT JOIN tiyatro_oyunu_koltuk_konumu ON tiyatro_oyunu_koltuk_konumu.id = tiyatro_oyunu_koltuk_sira.tiyatro_oyunu_koltuk_konumu
                                                                    LEFT JOIN tiyatro_oyunu_koltuk_sira AS toks2 ON tiyatro_oyunu_basvurusu.koltuk2 = toks2.id
                                                                    LEFT JOIN tiyatro_oyunu_koltuk_sira AS toks3 ON tiyatro_oyunu_basvurusu.koltuk3 = toks3.id 
                                                                    LEFT JOIN tiyatro_oyunu_koltuk_sira AS toks4 ON tiyatro_oyunu_basvurusu.koltuk4 = toks4.id 
                                                                    WHERE tiyatro_oyunu_basvurusu.id='$insert_id'
                                                                  ORDER BY
                                                                    tiyatro_oyunu_basvurusu.id ASC ");
                                                $rowsonuc=$dba->fetch_assoc($qsonuc);
                                                ?>
                                                <div class="box-body table-responsive no-padding">
                                                    <div><strong>Kayıt Bilgileriniz aşağıdaki gibidir</strong></div>
                                                    <table class="table table-bordered">
                                                        <tr>
                                                            <th>Oyun Adı</th>
                                                            <th>Oyun Tarihi</th>
                                                            <th>Seans</th>
                                                            <th>Koltuk</th>
                                                            <th>TC</th>
                                                            <th>Ad Soyad</th>
                                                            <?php if ($rowk['cocuk']==1) { ?>
                                                                <th>1. Çocuk Adı</th>
                                                                <th>2. Çocuk Adı</th>
                                                                <th>3. Çocuk Adı</th>
                                                            <?php } ?>
                                                            <th>Başvuru Tarihi</th>
                                                        </tr>
                                                        <tr>
                                                            <td><?=strip($rowsonuc['oyun_adi'])?></td>
                                                            <td><?=strip(global_date_to_tr($rowsonuc['oyun_tarihi']))?></td>
                                                            <td><?=strip($rowsonuc['seans'])?></td>
                                                            <td><?=strip($rowsonuc['koltuk_konumu'])." - ".strip($rowsonuc['sira'])?></td>
                                                            <td><?=strip($rowsonuc['ebeveyn_tc'])?></td>
                                                            <td><?=strip($rowsonuc['ebeveyn_adi_soyadi'])?></td>
                                                            <?php if ($rowk['cocuk']==1) { ?>
                                                                <td><?=strip($rowsonuc['cocuk_adi_soyadi_1'])?><br><?=strip($rowsonuc['cocuk_koltuk_1'])?></td>
                                                                <td><?=strip($rowsonuc['cocuk_adi_soyadi_2'])?><br><?=strip($rowsonuc['cocuk_koltuk_2'])?></td>
                                                                <td><?=strip($rowsonuc['cocuk_adi_soyadi_3'])?><br><?=strip($rowsonuc['cocuk_koltuk_3'])?></td>
                                                            <?php } ?>
                                                            <td><?=strip(($rowsonuc['in_time']))?></td>
                                                        </tr>
                                                    </table>
                                                </div>
                                                <?php
                                                $cep_telefonu=cep_tel_clear($ebeveyn_telefonu);
                                                $sms_içerik="Sayın ".strip($rowsonuc['ebeveyn_adi_soyadi'])." Tiyatro Başvuru Bilgileriniz: ".strip($rowsonuc['oyun_adi'])." - ".strip(global_date_to_tr($rowsonuc['oyun_tarihi']))." - ".strip($rowsonuc['seans'])." - ".strip($rowsonuc['koltuk_konumu'])."/".strip($rowsonuc['sira']);
                                                sms_yolla($cep_telefonu, $sms_içerik, "Tiyatro Başvurusu", 1);
                                            }
                                        }
                                    }
                                }
                            }
                            ?>
                            <div class="col-md-12">
                                <div class="etext" style="text-align: justify;">
                                    <?php if (!empty($rowi['photo'])) { ?>
                                        <div class="event-thumb">
                                            <img src="https://www.kecioren.bel.tr/images/files/tiyatro_salonu_oturma_plani_sihirli_orman_1175.jpg" alt="<?= strip($rowi['baslik']) ?>" title="<?= strip($rowi['baslik']) ?>" style="padding-right: 15px;">
                                        </div>
                                    <?php } ?>
                                    <p><?= strip($rowi['icerik']) ?></p>
                                    <br>
                                </div>
                            </div>
                            <div style="clear: both;"></div>
                            <div class="portlet-body form">
                                <div class="modal fade " id="exampleModal" tabindex="-1" role="dialog"
                                     aria-labelledby="exampleModalLabel" data-backdrop="false" tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog " style="width: 70%; height: auto !important;"
                                         role="document">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title" id="exampleModalLabel">Tiyatro Salonu Oturma
                                                    Planı</h5>
                                                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                    <span aria-hidden="true">&times;</span>
                                                </button>
                                            </div>
                                            <div class="modal-body" id="salon_resim_icerik"> </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-dismiss="modal">
                                                    Kapat
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <form action="<?= htmlspecialchars($_SERVER['REQUEST_URI'], ENT_QUOTES, 'UTF-8') ?>" method="post" enctype="multipart/form-data">
                                    <input type="hidden" name="csrf_token" value="<?= get_csrf_token() ?>"/>
                                    <div class="form-body">
                                        <fieldset>
                                            <legend>Tiyatro Bilgisi</legend>
                                            <div class="form-group col-md-12">
                                                <script type="text/javascript">
                                                    function salon_getir(tiyatro_salonu, cocuk) {
                                                        $("#tiyatro_oyunu").load("sec/tiyatro_oyun_getir.php?tiyatro_salonu=" + tiyatro_salonu+ "&cocuk=" + cocuk);
                                                        $("#salon_resim").load("sec/tiyatro_resim_getir.php?tiyatro_salonu=" + tiyatro_salonu);
                                                        $("#salon_resim_icerik").load("sec/tiyatro_resim_getir.php?tiyatro_salonu_resim=" + tiyatro_salonu);
                                                    }
                                                </script>
                                                <label>Tiyatro Salonu</label>
                                                <div class="input-icon input-icon-lg">
                                                    <select name="salon_id" id="salon_id" class="form-control" required onchange="salon_getir(this.value, 2);">
                                                        <option value="">..:: Seçiniz ::..</option>
                                                        <?php
                                                        $qto = $dba->query("SELECT * FROM tiyatro_oyunu_salon_adi WHERE durum='1' Order By salon_adi ASC");
                                                        while ($rowto = $dba->fetch_assoc($qto)) { ?>
                                                            <option value="<?= strip((int)$rowto['id']) ?>"><?= strip($rowto['salon_adi']) ?></option>
                                                        <?php } ?>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="form-group col-md-12" id="salon_resim" >

                                            </div>
                                            <div class="form-group col-md-12">
                                                <script type="text/javascript">
                                                    function tarih_getir(tiyatro_oyunu) {
                                                        $("#tarih").load("sec/tiyatro_oyun_tarihi_getir.php?tiyatro_oyunu=" + tiyatro_oyunu);
                                                    }
                                                </script>
                                                <label>Tiyatro Oyunu</label>
                                                <div class="input-icon input-icon-lg">
                                                    <select name="tiyatro_oyunu" id="tiyatro_oyunu" class="form-control" required
                                                            onchange="tarih_getir(this.value);">
                                                        <option value="">..:: Seçiniz ::..</option>
                                                        <?php
                                                        /*                                                    $qto = $dba->query("SELECT * FROM tiyatro_oyunu WHERE online_basvuru='1' AND cocuk='2' Order By oyun_adi ASC");
                                                                                                            while ($rowto = $dba->fetch_assoc($qto)) { */?><!--
                                                        <option value="<?/*= strip((int)$rowto['id']) */?>"><?/*= strip($rowto['oyun_adi']) */?></option>
                                                    --><?php /*} */?>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="form-group col-md-12">
                                                <script type="text/javascript">
                                                    function seans_getir(tarih) {
                                                        var tiyatro_oyunu = document.getElementById("tiyatro_oyunu").value;
                                                        $("#seans").load("sec/tiyatro_oyun_seans_getir.php?tiyatro_oyunu=" + tiyatro_oyunu + "&tarih=" + tarih);
                                                    }
                                                </script>
                                                <label>Oyun Tarihi Seçiniz</label>
                                                <div class="input-icon input-icon-lg">
                                                    <select name="oyun_tarihi" id="tarih" class="form-control" required onchange="seans_getir(this.value);">
                                                        <option value="">..:: Seçiniz ::..</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="form-group col-md-12">
                                                <script type="text/javascript">
                                                    function blok_getir(blok_id) {
                                                        $("#blok_id").load("sec/tiyatro_oyun_blok_getir.php?blok_id=" + blok_id);
                                                    }
                                                    function koltuk_getir(blok_id) {
                                                        var salon_id = document.getElementById("salon_id").value;
                                                        var tiyatro_oyunu = document.getElementById("tiyatro_oyunu").value;
                                                        var tarih = document.getElementById("tarih").value;
                                                        var seans = document.getElementById("seans").value;

                                                        $("#koltuk1").load("sec/tiyatro_oyun_koltuk_getir.php?tiyatro_oyunu=" + tiyatro_oyunu + "&tarih=" + tarih+ "&salon_id=" + salon_id + "&seans=" + seans+ "&blok_id=" + blok_id);
                                                    }
                                                </script>
                                                <label>Seans Seçiniz</label>
                                                <select name="seans" id="seans" class="form-control" required onchange="blok_getir(this.value);">
                                                    <option value="">..:: Seçiniz ::..</option>
                                                </select>
                                            </div>
                                            <!--<div class="form-group col-md-12">
                                                <strong>NOT : </strong> 2. ve 3. Çocuk getirme isteğe bağlıdır.
                                            </div>-->
                                            <div class="form-group col-md-6">
                                                <label>Blok Seçiniz</label>
                                                <div class="input-icon input-icon-lg">
                                                    <select name="blok_id" id="blok_id" class="form-control" required onchange="koltuk_getir(this.value);">
                                                        <option value="">..:: Seçiniz ::..</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="form-group col-md-6">
                                                <script type="text/javascript">
                                                    function evebeyn_koltuk_secimi(secilen_koltuk) {
                                                        /*document.getElementById('koltuk2').value="";
                                                        document.getElementById('koltuk3').value="";
                                                        document.getElementById('koltuk4').value="";*/
                                                    }
                                                </script>
                                                <label id="evebeyn_koltugu_Seciniz_yazi_alani">Koltuk Seçiniz</label>
                                                <label id="koltuk_Seciniz_yazi_alani" style="display: none">Koltuk Seçiniz</label>
                                                <select name="koltuk1" id="koltuk1" class="form-control" required >
                                                    <option value="">..:: Seçiniz ::..</option>
                                                </select>
                                            </div>
                                            <!--<div class="form-group col-md-6" id="1incicocuk_alani">
                                                <script type="text/javascript">
                                                    function koltuk_kontrol_1(secilen_koltuk) {
                                                        var evebeyn_koltugu = document.getElementById('koltuk1').value;
                                                        var cocuk_koltugu2 = document.getElementById('koltuk3').value;
                                                        var cocuk_koltugu3 = document.getElementById('koltuk4').value;

                                                        if (secilen_koltuk == evebeyn_koltugu) {
                                                            alert("Lütfen Ebeveyn koltugundan farklı bir koltuk seçiniz");
                                                            document.getElementById('koltuk2').value = "";
                                                        }
                                                        if (cocuk_koltugu2 == secilen_koltuk) {
                                                            alert("2. Çocuk koltuğundan farklu bir koltukseçiniz");
                                                            document.getElementById('koltuk2').value = "";
                                                        }
                                                        if (cocuk_koltugu3 == secilen_koltuk) {
                                                            alert("3. Çocuk koltuğundan farklu bir koltukseçiniz");
                                                            document.getElementById('koltuk2').value = "";
                                                        }
                                                    }
                                                </script>
                                                <label>1. Çocuk Koltuğu Seçiniz</label>
                                                <select name="koltuk2" id="koltuk2" class="form-control" onchange="koltuk_kontrol_1(this.value);" required ></select>
                                            </div>
                                            <div class="form-group col-md-6" id="2incicocuk_alani">
                                                <script type="text/javascript">
                                                    function koltuk_kontrol_2(secilen_koltuk){
                                                        if(secilen_koltuk>0) {
                                                            document.getElementById('cocuk_adi_soyadi_2').setAttribute("required","");
                                                        }
                                                        else {
                                                            document.getElementById('cocuk_adi_soyadi_2').removeAttribute("required");
                                                        }
                                                        var evebeyn_koltugu=document.getElementById('koltuk1').value;
                                                        var cocuk_koltugu1=document.getElementById('koltuk2').value;
                                                        var cocuk_koltugu3=document.getElementById('koltuk4').value;

                                                        if(secilen_koltuk==evebeyn_koltugu) {
                                                            alert("Lütfen Ebeveyn koltugundan farklı bir koltuk seçiniz");
                                                            document.getElementById('koltuk3').value="";
                                                            document.getElementById('cocuk_adi_soyadi_2').removeAttribute("required");
                                                            document.getElementById('cocuk_adi_soyadi_2').value='';
                                                        }
                                                        if(cocuk_koltugu1==secilen_koltuk) {
                                                            alert("1. Çocuk koltuğundan farklu bir koltukseçiniz");
                                                            document.getElementById('koltuk3').value="";
                                                            document.getElementById('cocuk_adi_soyadi_2').removeAttribute("required");
                                                            document.getElementById('cocuk_adi_soyadi_2').value='';
                                                        }
                                                        if(cocuk_koltugu3==secilen_koltuk) {
                                                            alert("3. Çocuk koltuğundan farklu bir koltukseçiniz");
                                                            document.getElementById('koltuk3').value="";
                                                            document.getElementById('cocuk_adi_soyadi_2').removeAttribute("required");
                                                            document.getElementById('cocuk_adi_soyadi_2').value='';
                                                        }
                                                    }
                                                </script>
                                                <label>2. Çocuk Koltuğu Seçiniz</label>
                                                <select name="koltuk3" id="koltuk3" class="form-control" onchange="koltuk_kontrol_2(this.value);"></select>
                                            </div>
                                            <div class="form-group col-md-6" id="3incicocuk_alani">
                                                <script type="text/javascript">
                                                    function koltuk_kontrol_3(secilen_koltuk){
                                                        if(secilen_koltuk>0) {
                                                            document.getElementById('cocuk_adi_soyadi_3').setAttribute("required","");
                                                        }
                                                        else {
                                                            document.getElementById('cocuk_adi_soyadi_3').removeAttribute("required");
                                                        }
                                                        var evebeyn_koltugu=document.getElementById('koltuk1').value;
                                                        var cocuk_koltugu1=document.getElementById('koltuk2').value;
                                                        var cocuk_koltugu2=document.getElementById('koltuk3').value;
                                                        if(secilen_koltuk==evebeyn_koltugu) {
                                                            alert("Lütfen Ebeveyn koltugundan farklı bir koltuk seçiniz");
                                                            document.getElementById('koltuk4').value="";
                                                            document.getElementById('cocuk_adi_soyadi_3').removeAttribute("required");
                                                            document.getElementById('cocuk_adi_soyadi_3').value='';
                                                        }
                                                        if(cocuk_koltugu1==secilen_koltuk) {
                                                            alert("1. Çocuk koltuğundan farklu bir koltukseçiniz");
                                                            document.getElementById('koltuk4').value="";
                                                            document.getElementById('cocuk_adi_soyadi_3').removeAttribute("required");
                                                            document.getElementById('cocuk_adi_soyadi_3').value='';
                                                        }
                                                        if(cocuk_koltugu2==secilen_koltuk) {
                                                            alert("2. Çocuk koltuğundan farklu bir koltukseçiniz");
                                                            document.getElementById('koltuk4').value="";
                                                            document.getElementById('cocuk_adi_soyadi_3').removeAttribute("required");
                                                            document.getElementById('cocuk_adi_soyadi_3').value='';
                                                        }
                                                    }
                                                </script>
                                                <label>3. Çocuk Koltuğu Seçiniz</label>
                                                <select name="koltuk4" id="koltuk4" class="form-control" onchange="koltuk_kontrol_3(this.value);"></select>
                                            </div>-->
                                        </fieldset>
                                        <fieldset id="kisi_bilgisi_alani">
                                            <legend>Kişi Bilgisi</legend>
                                            <div class="form-group col-md-3">
                                                <div class="input-icon input-icon-lg">
                                                    <label>Telefon *</label>
                                                    <input type="text" class="form-control" name="kisi_telefonu" data-inputmask='"mask": "(999)999-99-99"' data-mask required/>
                                                </div>
                                            </div>
                                            <div class="form-group col-md-3">
                                                <div class="input-icon input-icon-lg">
                                                    <label>TC *</label>
                                                    <input type="text" class="form-control" name="kisi_tc" id="kisi_tc"
                                                           data-inputmask='"mask": "99999999999"' data-mask required>
                                                </div>
                                            </div>
                                            <div class="form-group col-md-3">
                                                <div class="input-icon input-icon-lg">
                                                    <label>Ad Soyad *</label>
                                                    <input type="text" class="form-control" name="kisi_adi_soyadi" id="kisi_adi_soyadi" required>
                                                </div>
                                            </div>
                                            <div class="form-group col-md-3">
                                                <div class="input-icon input-icon-lg">
                                                    <label>Doğum Tarihi *</label>
                                                    <input type="text" class="form-control" name="kisi_dogum_tarihi" id="kisi_dogum_tarihi" data-inputmask='"mask": "99-99-9999"' data-mask required>
                                                </div>
                                            </div>
                                        </fieldset>
                                        <!--<fieldset id="ebeveyn_bilgisi_alani">
                                            <legend>Ebeveyn Bilgisi</legend>
                                            <div class="form-group col-md-3">
                                                <div class="input-icon input-icon-lg">
                                                    <label>Ebeveyn Telefon</label>
                                                    <input type="text" class="form-control" name="ebeveyn_telefonu" data-inputmask='"mask": "(999)999-99-99"' data-mask />
                                                </div>
                                            </div>
                                            <div class="form-group col-md-3">
                                                <div class="input-icon input-icon-lg">
                                                    <label>Ebeveyn TC NO</label>
                                                    <input type="text" class="form-control" name="ebeveyn_tc" data-inputmask='"mask": "99999999999"' data-mask>
                                                </div>
                                            </div>
                                            <div class="form-group col-md-3">
                                                <div class="input-icon input-icon-lg">
                                                    <label>Ebeveyn Adı Soyadı</label>
                                                    <input type="text" class="form-control" name="ebeveyn_adi_soyadi">
                                                </div>
                                            </div>
                                            <div class="form-group col-md-3">
                                                <div class="input-icon input-icon-lg">
                                                    <label>Ebeveyn Doğum Tarihi</label>
                                                    <input type="text" class="form-control" name="ebeveyn_dogum_tarihi" data-inputmask='"mask": "99-99-9999"' data-mask >
                                                </div>
                                            </div>
                                        </fieldset>
                                        <fieldset id="cocuk_bilgisi_alani">
                                            <legend>Çocuk Bilgisi</legend>
                                            <div class="form-group col-md-12">
                                                <div class="input-icon input-icon-lg">
                                                    <label>1. Çocuk Adı & Soyadı</label>
                                                    <input type="text" class="form-control" name="cocuk_adi_soyadi_1" id="cocuk_adi_soyadi_1" required />
                                                </div>
                                            </div>
                                            <div class="form-group col-md-12">
                                                <div class="input-icon input-icon-lg">
                                                    <label>2. Çocuk Adı & Soyadı (İsteğe bağlı)</label>
                                                    <input type="text" class="form-control" name="cocuk_adi_soyadi_2" id="cocuk_adi_soyadi_2"/>
                                                </div>
                                            </div>
                                            <div class="form-group col-md-12">
                                                <div class="input-icon input-icon-lg">
                                                    <label>3. Çocuk Adı & Soyadı (İsteğe bağlı)</label>
                                                    <input type="text" class="form-control" name="cocuk_adi_soyadi_3" id="cocuk_adi_soyadi_3"/>
                                                </div>
                                            </div>
                                        </fieldset>-->
                                    </div>
                                    <div class="form-group">
                                        <script type="text/javascript">
                                            function kvkk(){
                                                var secilimi=document.getElementById("kisisel_veri_onayi");
                                                if(secilimi.checked==true){
                                                    $('#myModal').modal('show');
                                                }
                                            }
                                        </script>
                                        <label><input type="checkbox" name="kisisel_veri_onayi" id="kisisel_veri_onayi" onchange="kvkk()" value="1" required > Kişisel verilerin işlenme onayı </label>
                                        <div class="modal fade" id="myModal" tabindex="-1" role="dialog" aria-labelledby="myModal" aria-hidden="true">
                                            <div class="modal-dialog modal-lg modal-dialog-scrollable" role="document">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title" id="exampleModalLabel">Kişisel Verileri Koruma Kanunu</h5>
                                                    </div>
                                                    <div class="modal-body">
                                                        <?php
                                                        $qsi=$dba->query("SELECT * FROM sabit_icerik WHERE id='99' ");
                                                        $rowsi=$dba->fetch_assoc($qsi);
                                                        echo strip($rowsi['icerik']);
                                                        ?>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-default" data-dismiss="modal"> Kapat </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <div style="margin-bottom: 5px;">
                                            <label class="kirmizi">Güvenlik Kodu :</label>
                                            <img id="security" src="../uygulamalar/security.php">
                                        </div>
                                        <input type="text" class="form-control" name="guvenlik_kodu" id="guvenlik_kodu"
                                               placeholder="Güvenlik Kodunu Giriniz *" required>
                                    </div>
                                    <div class="form-group" style="margin-top: 15px;">
                                        <div class="form-actions">
                                            <button type="submit" class="btn btn-uyegiris" name="gonder">Gönder</button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php require_once("inc/footer.php"); ?>