<?php
ob_start();
session_start();
/*if(empty($_SESSION['kecvet_login_admin_id']) or empty($_SESSION['kecvet_login_admin_name']) ){
        exit();
}*/
	
require_once("../class/mysql.php");
require_once("../class/functions.php");

set_time_limit(1200);
ini_set('default_socket_timeout', 240);
ini_set('error_reporting',E_ERROR);
ini_set("log_errors" , "1");
ini_set("error_log" , "5223errors.log");
ini_set("display_errors" , "1");

require_once($_SERVER['DOCUMENT_ROOT'] . "/htmlpurifier/library/HTMLPurifier.auto.php");
$config = HTMLPurifier_Config::createDefault();
$config->set("Core", "Encoding", "utf-8");
$purifier = new HTMLPurifier($config);

$q_erisim_kontrol=$dba->query("SELECT yetkili.id, yetkili.tc 
							   FROM yetkili 
							   WHERE id=".admin_id());
$row_erisim_kontrol=$dba->fetch_assoc($q_erisim_kontrol);

require_once($_SERVER['DOCUMENT_ROOT'] . "/cankayakentkonseyi/class/login_kontrol1.php");
$erisim_durumu="";
$erisim_durumu=$login_kontrol->erisim_kontrol(uygulama_id(),1,$row_erisim_kontrol['tc']);

if($erisim_durumu!=1) {
    ?>
    <style>
	.erisim_kontrol_class {
		 padding: 15px;
		 padding-right: 15px;
		 padding-left: 15px;
		 margin-bottom: 20px;
		 margin-left: 15px;
		 border: 1px solid transparent;
		 border-top-color: transparent;
		 border-right-color: transparent;
		 border-bottom-color: transparent;
		 border-left-color: transparent;
		 border-radius: 4px;
		 color: #a94442;
		 background-color: #f2dede;
		 border-color: #ebccd1;
	 }
    </style>
    <div class="erisim_kontrol_class">
        <p>Yetki Formunuz Bulunmamaktadır. Sorgulama Yapamazsınız.</p>
        <p>
			<span style="font-weight: bold; color:#464646 ">"KPS (TC No Sorgulama)Yetki Formu"</span>için
			<a href="https://<?=strip($_SERVER['SERVER_NAME'])?>/yetki-formlari/index.html" style="color: #cb3500; text-decoration: underline; font-weight: bold" target="_blank">TIKLAYIN !!! </a>
        </p>
        <p>Formu doldurunup üst yazı ile Bilgi İşlem Müdürlüğüne gönderiniz.</p>
    </div>
    <?php
    exit;
}
	$tc=$purifier->purify(rescape((int)$_POST['tc']));
    $dogum_tarihi = $purifier->purify(rescape($_POST['dogum_tarihi']));
    $dogum_tarihi=explode("/", $dogum_tarihi);
    $dogum_gun=$dogum_tarihi[0];
    $dogum_ay=(int)$dogum_tarihi[1];
    $dogum_yil=$dogum_tarihi[2];

	$yetkili=$purifier->purify(rescape((int)$_GET['yetkili']));

	if(!empty($yetkili)){
		$qc=$dba->query("SELECT * 
						 FROM yetkili 
						 WHERE tc='$tc' and id!='$yetkili' ");
	}else{
		$qc=$dba->query("SELECT * 
						 FROM yetkili 
						 WHERE tc='$tc' ");
	}

	$rowuye=$dba->fetch_assoc($qc);
	$rowc=$dba->num_rows($qc);

	if($rowc>0){
	?>
	<div style="color:#900; font-weight:bold;">TC kayitli bulunmaktadir. <a href="yetkili.php?x=2&edit=<?=strip((int)$rowuye['id'])?>">Buradan</a> düzenleme yapabilirsiniz.</div>
	<?php
	}
	
	$rowuye=$dba->fetch_assoc($qc);
	$rowc=$dba->num_rows($qc);

	$params = array(
        'tcno' => $tc,
        'DogumGunu' => $dogum_gun,
        'DogumAyi' => $dogum_ay,
        'DogumYili' => $dogum_yil,
		'kullaniciip' => GetIP(),
		'uygulama' =>"CODAM",
		'kullanici' => $_SESSION['kecvet_admin_name'],
		'sifre' => "kEciOren%(//()=",
		'kullaniciadi'=>"Keciorenkps%&+^"
	);
	try {
		$options = array(
			'soap_version'=>SOAP_1_1,
			'exceptions'=>true,
			'trace'=>1
		);
        $client = new SoapClient('https://kps.kecioren.bel.tr/service.asmx?wsdl', $options);
	}
	catch (Exception $e) {
		echo "<h2>Exception Error!</h2>";
		echo $e->getMessage();
		var_dump($client->__getLastRequest());
		var_dump($client->__getLastResponse());
	}

	try {
		$response=$client->AdresSorgula($params);
	}
	catch (Exception $e) {
		echo 'Caught exception: ',  $e->getMessage(), "\n";
		var_dump($client->__getLastRequest());
		var_dump($client->__getLastResponse());
	}

	$sonuc = json_decode($response->AdresSorgulaResult);
	$sonuc = $sonuc->SorguSonucu[0];
	if($sonuc->YerlesimYeriAdresi->HataBilgisi != "" || $response==new stdClass()){
		$adres_durumu="Keçiörende Oturmuyor";
		$mahalle_kod=0;
		$csbm_kod=0;			
	}
	else{
		$sonuc = $sonuc->YerlesimYeriAdresi;
		$adres_durumu=$sonuc->AcikAdres;
		$mahalle_kod=$sonuc->IlIlceMerkezAdresi->MahalleKodu;
		$csbm_kod=$sonuc->IlIlceMerkezAdresi->CsbmKodu;			
	}
	
	try {
		$response_kisi=$client->KisiSorgula($params);
	}
	catch (Exception $e) {
		echo 'Caught exception: ',  $e->getMessage(), "\n";
		var_dump($client->__getLastRequest());
		var_dump($client->__getLastResponse());
	}

	$sonuc_kisi = json_decode($response_kisi->KisiSorgulaResult);
	
	//var_dump($sonuc_kisi);
	
	$sonuc_kisi = $sonuc_kisi->SorguSonucu[0];
	$sonuc_kisi = $sonuc_kisi->TemelBilgisi;
	
	$ad=$sonuc_kisi->Ad;
	$soyad=$sonuc_kisi->Soyad;
	$cinsiyet=$sonuc_kisi->Cinsiyet->Aciklama;
	
	if($cinsiyet==Erkek){
		$cinsiyet=1;
	}
	else if($cinsiyet==Kadin){
		$cinsiyet=2;
	}
	
	$dogum_yeri=$sonuc_kisi->DogumYer;

	$gun= $sonuc_kisi->DogumTarih->Gun;
	if(strlen($gun)<2){
		$gun="0".$gun;
	}
	$ay=$sonuc_kisi->DogumTarih->Ay;
	if(strlen($ay)<2){
		$ay="0".$ay;
	}
	$yil=$sonuc_kisi->DogumTarih->Yil;

	$dogum_tarihi=$gun."-".$ay."-".$yil;
?>
    <div class="form-group">
        <label>Adi</label>
        <input type="text" class="form-control" name="adi" id="adi" value="<?=strip($ad)?>"  />
    </div>

    <div class="form-group">
        <label>Soyadi</label>
        <input type="text" class="form-control" name="soyadi" id="soyadi" value="<?=strip($soyad)?>" />
    </div>

    <div class="form-group">
        <label>Adres</label>
        <input type="text" class="form-control" name="adres" id="adres" value="<?=strip($adres_durumu)?>" />
        <input type="hidden" id="textfield" class="text" name="mahalle" value="<?=strip($mahalle_kod)?>"  />
        <input type="hidden" id="textfield" class="text" name="csbm" value="<?=strip($csbm_kod)?>"  />
    </div>

    <div class="form-group">
        <label>Dogum Yeri</label>
        <input type="text" class="form-control" name="dogum_yeri" id="dogum_yeri" value="<?=strip($dogum_yeri)?>" />
    </div>

	


