<?php
require_once("inc/header.php");
require_once("inc/menu1.php");
require_once "inc/kontrol.php";
// Form gönderildiyse API isteği yapılacak
$response = null;
$error = null;

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $tc = trim($_POST["tc"]);
    $endpoint = $_POST["endpoint"];

    if ($endpoint === "egitim") {
        $token = "Zz91fE3Ixr9vAQL";
    } else {
        $token = "R1Tip0v804N4";
    }

    // Geçersiz TC kontrolü
    if (!is_numeric($tc) || strlen($tc) < 8) {
        $error = "Lütfen geçerli bir TCKimlikNo girin.";
    } else {
        // URL seçimi
        if ($endpoint === "egitim") {
            $url = "https://yazilim.ankara.bel.tr/api/EgoOgrenci/sorgula";
        } elseif ($endpoint === "mezun") {
            $url = "https://yazilim.ankara.bel.tr/api/YOK/EgitimBilgisiMezun";
        } else {
            $url = "https://yazilim.ankara.bel.tr/api/YOK/DenklikBilgisiMezun";
        }

        // CURL ile POST atma
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Content-Type: application/json",
            "token: $token"
        ]);
        curl_setopt($ch, CURLOPT_POST, true);

// API EgoOgrenci/sorgula için body => { "tckn" : 1111111111 }
        if ($endpoint === "egitim") {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(["tckn" => $tc]));
        } else {
            // Mezun ve denklik için de TCKN kullanmayı deneyelim
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(["TCKimlikNo" => $tc]));
        }

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $result = curl_exec($ch);

        if (curl_errno($ch)) {
            $error = "CURL Hatası: " . curl_error($ch);
        } else {
            $response = $result;
        }
        curl_close($ch);

        $dba->addLog($ip, $ldap_username, $personelTC, "create","Kullanıcıları içinde arama yapıldı: \"".$tc."\" " );
    }
}
?>
<aside class="right-side">
    <section class="content-header">
        <div class="row">
            <div class="col-xs-6"><h2>YÖK Bilgi Sorgulama</h2></div>
            <div class="col-xs-6 text-right">
                <div class="input-group" style="width: 250px; margin-top: 20px; float: right;">
                    <input type="text" id="dosyaArama" class="form-control" placeholder="Dosya ara...">
                    <span class="input-group-btn">
                        <button class="btn btn-default" type="button"><i class="fa fa-search"></i></button>
                    </span>
                </div>
            </div>
        </div>
    </section>
    <section class="content">
        <div class="box-body">
            <div class="row" style="margin-top:10px;">
                <div class="col-md-12">
                    <div class="box box-success" style="margin-bottom: 0">
                        <div class="box-body">
                            <form method="POST">
                                <div class="form-group">
                                    <label for="tc">TC Kimlik No:</label>
                                    <input type="text" name="tc" id="tc" class="form-control" maxlength="11" required placeholder="TC Kimlik Numarası">
                                </div>
                                <div class="form-group">
                                    <label for="endpoint">Sorgu Türü:</label>
                                    <select name="endpoint" id="endpoint" class="form-control">
                                        <option value="egitim">Aktif Öğrenci Bilgi</option>
                                        <option value="mezun">Mezun Bilgi</option>
                                        <option value="denklik">Denklik Bilgi</option>
                                    </select>
                                </div>
                                <button type="submit" class="btn btn-success btn-block">
                                    <i class="fa fa-search"></i> Sorgula
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row">
                <?php
                if ($response) {
                    if ($endpoint === "egitim") {
                        if ($error) {
                            echo '<div class="col-12"><div class="alert alert-danger">' . htmlspecialchars($error) . '</div></div>';
                        } elseif ($response) {
                            $json = json_decode($response, true);
                            if (!$json) {
                                echo '<div class="col-12"><div class="alert alert-danger">JSON parse edilemedi!</div></div>';
                            } else {
                                // Sonuç kontrolü
                                $sonuc = $json["sonuc"] ?? "Hata";
                                echo '<h2 style="margin-left:15px;">Öğrenci Bilgisi</h2>';

                                if (!empty($json["aktifOgrenimler"]) && is_array($json["aktifOgrenimler"])) {
                                    foreach ($json["aktifOgrenimler"] as $o) { ?>
                                        <div class="col-md-4">
                                            <div class="box box-success" style="min-height:200px; border-radius:10px;">
                                                <div class="box-header with-border">
                                                    <h3 class="box-title">🎓 <?= htmlspecialchars($o["universiteAdi"]) ?></h3>
                                                </div>
                                                <div class="box-body" style="font-size:14px; line-height:1.5;">
                                                    Öğrenim Süresi: <?= intval($o["ogrenimSuresi"]) ?> yıl<br>
                                                    Öğrenim Türü: <?= htmlspecialchars($o["ogrenimTuru"]) ?><br>
                                                </div>
                                            </div>
                                        </div>
                                    <?php }
                                } else {
                                    echo '<div class="col-12"><div class="alert alert-warning">Aktif öğrenim bulunamadı.</div></div>';
                                }
                            }
                        }
                    }
                    else {
                        $json = json_decode($response, true);

                        $sonucKod = $json["sonuc"]["sonucKod"] ?? null;
                        $sonucMesaj = $json["sonuc"]["sonucMesaj"] ?? "Bilinmeyen hata";

                        echo '<h2 style="margin-left:15px;">Mezuniyet Bilgileri</h2>';

                        if ($sonucKod == 1 && !empty($json["egitimBilgisiMezunKayit"])) {
                            foreach ($json["egitimBilgisiMezunKayit"] as $kayit) {
                                $kisi = $kayit["kisiselBilgi"];
                                $mezun = $kayit["mezunBilgi"];
                                $birim = $kayit["birimBilgi"];
                                ?>
                                <div class="col-md-4">
                                    <div class="box box-success" style="min-height:300px; border-radius:10px;">
                                        <div class="box-header with-border">
                                            <h3 class="box-title" style="white-space:normal;">
                                                🎓 <?= $birim["birimAdi"] ?> (<?= $birim["birimTuru"]["ad"] ?>)
                                            </h3>
                                        </div>
                                        <div class="box-body" style="font-size:14px; line-height:1.5;">
                                            <b style="text-decoration: underline">Kişisel Bilgi</b><br>
                                            Ad Soyad: <b><?= $kisi["adi"] . " " . $kisi["soyadi"] ?></b><br>
                                            TC: <?= $kisi["tcKimlikNo"] ?><br>
                                            Doğum Tarihi: <?= $kisi["dogumTarihi"] ?><br>
                                            Cinsiyet: <?= $kisi["cinsiyeti"]["ad"] ?><br>

                                            <b style="text-decoration: underline">Mezuniyet Bilgisi</b><br>
                                            GANO: <?= $mezun["gano"]["not"] ?> / <?= $mezun["gano"]["notSistem"] ?><br>
                                            Ayrılma Nedeni: <?= $mezun["ayrilmaNedeni"]["ad"] ?><br>
                                            Ayrılma Tarihi: <?= $mezun["ayrilmaTarihi"] ?><br>
                                            Diploma No: <?= $mezun["diplomaNo"] ?><br>
                                            Durum: <?= $mezun["durum"] ?><br>

                                            <b style="text-decoration: underline">Üniversite Bilgisi</b><br>
                                            Üniversite: <?= $birim["universite"]["ad"] ?><br>
                                            Fakülte: <?= $birim["fakulteYoMyoEnstitu"]["ad"] ?><br>
                                            Bölüm: <?= $birim["birimAdi"] ?><br>
                                            Öğrenim Türü: <?= $birim["ogrenimTuru"]["ad"] ?><br>
                                            Öğrenim Dili: <?= $birim["ogrenimDili"]["ad"] ?><br>
                                            Şehir: <?= $birim["il"]["ad"] ?> / <?= $birim["ilce"]["ad"] ?><br>
                                            Rehber Kod: <?= $birim["kilavuzKodu"] ?><br>
                                        </div>
                                    </div>
                                </div>
                                <?php
                            }
                        }
                        else {
                            echo '<div class="col-12">
                                    <div class="alert alert-warning" style="margin: 15px;">
                                        ' . htmlspecialchars($sonucMesaj) . '
                                    </div>
                                  </div>';
                        }
                    }
                }
                ?>
            </div>
        </div>
    </section>
</aside>
<?php require_once "inc/footer.php"; ?>
