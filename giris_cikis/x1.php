<section class="content-header">
    <div class="row">
        <div class="col-xs-6">
            <h2>Giriş - Çıkış Bilgileri</h2>
        </div>
    </div>
</section>
<section class="content" style="padding-left: 10px !important;">
        <div class="row"  style="margin:10px 0 50px;">
            <div class="col-md-12">
                <table class="table table-bordered" style="color: #000000;">
                    <thead>
                    <tr>
                        <th>Tarih</th>
                        <th>Gün</th>
                        <th>Giriş Saati</th>
                        <th>Çıkış Saati</th>
                    </tr>
                    </thead>
                    <tbody id="attendanceBody">
                    <?php
                    // Hataları gizle (production için)
                    error_reporting(0);
                    ini_set('display_errors', 0);

                    $apiUrl = "https://pdks.cankaya.bel.tr/api/pdksModule/PdksActivity/GetPdksPersonelFirstEnterLastExitLog";
                    $today = date('Y-m-d');
                    $start = date('Y-m-d', strtotime('-14 days')); // Son 14 gün
                    $accessToken = "5c84a43b-0afc-49b5-91fd-e647df2b87f8";
                    $includeNonworkerPersonels = "true";

                    // cURL başlat
                    $ch = curl_init();
                    $url = "$apiUrl?Start=$start&End=$today&access_token=$accessToken&IncludeNonworkerPersonels=$includeNonworkerPersonels&personelTckimlikno=$personelTC";
                    curl_setopt($ch, CURLOPT_URL, $url);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($ch, CURLOPT_TIMEOUT, 60);

                    $response = curl_exec($ch);
                    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                    $curlError = curl_error($ch);
                    curl_close($ch);

                    if ($response === false) {
                        echo "<tr><td colspan='4' style='color:red; text-align:center;'>API'den veri alınamadı.</td></tr>";
                        $data = [];
                    } else {
                        $data = json_decode($response, true);
                        if (json_last_error() !== JSON_ERROR_NONE || !is_array($data)) {
                            echo "<tr><td colspan='4' style='color:red; text-align:center;'>JSON hatası: " . json_last_error_msg() . "</td></tr>";
                            $data = [];
                        }
                    }

                    // Tarih bazlı eşleme
                    $dataMap = [];
                    if (!empty($data) && is_array($data)) {
                        foreach ($data as $entry) {
                            $dateKey = isset($entry['date']) ? substr($entry['date'], 0, 10) : null;
                            if ($dateKey) {
                                $dataMap[$dateKey] = $entry;
                            }
                        }
                    }

                    // Son 15 günü oluştur
                    for ($i = 0; $i < 15; $i++) {
                        $date = date('Y-m-d', strtotime("-$i day", strtotime($today)));
                        $dayOfWeek = date('N', strtotime($date));
                        $isWeekend = $dayOfWeek > 5;
                        $dayNames = ['Pazartesi','Salı','Çarşamba','Perşembe','Cuma'];
                        $dayName = $isWeekend ? ($dayOfWeek == 6 ? 'Cumartesi' : 'Pazar') : $dayNames[$dayOfWeek-1];

                        if (isset($dataMap[$date])) {
                            $entry = $dataMap[$date];

                            // Güvenli erişim
                            $enterDate = $entry['enterDate'] ?? null;
                            $exitDate  = $entry['exitDate']  ?? null;

                            if (!empty($enterDate)) {
                                $dtEnter = new DateTime($enterDate);
                                $dtEnter->modify('+3 hours');
                                $enterTime = $dtEnter->format('H:i:s');
                            } else {
                                $enterTime = 'Giriş Yapılmadı';
                            }

                            if (!empty($exitDate)) {
                                $dtExit = new DateTime($exitDate);
                                $dtExit->modify('+3 hours');
                                $exitTime = $dtExit->format('H:i:s');
                            } else {
                                $exitTime = 'Çıkış Yapılmadı';
                            }
                        } else {
                            $enterTime = '-';
                            $exitTime = '-';
                        }

                        $rowColor = $isWeekend ? 'style="background-color: #efefef; color: #000000;"' : '';
                        $dayDisplay = $isWeekend ? "<strong><em>$dayName</em></strong>" : $dayName;

                        echo "<tr $rowColor data-date='$date'>";
                        echo "<td>".htmlspecialchars(global_date_to_tr($date))."</td>";
                        echo "<td>$dayDisplay</td>";
                        echo "<td>".htmlspecialchars($enterTime)."</td>";
                        echo "<td>".htmlspecialchars($exitTime)."</td>";
                        echo "</tr>";
                    }
                    ?>
                    </tbody>
                </table>
                <button id="loadMoreBtn" class="btn btn-primary mt-2">Geçmiş 15 günü yükle</button>
            </div>
        </div>
</section>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
    var personelTckimlikno = <?= json_encode($personelTC) ?>;

    $('#loadMoreBtn').on('click', function(){
        var lastDate = $('#attendanceBody tr:last').data('date');

        $.get('giris_cikis/api_veri.php', {
            lastDate: lastDate,
            personelTckimlikno: personelTckimlikno
        }, function(response){
            if(response.trim() === ''){
                alert('Daha fazla veri yok.');
                return;
            }
            $('#attendanceBody').append(response);
        }).fail(function(){
            alert('API çağrısı sırasında hata oluştu.');
        });
    });
</script>
