<?php

function getPersonelList($mudurlukID)
{
    if (empty($mudurlukID)) {
        return [];
    }

    $postData = "datasetName=PRONETA_PERSONEL_YAZILIM&parameters="
        . "{TCKIMLIKNO:'',DURUMU:'AKTIF',USERNAME:'',ALTTURU:'',MUDURLUK:'" . $mudurlukID . "',STATU_TURU:''}";

    $url = "https://ybscanli.cankaya.bel.tr/FlexCityUi/rest/json/sis/RestDataset";
    $headers = [
        "Authorization: applicationkey=PRONETA,requestdate=2025-05-02T15:02:47+03:00,md5hashcode=9ead7fa09a505ba912689a4deffb55ab",
        "Content-Type: application/json",
    ];

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => $headers,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $postData,
        CURLOPT_TIMEOUT        => 30,
    ]);

    $response = curl_exec($ch);
    curl_close($ch);

    $final = [];

    if ($response) {
        $json = json_decode($response, true);

        if (!empty($json['resultSet']) && is_array($json['resultSet'])) {
            foreach ($json['resultSet'] as $p) {
                if (!empty($p['TCKN']) && !empty($p['ADI']) && !empty($p['SOYADI'])) {
                    $final[] = [
                        "id"   => $p["TCKN"],
                        "name" => $p["ADI"] . " " . $p["SOYADI"],
                        "gsm"  => $p["GSM"] ?? ""
                    ];
                }
            }
        }
    }

    return $final;
}