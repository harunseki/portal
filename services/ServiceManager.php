<?php
require_once __DIR__ . '/ServiceDriverInterface.php';
require_once __DIR__ . '/ServiceDriverFactory.php';
require_once __DIR__ . '/HttpDriver.php';
require_once __DIR__ . '/SoapDriver.php';
require_once __DIR__ . '/TokenManager.php';
require_once __DIR__ . '/autoload.php';

class ServiceManager
{
    private Db $db;
    private string $logFile;
    public function __construct(Db $db)
    {
        $this->db = $db;
        $this->logFile = __DIR__ . '/service_debug.log';
    }
    private function log(string $msg): void
    {
        file_put_contents(
            $this->logFile,
            date('Y-m-d H:i:s') . ' - ' . $msg . PHP_EOL,
            FILE_APPEND
        );
    }
    public function checkServices(): void
    {
        $this->log("=== ServiceManager START ===");

        $res = $this->db->query("SELECT * FROM services WHERE is_active=1");

        if (!$res) {
            $this->log("DB query failed");
            return;
        }

        while ($service = $this->db->fetch_assoc($res)) {
            $serviceId = (int)$service['id'];
            $serviceName = $service['name'] ?? ('ID:' . $serviceId);

            $this->log("Checking service: $serviceName");

            if (!$this->shouldCheck($service)) {
                $this->log("Skipped (interval not reached): $serviceName");
                continue;
            }

            // Lock al
            $lockRes = $this->db->query("SELECT GET_LOCK('service_$serviceId', 2) AS l");
            $lockRow = $this->db->fetch_assoc($lockRes);

            if (!$lockRow || !$lockRow['l']) {
                $this->log("Lock failed: $serviceName");
                continue;
            }

            try {
                $this->db->begin_transaction();

                // Token kontrol
                TokenManager::ensureValidToken($this->db, $service);
                $this->log("Token OK: $serviceName");

                // Driver seç
                $driver = ServiceDriverFactory::make($service);
                $this->log("Driver selected: " . get_class($driver));

                // Check yap
                $result = $driver->check($service);

                $this->log("Result: " . json_encode($result));

                $this->updateService($serviceId, $result);
                $this->insertLog($serviceId, $result);

                $this->db->commit();

                $this->log("Committed: $serviceName");

            }
            catch (Throwable $e)
            {
                $this->db->rollback();

                $this->log("ERROR in $serviceName: " . $e->getMessage());

                $errorResult = [
                    'status' => false,
                    'response_time' => 0,
                    'error' => $e->getMessage()
                ];

                $this->updateService($serviceId, $errorResult);
                $this->insertLog($serviceId, $errorResult);
            }

            // Lock bırak
            $this->db->query("SELECT RELEASE_LOCK('service_$serviceId')");
        }

        $this->log("=== ServiceManager END ===");
    }
    public function checkSingleService(array $service, bool $force = false): array
    {
        $serviceId = (int)$service['id'];
        $serviceName = $service['name'] ?? ('ID:' . $serviceId);

        // Interval kontrolü
        if (!$force && !$this->shouldCheck($service)) {
            $this->log("Skipped (interval not reached): $serviceName");
            return [
                'status' => $service['last_status'] ?? false,
                'response_time' => $service['last_response_time'] ?? 0,
                'last_checked' => $service['last_checked'] ?? null,
                'skipped' => true
            ];
        }

        try {
            $this->db->begin_transaction();
            TokenManager::ensureValidToken($this->db, $service);
            $driver = ServiceDriverFactory::make($service);
            $result = $driver->check($service);

            // Sadece check yapılmışsa DB’ye kaydet
            $this->updateService($serviceId, $result, true);
            $this->insertLog($serviceId, $result);

            $this->db->commit();

            return $result;

        } catch (Throwable $e) {
            $this->db->rollback();

            $errorResult = [
                'status' => false,
                'response_time' => 0,
                'error' => $e->getMessage()
            ];

            $this->updateService($serviceId, $errorResult, true);
            $this->insertLog($serviceId, $errorResult);

            return $errorResult;
        }
    }
    private function shouldCheck(array $service): bool
    {
        if (empty($service['last_checked']))
            return true;

        return (time() - strtotime($service['last_checked']))
            > ($service['check_interval'] ?? 300);
    }
    private function updateService(int $id, array $result, bool $updateChecked = true): void
    {
        $sql = "
            UPDATE services SET
                last_status=?,
                last_response_time=?,
                last_error=?,
                fail_count = IF(?=0, fail_count+1, 0)
        ";
        if ($updateChecked) {
            $sql .= ", last_checked=NOW()";
        }
        $sql .= " WHERE id=?";

        $stmt = $this->db->prepare($sql);

        $status = $result['status'] ? 1 : 0;
        $responseTime = (int)$result['response_time'];
        $error = $result['error'] ?? null;

        $this->db->execute(
            $stmt,
            "iisii",
            [$status, $responseTime, $error, $status, $id]
        );

        $stmt->close();
    }
    private function insertLog(int $id, array $result): void
    {
        $stmt = $this->db->prepare("
            INSERT INTO service_logs
            (service_id,status,response_time,error,created_at)
            VALUES (?,?,?,?,NOW())
        ");

        $status = $result['status'] ? 1 : 0;
        $responseTime = (int)$result['response_time'];
        $error = $result['error'] ?? null;

        $this->db->execute(
            $stmt,
            "iiis",
            [$id, $status, $responseTime, $error]
        );

        $stmt->close();
    }
}