<?php
class ZKLibrary {
    private $ip;
    private $port;
    private $socket;

    public function __construct($ip, $port = 4370) {
        $this->ip = $ip;
        $this->port = $port;
    }

    public function connect() {
        $this->socket = @fsockopen($this->ip, $this->port, $errno, $errstr, 3);
        return (bool)$this->socket;
    }

    public function disconnect() {
        if ($this->socket) fclose($this->socket);
    }

    // Cihazdan logları çek
    public function getAttendance() {
        $command = "\x50\x00\x00\x00\x00\x00\x00\x00"; // attendance request
        fwrite($this->socket, $command);
        $data = fread($this->socket, 4096);
        return $this->parseAttendance($data);
    }

    private function parseAttendance($data) {
        $records = [];
        // Bu sadece temel örnektir. Gerçek cihazda binary data çözümlenir.
        // Eğer JSON veya text modda veri dönüyorsa:
        if (strpos($data, "\n") !== false) {
            foreach (explode("\n", trim($data)) as $line) {
                if ($line !== '') $records[] = trim($line);
            }
        } else {
            $records[] = bin2hex($data);
        }
        return $records;
    }
}
?>