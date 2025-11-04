<?php
class Db {
    private $link;
    public function __construct($host, $username, $password, $database) {
        $this->connect($host, $username, $password, $database);
    }
    private function connect($host, $username, $password, $database) {
        try {
            mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT); // Hataları istisna (Exception) olarak ele al
            $this->link = new mysqli($host, $username, $password, $database);

            $this->link->set_charset("utf8"); // Karakter setini ayarla
        } catch (mysqli_sql_exception $e) {
            // Hata mesajını logla ama kullanıcıya göstermeden
            error_log("MySQL bağlantı hatası: " . $e->getMessage());

            // Kullanıcıya sade bir mesaj döndür
            http_response_code(500); // İsteğin başarısız olduğunu belirtir
            echo json_encode(['error' => 'Veritabanı bağlantısı kurulamadı. Lütfen sistem yöneticinize başvurun.'], JSON_UNESCAPED_UNICODE);
            exit;
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
}

// Örnek kullanım:
$dba = new Db("10.2.207.61", "root", "kobil2013", "cankaya");
?>