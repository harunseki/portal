<?php
class SoapDriver implements ServiceDriverInterface
{
    public function check(array $service): array
    {
        $start = microtime(true);

        try {
            // WSDL URL'i
            $wsdl = $service['base_url'];

            // SOAP Client oluştur
            $client = new SoapClient($wsdl, [
                'trace' => true,
                'exceptions' => true,
                'connection_timeout' => $service['timeout'] ?? 10,
            ]);

            // Metod adı
            $method = $service['health_endpoint'];

            // Parametreleri JSON'dan array olarak al
            $params = json_decode($service['request_body'] ?? '{}', true);
            if (!is_array($params)) {
                throw new Exception("request_body JSON parse hatası");
            }

            // SOAP çağrısı
            $result = $client->__soapCall($method, [$params]);

            $duration = round((microtime(true) - $start) * 1000);

            return [
                'status' => true,
                'response_time' => $duration,
                'error' => null,
            ];

        } catch (Throwable $e) {
            return [
                'status' => false,
                'response_time' => 0,
                'error' => $e->getMessage(),
            ];
        }
    }
}