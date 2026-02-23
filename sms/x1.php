<?php
if (empty($_SESSION['sifre']) && empty($_SESSION['admin'])) {
    http_response_code(403);
    ?>
    <style>
        .error-box { text-align:center; padding:50px; }
        .error-box h1 { font-size:100px; }
        .error-box p { font-size:20px; }
        .error-box a { color:#007bff; text-decoration:none; }
        .error-box a:hover { text-decoration:underline; }
    </style>
    <div class="error-box">
        <h1>403</h1>
        <p>Bu sayfaya erişim yetkiniz yok.</p>
        <a href="index.php">Ana Sayfaya Dön</a>
    </div>
    <?php
    exit();
}

$sonuc = "";

// SMS gönderme işlemi
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mesaj'])) {
    $mesaj = trim($_POST['mesaj']);
    //$mesaj = "Bim yazılım deneme SMS' idir. Lütfen dikkate almayın";
    $telefon = trim($_POST['telefon']);
    $mudurlukler = $_POST['mudurluk'] ?? [];
    $secilenPersoneller = $_POST['personellers'] ?? [];

    $telefonlar = [];

    // 🔵 Çoklu müdürlük seçilmişse personel listesi getirilmez → tüm personeller gönderilir
    if (count($mudurlukler) > 0 && empty($secilenPersoneller)) {
        foreach ($mudurlukler as $mid) {
            $personelList = getPersonelList($mid);

            foreach ($personelList as $p) {
                $gsm = preg_replace('/[^0-9]/', '', $p['gsm']);
                if (strlen($gsm) >= 10 && substr($gsm,0,1) === '5') {
                    $telefonlar[] = $gsm;
                }
            }
        }
        // Tekilleştir
        $telefonlar = array_unique($telefonlar);
    }
    // 🔵 Tek müdürlük + personel seçilmişse
    else if (count($mudurlukler) == 1 && !empty($secilenPersoneller)) {

        $personelList = getPersonelList($mudurlukler[0]);

        foreach ($personelList as $p) {
            if (in_array($p['id'], $secilenPersoneller)) {
                $gsm = preg_replace('/[^0-9]/', '', $p['gsm']);
                if (strlen($gsm) >= 10 && substr($gsm,0,1) === '5') {
                    $telefonlar[] = $gsm;
                }
            }
        }
    }
    // 🔵 Manuel telefon girilmişse
    else if (!empty($telefon)) {
        $telefonlar[] = preg_replace('/[^0-9]/', '', $telefon);
    }

    // Son gönderim numarası
    $numbersToSend = implode(",", $telefonlar);
    /*(count($telefonlar));
    exit();*/

    if (!empty($numbersToSend) && !empty($mesaj)) {
        try {
            $client = new SoapClient("http://ws.ttmesaj.com/service1.asmx?WSDL", [
                "trace" => 1,
                "exceptions" => 1
            ]);

            $params = [
                "username" => "cankaya.iek",
                "password" => "D7G8M9S1F",
                "numbers" => $numbersToSend,
                "message" => $mesaj,
                "origin" => "CANKAYA BLD",
                "sd" => "",
                "ed" => "",
                "isNotification" => true,
                "recipentType" => "0",
                "brandCode" => "0"
            ];

            $result = $client->__soapCall("sendSingleSMS", [$params]);

            if (isset($result->sendSingleSMSResult) && strpos($result->sendSingleSMSResult, '*OK*') === 0) {
                $count = count(explode(',', $numbersToSend));
                $sonuc = "<div class='alert alert-success text-center'><strong>✅ $count adet SMS başarıyla gönderildi!</strong></div>";
            } else {
                $sonuc = "<div class='alert alert-danger text-center'><strong>❌ SMS gönderilemedi.</strong> Hata: " . htmlspecialchars($result->sendSingleSMSResult ?? "bilinmiyor") . "</div>";
            }
        } catch (Exception $e) {
            $sonuc = "<div class='alert alert-warning text-center'><strong>⚠️ Hata:</strong> " . htmlspecialchars($e->getMessage()) . "</div>";
        }
    } else {
        $sonuc = "<div class='alert alert-info text-center'>⚠️ Lütfen bir telefon numarası / müdürlük / mesaj giriniz.</div>";
    }
}
?>

<section class="content-header">
    <h2><i class="fa fa-envelope"></i> SMS Gönder</h2>
</section>
<section class="content" style="min-height:725px; margin-top:10px;">
    <div class="box box-success">
        <div class="box-body" style="padding-top:10px;">
            <form method="POST">
                <div class="form-group">
                    <label>Müdürlük (çoklu seçim)</label>
                    <select name="mudurluk[]" id="mudurluk" class="form-control select2" multiple>
                        <?php
                        $res = $dba->query("SELECT flexy_id, mudurluk FROM mudurlukler ORDER BY mudurluk ASC");
                        while ($m = $res->fetch_assoc()) {
                            echo '<option value="'.$m['flexy_id'].'">'.htmlspecialchars($m['mudurluk']).'</option>';
                        }
                        ?>
                    </select>
                </div>
                <div class="form-group">
                    <div id="personel_area">
                        <label>Personeller (çoklu seçim)</label>
                        <select name="personellers[]" id="personellers" multiple class="form-control">
                            <option value="">Önce müdürlük seçiniz</option>
                        </select>
                    </div>
                </div>
                <div class="form-group" id="telephone_area">
                    <label>Telefon Numarası (Bireysel bir numaraya messj atmak için bu alanı kullanın.)</label>
                    <input type="text" name="telefon" class="form-control" placeholder="5XXXXXXXXX" maxlength="10">
                </div>
                <div class="form-group">
                    <label>Mesak İçerik</label>
                    <textarea name="mesaj" class="form-control" rows="4" required placeholder="Mesaj içeriğinizi buraya yazınız"></textarea>
                </div>
                <button type="submit" class="btn btn-success btn-block">
                    <i class="fa fa-paper-plane"></i> SMS Gönder
                </button>
            </form>
            <?php if (!empty($sonuc)) echo '<div style="margin-top:20px;">'.$sonuc.'</div>'; ?>
        </div>
    </div>
</section>
<script>
    $(document).ready(function () {

        $("#mudurluk").chosen();
        $("#personellers").chosen();

        $("#mudurluk").on("change", function () {

            let selected = $(this).val();
            let $area = $("#personel_area");
            let $personel = $("#personellers");
            let $telephone = $("#telephone_area");

            // 🔵 Çoklu müdürlük seçildiyse personel alanını gizle
            if (selected.length > 1) {
                $area.hide();
                $personel.empty();
                return;
            }

            // Tek müdürlük seçildiyse personelleri getir
            $area.show();
            $telephone.hide();

            let mid = selected[0];

            $personel.empty().append(`<option>Yükleniyor...</option>`).trigger("chosen:updated");

            $.ajax({
                url: "sms/get_personel_api.php",
                type: "POST",
                data: { mudurluk: mid },
                dataType: "json",
                success: function (response) {
                    $personel.empty();
                    $.each(response, function (i, p) {
                        $personel.append(`<option value="${p.id}">${p.name}</option>`);
                    });
                    $personel.trigger("chosen:updated");
                }
            });

        });

    });
</script>