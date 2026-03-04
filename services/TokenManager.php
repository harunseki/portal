<?php
declare(strict_types=1);
class TokenManager
{
    public static function ensureValidToken(Db $db, array &$service): void
    {
        $authType = $service['auth_type'] ?? 'none';

        // token gerektirmeyen auth tipleri
        if (!in_array($authType, ['bearer','query_token','header_token'])) {
            return;
        }

        // statik token varsa ve expiry yoksa refresh etme
        if (!empty($service['access_token']) && empty($service['token_endpoint'])) {
            return;
        }

        // token hala geçerli mi
        if (!empty($service['access_token']) && !empty($service['token_expires_at']) && strtotime($service['token_expires_at']) > time() + 60) {
            return;
        }

        self::refreshToken($db, $service);
    }

    private static function refreshToken($db, array &$service)
    {
        if (empty($service['token_endpoint'])) {
            throw new Exception("Token endpoint boş");
        }

        $config = json_decode($service['token_config'] ?? '{}', true);

        if (!is_array($config)) {
            throw new Exception("token_config JSON invalid");
        }

        $headers = ["Content-Type: application/json"];

        $ch = curl_init($service['token_endpoint']);

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($config),
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => 20,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false
        ]);

        $response = curl_exec($ch);

        if ($response === false) {
            throw new Exception("Token CURL error: " . curl_error($ch));
        }

        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if ($httpCode != 200) {
            throw new Exception("Token HTTP error: $httpCode Response: " . substr($response,0,500));
        }

        $data = json_decode($response, true);

        if (!$data) {
            throw new Exception("Token JSON parse error: " . substr($response,0,500));
        }

        // ERP özel durumu
        $tokenField = $service['token_field'] ?? 'access_token';

        if (!isset($data[$tokenField])) {
            throw new Exception("Token field bulunamadı: $tokenField");
        }

        $expires = time() + 3600;

        if (isset($data['expires_in'])) {
            $expires = time() + (int)$data['expires_in'];
        }

        $service['access_token'] = $data[$tokenField];
        $service['token_expires_at'] = date('Y-m-d H:i:s', $expires);

        $stmt = $db->prepare("UPDATE services SET access_token=?, token_expires_at=? WHERE id=? ");

        $stmt->bind_param(
            "ssi",
            $service['access_token'],
            $service['token_expires_at'],
            $service['id']
        );

        if (!$stmt->execute()) {
            throw new Exception("Token DB update failed: " . $stmt->error);
        }

        $stmt->close();
    }
}