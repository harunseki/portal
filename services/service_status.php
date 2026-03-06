<?php
require "../class/mysql.php";
require_once __DIR__ . '/autoload.php'; // doğru path ver

$serviceId = (int)($_GET['id'] ?? 0);
$refresh = isset($_GET['refresh']) && $_GET['refresh'] == 1;
if (!$serviceId) {
    http_response_code(400);
    echo json_encode(['status'=>false,'error'=>'Missing service ID']);
    exit;
}

$service = $dba->query("SELECT * FROM services WHERE id=$serviceId")->fetch_assoc();
if (!$service) {
    http_response_code(404);
    echo json_encode(['status'=>false,'error'=>'Service not found']);
    exit;
}

$manager = new ServiceManager($dba);
try {
    $result = $manager->checkSingleService($service, $refresh);
} catch (Throwable $e) {
    $result = ['status'=>false,'response_time'=>0,'error'=>$e->getMessage()];
}

$updatedService = $dba->query("SELECT id, last_checked, last_status, last_response_time FROM services WHERE id=$serviceId")->fetch_assoc();

header('Content-Type: application/json');

echo json_encode([
    'id' => $updatedService['id'],
    'status' => (bool)$updatedService['last_status'],
    'response_time' => (int)$updatedService['last_response_time'],
    'last_checked' => $updatedService['last_checked'],
    'skipped' => $result['skipped'] ?? false
]);