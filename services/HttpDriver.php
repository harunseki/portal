<?php

require_once __DIR__ . '/ServiceDriverInterface.php';

class HttpDriver implements ServiceDriverInterface
{
    public function check(array $service): array
    {
        $start = microtime(true);

        $url = rtrim($service['base_url'], '/');
        if (!empty($service['health_endpoint'])) {
            $url .= '/' . ltrim($service['health_endpoint'], '/');
        }

        if (!empty($service['query_params']) && (($service['auth_type'] ?? '') === 'query_token' || ($service['auth_type'] ?? '') === 'header_token')) {
            $url .= (strpos($url,'?') === false ? '?' : '&') . $service['query_params'];
        }
        //echo $url;

        $ch = curl_init($url);

        $headers = [];
        // Bearer token
        if (($service['auth_type'] ?? '') === 'bearer' && !empty($service['access_token'])) {
            $headers[] = "Authorization: Bearer " . $service['access_token'];
        }

        // JSON headers
        $jsonHeaders = json_decode($service['headers'] ?? '{}', true);

        if (is_array($jsonHeaders)) {
            foreach ($jsonHeaders as $k=>$v) {
                $headers[] = "$k: $v";
            }
        }

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => (int)($service['connecttimeout']),
            CURLOPT_TIMEOUT => (int)($service['timeout'] ?? 10),
            CURLOPT_CUSTOMREQUEST => $service['method'] ?? 'GET',
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false
        ]);

        // BODY varsa ekle
        $body = $service['request_body'] ?? $service['body_template'] ?? null;
        if ($body) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        }

        $response = curl_exec($ch);

        if ($response === false) {
            return [
                'status'=>false,
                'response_time'=>0,
                'error'=>curl_error($ch)
            ];
        }

        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        $duration = round((microtime(true)-$start)*1000);

        curl_close($ch);

        if ($httpCode !== 200) {
            return [
                'status'=>false,
                'response_time'=>$duration,
                'error'=>"HTTP $httpCode : " . substr($response,0,300)
            ];
        }

        return [
            'status'=>true,
            'response_time'=>$duration,
            'error'=>null
        ];
    }
}