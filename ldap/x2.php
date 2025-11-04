<?php
if (empty($_SESSION['sifre']) AND empty($_SESSION['admin']) ) {
    // Yetkisiz erişim
    http_response_code(403); // 403 Forbidden
    ?>
    <style>
        .error-box {
            background: #fff;
            padding: 40px;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
            text-align: center;
        }
        .error-box h1 {
            font-size: 48px;
            margin-bottom: 10px;
            color: #e74c3c;
        }
        .error-box p {
            font-size: 18px;
            margin-bottom: 20px;
        }
        .error-box a {
            display: inline-block;
            padding: 10px 20px;
            background-color: rgba(6, 90, 40);
            color: #fff;
            text-decoration: none;
            border-radius: 5px;
        }
        .error-box a:hover {
            background-color: rgba(6, 90, 40);;
        }
    </style>
    <div class="error-box">
        <h1>403</h1>
        <p>Bu sayfaya erişim yetkiniz yok.</p>
        <a href="index.php">Ana Sayfaya Dön</a>
    </div>
    <?php
    exit();
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $telefon = trim($_POST['telefon'] ?? '');
    $mesaj = trim($_POST['mesaj'] ?? '');

    if ($telefon && $mesaj) {
        try {
            $client = new SoapClient("http://ws.ttmesaj.com/service1.asmx?WSDL", [
                "trace" => 1,
                "exceptions" => 1
            ]);

            $username = "cankaya.iek";
            $password = "D7G8M9S1F";

            $params = [
                "username" => $username,
                "password" => $password,
                "numbers" => $telefon,
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
                $sonuc = "<div class='alert alert-success text-center'><strong>✅ SMS başarıyla gönderildi!</strong></div>";
            } else {
                $sonuc = "<div class='alert alert-danger text-center'><strong>❌ SMS gönderilemedi.</strong> Hata: " . htmlspecialchars($result->sendSingleSMSResult ?? "bilinmiyor") . "</div>";
            }
        } catch (Exception $e) {
            $sonuc = "<div class='alert alert-warning text-center'><strong>⚠️ Hata:</strong> " . htmlspecialchars($e->getMessage()) . "</div>";
        }
    } else {
        $sonuc = "<div class='alert alert-info text-center'>⚠️ Telefon numarası ve mesaj girilmelidir.</div>";
    }
}
?>
<section class="content-header">
    <div class="row">
        <div class="col-xs-12">
            <h2><i class="fa fa-envelope"></i> SMS Gönder</h2>
        </div>
    </div>
</section>
<section class="content" style="min-height: 725px; margin-top: 10px">
    <div class="box box-success">
        <div class="box-body" style="padding-top: 10px">
            <form method="POST" class="form-horizontal">
                <div class="form-group">
                    <label class="col-sm-3 control-label">Telefon Numarası</label>
                    <div class="col-sm-6">
                        <input type="text" name="telefon" class="form-control" placeholder="5XXXXXXXXX" required>
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-sm-3 control-label">Mesaj İçeriği</label>
                    <div class="col-sm-6">
                        <textarea name="mesaj" class="form-control" rows="4" placeholder="Göndermek istediğiniz mesaj..." required></textarea>
                    </div>
                </div>
                <div class="form-group text-center">
                    <div class="col-sm-offset-3 col-sm-6">
                        <button type="submit" class="btn btn-success btn-block">
                            <i class="fa fa-paper-plane"></i> SMS Gönder
                        </button>
                    </div>
                </div>
            </form>
            <?php if (!empty($sonuc)): ?>
                <div style="margin-top:20px;">
                    <?= $sonuc ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>