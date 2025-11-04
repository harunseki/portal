<section class="content-header">
    <div class="row">
        <div class="col-xs-6">
            <h2>QR Oluşturucu</h2>
        </div>
        <div class="col-xs-6 text-right"></div>
    </div>
</section>
<section class="content">
    <div class='row' style="margin-top:10px;">
        <div class='col-md-12'>
            <div class='box box-success'>
                <div class='box-header'>
                    <h3 class='box-title'>QR Oluşturucu</h3>
                </div>
                <div class='box-body'>
                    <form action="" method="post">
                        <?php
                        if ($_SERVER['REQUEST_METHOD'] === "POST") {
                            $text = trim($_POST['text']);
                            $boyut = (int)($_POST['boyut'] ?? 5);

                            $hata = [];
                            if (empty($text)) $hata[] = "<div>Text giriniz</div>";
                            if ($boyut < 1 || $boyut > 20) $hata[] = "<div>Boyut 1-20 arasında olmalı</div>";

                            if (count($hata) > 0) {
                                echo "<div class='alert alert-danger'>" . implode("<br>", $hata) . "</div>";
                            } else {
                                $qr_url = "qr_olusturucu/code.php?adres=" . urlencode($text) . "&size=" . $boyut;
                                ?>
                                <div class="form-group text-center">
                                    <div style="margin: 10px"> <span style="text-decoration: underline; font-weight: bold"><?= $text ?></span> linki için QR kod oluşturdunuz.</div>
                                    <img src="qr_olusturucu/code.php?adres=<?= urlencode($text) ?>&size=<?= $boyut ?>"
                                         style="max-width: 500px;"/>
                                    <div style="margin: 10px">📌 QR Kodu kullanmadan önce test etmeyi unutmayınız!</div>
                                    <a href="<?= $qr_url ?>" download="qr_code.png" class="btn btn-success">
                                        📥 QR'ı Kaydet
                                    </a>
                                </div>
                                <?php
                                $dba->addLog($ip, $ldap_username, $personelTC, "create", "Yeni qr oluşturuldu : ".$text);
                            }
                        }
                        ?>
                        <div class="form-group">
                            <label class="kirmizi">Text</label>
                            <input type="text" class="form-control" id="text" name="text" required value="<?= $_POST['text'] ?>"/>
                        </div>

                        <div class="form-group">
                            <label class="kirmizi">Boyut (1-20)</label>
                            <input type="number" class="form-control" id="boyut" name="boyut" min="1" max="20" value="5" required />
                        </div>

                        <div class="box-footer">
                            <button class="btn btn-success" type="submit">QR Oluştur</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>
