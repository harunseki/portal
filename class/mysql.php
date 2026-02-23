<?php
class Db {
    private $link;
    public function __construct($host, $username, $password, $database) {
        $this->connect($host, $username, $password, $database);
    }
    private function connect($host, $username, $password, $database) {
        $this->link = new mysqli($host, $username, $password, $database);

        if ($this->link->connect_error) {
            error_log("MySQL bağlantı hatası: " . $this->link->connect_error);
            die("Veritabanına bağlanılamadı. Lütfen sistem yöneticisine başvurun.");
        }

        if (!$this->link->set_charset("utf8")) {
            error_log("UTF8 karakter seti yüklenemedi: " . $this->link->error);
            die("Karakter seti ayarlanamadı.");
        }
    }
    public function uygulama_bilgisi($uygulama_id) {
        $uygulama_id = $this->escape($uygulama_id);
        $sql = "SELECT adi FROM login_log.uygulamalar WHERE id='$uygulama_id'";
        $res = $this->query($sql);
        return $res ? $this->fetch_array($res) : $this->error();
    }
    public function login_error($tc, $ip, $uygulama_id, $password) {
        $tc = $this->escape($tc);
        $ip = $this->escape($ip);
        $uygulama_id = $this->escape($uygulama_id);
        $password = $this->escape($password);

        $sql = "INSERT INTO login_log.login_error (tc, ip, uygulama_id, password) VALUES ('$tc', '$ip', '$uygulama_id', '$password')";
        $this->query($sql);
    }
    public function login_log($yetkili_id, $yetkili_tc, $yetkili_adi, $uygulama_id, $ip) {
        $yetkili_id = $this->escape($yetkili_id);
        $yetkili_tc = $this->escape($yetkili_tc);
        $yetkili_adi = $this->escape($yetkili_adi);
        $uygulama_id = $this->escape($uygulama_id);
        $ip = $this->escape($ip);

        $sql = "INSERT INTO login_log.login (yetkili_id, yetkili_tc, yetkili_adi, uygulama_id, ip) VALUES ('$yetkili_id', '$yetkili_tc', '$yetkili_adi', '$uygulama_id', '$ip')";
        $this->query($sql);
    }
    public function query($sql) {
        $result = $this->link->query($sql);
        if (!$result) {
            error_log("SQL hatası: " . $this->link->error . " | Sorgu: $sql");
        }
        return $result;
    }
    public function multi_query($sql) {
        $result = $this->link->multi_query($sql);
        if (!$result) {
            error_log("SQL çoklu sorgu hatası: " . $this->link->error . " | Sorgu: $sql");
        }
        return $result;
    }
    public function escape($string) {
        return $this->link->real_escape_string($string);
    }
    public function error() {
        return $this->link->error;
    }
    public function num_fields($result) {
        return $result->field_count;
    }
    public function fetch_field($result) {
        return $result->fetch_field();
    }
    public function fetch_row($result) {
        return $result->fetch_row();
    }
    public function num_rows($result) {
        return $result->num_rows;
    }
    public function affected_rows() {
        return $this->link->affected_rows;
    }
    public function free_result($result) {
        return $result->free();
    }
    public function fetch_array($result) {
        return $result->fetch_array(MYSQLI_ASSOC);
    }
    public function insert_id() {
        return $this->link->insert_id;
    }
    public function fetch_assoc($result) {
        return $result->fetch_assoc();
    }
    // Güvenli sorgu çalıştırma ve sonucu döndürme
    function safe_query($sql) {
        $result = mysqli_query($this->link, $sql);

        if ($result === false) {
            // Burada istersen log dosyasına yazabilirsin
            error_log("MYSQL ERROR: " . mysqli_error($this->link));
            die("Veritabanı hatası: " . mysqli_error($this->link));
        }

        return $result;
    }
    // fetch_assoc için güvenli sarmalayıcı
    function safe_fetch_assoc($result) {
        if ($result instanceof mysqli_result) {
            $row = mysqli_fetch_assoc($result);
            if ($row === null) {
                // Sorgudan veri gelmemiş olabilir
                die("Kayıt bulunamadı.");
            }
            return $row;
        } else {
            die("Geçersiz sorgu sonucu: fetch_assoc() başarısız.");
        }
    }

    public function close() {
        return $this->link->close();
    }

    public function prepare($sql) {
        $stmt = $this->link->prepare($sql);
        if (!$stmt) {
            error_log("Prepare hatası: " . $this->link->error . " | Sorgu: $sql");
            die("Veritabanı hatası: Prepare işlemi başarısız.");
        }
        return $stmt;
    }

    /**
     * Hazırlanmış sorguyu parametre bağlayarak çalıştırır
     */
    public function execute($stmt, $types = "", $params = []) {
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        if (!$stmt->execute()) {
            error_log("Execute hatası: " . $stmt->error);
            die("Veritabanı hatası: Execute işlemi başarısız.");
        }
        return $stmt;
    }
    public function fetch_all($stmt) {
        $result = $stmt->get_result();
        if ($result) {
            return $result->fetch_all(MYSQLI_ASSOC);
        }
        return [];
    }
    public function addLog($ip, $userAgent, $userId, $action, $details = null) {
        $table = "user_logs";
        if ($userAgent!='harunseki') {
            $stmt = $this->link->prepare("INSERT INTO $table (sicil_no, action, details, ip_address, ldap_username) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("issss", $userId, $action, $details, $ip, $userAgent);
            $stmt->execute();
            $stmt->close();
        }
    }
    // mysqli standardına birebir
    public function begin_transaction()
    {
        return $this->link->begin_transaction();
    }
    public function commit()
    {
        return $this->link->commit();
    }
    public function rollback()
    {
        return $this->link->rollback();
    }
}

// Örnek kullanım:
$dba = new Db("localhost", "portal_hs", "hlVO2U2JrI.oB97Y", "portal");
?>