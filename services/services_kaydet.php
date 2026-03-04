<?php
require "../class/mysql.php";

// Hata raporlamayı aç
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$id = isset($_POST['id']) ? intval($_POST['id']) : 0;

$name = $_POST['name'] ?? '';
$description = $_POST['description'] ?? '';
$protocol = $_POST['protocol'] ?? 'http';
$base_url = $_POST['base_url'] ?? '';
$health_endpoint = $_POST['health_endpoint'] ?? '';
$method = $_POST['method'] ?? 'GET';
$auth_type = $_POST['auth_type'] ?? 'none';
$auth_config = isset($_POST['auth_config']) && trim($_POST['auth_config']) ? $_POST['auth_config'] : '{}';
$headers = isset($_POST['headers']) && trim($_POST['headers']) ? $_POST['headers'] : '{}';
$body_template = $_POST['body_template'] ?? null;
$request_body = $_POST['request_body'] ?? null;
$query_params = $_POST['query_params'] ?? null;
$success_condition  = isset($_POST['success_condition']) && trim($_POST['success_condition']) ? $_POST['success_condition'] : '{}';
$token_endpoint = $_POST['token_endpoint'] ?? null;
$token_config = isset($_POST['token_config']) && trim($_POST['token_config']) ? $_POST['token_config'] : '{}';
$access_token = $_POST['access_token'] ?? null;
$token_expires_at = $_POST['token_expires_at'] ?? null;
$timeout = isset($_POST['timeout']) ? intval($_POST['timeout']) : 10;
$check_interval = isset($_POST['check_interval']) ? intval($_POST['check_interval']) : 300;
$is_active = isset($_POST['is_active']) ? intval($_POST['is_active']) : 1;

try {

    if($id === 0) {
        $stmt = $dba->prepare("
            INSERT INTO services
            (name, description, protocol, base_url, health_endpoint, method,
             auth_type, auth_config, headers, body_template, request_body, query_params,
             success_condition, token_endpoint, token_config, access_token, token_expires_at,
             timeout, check_interval, is_active)
            VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)
        ");
        if(!$stmt) die("Prepare Hatası: ".$dba->error());

        $bind = $stmt->bind_param(
            "ssssssssssssssssiiii",
            $name, $description, $protocol, $base_url, $health_endpoint, $method,
            $auth_type, $auth_config, $headers, $body_template, $request_body, $query_params,
            $success_condition, $token_endpoint, $token_config, $access_token, $token_expires_at,
            $timeout, $check_interval, $is_active
        );
        if(!$bind) die("Bind Hatası: ".$stmt->error);

        $msg = "Servis eklendi";
    } else {
        $stmt = $dba->prepare("
            UPDATE services SET
            name=?, description=?, protocol=?, base_url=?, health_endpoint=?, method=?,
            auth_type=?, auth_config=?, headers=?, body_template=?, request_body=?, query_params=?,
            success_condition=?, token_endpoint=?, token_config=?, access_token=?, token_expires_at=?,
            timeout=?, check_interval=?, is_active=?
            WHERE id=?
        ");
        if(!$stmt) die("Prepare Hatası: ".$dba->error());

        $bind = $stmt->bind_param(
            "ssssssssssssssssiiiii",
            $name, $description, $protocol, $base_url, $health_endpoint, $method,
            $auth_type, $auth_config, $headers, $body_template, $request_body, $query_params,
            $success_condition, $token_endpoint, $token_config, $access_token, $token_expires_at,
            $timeout, $check_interval, $is_active, $id
        );
        if(!$bind) die("Bind Hatası: ".$stmt->error);

        $msg = "Servis güncellendi";
    }

    if(!$stmt->execute()){
        die("Execute Hatası: ".$stmt->error);
    }

    echo json_encode([
        "ok" => true,
        "mesaj" => $msg
    ]);

} catch(Exception $e) {
    echo json_encode([
        "ok" => false,
        "mesaj" => "Hata: " . $e->getMessage()
    ]);
}
?>