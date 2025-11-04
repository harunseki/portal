<?php
/*error_reporting(E_ALL);
ini_set("display_errors", 1);*/
class login_kontrol
{
    private $link;
    private $purifier;
    
    public function __construct($host, $username, $password, $database)
    {
        $this->connect($host, $username, $password, $database);
        $this->initPurifier();
    }
    private function connect($host, $username, $password, $database)
    {
        $this->link = mysqli_connect($host, $username, $password, $database);

        if (!$this->link) {
            exit('Connect failed: ' . mysqli_connect_error());
        }

        if (!mysqli_set_charset($this->link, 'utf8')) {
            exit('Charset error: ' . mysqli_error($this->link));
        }
    }
    private function initPurifier()
    {
        require_once($_SERVER['DOCUMENT_ROOT'] . "/htmlpurifier/library/HTMLPurifier.auto.php");
        $config = HTMLPurifier_Config::createDefault();
        $config->set('Core.Encoding', 'UTF-8'); // replace with your encoding
        $config->set('HTML.Doctype', 'HTML 4.01 Transitional'); // replace with your doctype
        $config->set('HTML.Doctype', 'HTML 4.01 Transitional'); // replace with your doctype
        $this->purifier = new HTMLPurifier($config);
    }

    public function query($sql)
    {
        return mysqli_query($this->link, $sql);
    }

    public function error()
    {
        return mysqli_error($this->link);
    }

    public function num_rows($result)
    {
        return mysqli_num_rows($result);
    }

    public function fetch_assoc($result)
    {
        return mysqli_fetch_assoc($result);
    }

    public function close()
    {
        return mysqli_close($this->link);
    }

    public function isValidUserName($value)
    {
        return preg_match('/[a-z]+$/', $value);
    }

    public function isValidTC($value)
    {
        return preg_match('/^\d{11}$/', $value);
    }

    public function isValidPassword($value)
    {
        return preg_match('/^(?=.*[A-Za-z_ğüşıöçĞÜŞİÖÇ])(?=.*\d)(?=.*[@$!%*#?&+.\-_,()=])[A-Za-z_ğüşıöçĞÜŞİÖÇ\d@$!%*#?&+.\-_,()=]{8,}$/', $value);
    }
    public function ldapQuote($str)
    {
        return str_replace(['\\', ' ', '*', '(', ')'], ['\\5c', '\\20', '\\2a', '\\28', '\\29'], $str);
    }

    public function rescape($var)
    {
        $var = str_ireplace(["SLEEP", "BENCHMARK"], "", $var);
        $clean = $this->purifier->purify($var);
        return mysqli_real_escape_string($this->link, $clean);
    }
    public function loginError($tc, $ip, $uygulama_id, $username, $kaynak)
    {
        // Parametreleri toplu şekilde güvenli hale getir
        $params = array_map([$this, 'rescape'], [
            'tc' => $tc,
            'username' => $username,
            'ip' => $ip,
            'uygulama_id' => $uygulama_id,
            'kaynak' => $kaynak
        ]);

        // SQL sorgusunu oluştur
        $sql = sprintf(
            "INSERT INTO login_log.login_error (tc, username, ip, uygulama_id, kaynak) VALUES ('%s', '%s', '%s', '%s', '%s')",
            $params['tc'],
            $params['username'],
            $params['ip'],
            $params['uygulama_id'],
            $params['kaynak']
        );

        $this->query($sql);
    }

    function loginlog($yetkili_id, $yetkili_tc, $yetkili_adi, $uygulama_id, $ip, $yetkili_username, $kaynak)
    {
        // Tüm verileri tek seferde filtrele
        $params = array_map([$this, 'rescape'], [
            'yetkili_id' => $yetkili_id,
            'yetkili_tc' => $yetkili_tc,
            'yetkili_adi' => $yetkili_adi,
            'uygulama_id' => $uygulama_id,
            'ip' => $ip,
            'yetkili_username' => $yetkili_username,
            'kaynak' => $kaynak
        ]);

        // Hazırlanmış veri dizisiyle SQL’i oluştur
        $sql = sprintf(
            "INSERT INTO login_log.login (yetkili_id, yetkili_tc, yetkili_username, yetkili_adi, uygulama_id, ip, kaynak) VALUES ('%s', '%s', '%s', '%s', '%s', '%s', '%s')",
            $params['yetkili_id'],
            $params['yetkili_tc'],
            $params['yetkili_username'],
            $params['yetkili_adi'],
            $params['uygulama_id'],
            $params['ip'],
            $params['kaynak']
        );

        return $this->query($sql);
    }

    function erisim_kontrol($uygulama_id, $yetki_turu, $tc, $username)
    {
        $while="";
        $tc = rescape($tc);
        //if(!empty($tc)) $while=" AND tc=".$tc;
        $uygulama_id = rescape($uygulama_id);
        $yetki_turu = rescape($yetki_turu);

        $sql="SELECT
                erisim_istisna.id,
                erisim_istisna.tc,
                erisim_istisna.adi,
                erisim_istisna.soyadi,
                erisim_istisna.kayit_tarihi,
                erisim_istisna.durum
                FROM
                login_log.erisim_istisna
                WHERE erisim_istisna.username='$username' AND durum=1";
        $q_erisim_istisna = $this->query($sql);

        $row_erisim_istisna = $this->fetch_assoc($q_erisim_istisna);
        //print_r($row_erisim_istisna);
        if (empty($row_erisim_istisna['tc'])) {
            if ($yetki_turu == 9) {
                $sql = "SELECT
                        personel_uygulama_yetkisi.uygulama_id,
                        personel_uygulama_yetkisi.uygulama_adi,
                        personel_yetkileri.erisim,
                        personel.tc,
                        personel.username,
                        personel_yetkileri.talep_tarihi,
                        personel_yetkileri.p_yetki_turu_id
                        FROM
                        personel
                        INNER JOIN personel_yetkileri ON personel.id = personel_yetkileri.personel_id
                        INNER JOIN personel_uygulama_yetkisi ON personel_yetkileri.id = personel_uygulama_yetkisi.personel_yetkileri_id
                        WHERE personel_uygulama_yetkisi.uygulama_id=$uygulama_id and personel.username='$username' AND personel_yetkileri.p_yetki_turu_id=$yetki_turu
                        AND personel_yetkileri.sil!=1
                        ORDER BY talep_tarihi DESC
                        LIMIT 1";
            } else {
                $sql = "SELECT
                        personel_yetkileri.erisim,
                        personel.tc,
                        personel.username,
                        personel_yetkileri.talep_tarihi,
                        personel_yetkileri.p_yetki_turu_id
                        FROM
                        personel
                        INNER JOIN personel_yetkileri ON personel.id = personel_yetkileri.personel_id
                        WHERE  personel.username='$username' AND personel_yetkileri.p_yetki_turu_id=$yetki_turu
                        AND personel_yetkileri.sil!=1
                        ORDER BY talep_tarihi DESC
                        LIMIT 1";
            }
            $res = $this->query($sql);
            $row = $this->fetch_assoc($res);

            if ($row['username'] == $username && $row['p_yetki_turu_id'] == $yetki_turu && $row['erisim'] == 1) {
                $sql2 = "SELECT
                    personel.tc
                    FROM
                    personel_takip.personel
                    INNER JOIN personel_takip.personel_son_calisma_yeri ON personel.id = personel_son_calisma_yeri.personel_id
                    WHERE
                    personel.personel_durumu = 0
                    AND personel.sil = 0
                    AND (personel_son_calisma_yeri.isten_cikis_tarihi IS NULL OR personel_son_calisma_yeri.isten_cikis_tarihi = '0000-00-00')
                    AND personel.tc='$tc'
                    UNION ALL
                    SELECT
                     kadrolu_personel.tcno
                    FROM
                     personel_takip.kadrolu_personel
                    WHERE
                     kadrolu_personel.ne != 'T'
                    AND kadrolu_personel.ne != 'J'
                    AND kadrolu_personel.ne != 'S'
                    AND kadrolu_personel.ne != 'B'
                    AND kadrolu_personel.ne != 'H'
                    AND kadrolu_personel.tcno='$tc'";
                $res2 = $this->query($sql2);
                $row2 = $this->fetch_assoc($res2);

                if ($row2['tc'] == $tc) {
                    return 1; //AKTİF PERSONEL
                } else {
                    return 0; //PASİF PERSONEL
                }
            } else {
                return 2; //YETKİ FORMU BULUNMAMAKTADIR
            }
        } else {
            if ($row_erisim_istisna['durum'] == 1) return 1;
            elseif ($row_erisim_istisna['durum'] == 2) return 0;
            elseif ($row_erisim_istisna['durum'] == 0) return 0;
        }
    }

    function getldapusername($tcno, $username, $password)
    {
        if (!empty($tcno) and $this->isValidTC($tcno) != 1) {
            return $array = array("hata" => 1, "hata_aciklama" => "TCNo Geçersiz");
        } else if (!empty($username) and $this->isValidUserName($username) != 1) {
            return $array = array("hata" => 1, "hata_aciklama" => "Kullanıcı Adı Geçersiz");
        } else {
            // LDAP sunucusu adresi
            $ldap_server = "ldap://10.1.1.21";
            $ldap_port = 389;

            $user_dn = "CN=Harun Seki,OU=Yazilim Destek Burosu,OU=Kullanicilar,OU=Bilgi Islem,OU=Cankaya Belediyesi,DC=cankaya,DC=bel,DC=tr";
            $ad_dn = "DC=cankaya,DC=bel,DC=tr";

            $ldap_conn = ldap_connect($ldap_server, $ldap_port);
            if (!$ldap_conn) die("LDAP sunucusuna bağlanılamadı!");

            ldap_set_option($ldap_conn, LDAP_OPT_PROTOCOL_VERSION, 3);
            ldap_set_option($ldap_conn, LDAP_OPT_REFERRALS, 0);

            $password = html_entity_decode($password);
            $bind = @ldap_bind($ldap_conn, $username . "@cankaya.bel.tr", $password);

            $samaccountname = "";
            if ($bind) {
                if (!empty($tcno)) {
                    $filter = "(tckimlik=" . $tcno . ")";
                    $result = ldap_search($ldap_conn, $ad_dn, $filter) or exit("Unable to search LDAP server");
                    $entry = ldap_first_entry($ldap_conn, $result);
                    while ($entry) {
                        $samaccountname = ldap_get_values($ldap_conn, $entry, "samaccountname")[0];
                        $useraccountcontrol = ldap_get_values($ldap_conn, $entry, "useraccountcontrol")[0];
                        $entry = null;
                    }
                    if (!empty($samaccountname) && ($useraccountcontrol == 512 || $useraccountcontrol == 66048)) {
                        ldap_unbind($ldap_conn);
                        return $array = array("hata" => 0, "username" => $samaccountname);
                    } else {
                        ldap_unbind($ldap_conn);
                        return $array = array("hata" => 1, "hata_aciklama" => "Kullanıcı Adı veya Şifreniz yanlış" . $useraccountcontrol);
                        ldap_close($ldap_conn);
                    }
                }
                else if (!empty($username)) {
                    $filter = "(sAMAccountName=" . $username . ")";
                    $result = ldap_search($ldap_conn, $ad_dn, $filter) or exit("Unable to search LDAP server");
                    $entry = ldap_first_entry($ldap_conn, $result);
                    while ($entry) {
                        //$ldap_tc = ldap_get_values($ldap_conn, $entry, "tckimlik")[0];
                        $samaccountname = ldap_get_values($ldap_conn, $entry, "samaccountname")[0];
                        $useraccountcontrol = ldap_get_values($ldap_conn, $entry, "useraccountcontrol")[0];
                        $entry = null;
                    }
                    if (!empty($samaccountname) && ($useraccountcontrol == 512 || $useraccountcontrol == 66048)) {
                        ldap_unbind($ldap_conn);
                        return $array = array("hata" => 0, "username" => $samaccountname);
                    } else {
                        ldap_unbind($ldap_conn);
                        return $array = array("hata" => 1, "hata_aciklama" => "Kullanıcı Adı veya Şifreniz yanlış" . $useraccountcontrol);
                        ldap_close($ldap_conn);
                    }
                }
            }
            else {
                ldap_unbind($ldap_conn);
                return $array = array("hata" => 1, "hata_aciklama" => "Kullanıcı Adı veya Şifreniz yanlış");
                ldap_close($ldap_conn);
            }
        }

    }

    function loginldap($username, $password)
    {
        //$tcno=rescape($tcno);
        $password = html_entity_decode($password);


        if ($this->isValidUserName($username) != 1) {
            return $array = array("hata" => 1, "hata_aciklama" => "Kullanıcı Adı veya Şifre Hatalı");
        } /*else if ($this->isValidPassword($password) != 1) {
            return $array = array("hata" => 1, "hata_aciklama" => "Kurum şifre politikasına uymamaktadır. Şifreniz en az 8 karakter, harf, rakam ve özel karakter içermesi gerekmektedir. Lütfen bilgisayar şifrenizi değiştirin. Şifre ile ilgili bilgi almak için 1403 arayabilirsiniz");
        }*/ else {
            $ldap_server = "ldap://10.1.1.21";
            $ldap_port = 389;

            $ldap_conn = ldap_connect($ldap_server, $ldap_port);
            if (!$ldap_conn) die("LDAP sunucusuna bağlanılamadı!");

            ldap_set_option($ldap_conn, LDAP_OPT_PROTOCOL_VERSION, 3);
            ldap_set_option($ldap_conn, LDAP_OPT_REFERRALS, 0);

            $ldap = @ldap_bind($ldap_conn, $username . "@cankaya.bel.tr", $password);

            if ($ldap) {
                $bind = @ldap_bind($ldap_conn, $username . "@cankaya.bel.tr", $password);

                if ($bind)
                    return array("success" => 1);
                else
                    return array("hata" => 1, "hata_aciklama" => "Kullanıcı Adı veya Şifre Hatalı");
            }
        }
    }

    function yetkiliuygulamalari($tc)
    {
        $tc = rescape($tc);
        $sql_uygulamalar="SELECT
                        uygulamalar.id,
                        uygulamalar.adi,
                        uygulamalar.ldap_method,
                        uygulamalar.db_name,
                        uygulamalar.table_name,
                        uygulamalar.kolon_tc,
                        uygulamalar.kolon_username,
                        uygulamalar.kolon_adi,
                        uygulamalar.kolon_soyadi,
                        uygulamalar.kolon_yetkili_durumu
                        FROM 
                        login_log.uygulamalar
                        WHERE db_name is not null and db_name!='' and aktif=1  order by adi";
        $q_uygulamalar = $this->query($sql_uygulamalar);
        /*print_r($q_uygulamalar);
        exit();*/
        $sql_kullanici_kontrol = "";
        while ($row_uygulamalar = $this->fetch_assoc($q_uygulamalar)) {
            if (!empty($row_uygulamalar['kolon_tc']) and !empty($row_uygulamalar['kolon_adi']) and !empty($row_uygulamalar['kolon_soyadi']) and !empty($row_uygulamalar['kolon_tc']) and !empty($row_uygulamalar['kolon_yetkili_durumu']) and !empty($row_uygulamalar['adi']) and !empty($row_uygulamalar['id']) and !empty($row_uygulamalar['ldap_method']) and !empty($row_uygulamalar['db_name']) and !empty($row_uygulamalar['table_name'])) {
                $sql_kullanici_kontrol .= "SELECT " . $row_uygulamalar['kolon_tc'] . ", " . $row_uygulamalar['kolon_username'] . ", " . $row_uygulamalar['kolon_adi'] . ", " . $row_uygulamalar['kolon_soyadi'] . ", " . $row_uygulamalar['kolon_yetkili_durumu'] . " as yetkili_durumu, '" . $row_uygulamalar['adi'] . "' AS program, " . $row_uygulamalar['id'] . " AS uygulama_id, '" . $row_uygulamalar['ldap_method'] . "' as ldap_method  FROM " . $row_uygulamalar['db_name'] . "." . $row_uygulamalar['table_name'] . " UNION ALL ";
            }
        }
        $sql_kullanici_kontrol = rtrim($sql_kullanici_kontrol, "UNION ALL");
        $sql_kullanici_kontrol = "SELECT * FROM ( " . $sql_kullanici_kontrol . " ) as uygulama_yetkileri WHERE username='$tc' and yetkili_durumu=1 order by program asc";
        $qqqqq = $this->query("$sql_kullanici_kontrol");
        //$uygulama_arr[] = $sql_kullanici_kontrol;
        /*print_r($this->num_rows($qqqqq));
        exit();*/
        $uygulama_arr=[];
        while ($row = $this->fetch_assoc($qqqqq)) {
            $uygulama_arr[] = [
                "uygulama_id" => $row['uygulama_id'],
                "method" => $row['ldap_method'],
                "uygulama" => $row['program'],
                "sql" => $sql_kullanici_kontrol
            ];
        }
        return $uygulama_arr;
    }

    function bim_takip_login($tcno, $ldapusername, $uygulama_id, $uygulama_adi, $kaynak)
    {
        $tcno = $ldapusername;
        $ldapusername = rescape($ldapusername);
        $uygulama_id = rescape($uygulama_id);
        $uygulama_adi = rescape($uygulama_adi);
        $kaynak = rescape($kaynak);

        if (empty($kaynak)) {
            return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => "Parametre Eksik1", "erisim_durumu" => "");
        } else if ($kaynak == "ldap" and (empty($tcno) or empty($ldapusername) or empty($uygulama_id) or empty($uygulama_adi))) {
            return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => "Parametre Eksik2", "erisim_durumu" => "");
        } else if ($kaynak == "edevlet" and (empty($tcno) or empty($uygulama_id) or empty($uygulama_adi))) {
            return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => "Parametre Eksik3", "erisim_durumu" => "");
        } else {
            $q1 = $this->query("SELECT * FROM bim_takip.yetkili WHERE username='$ldapusername' and yetkili_durumu='1' ");
            $rowc1 = $this->num_rows($q1);

            if ($rowc1 < 1) {
                $this->loginerror($tcno, GetIP(), $uygulama_id, $ldapusername, $kaynak);
                return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => $uygulama_adi . " Yetkisi Bulunamadı");
            } else {
                $row = $this->fetch_assoc($q1);
                ////$erisim_durumu = $this->erisim_kontrol($uygulama_id, 9, $tcno, $ldapusername);
                $erisim_durumu = 1;
                $erisim_durumu = 1;

                if ($erisim_durumu == 1) {
                    $_SESSION['bim_takip_admin_id'] = (int)$row['id'];
                    $_SESSION['bim_takip_admin_tc'] = $row['tc'];
                    $_SESSION['bim_takip_admin_name'] = $row['adi'] . ' ' . $row['soyadi'];

                    $_SESSION['bim_takip_personel_yetki_islemleri'] = $row['personel_yetki_islemleri'];
                    $_SESSION['bim_takip_personel_yetki_ekle'] = $row['personel_yetki_ekle'];
                    $_SESSION['bim_takip_personel_yetki_duzenle'] = $row['personel_yetki_duzenle'];
                    $_SESSION['bim_takip_personel_yetki_sil'] = $row['personel_yetki_sil'];

                    $_SESSION['bim_takip_yetkili_islemleri'] = $row['yetkili_islemleri'];
                    $_SESSION['bim_takip_uygulama_kullanicilari'] = $row['uygulama_kullanicilari'];
                    $_SESSION['bim_takip_uygulama_ldap_islemler'] = $row['ldap_islemler'];
                    $_SESSION['bim_takip_uygulama_log_islemleri'] = $row['login_log'];
                    $_SESSION['bim_takip_ayarlar'] = $row['ayarlar'];
                    $_SESSION['bim_takip_kamera_takip'] = $row['kamera_takip'];
                    $_SESSION['bim_takip_internet_abonelik'] = $row['internet_abonelik'];

                    $_SESSION['bim_takip_is_takip'] = $row['is_takip'];
                    $_SESSION['is_takip_gorev_ekle'] = $row['is_takip_gorev_ekle'];
                    $_SESSION['is_takip_gorev_duzenle'] = $row['is_takip_gorev_duzenle'];
                    $_SESSION['is_takip_birim_ekle'] = $row['is_takip_birim_ekle'];
                    $_SESSION['is_takip_birim_duzenle'] = $row['is_takip_birim_duzenle'];
                    $_SESSION['bim_takip_yetki_turu'] = $row['is_takip_yetki_turu'];
                    $_SESSION['bim_takip_yetki_mud_id'] = $row['is_takip_mud_id'];
                    $_SESSION['bim_takip_yetki_birim_id'] = $row['is_takip_birim_id'];
                    $_SESSION['bim_takip_yetki_alt_birim_id'] = $row['is_takip_alt_birim_id'];

                    $_SESSION['bim_takip_envanter_takip'] = $row['envanter_takip'];
                    $_SESSION['bim_takip_envanter_ekle'] = $row['envanter_ekle'];
                    $_SESSION['bim_takip_envanterler'] = $row['envanterler'];
                    $_SESSION['bim_takip_envanter_takip_soru_ekle'] = $row['envanter_takip_soru_ekle'];
                    $_SESSION['bim_takip_envanter_takip_sorular'] = $row['envanter_takip_sorular'];
                    $_SESSION['bim_takip_envanter_takip_envanter_takip_soru_sil'] = $row['envanter_takip_soru_sil'];
                    $_SESSION['bim_takip_envanter_takip_birim_ekle'] = $row['envanter_takip_birim_ekle'];
                    $_SESSION['bim_takip_envanter_takip_birimler'] = $row['envanter_takip_birimler'];

                    $_SESSION['bim_takip_envanter_takip_kontol_sebebi_ekle'] = $row['envanter_takip_kontol_sebebi_ekle'];
                    $_SESSION['bim_takip_envanter_takip_kontol_sebepleri'] = $row['envanter_takip_kontol_sebepleri'];
                    $_SESSION['bim_takip_envanter_temel_bilgileri_guncelle'] = $row['envanter_temel_bilgileri_guncelle'];

                    $_SESSION['bim_takip_fazla_mesai'] = $row['fazla_mesai'];
                    $_SESSION['bim_takip_mesai_atama'] = $row['mesai_atama'];
                    $_SESSION['bim_takip_mesai_girisi'] = $row['mesai_girisi'];
                    $_SESSION['bim_takip_mesai_istekleri_onaylama'] = $row['mesai_istekleri_onaylama'];
                    $_SESSION['bim_takip_onayli_mesai_istekleri'] = $row['onayli_mesai_istekleri'];
                    $_SESSION['bim_takip_mesai_isteklerim'] = $row['mesai_isteklerim'];
                    $_SESSION['bim_takip_yetki_durumu'] = $row['yetki_durumu'];

                    $_SESSION['bim_takip_sunucu_bilgileri'] = $row['sunucu_bilgileri'];

                    $this->loginlog((int)$row['id'], $row['tc'], $row['adi'] . ' ' . $row['soyadi'], $uygulama_id, GetIP(), $ldapusername, $kaynak);
                    // header('Location: index.php');
                    return $array = array("hata" => 0, "success" => 1, "uygulama_id" => $uygulama_id, "erisim_durumu" => $erisim_durumu);

                } else if ($erisim_durumu == 2) {
                    $this->loginerror($tcno, GetIP(), $uygulama_id, $ldapusername, $kaynak);
                    return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => $uygulama_adi . " Yetki Formu Bulunmamaktadır", "erisim_durumu" => $erisim_durumu);

                } else if ($erisim_durumu == 0) {
                    $this->loginerror($tcno, GetIP(), $uygulama_id, $ldapusername, $kaynak);
                    return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => $uygulama_adi . " Yetkisi Bulunamadı", "erisim_durumu" => $erisim_durumu);
                }
            }
        }
    }
    function kurs_merkezi_login($tcno, $ldapusername, $uygulama_id, $uygulama_adi, $kaynak)
    {
        $tcno = rescape($tcno);
        $ldapusername = rescape($ldapusername);
        $uygulama_id = rescape($uygulama_id);
        $uygulama_adi = rescape($uygulama_adi);
        $kaynak = rescape($kaynak);
        if (empty($kaynak)) {
            return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => "Parametre Eksik", "erisim_durumu" => "");
        } else if ($kaynak == "ldap" and (empty($tcno) or empty($ldapusername) or empty($uygulama_id) or empty($uygulama_adi))) {
            return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => "Parametre Eksik", "erisim_durumu" => "");
        } else if ($kaynak == "edevlet" and (empty($tcno) or empty($uygulama_id) or empty($uygulama_adi))) {
            return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => "Parametre Eksik", "erisim_durumu" => "");
        } else {

            $q1 = $this->query("SELECT * FROM kurs_merkezi.yetkili WHERE username='$ldapusername' and yetkili_durumu='1' ");
            $rowc1 = $this->num_rows($q1);
            if ($rowc1 < 1) {
                $this->loginerror($tcno, GetIP(), $uygulama_id, $ldapusername, $kaynak);
                return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => $uygulama_adi . " Yetkisi Bulunamadı");
            } else {
                $row = $this->fetch_assoc($q1);

                $erisim_durumu = "";

                ////$erisim_durumu = $this->erisim_kontrol($uygulama_id, 9, $tcno, $ldapusername);
                $erisim_durumu = 1;
                $erisim_durumu = 1;

                if ($erisim_durumu == 1) {
                    $_SESSION['kurs_merkezi_admin_yetkili_durumu'] = $row['yetkili_durumu'];
                    $_SESSION['kurs_merkezi_admin_id'] = $row['id'];
                    $_SESSION['kurs_merkezi_admin_name'] = $row['adi'] . ' ' . $row['soyadi'];
                    $_SESSION['kurs_merkezi_admin_tc'] = $row['tc'];
                    $_SESSION['kurs_merkezi_admin_cep_telefonu'] = $row['cep_telefonu'];
                    $_SESSION['kurs_merkezi_admin_yetkili'] = $row['yetkili'];
                    $_SESSION['kurs_merkezi_admin_ogretmen_islemleri'] = $row['ogretmen_islemleri'];
                    $_SESSION['kurs_merkezi_admin_donem_islemleri'] = $row['donem_islemleri'];
                    $_SESSION['kurs_merkezi_admin_ders_islemleri'] = $row['ders_islemleri'];
                    $_SESSION['kurs_merkezi_admin_kurs_merkezleri'] = $row['kurs_merkezleri'];
                    $_SESSION['kurs_merkezi_admin_sinif_islemleri'] = $row['sinif_islemleri'];
                    $_SESSION['kurs_merkezi_admin_ogrenci_islemleri'] = $row['ogrenci_islemleri'];
                    $_SESSION['kurs_merkezi_admin_anket_islemleri'] = $row['anket_islemleri'];
                    $_SESSION['kurs_merkezi_admin_ogrenci_sil'] = $row['ogrenci_sil'];
                    $_SESSION['kurs_merkezi_admin_sinif_sil'] = $row['sinif_sil'];
                    $_SESSION['kurs_merkezi_admin_ogretmen_sil'] = $row['ogretmen_sil'];
                    $_SESSION['kurs_merkezi_admin_istatistik_islemleri'] = $row['istatistik_islemleri'];
                    $_SESSION['kurs_merkezi_admin_sms_islemleri'] = $row['sms_islemleri'];
                    $_SESSION['kurs_merkezi_admin_rehberlik_islemleri'] = $row['rehberlik_islemleri'];
                    $_SESSION['kurs_merkezi_admin_duyuru_islemleri'] = $row['duyuru_islemleri'];
                    $_SESSION['kurs_merkezi_admin_sosyal_etkinlik_islemleri'] = $row['sosyal_etkinlik_islemleri'];
                    $_SESSION['kurs_merkezi_admin_sosyal_okul_islemleri'] = $row['okul_islemleri'];

                    $this->loginlog((int)$row['id'], $row['tc'], $row['adi'] . ' ' . $row['soyadi'], $uygulama_id, GetIP(), $ldapusername, $kaynak);
                    // header('Location: index.php');
                    return $array = array("hata" => 0, "success" => 1, "uygulama_id" => $uygulama_id, "erisim_durumu" => $erisim_durumu);

                } else if ($erisim_durumu == 2) {
                    $this->loginerror($tcno, GetIP(), $uygulama_id, $ldapusername, $kaynak);
                    return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => $uygulama_adi . " Yetki Formu Bulunmamaktadır", "erisim_durumu" => $erisim_durumu);

                } else if ($erisim_durumu == 0) {
                    $this->loginerror($tcno, GetIP(), $uygulama_id, $ldapusername, $kaynak);
                    return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => $uygulama_adi . " Yetkisi Bulunamadı", "erisim_durumu" => $erisim_durumu);
                }
            }
        }
    }
    function kurs_merkezi_ogretmen_login($tcno, $ldapusername, $uygulama_id, $uygulama_adi, $kaynak)
    {
        $tcno = rescape($tcno);
        $ldapusername = rescape($ldapusername);
        $uygulama_id = rescape($uygulama_id);
        $uygulama_adi = rescape($uygulama_adi);
        $kaynak = rescape($kaynak);
        if (empty($kaynak)) {
            return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => "Parametre Eksik", "erisim_durumu" => "");
        } else if ($kaynak == "ldap" and (empty($tcno) or empty($ldapusername) or empty($uygulama_id) or empty($uygulama_adi))) {
            return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => "Parametre Eksik", "erisim_durumu" => "");
        } else if ($kaynak == "edevlet" and (empty($tcno) or empty($uygulama_id) or empty($uygulama_adi))) {
            return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => "Parametre Eksik", "erisim_durumu" => "");
        } else {
            $q1 = $this->query("SELECT * FROM kurs_merkezi.ogretmen WHERE tc='$tcno' AND yetkili_durumu='1' ");
            $rowc1 = $this->num_rows($q1);
            if ($rowc1 < 1) {
                $this->loginerror($tcno, GetIP(), $uygulama_id, $ldapusername, $kaynak);
                return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => $uygulama_adi . " Yetkisi Bulunamadı");
            } else {
                $row = $this->fetch_assoc($q1);

                $erisim_durumu = "";

                ////$erisim_durumu = $this->erisim_kontrol($uygulama_id, 9, $tcno, $ldapusername);
                $erisim_durumu = 1;
                $erisim_durumu = 1;

                if ($erisim_durumu == 1) {
                    $_SESSION['kurs_merkezi_ogretmen_id'] = $row['id'];
                    $_SESSION['kurs_merkezi_ogretmen_name'] = $row['adi'] . ' ' . $row['soyadi'];

                    $this->loginlog((int)$row['id'], $row['tc'], $row['adi'] . ' ' . $row['soyadi'], $uygulama_id, GetIP(), $ldapusername, $kaynak);
                    // header('Location: index.php');
                    return $array = array("hata" => 0, "success" => 1, "uygulama_id" => $uygulama_id, "erisim_durumu" => $erisim_durumu);

                } else if ($erisim_durumu == 2) {
                    $this->loginerror($tcno, GetIP(), $uygulama_id, $ldapusername, $kaynak);
                    return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => $uygulama_adi . " Yetki Formu Bulunmamaktadır", "erisim_durumu" => $erisim_durumu);

                } else if ($erisim_durumu == 0) {
                    $this->loginerror($tcno, GetIP(), $uygulama_id, $ldapusername, $kaynak);
                    return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => $uygulama_adi . " Yetkisi Bulunamadı", "erisim_durumu" => $erisim_durumu);

                }
            }
        }
    }

    function dogrudan_temin_login($tcno, $ldapusername, $uygulama_id, $uygulama_adi, $kaynak)
    {
        $tcno = rescape($tcno);
        $ldapusername = rescape($ldapusername);
        $uygulama_id = rescape($uygulama_id);
        $uygulama_adi = rescape($uygulama_adi);
        $kaynak = rescape($kaynak);
        if (empty($kaynak)) {
            return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => "Parametre Eksik", "erisim_durumu" => "");
        } else if ($kaynak == "ldap" and (empty($tcno) or empty($ldapusername) or empty($uygulama_id) or empty($uygulama_adi))) {
            return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => "Parametre Eksik", "erisim_durumu" => "");
        } else if ($kaynak == "edevlet" and (empty($tcno) or empty($uygulama_id) or empty($uygulama_adi))) {
            return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => "Parametre Eksik", "erisim_durumu" => "");
        } else {

            $q1 = $this->query("SELECT * FROM dogrudan_temin.yetkili WHERE tc='$tcno' and yetkili_durumu='1' ");
            $rowc1 = $this->num_rows($q1);
            if ($rowc1 < 1) {
                $this->loginerror($tcno, GetIP(), $uygulama_id, $ldapusername, $kaynak);
                return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => $uygulama_adi . " Yetkisi Bulunamadı");
            } else {
                $row = $this->fetch_assoc($q1);

                $erisim_durumu = "";

                ////$erisim_durumu = $this->erisim_kontrol($uygulama_id, 9, $tcno, $ldapusername);
                $erisim_durumu = 1;
                $erisim_durumu = 1;

                if ($erisim_durumu == 1) {
                    $_SESSION['dogrudan_temin_id'] = $row['id'];
                    $_SESSION['dogrudan_temin_name'] = $row['adi'] . ' ' . $row['soyadi'];
                    $_SESSION['dogrudan_temin_silme_yetkisi'] = $row['silme'];
                    $_SESSION['dogrudan_temin_butce_kodu_sil'] = $row['butce_kodu_sil'];
                    $_SESSION['dogrudan_temin_dogrudan_temin_islemleri'] = $row['dogrudan_temin_islemleri'];
                    $_SESSION['dogrudan_temin_raporlar'] = $row['raporlar'];
                    $_SESSION['dogrudan_temin_ayarlar'] = $row['ayarlar'];
                    $_SESSION['dogrudan_temin_ihale_takvimi'] = $row['ihale_takvimi'];
                    $_SESSION['dogrudan_temin_yetkili_islemleri'] = $row['yetkili_islemleri'];
                    $_SESSION['dogrudan_temin_tc'] = $row['tc'];
                    $_SESSION['dogrudan_temin_cep_telefonu'] = $row['cep_telefonu'];

                    $this->loginlog((int)$row['id'], $row['tc'], $row['adi'] . ' ' . $row['soyadi'], $uygulama_id, GetIP(), $ldapusername, $kaynak);
                    // header('Location: index.php');
                    return $array = array("hata" => 0, "success" => 1, "uygulama_id" => $uygulama_id, "erisim_durumu" => $erisim_durumu);

                } else if ($erisim_durumu == 2) {
                    $this->loginerror($tcno, GetIP(), $uygulama_id, $ldapusername, $kaynak);
                    return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => $uygulama_adi . " Yetki Formu Bulunmamaktadır", "erisim_durumu" => $erisim_durumu);

                } else if ($erisim_durumu == 0) {
                    $this->loginerror($tcno, GetIP(), $uygulama_id, $ldapusername, $kaynak);
                    return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => $uygulama_adi . " Yetkisi Bulunamadı", "erisim_durumu" => $erisim_durumu);

                }
            }
        }
    }

    function emekliler_platformu_login($tcno, $ldapusername, $uygulama_id, $uygulama_adi, $kaynak)
    {
        $tcno = rescape($tcno);
        $ldapusername = rescape($ldapusername);
        $uygulama_id = rescape($uygulama_id);
        $uygulama_adi = rescape($uygulama_adi);
        $kaynak = rescape($kaynak);
        if (empty($kaynak)) {
            return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => "Parametre Eksik", "erisim_durumu" => "");
        } else if ($kaynak == "ldap" and (empty($tcno) or empty($ldapusername) or empty($uygulama_id) or empty($uygulama_adi))) {
            return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => "Parametre Eksik", "erisim_durumu" => "");
        } else if ($kaynak == "edevlet" and (empty($tcno) or empty($uygulama_id) or empty($uygulama_adi))) {
            return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => "Parametre Eksik", "erisim_durumu" => "");
        } else {


            $q1 = $this->query("SELECT * FROM emekliler_platformu.yetkili WHERE tc='$tcno' and yetkili_durumu='1' ");
            $rowc1 = $this->num_rows($q1);
            if ($rowc1 < 1) {
                $this->loginerror($tcno, GetIP(), $uygulama_id, $ldapusername, $kaynak);
                return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => $uygulama_adi . " Yetkisi Bulunamadı");
            } else {
                $row = $this->fetch_assoc($q1);

                $erisim_durumu = "";

                ////$erisim_durumu = $this->erisim_kontrol($uygulama_id, 9, $tcno, $ldapusername);
                $erisim_durumu = 1;
                $erisim_durumu = 1;

                if ($erisim_durumu == 1) {
                    $_SESSION['emekliler_platformu_admin_idd'] = $row['id'];
                    $_SESSION['emekliler_admin_name'] = $row['adi'] . ' ' . $row['soyadi'];

                    $_SESSION['emekliler_admin_tc'] = $row['tc'];
                    $_SESSION['emekliler_admin_cep_telefonu'] = $row['cep_telefonu'];

                    $this->loginlog((int)$row['id'], $row['tc'], $row['adi'] . ' ' . $row['soyadi'], $uygulama_id, GetIP(), $ldapusername, $kaynak);
                    // header('Location: index.php');
                    return $array = array("hata" => 0, "success" => 1, "uygulama_id" => $uygulama_id, "erisim_durumu" => $erisim_durumu);

                } else if ($erisim_durumu == 2) {
                    $this->loginerror($tcno, GetIP(), $uygulama_id, $ldapusername, $kaynak);
                    return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => $uygulama_adi . " Yetki Formu Bulunmamaktadır", "erisim_durumu" => $erisim_durumu);

                } else if ($erisim_durumu == 0) {
                    $this->loginerror($tcno, GetIP(), $uygulama_id, $ldapusername, $kaynak);
                    return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => $uygulama_adi . " Yetkisi Bulunamadı", "erisim_durumu" => $erisim_durumu);

                }
            }
        }
    }

    function giykimbil_login($tcno, $ldapusername, $uygulama_id, $uygulama_adi, $kaynak)
    {
        $tcno = rescape($tcno);
        $ldapusername = rescape($ldapusername);
        $uygulama_id = rescape($uygulama_id);
        $uygulama_adi = rescape($uygulama_adi);
        $kaynak = rescape($kaynak);
        if (empty($kaynak)) {
            return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => "Parametre Eksik", "erisim_durumu" => "");
        } else if ($kaynak == "ldap" and (empty($tcno) or empty($ldapusername) or empty($uygulama_id) or empty($uygulama_adi))) {
            return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => "Parametre Eksik", "erisim_durumu" => "");
        } else if ($kaynak == "edevlet" and (empty($tcno) or empty($uygulama_id) or empty($uygulama_adi))) {
            return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => "Parametre Eksik", "erisim_durumu" => "");
        } else {
            $q1 = $this->query("SELECT * FROM giykimbil.yetkili WHERE tc='$tcno' and yetkili_durumu='1' ");
            $rowc1 = $this->num_rows($q1);
            if ($rowc1 < 1) {
                $this->loginerror($tcno, GetIP(), $uygulama_id, $ldapusername, $kaynak);
                return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => $uygulama_adi . " Yetkisi Bulunamadı");
            } else {
                $row = $this->fetch_assoc($q1);

                $erisim_durumu = "";

                ////$erisim_durumu = $this->erisim_kontrol($uygulama_id, 9, $tcno, $ldapusername);
                $erisim_durumu = 1;
                $erisim_durumu = 1;

                if ($erisim_durumu == 1) {
                    $_SESSION['giykimbil_id'] = $row['id'];
                    $_SESSION['giykimbil_name'] = $row['adi'] . " " . $row['soyadi'];
                    $_SESSION['giykimbil_admin'] = $row['admin'];
                    $_SESSION['giykimbil_birim_yetki'] = "";

                    $_SESSION['giykimbil_tc'] = $row['tc'];
                    $_SESSION['giykimbil_cep_telefonu'] = $row['cep_telefonu'];

                    $birim_yetki = array();
                    $i = 0;
                    $q_birim = $this->query("SELECT * FROM giykimbil.yetkili_birim WHERE yetkili_id=" . $row['id'] . " ");
                    while ($row_birim = $this->fetch_assoc($q_birim)) {
                        $birim_yetki[$i] = $row_birim['birim_id'];
                        $i++;
                    }
                    $_SESSION['giykimbil_birim_yetki'] = $birim_yetki;

                    $this->loginlog((int)$row['id'], $row['tc'], $row['adi'] . ' ' . $row['soyadi'], $uygulama_id, GetIP(), $ldapusername, $kaynak);
                    // header('Location: index.php');
                    return $array = array("hata" => 0, "success" => 1, "uygulama_id" => $uygulama_id, "erisim_durumu" => $erisim_durumu);

                } else if ($erisim_durumu == 2) {
                    $this->loginerror($tcno, GetIP(), $uygulama_id, $ldapusername, $kaynak);
                    return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => $uygulama_adi . " Yetki Formu Bulunmamaktadır", "erisim_durumu" => $erisim_durumu);

                } else if ($erisim_durumu == 0) {
                    $this->loginerror($tcno, GetIP(), $uygulama_id, $ldapusername, $kaynak);
                    return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => $uygulama_adi . " Yetkisi Bulunamadı", "erisim_durumu" => $erisim_durumu);

                }
            }
        }
    }

    function gezitakip_login($tcno, $ldapusername, $uygulama_id, $uygulama_adi, $kaynak)
    {
        $tcno = rescape($tcno);
        $ldapusername = rescape($ldapusername);
        $uygulama_id = rescape($uygulama_id);
        $uygulama_adi = rescape($uygulama_adi);
        $kaynak = rescape($kaynak);
        if (empty($kaynak)) {
            return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => "Parametre Eksik", "erisim_durumu" => "");
        } else if ($kaynak == "ldap" and (empty($tcno) or empty($ldapusername) or empty($uygulama_id) or empty($uygulama_adi))) {
            return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => "Parametre Eksik", "erisim_durumu" => "");
        } else if ($kaynak == "edevlet" and (empty($tcno) or empty($uygulama_id) or empty($uygulama_adi))) {
            return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => "Parametre Eksik", "erisim_durumu" => "");
        } else {
            $q1 = $this->query("SELECT * FROM gezi_takip_programi.yetkili WHERE tc='$tcno' and yetkili_durumu='1' ");
            $rowc1 = $this->num_rows($q1);
            if ($rowc1 < 1) {
                $this->loginerror($tcno, GetIP(), $uygulama_id, $ldapusername, $kaynak);
                return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => $uygulama_adi . " Yetkisi Bulunamadı");
            } else {
                $row = $this->fetch_assoc($q1);

                $erisim_durumu = "";

                ////$erisim_durumu = $this->erisim_kontrol($uygulama_id, 9, $tcno, $ldapusername);
                $erisim_durumu = 1;
                $erisim_durumu = 1;

                if ($erisim_durumu == 1) {
                    $_SESSION['gezi_takip_programi_admin_id'] = $row['id'];
                    $_SESSION['gezi_takip_programi_admin_name'] = $row['adi'] . ' ' . $row['soyadi'];
                    $_SESSION['gezi_takip_programi_tc'] = $row['tc'];
                    $_SESSION['gezi_takip_programi_cep_telefonu'] = $row['cep_telefonu'];
                    $_SESSION['gezi_takip_programi_uye_islemleri'] = $row['uye_islemleri'];
                    $_SESSION['gezi_takip_programi_geziler'] = $row['geziler'];
                    $_SESSION['gezi_takip_programi_mesaj'] = $row['mesaj'];
                    $_SESSION['gezi_takip_programi_yetkili_islemleri'] = $row['yetkili_islemleri'];
                    $_SESSION['gezi_takip_programi_yetkili_istatistik'] = $row['yetkili_istatistik'];
                    $_SESSION['gezi_takip_programi_ticket_yetkilisi'] = $row['ticket_yetkilisi'];
                    $_SESSION['gezi_takip_programi_uye_kontrol'] = $row['uye_kontrol'];
                    $_SESSION['gezi_takip_programi_oniki_yas_alti_kayit_alma'] = $row['oniki_yas_alti_kayit_alma'];
                    $_SESSION['gezi_takip_programi_keciorende_oturmayan_kaydi_alma'] = $row['keciorende_oturmayan_kaydi_alma'];
                    $_SESSION['gezi_takip_programi_uye_silme'] = $row['uye_silme'];
                    $_SESSION['gezi_takip_programi_gezi_silme'] = $row['gezi_silme'];
                    $_SESSION['gezi_takip_programi_kura_cekilisi'] = $row['kura_cekilisi'];
                    $_SESSION['gezi_takip_programi_referansli_uyeler'] = $row['referansli_uyeler'];

                    $birim_yetki = array();
                    $i = 0;
                    $q_birim = $this->query("SELECT * FROM giykimbil.yetkili_birim WHERE yetkili_id=" . $row['id'] . " ");
                    while ($row_birim = $this->fetch_assoc($q_birim)) {
                        $birim_yetki[$i] = $row_birim['birim_id'];
                        $i++;
                    }
                    $_SESSION['giykimbil_birim_yetki'] = $birim_yetki;

                    $this->loginlog((int)$row['id'], $row['tc'], $row['adi'] . ' ' . $row['soyadi'], $uygulama_id, GetIP(), $ldapusername, $kaynak);
                    // header('Location: index.php');
                    return $array = array("hata" => 0, "success" => 1, "uygulama_id" => $uygulama_id, "erisim_durumu" => $erisim_durumu);

                } else if ($erisim_durumu == 2) {
                    $this->loginerror($tcno, GetIP(), $uygulama_id, $ldapusername, $kaynak);
                    return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => $uygulama_adi . " Yetki Formu Bulunmamaktadır", "erisim_durumu" => $erisim_durumu);

                } else if ($erisim_durumu == 0) {
                    $this->loginerror($tcno, GetIP(), $uygulama_id, $ldapusername, $kaynak);
                    return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => $uygulama_adi . " Yetkisi Bulunamadı", "erisim_durumu" => $erisim_durumu);

                }
            }
        }
    }

    function ihaletakip_login($tcno, $ldapusername, $uygulama_id, $uygulama_adi, $kaynak)
    {
        $tcno = rescape($tcno);
        $ldapusername = rescape($ldapusername);
        $uygulama_id = rescape($uygulama_id);
        $uygulama_adi = rescape($uygulama_adi);
        $kaynak = rescape($kaynak);
        if (empty($kaynak)) {
            return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => "Parametre Eksik", "erisim_durumu" => "");
        } else if ($kaynak == "ldap" and (empty($tcno) or empty($ldapusername) or empty($uygulama_id) or empty($uygulama_adi))) {
            return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => "Parametre Eksik", "erisim_durumu" => "");
        } else if ($kaynak == "edevlet" and (empty($tcno) or empty($uygulama_id) or empty($uygulama_adi))) {
            return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => "Parametre Eksik", "erisim_durumu" => "");
        } else {
            $q1 = $this->query("SELECT * FROM ihale_takip_sistemi.yetkili WHERE tc='$tcno' and yetkili_durumu='1' ");
            $rowc1 = $this->num_rows($q1);
            if ($rowc1 < 1) {
                $this->loginerror($tcno, GetIP(), $uygulama_id, $ldapusername, $kaynak);
                return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => $uygulama_adi . " Yetkisi Bulunamadı");
            } else {
                $row = $this->fetch_assoc($q1);

                $erisim_durumu = "";

                ////$erisim_durumu = $this->erisim_kontrol($uygulama_id, 9, $tcno, $ldapusername);
                $erisim_durumu = 1;
                $erisim_durumu = 1;

                if ($erisim_durumu == 1) {
                    $_SESSION['ihale_takip_sistemi_id'] = $row['id'];
                    $_SESSION['ihale_takip_sistemi_name'] = $row['adi'] . ' ' . $row['soyadi'];
                    $_SESSION['ihale_takip_sistemi_silme_yetkisi'] = $row['silme'];

                    $_SESSION['ihale_takip_sistemi_tc'] = $row['tc'];
                    $_SESSION['ihale_takip_sistemi_cep_telefonu'] = $row['cep_telefonu'];

                    //$_SESSION['ihale_takip_sistemi_sadece_takvim_gorme'] = $row['sadece_takvim_gorme'];

                    $_SESSION['ihale_takip_sistemi_ihale_islemleri'] = $row['ihale_islemleri'];
                    $_SESSION['ihale_takip_sistemi_raporlar'] = $row['raporlar'];
                    $_SESSION['ihale_takip_sistemi_ayarlar'] = $row['ayarlar'];
                    $_SESSION['ihale_takip_sistemi_ayarlar_firma_ekle'] = $row['ayarlar_firma_ekle'];
                    $_SESSION['ihale_takip_sistemi_ayarlar_firma_duzenle'] = $row['ayarlar_firma_duzenle'];
                    $_SESSION['ihale_takip_sistemi_ihale_takvimi'] = $row['ihale_takvimi'];
                    $_SESSION['ihale_takip_sistemi_yetkili_islemleri'] = $row['yetkili_islemleri'];


                    $this->loginlog((int)$row['id'], $row['tc'], $row['adi'] . ' ' . $row['soyadi'], $uygulama_id, GetIP(), $ldapusername, $kaynak);
                    // header('Location: index.php');
                    return $array = array("hata" => 0, "success" => 1, "uygulama_id" => $uygulama_id, "erisim_durumu" => $erisim_durumu);

                } else if ($erisim_durumu == 2) {
                    $this->loginerror($tcno, GetIP(), $uygulama_id, $ldapusername, $kaynak);
                    return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => $uygulama_adi . " Yetki Formu Bulunmamaktadır", "erisim_durumu" => $erisim_durumu);

                } else if ($erisim_durumu == 0) {
                    $this->loginerror($tcno, GetIP(), $uygulama_id, $ldapusername, $kaynak);
                    return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => $uygulama_adi . " Yetkisi Bulunamadı", "erisim_durumu" => $erisim_durumu);

                }
            }
        }
    }

    function kecmek_login($tcno, $ldapusername, $uygulama_id, $uygulama_adi, $kaynak)
    {
        $tcno = rescape($tcno);
        $ldapusername = rescape($ldapusername);
        $uygulama_id = rescape($uygulama_id);
        $uygulama_adi = rescape($uygulama_adi);
        $kaynak = rescape($kaynak);
        if (empty($kaynak)) {
            return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => "Parametre Eksik", "erisim_durumu" => "");
        } else if ($kaynak == "ldap" and (empty($tcno) or empty($ldapusername) or empty($uygulama_id) or empty($uygulama_adi))) {
            return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => "Parametre Eksik", "erisim_durumu" => "");
        } else if ($kaynak == "edevlet" and (empty($tcno) or empty($uygulama_id) or empty($uygulama_adi))) {
            return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => "Parametre Eksik", "erisim_durumu" => "");
        } else {
            $q1 = $this->query("SELECT * FROM kecmek.personel WHERE kullanici_adi='$tcno' and yetkili_durumu='1' and program_kullanicisi='1' ");
            $rowc1 = $this->num_rows($q1);
            if ($rowc1 < 1) {
                $this->loginerror($tcno, GetIP(), $uygulama_id, $ldapusername, $kaynak);
                return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => $uygulama_adi . " Yetkisi Bulunamadı");
            } else {
                $row = $this->fetch_assoc($q1);

                $erisim_durumu = "";

                ////$erisim_durumu = $this->erisim_kontrol($uygulama_id, 9, $tcno, $ldapusername);
                $erisim_durumu = 1;
                $erisim_durumu = 1;

                if ($erisim_durumu == 1) {
                    $_SESSION['kecmek_admin_yetkili_durumu'] = $row['yetkili_durumu'];
                    $_SESSION['kecmek_admin_id'] = $row['id'];
                    $_SESSION['kecmek_admin_name'] = $row['adi'] . ' ' . $row['soyadi'];
                    $_SESSION['kecmek_admin_ogrenci_ekleme'] = $row['ogrenci_ekleme'];
                    $_SESSION['kecmek_admin_ogrenci_duzenleme'] = $row['ogrenci_duzenleme'];
                    $_SESSION['kecmek_admin_ogrenci_silme'] = $row['ogrenci_silme'];
                    $_SESSION['kecmek_admin_ogrenci_sorgulama'] = $row['ogrenci_sorgulama'];
                    $_SESSION['kecmek_admin_ogrenci_on_kayitli_ogrenciler'] = $row['on_kayitli_ogrenciler'];
                    $_SESSION['kecmek_admin_ders_ekleme'] = $row['ders_ekleme'];
                    $_SESSION['kecmek_admin_ders_duzenleme'] = $row['ders_duzenleme'];
                    $_SESSION['kecmek_admin_ders_silme'] = $row['ders_silme'];
                    $_SESSION['kecmek_admin_ders_yoklama'] = $row['ders_yoklama'];
                    $_SESSION['kecmek_admin_sinif_ogrencileri'] = $row['sinif_ogrencileri'];
                    $_SESSION['kecmek_admin_bos_sinif_listesi_al'] = $row['bos_sinif_listesi_al'];
                    $_SESSION['kecmek_admin_ders_sms'] = $row['ders_sms'];
                    $_SESSION['kecmek_admin_ders_onay_islemleri'] = $row['ders_onay_islemleri'];
                    $_SESSION['kecmek_admin_kurs_merkezi_ekle'] = $row['kurs_merkezi_ekle'];
                    $_SESSION['kecmek_admin_kurs_merkezi_duzenle'] = $row['kurs_merkezi_duzenle'];
                    $_SESSION['kecmek_admin_kurs_merkezi_sms_gonder'] = $row['kurs_merkezi_sms_gonder'];
                    $_SESSION['kecmek_admin_brans_ekleme'] = $row['brans_ekleme'];
                    $_SESSION['kecmek_admin_brans_duzenle'] = $row['brans_duzenle'];
                    $_SESSION['kecmek_admin_brans_turu_ekleme'] = $row['brans_turu_ekleme'];
                    $_SESSION['kecmek_admin_brans_turu_duzenleme'] = $row['brans_turu_duzenleme'];
                    $_SESSION['kecmek_admin_seviye_ekleme'] = $row['seviye_ekleme'];
                    $_SESSION['kecmek_admin_seviye_duzenleme'] = $row['seviye_duzenleme'];
                    $_SESSION['kecmek_admin_donem_ekleme'] = $row['donem_ekleme'];
                    $_SESSION['kecmek_admin_donem_duzenleme'] = $row['donem_duzenleme'];
                    $_SESSION['kecmek_admin_istatistik_islemleri'] = $row['istatistik_islemleri'];
                    $_SESSION['kecmek_admin_personel_ekle'] = $row['personel_ekle'];
                    $_SESSION['kecmek_admin_personel_duzenle'] = $row['personel_duzenle'];
                    $_SESSION['kecmek_admin_personel_sorgulama'] = $row['personel_sorgulama'];
                    $_SESSION['kecmek_admin_personel_izin_ekleme'] = $row['personel_izin_ekleme'];
                    $_SESSION['kecmek_admin_personel_izin_onay'] = $row['personel_izin_onay'];
                    $_SESSION['kecmek_admin_personel_izin_gorme'] = $row['personel_izin_gorme'];
                    $_SESSION['kecmek_admin_personel_unvan_ekleme'] = $row['personel_unvan_ekleme'];
                    $_SESSION['kecmek_admin_personel_unvan_duzenleme'] = $row['personel_unvan_duzenleme'];
                    $_SESSION['kecmek_admin_personel_engel_turu_ekle'] = $row['personel_engel_turu_ekle'];
                    $_SESSION['kecmek_admin_personel_engel_turu_duzenle'] = $row['personel_engel_turu_duzenle'];
                    $_SESSION['kecmek_admin_personel_calisma_sekli_ekle'] = $row['personel_calisma_sekli_ekle'];
                    $_SESSION['kecmek_admin_personel_calisma_sekli_duzenle'] = $row['personel_calisma_sekli_duzenle'];
                    $_SESSION['kecmek_admin_yonetici'] = $row['yonetici'];
                    $_SESSION['kecmek_admin_istek_takip'] = $row['istek_takip'];
                    $_SESSION['kecmek_admin_sms'] = $row['sms'];
                    $_SESSION['kecmek_admin_web_site'] = $row['web_site'];
                    $_SESSION['kecmek_admin_ogrenci_online_ders_on_kayit'] = $row['online_ders_on_kayit'];
                    $_SESSION['kecmek_admin_sinav_sonucu_yukleme_excel'] = $row['sinav_sonucu_yukleme_excel'];
                    $_SESSION['kecmek_admin_personel_giris_cikis_ekle'] = $row['personel_giris_cikis_ekle'];
                    $_SESSION['kecmek_admin_personel_giris_cikislari'] = $row['personel_giris_cikislari'];
                    $_SESSION['kecmek_admin_personel_giris_cikis_excel'] = $row['personel_giris_cikis_excel'];

                    $this->loginlog((int)$row['id'], $row['tc'], $row['adi'] . ' ' . $row['soyadi'], $uygulama_id, GetIP(), $ldapusername, $kaynak);
                    // header('Location: index.php');
                    return $array = array("hata" => 0, "success" => 1, "uygulama_id" => $uygulama_id, "erisim_durumu" => $erisim_durumu);

                } else if ($erisim_durumu == 2) {
                    $this->loginerror($tcno, GetIP(), $uygulama_id, $ldapusername, $kaynak);
                    return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => $uygulama_adi . " Yetki Formu Bulunmamaktadır", "erisim_durumu" => $erisim_durumu);

                } else if ($erisim_durumu == 0) {
                    $this->loginerror($tcno, GetIP(), $uygulama_id, $ldapusername, $kaynak);
                    return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => $uygulama_adi . " Yetkisi Bulunamadı", "erisim_durumu" => $erisim_durumu);

                }
            }
        }
    }

    function kedemogretmen_login($tcno, $ldapusername, $uygulama_id, $uygulama_adi, $kaynak)
    {
        $tcno = rescape($tcno);
        $ldapusername = rescape($ldapusername);
        $uygulama_id = rescape($uygulama_id);
        $uygulama_adi = rescape($uygulama_adi);
        $kaynak = rescape($kaynak);
        if (empty($kaynak)) {
            return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => "Parametre Eksik", "erisim_durumu" => "");
        } else if ($kaynak == "ldap" and (empty($tcno) or empty($ldapusername) or empty($uygulama_id) or empty($uygulama_adi))) {
            return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => "Parametre Eksik", "erisim_durumu" => "");
        } else if ($kaynak == "edevlet" and (empty($tcno) or empty($uygulama_id) or empty($uygulama_adi))) {
            return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => "Parametre Eksik", "erisim_durumu" => "");
        } else {
            $q1 = $this->query("SELECT * FROM kedem.ogretmen WHERE tc='$tcno' AND yetkili_durumu='1' ");
            $rowc1 = $this->num_rows($q1);
            if ($rowc1 < 1) {
                $this->loginerror($tcno, GetIP(), $uygulama_id, $ldapusername, $kaynak);
                return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => $uygulama_adi . " Yetkisi Bulunamadı");
            } else {
                $row = $this->fetch_assoc($q1);

                $erisim_durumu = "";

                ////$erisim_durumu = $this->erisim_kontrol($uygulama_id, 9, $tcno, $ldapusername);
                $erisim_durumu = 1;
                $erisim_durumu = 1;

                if ($erisim_durumu == 1) {
                    $_SESSION['kedem_ogretmen_id'] = $row['id'];
                    $_SESSION['kedem_ogretmen_name'] = $row['adi'] . ' ' . $row['soyadi'];

                    $this->loginlog((int)$row['id'], $row['tc'], $row['adi'] . ' ' . $row['soyadi'], $uygulama_id, GetIP(), $ldapusername, $kaynak);
                    // header('Location: index.php');
                    return $array = array("hata" => 0, "success" => 1, "uygulama_id" => $uygulama_id, "erisim_durumu" => $erisim_durumu);

                } else if ($erisim_durumu == 2) {
                    $this->loginerror($tcno, GetIP(), $uygulama_id, $ldapusername, $kaynak);
                    return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => $uygulama_adi . " Yetki Formu Bulunmamaktadır", "erisim_durumu" => $erisim_durumu);

                } else if ($erisim_durumu == 0) {
                    $this->loginerror($tcno, GetIP(), $uygulama_id, $ldapusername, $kaynak);
                    return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => $uygulama_adi . " Yetkisi Bulunamadı", "erisim_durumu" => $erisim_durumu);

                }
            }
        }
    }

    function kedem_login($tcno, $ldapusername, $uygulama_id, $uygulama_adi, $kaynak)
    {
        $tcno = rescape($tcno);
        $ldapusername = rescape($ldapusername);
        $uygulama_id = rescape($uygulama_id);
        $uygulama_adi = rescape($uygulama_adi);
        $kaynak = rescape($kaynak);
        if (empty($kaynak)) {
            return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => "Parametre Eksik", "erisim_durumu" => "");
        } else if ($kaynak == "ldap" and (empty($tcno) or empty($ldapusername) or empty($uygulama_id) or empty($uygulama_adi))) {
            return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => "Parametre Eksik", "erisim_durumu" => "");
        } else if ($kaynak == "edevlet" and (empty($tcno) or empty($uygulama_id) or empty($uygulama_adi))) {
            return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => "Parametre Eksik", "erisim_durumu" => "");
        } else {
            $q1 = $this->query("SELECT * FROM kedem.yetkili WHERE tc='$tcno' and yetkili_durumu='1' ");
            $rowc1 = $this->num_rows($q1);
            if ($rowc1 < 1) {
                $this->loginerror($tcno, GetIP(), $uygulama_id, $ldapusername, $kaynak);
                return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => $uygulama_adi . " Yetkisi Bulunamadı");
            } else {
                $row = $this->fetch_assoc($q1);

                $erisim_durumu = "";

                ////$erisim_durumu = $this->erisim_kontrol($uygulama_id, 9, $tcno, $ldapusername);
                $erisim_durumu = 1;
                $erisim_durumu = 1;

                if ($erisim_durumu == 1) {
                    $_SESSION['kedem_admin_yetkili_durumu'] = $row['yetkili_durumu'];
                    $_SESSION['kedem_admin_id'] = $row['id'];
                    $_SESSION['kedem_admin_name'] = $row['adi'] . ' ' . $row['soyadi'];
                    $_SESSION['kedem_admin_tc'] = $row['tc'];
                    $_SESSION['kedem_admin_cep_telefonu'] = $row['cep_telefonu'];
                    $_SESSION['kedem_admin_yetkili'] = $row['yetkili'];
                    $_SESSION['kedem_admin_ogretmen_islemleri'] = $row['ogretmen_islemleri'];
                    $_SESSION['kedem_admin_donem_islemleri'] = $row['donem_islemleri'];
                    $_SESSION['kedem_admin_ders_islemleri'] = $row['ders_islemleri'];
                    $_SESSION['kedem_admin_kurs_merkezleri'] = $row['kurs_merkezleri'];
                    $_SESSION['kedem_admin_sinif_islemleri'] = $row['sinif_islemleri'];
                    $_SESSION['kedem_admin_ogrenci_islemleri'] = $row['ogrenci_islemleri'];
                    $_SESSION['kedem_admin_anket_islemleri'] = $row['anket_islemleri'];
                    $_SESSION['kedem_admin_ogrenci_sil'] = $row['ogrenci_sil'];
                    $_SESSION['kedem_admin_sinif_sil'] = $row['sinif_sil'];
                    $_SESSION['kedem_admin_ogretmen_sil'] = $row['ogretmen_sil'];
                    $_SESSION['kedem_admin_istatistik_islemleri'] = $row['istatistik_islemleri'];
                    $_SESSION['kedem_admin_sms_islemleri'] = $row['sms_islemleri'];
                    $_SESSION['kedem_admin_rehberlik_islemleri'] = $row['rehberlik_islemleri'];
                    $_SESSION['kedem_admin_duyuru_islemleri'] = $row['duyuru_islemleri'];
                    $_SESSION['kedem_admin_sosyal_etkinlik_islemleri'] = $row['sosyal_etkinlik_islemleri'];
                    $_SESSION['kedem_admin_sosyal_okul_islemleri'] = $row['okul_islemleri'];
                    $_SESSION['kedem_admin_web_site'] = $row['web_site'];

                    $_SESSION['kedem_admin_personel_ekle_duzenle'] = $row['personel_ekle_duzenle'];
                    $_SESSION['kedem_admin_personel_unvan_ekle_duzenle'] = $row['personel_unvan_ekle_duzenle'];
                    $_SESSION['kedem_admin_personel_giris_cikis_ekle'] = $row['personel_giris_cikis_ekle'];
                    $_SESSION['kedem_admin_personel_giris_cikislari'] = $row['personel_giris_cikislari'];

                    $this->loginlog((int)$row['id'], $row['tc'], $row['adi'] . ' ' . $row['soyadi'], $uygulama_id, GetIP(), $ldapusername, $kaynak);
                    // header('Location: index.php');
                    return $array = array("hata" => 0, "success" => 1, "uygulama_id" => $uygulama_id, "erisim_durumu" => $erisim_durumu);

                } else if ($erisim_durumu == 2) {
                    $this->loginerror($tcno, GetIP(), $uygulama_id, $ldapusername, $kaynak);
                    return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => $uygulama_adi . " Yetki Formu Bulunmamaktadır", "erisim_durumu" => $erisim_durumu);

                } else if ($erisim_durumu == 0) {
                    $this->loginerror($tcno, GetIP(), $uygulama_id, $ldapusername, $kaynak);
                    return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => $uygulama_adi . " Yetkisi Bulunamadı", "erisim_durumu" => $erisim_durumu);

                }
            }
        }
    }

    function konferans_login($tcno, $ldapusername, $uygulama_id, $uygulama_adi, $kaynak)
    {
        $tcno = rescape($tcno);
        $ldapusername = rescape($ldapusername);
        $uygulama_id = rescape($uygulama_id);
        $uygulama_adi = rescape($uygulama_adi);
        $kaynak = rescape($kaynak);
        if (empty($kaynak)) {
            return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => "Parametre Eksik", "erisim_durumu" => "");
        } else if ($kaynak == "ldap" and (empty($tcno) or empty($ldapusername) or empty($uygulama_id) or empty($uygulama_adi))) {
            return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => "Parametre Eksik", "erisim_durumu" => "");
        } else if ($kaynak == "edevlet" and (empty($tcno) or empty($uygulama_id) or empty($uygulama_adi))) {
            return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => "Parametre Eksik", "erisim_durumu" => "");
        } else {
            $q1 = $this->query("SELECT * FROM konferans_d_salonu_takip.yetkili WHERE tc='$tcno' and yetkili_durumu='1' ");
            $rowc1 = $this->num_rows($q1);
            if ($rowc1 < 1) {
                $this->loginerror($tcno, GetIP(), $uygulama_id, $ldapusername, $kaynak);
                return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => $uygulama_adi . " Yetkisi Bulunamadı");
            } else {
                $row = $this->fetch_assoc($q1);

                $erisim_durumu = "";

                ////$erisim_durumu = $this->erisim_kontrol($uygulama_id, 9, $tcno, $ldapusername);
                $erisim_durumu = 1;
                $erisim_durumu = 1;

                if ($erisim_durumu == 1) {
                    $_SESSION['konferans_d_salonu_admin_id'] = $row['id'];
                    $_SESSION['konferans_d_salonu_admin_name'] = $row['adi'] . ' ' . $row['soyadi'];
                    $_SESSION['konferans_d_salonu_admin_tc'] = $row['tc'];
                    $_SESSION['konferans_d_salonu_admin_cep_telefonu'] = $row['cep_telefonu'];
                    $_SESSION['konferans_d_salonu_tesis_temin'] = $row['tesis_temin'];
                    $_SESSION['konferans_d_salonu_ayarlar'] = $row['ayarlar'];
                    $_SESSION['konferans_d_salonu_sorgulama'] = $row['sorgulama'];
                    $_SESSION['konferans_d_salonu_yetkili'] = $row['yetkili'];
                    $_SESSION['konferans_d_salonu_kime_verildi'] = $row['kime_verildi'];
                    $_SESSION['konferans_d_salonu_tesis_bilgisi'] = $row['tesis_bilgisi'];
                    $_SESSION['konferans_d_salonu_tesis_bilgisi_telefonu_gorebilsin'] = $row['tesis_bilgisi_telefonu_gorebilsin'];
                    $_SESSION['konferans_d_salonu_hafta_tatili'] = $row['hafta_tatili'];
                    $_SESSION['konferans_d_salonu_rezervasyon_tablosu'] = $row['rezervasyon_tablosu'];
                    $_SESSION['konferans_d_salonu_mahalle_konagi_rezervasyon_tablosu'] = $row['mahalle_konagi_rezervasyon_tablosu'];
                    $_SESSION['konferans_d_salonu_tesis_program_dokumleri'] = $row['tesis_program_dokumleri'];


                    $this->loginlog((int)$row['id'], $row['tc'], $row['adi'] . ' ' . $row['soyadi'], $uygulama_id, GetIP(), $ldapusername, $kaynak);
                    // header('Location: index.php');
                    return $array = array("hata" => 0, "success" => 1, "uygulama_id" => $uygulama_id, "erisim_durumu" => $erisim_durumu);

                } else if ($erisim_durumu == 2) {
                    $this->loginerror($tcno, GetIP(), $uygulama_id, $ldapusername, $kaynak);
                    return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => $uygulama_adi . " Yetki Formu Bulunmamaktadır", "erisim_durumu" => $erisim_durumu);

                } else if ($erisim_durumu == 0) {
                    $this->loginerror($tcno, GetIP(), $uygulama_id, $ldapusername, $kaynak);
                    return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => $uygulama_adi . " Yetkisi Bulunamadı", "erisim_durumu" => $erisim_durumu);

                }
            }
        }
    }

    function malihizmetler_login($tcno, $ldapusername, $uygulama_id, $uygulama_adi, $kaynak)
    {
        $tcno = rescape($tcno);
        $ldapusername = rescape($ldapusername);
        $uygulama_id = rescape($uygulama_id);
        $uygulama_adi = rescape($uygulama_adi);
        $kaynak = rescape($kaynak);
        if (empty($kaynak)) {
            return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => "Parametre Eksik", "erisim_durumu" => "");
        } else if ($kaynak == "ldap" and (empty($tcno) or empty($ldapusername) or empty($uygulama_id) or empty($uygulama_adi))) {
            return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => "Parametre Eksik", "erisim_durumu" => "");
        } else if ($kaynak == "edevlet" and (empty($tcno) or empty($uygulama_id) or empty($uygulama_adi))) {
            return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => "Parametre Eksik", "erisim_durumu" => "");
        } else {
            $q1 = $this->query("SELECT * FROM mali_hizmetler.yetkili WHERE tc='$tcno' and yetkili_durumu='1' ");
            $rowc1 = $this->num_rows($q1);
            if ($rowc1 < 1) {
                $this->loginerror($tcno, GetIP(), $uygulama_id, $ldapusername, $kaynak);
                return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => $uygulama_adi . " Yetkisi Bulunamadı");
            } else {
                $row = $this->fetch_assoc($q1);

                $erisim_durumu = "";

                ////$erisim_durumu = $this->erisim_kontrol($uygulama_id, 9, $tcno, $ldapusername);
                $erisim_durumu = 1;
                $erisim_durumu = 1;

                if ($erisim_durumu == 1) {
                    $_SESSION['mali_hizmetler_yetkili_durumu'] = $row['yetkili_durumu'];
                    $_SESSION['mali_hizmetler_admin_id'] = $row['id'];
                    $_SESSION['mali_hizmetler_admin_name'] = $row['adi'] . ' ' . $row['soyadi'];


                    $_SESSION['mali_hizmetler_kredi_islemleri'] = $row['kredi_islemleri'];
                    $_SESSION['mali_hizmetler_firma_islemleri'] = $row['firma_islemleri'];
                    $_SESSION['mali_hizmetler_rapor_islemleri'] = $row['rapor_islemleri'];
                    $_SESSION['mali_hizmetler_yetkili_islemleri'] = $row['yetkili_islemleri'];
                    $_SESSION['mali_hizmetler_gib_sorgulama'] = $row['gib_sorgulama'];
                    $_SESSION['mali_hizmetler_gib_toplu_veri'] = $row['gib_toplu_veri'];
                    $_SESSION['mali_hizmetler_gayrimenkul_satis'] = $row['gayrimenkul_satis'];


                    $_SESSION['mali_hizmetler_vergi_borcu_yapilandirma'] = $row['vergi_borcu_yapilandirma'];


                    $_SESSION['mali_hizmetler_firma_odemeleri_firma'] = $row['firma_odemeleri_firma'];
                    $_SESSION['mali_hizmetler_firma_odemeleri_firma_alacagi'] = $row['firma_odemeleri_firma_alacagi'];
                    $_SESSION['mali_hizmetler_firma_odemeleri_firma_alacagi_odeme'] = $row['firma_odemeleri_firma_alacagi_odeme'];
                    $_SESSION['mali_hizmetler_firma_odemeleri_ayarlar'] = $row['firma_odemeleri_ayarlar'];
                    $_SESSION['mali_hizmetler_firma_odemeleri_gib_yetkisi'] = $row['firma_odemeleri_gib_yetkisi'];
                    $_SESSION['mali_hizmetler_firma_odemeleri_rapor_islemleri'] = $row['firma_odemeleri_rapor_islemleri'];


                    $_SESSION['mali_hizmetler_kidem_islemleri_kidim'] = $row['kidim_islemleri_kidem'];
                    $_SESSION['mali_hizmetler_sanal_pos_karsilastirma'] = $row['sanal_pos_karsilastirma'];


                    $this->loginlog((int)$row['id'], $row['tc'], $row['adi'] . ' ' . $row['soyadi'], $uygulama_id, GetIP(), $ldapusername, $kaynak);
                    // header('Location: index.php');
                    return $array = array("hata" => 0, "success" => 1, "uygulama_id" => $uygulama_id, "erisim_durumu" => $erisim_durumu);

                } else if ($erisim_durumu == 2) {
                    $this->loginerror($tcno, GetIP(), $uygulama_id, $ldapusername, $kaynak);
                    return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => $uygulama_adi . " Yetki Formu Bulunmamaktadır", "erisim_durumu" => $erisim_durumu);

                } else if ($erisim_durumu == 0) {
                    $this->loginerror($tcno, GetIP(), $uygulama_id, $ldapusername, $kaynak);
                    return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => $uygulama_adi . " Yetkisi Bulunamadı", "erisim_durumu" => $erisim_durumu);

                }
            }
        }
    }

    function metinalagoz_login($tcno, $ldapusername, $uygulama_id, $uygulama_adi, $kaynak)
    {
        $tcno = rescape($tcno);
        $ldapusername = rescape($ldapusername);
        $uygulama_id = rescape($uygulama_id);
        $uygulama_adi = rescape($uygulama_adi);
        $kaynak = rescape($kaynak);
        if (empty($kaynak)) {
            return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => "Parametre Eksik", "erisim_durumu" => "");
        } else if ($kaynak == "ldap" and (empty($tcno) or empty($ldapusername) or empty($uygulama_id) or empty($uygulama_adi))) {
            return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => "Parametre Eksik", "erisim_durumu" => "");
        } else if ($kaynak == "edevlet" and (empty($tcno) or empty($uygulama_id) or empty($uygulama_adi))) {
            return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => "Parametre Eksik", "erisim_durumu" => "");
        } else {
            $q1 = $this->query("SELECT * FROM metin_alagoz.yetkili WHERE tc='$tcno' and yetkili_durumu='1' ");
            $rowc1 = $this->num_rows($q1);
            if ($rowc1 < 1) {
                $this->loginerror($tcno, GetIP(), $uygulama_id, $ldapusername, $kaynak);
                return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => $uygulama_adi . " Yetkisi Bulunamadı");
            } else {
                $row = $this->fetch_assoc($q1);

                $erisim_durumu = "";

                ////$erisim_durumu = $this->erisim_kontrol($uygulama_id, 9, $tcno, $ldapusername);
                $erisim_durumu = 1;
                $erisim_durumu = 1;

                if ($erisim_durumu == 1) {
                    $_SESSION['metin_alagoz_admin_idd'] = $row['id'];
                    $_SESSION['metin_alagoz_admin_namee'] = $row['adi'] . ' ' . $row['soyadi'];
                    $_SESSION['metin_alagoz_admin_tc'] = $row['tc'];
                    $_SESSION['metin_alagoz_admin_cep_telefonu'] = $row['cep_telefonu'];

                    $this->loginlog((int)$row['id'], $row['tc'], $row['adi'] . ' ' . $row['soyadi'], $uygulama_id, GetIP(), $ldapusername, $kaynak);
                    // header('Location: index.php');
                    return $array = array("hata" => 0, "success" => 1, "uygulama_id" => $uygulama_id, "erisim_durumu" => $erisim_durumu);

                } else if ($erisim_durumu == 2) {
                    $this->loginerror($tcno, GetIP(), $uygulama_id, $ldapusername, $kaynak);
                    return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => $uygulama_adi . " Yetki Formu Bulunmamaktadır", "erisim_durumu" => $erisim_durumu);

                } else if ($erisim_durumu == 0) {
                    $this->loginerror($tcno, GetIP(), $uygulama_id, $ldapusername, $kaynak);
                    return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => $uygulama_adi . " Yetkisi Bulunamadı", "erisim_durumu" => $erisim_durumu);

                }
            }
        }
    }

    function muhtartakip_login($tcno, $ldapusername, $uygulama_id, $uygulama_adi, $kaynak)
    {
        $tcno = rescape($tcno);
        $ldapusername = rescape($ldapusername);
        $uygulama_id = rescape($uygulama_id);
        $uygulama_adi = rescape($uygulama_adi);
        $kaynak = rescape($kaynak);
        if (empty($kaynak)) {
            return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => "Parametre Eksik", "erisim_durumu" => "");
        } else if ($kaynak == "ldap" and (empty($tcno) or empty($ldapusername) or empty($uygulama_id) or empty($uygulama_adi))) {
            return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => "Parametre Eksik", "erisim_durumu" => "");
        } else if ($kaynak == "edevlet" and (empty($tcno) or empty($uygulama_id) or empty($uygulama_adi))) {
            return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => "Parametre Eksik", "erisim_durumu" => "");
        } else {
            $q1 = $this->query("SELECT * FROM muhtar_mobil.yetkili_mudurluk WHERE tc='$tcno' and yetkili_durumu='1' ");
            $rowc1 = $this->num_rows($q1);
            if ($rowc1 < 1) {
                $this->loginerror($tcno, GetIP(), $uygulama_id, $ldapusername, $kaynak);
                return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => $uygulama_adi . " Yetkisi Bulunamadı");
            } else {
                $row = $this->fetch_assoc($q1);

                $erisim_durumu = "";

                ////$erisim_durumu = $this->erisim_kontrol($uygulama_id, 9, $tcno, $ldapusername);
                $erisim_durumu = 1;
                $erisim_durumu = 1;

                if ($erisim_durumu == 1) {
                    $_SESSION['muhtar_takip_id'] = $row['id'];
                    $_SESSION['muhtar_takip_tc'] = $row['tc'];
                    $_SESSION['muhtar_takip_name'] = $row['adi'] . "  " . $row['soyadi'];
                    $_SESSION['muhtar_takip_yetkili_islemleri'] = $row['yetkili_islemleri'];
                    $_SESSION['muhtar_takip_yetkili_mudurluk'] = $row['mudurluk'];
                    $_SESSION['muhtar_takip_raporlama'] = $row['raporlama'];
                    $_SESSION['muhtar_takip_talep_olustur'] = $row['talep_olustur'];
                    $_SESSION['muhtar_takip_talep_takip'] = $row['talep_takip'];
                    $_SESSION['muhtar_takip_talep_cevap'] = $row['talep_cevap'];
                    $_SESSION['muhtar_takip_sosyal_yardim'] = $row['sosyal_yardim'];
                    $_SESSION['muhtar_takip_hasta_nakil'] = $row['hasta_nakil'];
                    $_SESSION['muhtar_takip_sms_islemleri'] = $row['sms_islemleri'];
                    $_SESSION['muhtar_takip_ayarlar'] = $row['ayarlar'];
                    $_SESSION['muhtar_takip_koordinator'] = $row['koordinator'];
                    $_SESSION['muhtar_takip_sosyal_yardim_import'] = $row['sosyal_yardim_import'];
                    $_SESSION['muhtar_takip_sosyal_yardim_sorgula'] = $row['sosyal_yardim_sorgula'];

                    $this->loginlog((int)$row['id'], $row['tc'], $row['adi'] . ' ' . $row['soyadi'], $uygulama_id, GetIP(), $ldapusername, $kaynak);
                    // header('Location: index.php');
                    return $array = array("hata" => 0, "success" => 1, "uygulama_id" => $uygulama_id, "erisim_durumu" => $erisim_durumu);

                } else if ($erisim_durumu == 2) {
                    $this->loginerror($tcno, GetIP(), $uygulama_id, $ldapusername, $kaynak);
                    return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => $uygulama_adi . " Yetki Formu Bulunmamaktadır", "erisim_durumu" => $erisim_durumu);

                } else if ($erisim_durumu == 0) {
                    $this->loginerror($tcno, GetIP(), $uygulama_id, $ldapusername, $kaynak);
                    return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => $uygulama_adi . " Yetkisi Bulunamadı", "erisim_durumu" => $erisim_durumu);

                }
            }
        }
    }

    function personeltakip_login($tcno, $ldapusername, $uygulama_id, $uygulama_adi, $kaynak)
    {
        $tcno = rescape($tcno);
        $ldapusername = rescape($ldapusername);
        $uygulama_id = rescape($uygulama_id);
        $uygulama_adi = rescape($uygulama_adi);
        $kaynak = rescape($kaynak);
        if (empty($kaynak)) {
            return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => "Parametre Eksik", "erisim_durumu" => "");
        } else if ($kaynak == "ldap" and (empty($tcno) or empty($ldapusername) or empty($uygulama_id) or empty($uygulama_adi))) {
            return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => "Parametre Eksik", "erisim_durumu" => "");
        } else if ($kaynak == "edevlet" and (empty($tcno) or empty($uygulama_id) or empty($uygulama_adi))) {
            return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => "Parametre Eksik", "erisim_durumu" => "");
        } else {
            $q1 = $this->query("SELECT * FROM personel_takip.yetkili WHERE tc='$tcno' and yetkili_durumu='1' ");
            $rowc1 = $this->num_rows($q1);
            if ($rowc1 < 1) {
                $this->loginerror($tcno, GetIP(), $uygulama_id, $ldapusername, $kaynak);
                return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => $uygulama_adi . " Yetkisi Bulunamadı");
            } else {
                $row = $this->fetch_assoc($q1);

                $erisim_durumu = "";

                ////$erisim_durumu = $this->erisim_kontrol($uygulama_id, 9, $tcno, $ldapusername);
                $erisim_durumu = 1;
                $erisim_durumu = 1;

                if ($erisim_durumu == 1) {
                    $_SESSION['personel_takip_admin_id'] = $row['id'];
                    $_SESSION['personel_takip_admin_name'] = $row['adi'] . "  " . $row['soyadi'];
                    $_SESSION['personel_takip_yetkili_islemleri'] = $row['yetkili_islemleri'];
                    $_SESSION['personel_takip_raporlama'] = $row['raporlama'];
                    $_SESSION['personel_takip_bugun_doganlar'] = $row['bugun_doganlar'];
                    $_SESSION['personel_takip_sil'] = $row['sil'];
                    $_SESSION['personel_takip_ihale_aktarma'] = $row['ihale_aktarma'];
                    $_SESSION['personel_takip_kaydet'] = $row['kaydet'];
                    $_SESSION['personel_takip_personel_ekle'] = $row['personel_ekle'];
                    $_SESSION['personel_takip_personel_duzenle'] = $row['personel_duzenle'];
                    $_SESSION['personel_takip_personel_izinleri'] = $row['personel_izinleri'];
                    $_SESSION['personel_takip_grafikler'] = $row['grafikler'];
                    $_SESSION['personel_ayrilan_personeller'] = $row['ayrilan_personeller'];
                    $_SESSION['personel_kadrolu_personel'] = $row['kadrolu_personel'];
                    $_SESSION['personel_takip_personel_giris_cikis'] = $row['personel_giris_cikis'];

                    $_SESSION['personel_ihale_kaydet'] = $row['ihale_kaydet'];
                    $_SESSION['personel_ihale_sil'] = $row['ihale_sil'];
                    $_SESSION['personel_ihale_guncelle'] = $row['ihale_guncelle'];
                    $_SESSION['personel_izin_kaydet'] = $row['izin_kaydet'];
                    $_SESSION['personel_izin_sil'] = $row['izin_sil'];
                    $_SESSION['personel_takip_toplu_excel_sms'] = $row['toplu_excel_sms'];
                    $_SESSION['personel_takip_fotografli_excel'] = $row['fotografli_excel'];

                    $_SESSION['personel_takip_tc'] = $row['tc'];
                    $_SESSION['personel_takip_cep_telefonu'] = $row['cep_telefonu'];

                    $this->loginlog((int)$row['id'], $row['tc'], $row['adi'] . ' ' . $row['soyadi'], $uygulama_id, GetIP(), $ldapusername, $kaynak);
                    // header('Location: index.php');
                    return $array = array("hata" => 0, "success" => 1, "uygulama_id" => $uygulama_id, "erisim_durumu" => $erisim_durumu);

                } else if ($erisim_durumu == 2) {
                    $this->loginerror($tcno, GetIP(), $uygulama_id, $ldapusername, $kaynak);
                    return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => $uygulama_adi . " Yetki Formu Bulunmamaktadır", "erisim_durumu" => $erisim_durumu);

                } else if ($erisim_durumu == 0) {
                    $this->loginerror($tcno, GetIP(), $uygulama_id, $ldapusername, $kaynak);
                    return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => $uygulama_adi . " Yetkisi Bulunamadı", "erisim_durumu" => $erisim_durumu);

                }
            }
        }
    }

    function syimyardim_login($tcno, $ldapusername, $uygulama_id, $uygulama_adi, $kaynak)
    {
        $tcno = rescape($tcno);
        $ldapusername = rescape($ldapusername);
        $uygulama_id = rescape($uygulama_id);
        $uygulama_adi = rescape($uygulama_adi);
        $kaynak = rescape($kaynak);
        if (empty($kaynak)) {
            return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => "Parametre Eksik", "erisim_durumu" => "");
        } else if ($kaynak == "ldap" and (empty($tcno) or empty($ldapusername) or empty($uygulama_id) or empty($uygulama_adi))) {
            return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => "Parametre Eksik", "erisim_durumu" => "");
        } else if ($kaynak == "edevlet" and (empty($tcno) or empty($uygulama_id) or empty($uygulama_adi))) {
            return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => "Parametre Eksik", "erisim_durumu" => "");
        } else {
            $q1 = $this->query("SELECT * FROM syim_yardim_listesi.yetkili WHERE tc='$tcno' and yetkili_durumu='1' ");
            $rowc1 = $this->num_rows($q1);
            if ($rowc1 < 1) {
                $this->loginerror($tcno, GetIP(), $uygulama_id, $ldapusername, $kaynak);
                return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => $uygulama_adi . " Yetkisi Bulunamadı");
            } else {
                $row = $this->fetch_assoc($q1);

                $erisim_durumu = "";

                ////$erisim_durumu = $this->erisim_kontrol($uygulama_id, 9, $tcno, $ldapusername);
                $erisim_durumu = 1;
                $erisim_durumu = 1;

                if ($erisim_durumu == 1) {
                    $_SESSION['syim_yardim_listesi_admin_yetkili_durumu'] = $row['yetkili_durumu'];
                    $_SESSION['syim_yardim_listesi_admin_id'] = $row['id'];
                    $_SESSION['syim_yardim_listesi_admin_name'] = $row['adi'];
                    $_SESSION['syim_yardim_listesi_admin_tc'] = $row['tc'];
                    $_SESSION['syim_yardim_listesi_admin_cep_telefonu'] = $row['cep_telefonu'];

                    $this->loginlog((int)$row['id'], $row['tc'], $row['adi'] . ' ' . $row['soyadi'], $uygulama_id, GetIP(), $ldapusername, $kaynak);
                    // header('Location: index.php');
                    return $array = array("hata" => 0, "success" => 1, "uygulama_id" => $uygulama_id, "erisim_durumu" => $erisim_durumu);

                } else if ($erisim_durumu == 2) {
                    $this->loginerror($tcno, GetIP(), $uygulama_id, $ldapusername, $kaynak);
                    return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => $uygulama_adi . " Yetki Formu Bulunmamaktadır", "erisim_durumu" => $erisim_durumu);

                } else if ($erisim_durumu == 0) {
                    $this->loginerror($tcno, GetIP(), $uygulama_id, $ldapusername, $kaynak);
                    return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => $uygulama_adi . " Yetkisi Bulunamadı", "erisim_durumu" => $erisim_durumu);

                }
            }
        }
    }

    function sosyalyardim_login($tcno, $ldapusername, $uygulama_id, $uygulama_adi, $kaynak)
    {
        $tcno = rescape($tcno);
        $ldapusername = rescape($ldapusername);
        $uygulama_id = rescape($uygulama_id);
        $uygulama_adi = rescape($uygulama_adi);
        $kaynak = rescape($kaynak);
        if (empty($kaynak)) {
            return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => "Parametre Eksik", "erisim_durumu" => "");
        } else if ($kaynak == "ldap" and (empty($tcno) or empty($ldapusername) or empty($uygulama_id) or empty($uygulama_adi))) {
            return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => "Parametre Eksik", "erisim_durumu" => "");
        } else if ($kaynak == "edevlet" and (empty($tcno) or empty($uygulama_id) or empty($uygulama_adi))) {
            return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => "Parametre Eksik", "erisim_durumu" => "");
        } else {
            $q1 = $this->query("SELECT * FROM sosyal_yardim.yetkili WHERE tc='$tcno' and yetkili_durumu='1' ");
            $rowc1 = $this->num_rows($q1);
            if ($rowc1 < 1) {
                $this->loginerror($tcno, GetIP(), $uygulama_id, $ldapusername, $kaynak);
                return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => $uygulama_adi . " Yetkisi Bulunamadı");
            } else {
                $row = $this->fetch_assoc($q1);

                $erisim_durumu = "";

                ////$erisim_durumu = $this->erisim_kontrol($uygulama_id, 9, $tcno, $ldapusername);
                $erisim_durumu = 1;
                $erisim_durumu = 1;

                if ($erisim_durumu == 1) {
                    $_SESSION['sosyal_yardim_admin_id'] = (int)$row['id'];
                    $_SESSION['sosyal_yardim_admin_tc'] = $row['tc'];
                    $_SESSION['sosyal_yardim_admin_name'] = $row['adi'] . ' ' . $row['soyadi'];

                    $_SESSION['sosyal_yardim_yetkili_islemleri'] = $row['yetkili_islemleri'];
                    $_SESSION['sosyal_yardim_engelli_tanimlama'] = $row['engelli_tanimlama'];
                    $_SESSION['sosyal_yardim_engelli_duzenleme'] = $row['engelli_duzenle'];
                    $_SESSION['sosyal_yardim_yetkili_islemleri'] = $row['yetkili_islemleri'];
                    $_SESSION['sosyal_yardim_randevu_ekle'] = $row['randevu_ekle'];
                    $_SESSION['sosyal_yardim_randevu_duzenle'] = $row['randevu_duzenle'];
                    $_SESSION['sosyal_yardim_randevu_takvimi'] = $row['randevu_takvimi'];
                    $_SESSION['sosyal_yardim_etkinlik_ekle'] = $row['etkinlik_ekle'];
                    $_SESSION['sosyal_yardim_etkinlik_duzenle'] = $row['etkinlik_duzenle'];
                    $_SESSION['sosyal_yardim_etkinlik_takvimi'] = $row['etkinlik_takvimi'];
                    $_SESSION['sosyal_yardim_excel_rapor'] = $row['excel_rapor'];
                    $_SESSION['sosyal_yardim_sms'] = $row['sms'];
                    $_SESSION['sosyal_yardim_ayarlar'] = $row['ayarlar'];
                    $_SESSION['sosyal_yardim_stok_cikis_ekle'] = $row['stok_cikis_ekle'];
                    $_SESSION['sosyal_yardim_stok_cikis_duzenle'] = $row['stok_cikis_duzenle'];
                    $_SESSION['sosyal_yardim_stok_giris_ekle'] = $row['stok_giris_ekle'];
                    $_SESSION['sosyal_yardim_stok_giris_duzenle'] = $row['stok_giris_duzenle'];
                    $_SESSION['sosyal_yardim_stok_urun_tanimlama'] = $row['stok_urun_tanimlama'];
                    $_SESSION['sosyal_yardim_stok_urun_duzenleme'] = $row['stok_urun_duzenleme'];
                    $_SESSION['sosyal_yardim_stok_durum_raporu'] = $row['stok_durum_raporu'];
                    $_SESSION['sosyal_yardim_kaydet'] = $row['kaydet'];
                    $_SESSION['sosyal_yardim_sil'] = $row['sil'];
                    $_SESSION['sosyal_yardim_form_ayarlari'] = $row['form_ayarlari'];
                    $_SESSION['sosyal_yardim_kan_bankasi'] = $row['kan_bankasi'];

                    $this->loginlog((int)$row['id'], $row['tc'], $row['adi'] . ' ' . $row['soyadi'], $uygulama_id, GetIP(), $ldapusername, $kaynak);
                    // header('Location: index.php');
                    return $array = array("hata" => 0, "success" => 1, "uygulama_id" => $uygulama_id, "erisim_durumu" => $erisim_durumu);

                } else if ($erisim_durumu == 2) {
                    $this->loginerror($tcno, GetIP(), $uygulama_id, $ldapusername, $kaynak);
                    return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => $uygulama_adi . " Yetki Formu Bulunmamaktadır", "erisim_durumu" => $erisim_durumu);

                } else if ($erisim_durumu == 0) {
                    $this->loginerror($tcno, GetIP(), $uygulama_id, $ldapusername, $kaynak);
                    return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => $uygulama_adi . " Yetkisi Bulunamadı", "erisim_durumu" => $erisim_durumu);

                }
            }
        }
    }

    function togem_login($tcno, $ldapusername, $uygulama_id, $uygulama_adi, $kaynak)
    {
        $tcno = rescape($tcno);
        $ldapusername = rescape($ldapusername);
        $uygulama_id = rescape($uygulama_id);
        $uygulama_adi = rescape($uygulama_adi);
        $kaynak = rescape($kaynak);
        if (empty($kaynak)) {
            return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => "Parametre Eksik", "erisim_durumu" => "");
        } else if ($kaynak == "ldap" and (empty($tcno) or empty($ldapusername) or empty($uygulama_id) or empty($uygulama_adi))) {
            return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => "Parametre Eksik", "erisim_durumu" => "");
        } else if ($kaynak == "edevlet" and (empty($tcno) or empty($uygulama_id) or empty($uygulama_adi))) {
            return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => "Parametre Eksik", "erisim_durumu" => "");
        } else {
            $q1 = $this->query("SELECT * FROM togem.yetkili WHERE tc='$tcno' and yetkili_durumu='1' ");
            $rowc1 = $this->num_rows($q1);
            if ($rowc1 < 1) {
                $this->loginerror($tcno, GetIP(), $uygulama_id, $ldapusername, $kaynak);
                return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => $uygulama_adi . " Yetkisi Bulunamadı");
            } else {
                $row = $this->fetch_assoc($q1);

                $erisim_durumu = "";

                ////$erisim_durumu = $this->erisim_kontrol($uygulama_id, 9, $tcno, $ldapusername);
                $erisim_durumu = 1;
                $erisim_durumu = 1;

                if ($erisim_durumu == 1) {
                    $_SESSION['togem_admin_id'] = $row['id'];
                    $_SESSION['togem_admin_name'] = $row['adi'] . ' ' . $row['soyadi'];
                    $_SESSION['togem_admin_tc'] = $row['tc'];
                    $_SESSION['togem_admin_cep_telefonu'] = $row['cep_telefonu'];
                    $_SESSION['togem_perm_basvurular'] = $row['basvurular'];
                    $_SESSION['togem_perm_toplu_sms'] = $row['toplu_sms'];
                    $_SESSION['togem_perm_ayarlar'] = $row['ayarlar'];
                    $_SESSION['togem_perm_stok'] = $row['stok'];
                    $_SESSION['togem_perm_yetkili'] = $row['yetkili'];
                    $_SESSION['togem_perm_sik_kullanilanlar'] = $row['sik_kullanilanlar'];
                    $_SESSION['togem_perm_gonderilen_excel_toplu_sms_silme'] = $row['gonderilen_excel_toplu_sms_silme'];
                    $_SESSION['togem_perm_rapor'] = $row['rapor'];

                    $this->loginlog((int)$row['id'], $row['tc'], $row['adi'] . ' ' . $row['soyadi'], $uygulama_id, GetIP(), $ldapusername, $kaynak);
                    // header('Location: index.php');
                    return $array = array("hata" => 0, "success" => 1, "uygulama_id" => $uygulama_id, "erisim_durumu" => $erisim_durumu);

                } else if ($erisim_durumu == 2) {
                    $this->loginerror($tcno, GetIP(), $uygulama_id, $ldapusername, $kaynak);
                    return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => $uygulama_adi . " Yetki Formu Bulunmamaktadır", "erisim_durumu" => $erisim_durumu);

                } else if ($erisim_durumu == 0) {
                    $this->loginerror($tcno, GetIP(), $uygulama_id, $ldapusername, $kaynak);
                    return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => $uygulama_adi . " Yetkisi Bulunamadı", "erisim_durumu" => $erisim_durumu);

                }
            }
        }
    }

    function ybs_login($tcno, $ldapusername, $uygulama_id, $uygulama_adi, $kaynak)
    {
        $tcno = rescape($tcno);
        $ldapusername = rescape($ldapusername);
        $uygulama_id = rescape($uygulama_id);
        $uygulama_adi = rescape($uygulama_adi);
        $kaynak = rescape($kaynak);
        if (empty($kaynak)) {
            return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => "Parametre Eksik", "erisim_durumu" => "");
        } else if ($kaynak == "ldap" and (empty($tcno) or empty($ldapusername) or empty($uygulama_id) or empty($uygulama_adi))) {
            return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => "Parametre Eksik", "erisim_durumu" => "");
        } else if ($kaynak == "edevlet" and (empty($tcno) or empty($uygulama_id) or empty($uygulama_adi))) {
            return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => "Parametre Eksik", "erisim_durumu" => "");
        } else {
            $q1 = $this->query("SELECT * FROM ybs.yetkili WHERE tc='$tcno' and yetkili_durumu='1' ");
            $rowc1 = $this->num_rows($q1);
            if ($rowc1 < 1) {
                $this->loginerror($tcno, GetIP(), $uygulama_id, $ldapusername, $kaynak);
                return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => $uygulama_adi . " Yetkisi Bulunamadı");
            } else {
                $row = $this->fetch_assoc($q1);

                $erisim_durumu = "";

                ////$erisim_durumu = $this->erisim_kontrol($uygulama_id, 9, $tcno, $ldapusername);
                $erisim_durumu = 1;
                $erisim_durumu = 1;

                if ($erisim_durumu == 1) {
                    $_SESSION['ybs_yetkili_durumu'] = $row['yetkili_durumu'];
                    $_SESSION['ybs_id'] = $row['id'];
                    $_SESSION['ybs_name'] = $row['adi'] . ' ' . $row['soyadi'];
                    $_SESSION['ybs_tc'] = $row['tc'];

                    $_SESSION['ybs_yil_gelir'] = $row['yil_gelir'];
                    $_SESSION['ybs_mudurluk_gider'] = $row['mudurluk_gider'];
                    $_SESSION['ybs_tarih_tahsilat'] = $row['tarih_tahsilat'];
                    $_SESSION['ybs_paket_tahsilat'] = $row['paket_tahsilat'];
                    $_SESSION['ybs_gelir_kalemi_tahsilat'] = $row['gelir_kalemi_tahsilat'];
                    $_SESSION['ybs_vezne_tahsilat'] = $row['vezne_tahsilat'];
                    $_SESSION['ybs_paket_borc'] = $row['paket_borc'];
                    $_SESSION['ybs_gelir_kalemi_borc'] = $row['gelir_kalemi_borc'];
                    $_SESSION['ybs_yil_borc'] = $row['yil_borc'];
                    $_SESSION['ybs_en_borclu_mukellef'] = $row['en_borclu_mukellef'];
                    $_SESSION['ybs_mukellef_istatistik'] = $row['mukellef_istatistik'];
                    $_SESSION['ybs_personel_takip'] = $row['personel_takip'];
                    $_SESSION['ybs_ihale_takip'] = $row['ihale_takip'];
                    $_SESSION['ybs_akmasa'] = $row['akmasa'];
                    $_SESSION['ybs_sosyal_yardim'] = $row['sosyal_yardim'];
                    $_SESSION['ybs_ebys'] = $row['ebys'];
                    $_SESSION['ybs_evlendirme_cenaze'] = $row['evlendirme_cenaze'];
                    $_SESSION['ybs_kultur_sosyal_md'] = $row['kultur_sosyal_md'];
                    $_SESSION['ybs_sosyal_yardim_md'] = $row['sosyal_yardim_md'];
                    $_SESSION['ybs_zabita_md'] = $row['zabita_md'];
                    $_SESSION['ybs_yetkili_islemleri'] = $row['yetkili_islemleri'];
                    $_SESSION['ybs_tc_sorgulama'] = $row['tc_sorgulama'];
                    $_SESSION['ybs_gib_sorgulama'] = $row['gib_sorgulama'];

                    $this->loginlog((int)$row['id'], $row['tc'], $row['adi'] . ' ' . $row['soyadi'], $uygulama_id, GetIP(), $ldapusername, $kaynak);
                    // header('Location: index.php');
                    return $array = array("hata" => 0, "success" => 1, "uygulama_id" => $uygulama_id, "erisim_durumu" => $erisim_durumu);

                } else if ($erisim_durumu == 2) {
                    $this->loginerror($tcno, GetIP(), $uygulama_id, $ldapusername, $kaynak);
                    return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => $uygulama_adi . " Yetki Formu Bulunmamaktadır", "erisim_durumu" => $erisim_durumu);

                } else if ($erisim_durumu == 0) {
                    $this->loginerror($tcno, GetIP(), $uygulama_id, $ldapusername, $kaynak);
                    return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => $uygulama_adi . " Yetkisi Bulunamadı", "erisim_durumu" => $erisim_durumu);

                }
            }
        }
    }

    function zabitatakip_login($tcno, $ldapusername, $uygulama_id, $uygulama_adi, $kaynak)
    {
        $tcno = rescape($tcno);
        $ldapusername = rescape($ldapusername);
        $uygulama_id = rescape($uygulama_id);
        $uygulama_adi = rescape($uygulama_adi);
        $kaynak = rescape($kaynak);
        if (empty($kaynak)) {
            return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => "Parametre Eksik", "erisim_durumu" => "");
        } else if ($kaynak == "ldap" and (empty($tcno) or empty($ldapusername) or empty($uygulama_id) or empty($uygulama_adi))) {
            return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => "Parametre Eksik", "erisim_durumu" => "");
        } else if ($kaynak == "edevlet" and (empty($tcno) or empty($uygulama_id) or empty($uygulama_adi))) {
            return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => "Parametre Eksik", "erisim_durumu" => "");
        } else {
            $q1 = $this->query("SELECT
                                    yetkili.id,
                                    yetkili.tc,
                                    yetkili.adi,
                                    yetkili.soyadi,
                                    yetkili.`password`,
                                    yetkili.salt,
                                    yetkili.pazar_fiyatlari,
                                    yetkili_faaliyet_istek.faaliyet_islemleri,
                                    yetkili_faaliyet_istek.gelen_faaliyetler,
                                    yetkili_faaliyet_istek.vazife_listesi,
                                    yetkili_faaliyet_istek.gelen_vazife_listesi,
                                    yetkili_faaliyet_istek.ayarlar,
                                    yetkili_faaliyet_istek.gorev_kategorisi,
                                    yetkili_faaliyet_istek.yetkili_islemleri,
                                    yetkili_faaliyet_istek.faaliyet_onayla,
                                    yetkili_faaliyet_istek.faaliyet_onay_kaldir,
                                    yetkili_faaliyet_istek.vazife_onayla,
                                    yetkili_faaliyet_istek.vazife_onay_kaldir,
                                    yetkili_faaliyet_istek.faaliyet_gunu_sil,
                                    yetkili_faaliyet_istek.faaliyet_detay_sil,
                                    yetkili_faaliyet_istek.vazife_gunu_sil,
                                    yetkili_faaliyet_istek.vazife_detay_sil,
                                    yetkili_faaliyet_istek.faaliyet_detay_ekle,
                                    yetkili_faaliyet_istek.faaliyet_tarih_arasi_raporu,
                                    yetkili_faaliyet_istek.faaliyet_gunu_ekle,
                                    yetkili_faaliyet_istek.vazife_gunu_ekle,
                                    yetkili_faaliyet_istek.vazife_detay_ekle,
                                    yetkili_faaliyet_istek.yeni_istek_ekle,
                                    yetkili_faaliyet_istek.yonlendirilmemis_istekler,
                                    yetkili_faaliyet_istek.yonlendirilen_istekler,
                                    yetkili_faaliyet_istek.karakol_birim_istekler,
                                    yetkili_faaliyet_istek.istek_kaydet,
                                    yetkili_faaliyet_istek.istek_sil,
                                    yetkili_faaliyet_istek.istek_sonuclandir,
                                    yetkili_faaliyet_istek.istek_onayla,
                                    yetkili_faaliyet_istek.istek_guncelle,
                                    yetkili_faaliyet_istek.istek_onay_kaldir,
                                    yetkili_faaliyet_istek.istek_yonlendir,
                                    yetkili_faaliyet_istek.istek_karakol_yetki,
                                    yetkili_faaliyet_istek.pazarci_listesi,
                                    yetkili_faaliyet_istek.ceza_sicil_idari_yaptirim_teslim,
                                    yetkili_faaliyet_istek.denetimler,
                                    yetkili_faaliyet_istek.denetim_kaydet,
                                    yetkili_faaliyet_istek.denetim_sil,
                                    yetkili_faaliyet_istek.denetim_onayla,
                                    yetkili_faaliyet_istek.mobil_yetkili_islemleri,
                                    yetkili_faaliyet_istek.mazeretli_tezgah,
                                    yetkili_faaliyet_istek.tum_denetimler,
                                    yetkili_faaliyet_istek.tum_pazarci_denetimler,
                                    yetkili_faaliyet_istek.istek_tumu,
                                    yetkili_faaliyet_istek.istek_icmali,
                                    yetkili_faaliyet_istek.ebys_birim_gelen,
                                    yetkili_faaliyet_istek.ebys_alt_birim_gelen,
                                    yetkili.tc_sorgulama,
                                    yetkili.gib_sorgulama,
                                    yetkili_stok.stok_ayarlar,
                                    yetkili_stok.stok_islemleri,
                                    yetkili_stok.stok_kaydet,
                                    yetkili_stok.stok_sil,
                                    yetkili_stok.stok_yetkili_islemleri,
                                    yetkili_cs.durum_tespit,
                                    yetkili_cs.durum_tespit_encumen,
                                    yetkili_cs.durum_tespit_mali_hiz,
                                    yetkili_cs.idari_yaptirim,
                                    yetkili_cs.idari_yaptirim_mali_hiz,
                                    yetkili_cs.idari_yaptirim_ceza_sicil,
                                    yetkili_cs.idari_yaptirim_mali_hiz_yatirma,
                                    yetkili_cs.idari_yaptirim_vezne_rapor,
                                    yetkili_cs.cs_kaydet,
                                    yetkili_cs.cs_sil,
                                    yetkili_cs.cs_excel,
                                    yetkili_cs.cs_yetkili_islemleri,
                                    yetkili_cs.idari_yaptirim_tahsilat_rapor,
                                    yetkili_cs.cs_ruhsatsiz_faaliyet,
                                    yetkili_cs.emniyete_sevk_edilmemis,
                                    yetkili_iase.iase_hukumlu,
                                    yetkili_iase.iase_yetkili_islemleri,
                                    yetkili_iase.iase_kaydet,
                                    yetkili_iase.iase_sil,
                                    yetkili_iase.iase_excel,
                                    yetkili_iase.iase_ebys,
                                    yetkili_mobil.denetim,
                                    yetkili_mobil.gbt,
                                    yetkili_mobil.pazarci,
                                    yetkili_mobil.kaydet,
                                    yetkili_mobil.sil
                                    FROM
                                    zabita_takip.yetkili
                                    LEFT JOIN zabita_takip.yetkili_faaliyet_istek ON yetkili.id = yetkili_faaliyet_istek.yetkili_id
                                    LEFT JOIN zabita_takip.yetkili_stok ON yetkili.id = yetkili_stok.yetkili_id
                                    LEFT JOIN zabita_takip.yetkili_cs ON yetkili.id = yetkili_cs.yetkili_id
                                    LEFT JOIN zabita_takip.yetkili_iase ON yetkili.id = yetkili_iase.yetkili_id
                                    LEFT JOIN zabita_takip.yetkili_mobil ON yetkili.id = yetkili_mobil.yetkili_id
                                    WHERE yetkili.tc='$tcno' and yetkili.yetkili_durumu='1'  ");
            $rowc1 = $this->num_rows($q1);
            if ($rowc1 < 1) {
                $this->loginerror($tcno, GetIP(), $uygulama_id, $ldapusername, $kaynak);
                return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => $uygulama_adi . " Yetkisi Bulunamadı");
            } else {
                $row = $this->fetch_assoc($q1);

                $erisim_durumu = "";

                ////$erisim_durumu = $this->erisim_kontrol($uygulama_id, 9, $tcno, $ldapusername);
                $erisim_durumu = 1;
                $erisim_durumu = 1;

                if ($erisim_durumu == 1) {
                    $_SESSION['zabita_takip_admin_id'] = $row['id'];
                    $_SESSION['zabita_takip_admin_tc'] = $row['tc'];
                    $_SESSION['zabita_takip_admin_name'] = $row['adi'] . ' ' . $row['soyadi'];

                    $_SESSION['zabita_takip_pazar_fiyatlari'] = $row['pazar_fiyatlari'];

                    $_SESSION['zabita_takip_faaliyet_islemleri'] = $row['faaliyet_islemleri'];
                    $_SESSION['zabita_takip_gelen_faaliyetler'] = $row['gelen_faaliyetler'];
                    $_SESSION['zabita_takip_vazife_listesi'] = $row['vazife_listesi'];
                    $_SESSION['zabita_takip_gelen_vazife_listesi'] = $row['gelen_vazife_listesi'];
                    $_SESSION['zabita_takip_ayarlar'] = $row['ayarlar'];
                    $_SESSION['zabita_takip_gorev_kategorisi'] = $row['gorev_kategorisi'];
                    $_SESSION['zabita_takip_yetkili_islemleri'] = $row['yetkili_islemleri'];
                    $_SESSION['zabita_takip_faaliyet_onayla'] = $row['faaliyet_onayla'];
                    $_SESSION['zabita_takip_faaliyet_onay_kaldir'] = $row['faaliyet_onay_kaldir'];
                    $_SESSION['zabita_takip_vazife_onayla'] = $row['vazife_onayla'];
                    $_SESSION['zabita_takip_vazife_onay_kaldir'] = $row['vazife_onay_kaldir'];

                    $_SESSION['zabita_takip_faaliyet_gunu_sil'] = $row['faaliyet_gunu_sil'];
                    $_SESSION['zabita_takip_faaliyet_detay_sil'] = $row['faaliyet_detay_sil'];
                    $_SESSION['zabita_takip_vazife_gunu_sil'] = $row['vazife_gunu_sil'];
                    $_SESSION['zabita_takip_vazife_detay_sil'] = $row['vazife_detay_sil'];
                    $_SESSION['zabita_takip_faaliyet_detay_ekle'] = $row['faaliyet_detay_ekle'];
                    $_SESSION['zabita_takip_faaliyet_tarih_arasi_raporu'] = $row['faaliyet_tarih_arasi_raporu'];
                    $_SESSION['zabita_takip_faaliyet_gunu_ekle'] = $row['faaliyet_gunu_ekle'];
                    $_SESSION['zabita_takip_vazife_gunu_ekle'] = $row['vazife_gunu_ekle'];
                    $_SESSION['zabita_takip_vazife_detay_ekle'] = $row['vazife_detay_ekle'];
                    $_SESSION['zabita_takip_pazarci_listesi'] = $row['pazarci_listesi'];
                    $_SESSION['zabita_takip_ceza_sicil_idari_yaptirim_teslim'] = $row['ceza_sicil_idari_yaptirim_teslim'];

                    $_SESSION['zabita_takip_denetimler'] = $row['denetimler'];
                    $_SESSION['zabita_takip_denetim_kaydet'] = $row['denetim_kaydet'];
                    $_SESSION['zabita_takip_denetim_sil'] = $row['denetim_sil'];
                    $_SESSION['zabita_takip_denetim_onayla'] = $row['denetim_onayla'];
                    $_SESSION['zabita_takip_mobil_yetkili_islemleri'] = $row['mobil_yetkili_islemleri'];
                    $_SESSION['zabita_takip_mazeretli_tezgah'] = $row['mazeretli_tezgah'];
                    $_SESSION['zabita_takip_tum_denetimler'] = $row['tum_denetimler'];
                    $_SESSION['zabita_takip_tum_pazarci_denetimler'] = $row['tum_pazarci_denetimler'];

                    $_SESSION['zabita_takip_mobil_denetim'] = $row['denetim'];
                    $_SESSION['zabita_takip_mobil_gbt'] = $row['gbt'];
                    $_SESSION['zabita_takip_mobil_pazarci'] = $row['pazarci'];
                    $_SESSION['zabita_takip_mobil_kaydet'] = $row['kaydet'];
                    $_SESSION['zabita_takip_mobil_sil'] = $row['sil'];


                    $_SESSION['zabita_takip_tc_sorgulama'] = $row['tc_sorgulama'];
                    $_SESSION['zabita_takip_gib_sorgulama'] = $row['gib_sorgulama'];

                    $_SESSION['zabita_takip_ebys_birim_gelen'] = $row['ebys_birim_gelen'];
                    $_SESSION['zabita_takip_ebys_alt_birim_gelen'] = $row['ebys_alt_birim_gelen'];

                    if (!empty($row['istek_karakol_yetki'])) {
                        $_SESSION['zabita_takip_yeni_istek_ekle'] = $row['yeni_istek_ekle'];
                        $_SESSION['zabita_takip_yonlendirilmemis_istekler'] = $row['yonlendirilmemis_istekler'];
                        $_SESSION['zabita_takip_yonlendirilen_istekler'] = $row['yonlendirilen_istekler'];
                        $_SESSION['zabita_takip_karakol_birim_istekler'] = $row['karakol_birim_istekler'];
                        $_SESSION['zabita_takip_istek_kaydet'] = $row['istek_kaydet'];
                        $_SESSION['zabita_takip_istek_sil'] = $row['istek_sil'];
                        $_SESSION['zabita_takip_istek_sonuclandir'] = $row['istek_sonuclandir'];
                        $_SESSION['zabita_takip_istek_onayla'] = $row['istek_onayla'];
                        $_SESSION['zabita_takip_istek_guncelle'] = $row['istek_guncelle'];
                        $_SESSION['zabita_takip_istek_onay_kaldir'] = $row['istek_onay_kaldir'];
                        $_SESSION['zabita_takip_istek_karakol_yetki'] = $row['istek_karakol_yetki'];
                        $_SESSION['zabita_takip_istek_tumu'] = $row['istek_tumu'];
                        $_SESSION['zabita_takip_istek_icmali'] = $row['istek_icmali'];
                        $_SESSION['zabita_takip_istek_yonlendir'] = $row['istek_yonlendir'];


                        $q_istek_karakol = $this->query("SELECT
                                                        p_karakol.id,
                                                        p_karakol.p_karakol
                                                        FROM
                                                        zabita_takip.p_karakol
                                                        WHERE p_karakol.id='" . $row['istek_karakol_yetki'] . "'");
                        $row_istek_karakol = $this->fetch_assoc($q_istek_karakol);

                        $_SESSION['zabita_takip_istek_karakol_adi'] = $row_istek_karakol['p_karakol'];
                    }


                    $sql_zabita_merkezi_yetki = "SELECT
                                                    yetkili_karakol.yetkili_id,
                                                    p_karakol.id,
                                                    p_karakol.p_karakol,
                                                    p_karakol.p_karakol_tur
                                                    FROM
                                                    zabita_takip.yetkili_karakol
                                                    INNER JOIN zabita_takip.p_karakol ON yetkili_karakol.karakol_id = p_karakol.id
                                                    WHERE yetkili_id='" . $row['id'] . "' and p_karakol_tur=1 and p_karakol.id!=10";
                    $q_zabita_merkezi_yetki = $this->query($sql_zabita_merkezi_yetki);
                    $count_zabita_merkezi_yetki = $this->num_rows($q_zabita_merkezi_yetki);
                    if ($count_zabita_merkezi_yetki > 0) {
                        $_SESSION['zabita_takip_zabita_merkezi'] = 1;
                    }
                    $sql_faaliyet_yetkileri = "SELECT
                                                yetkili_karakol.yetkili_id,
                                                p_karakol.id,
                                                p_karakol.p_karakol,
                                                p_karakol.p_karakol_tur,
                                                yetkili_karakol.karakol_id
                                                FROM
                                                zabita_takip.yetkili_karakol
                                                INNER JOIN zabita_takip.p_karakol ON yetkili_karakol.karakol_id = p_karakol.id
                                                WHERE yetkili_id='" . $row['id'] . "'
                                                ORDER BY karakol_id";
                    $q_faaliyet_yetkileri = $this->query($sql_faaliyet_yetkileri);
                    while ($row_faaliyet_yetkileri = $this->fetch_assoc($q_faaliyet_yetkileri)) {
                        if ($row_faaliyet_yetkileri['karakol_id'] == 1)  //MURACAAT AMIRLIGI
                        {
                            $_SESSION['zabita_takip_muracaat'] = 1;
                        } else if ($row_faaliyet_yetkileri['karakol_id'] == 4) //KUSAT
                        {
                            $_SESSION['zabita_takip_kusat'] = 1;
                        } else if ($row_faaliyet_yetkileri['karakol_id'] == 5) //OLCU AYAR
                        {
                            $_SESSION['zabita_takip_olcu_ayar'] = 1;
                        } else if ($row_faaliyet_yetkileri['karakol_id'] == 7) //CEZA SİCİL
                        {
                            $_SESSION['zabita_takip_ceza_sicil'] = 1;
                        } else if ($row_faaliyet_yetkileri['karakol_id'] == 8) //KAPAMA AMİRLİĞİ
                        {
                            $_SESSION['zabita_takip_kapama'] = 1;
                        } else if ($row_faaliyet_yetkileri['karakol_id'] == 9) //TEMİZLİK İŞLERİNDE GÖREVLİ EKİP
                        {
                            $_SESSION['zabita_takip_temizlik'] = 1;
                        } else if ($row_faaliyet_yetkileri['karakol_id'] == 10) //SEYYAR EKİP
                        {
                            $_SESSION['zabita_takip_seyyar'] = 1;
                        } else if ($row_faaliyet_yetkileri['karakol_id'] == 28) //Gece Nöbetçi Amirliği (1. Grup)
                        {
                            $_SESSION['zabita_takip_gece_denetim_1'] = 1;
                        } else if ($row_faaliyet_yetkileri['karakol_id'] == 29) //Gece Nöbetçi Amirliği (2. Grup)
                        {
                            $_SESSION['zabita_takip_gece_denetim_2'] = 1;
                        } else if ($row_faaliyet_yetkileri['karakol_id'] == 33) //Çevre Denetim ve Kontrol Amirliği
                        {
                            $_SESSION['zabita_takip_cevre_denetim'] = 1;
                        }
                    }

                    $_SESSION['zabita_takip_stok_ayarlar'] = $row['stok_ayarlar'];
                    $_SESSION['zabita_takip_stok_islemleri'] = $row['stok_islemleri'];
                    $_SESSION['zabita_takip_stok_kaydet'] = $row['stok_kaydet'];
                    $_SESSION['zabita_takip_stok_sil'] = $row['stok_sil'];
                    $_SESSION['zabita_takip_stok_yetkili_islemleri'] = $row['stok_yetkili_islemleri'];

                    $_SESSION['zabita_takip_cs_durum_tespit'] = $row['durum_tespit'];
                    $_SESSION['zabita_takip_cs_durum_tespit_encumen'] = $row['durum_tespit_encumen'];
                    $_SESSION['zabita_takip_cs_durum_tespit_mali_hiz'] = $row['durum_tespit_mali_hiz'];
                    $_SESSION['zabita_takip_cs_idari_yaptirim'] = $row['idari_yaptirim'];
                    $_SESSION['zabita_takip_cs_idari_yaptirim_mali_hiz'] = $row['idari_yaptirim_mali_hiz'];
                    $_SESSION['zabita_takip_cs_idari_yaptirim_ceza_sicil'] = $row['idari_yaptirim_ceza_sicil'];
                    $_SESSION['zabita_takip_cs_idari_yaptirim_mali_hiz_yatirma'] = $row['idari_yaptirim_mali_hiz_yatirma'];
                    $_SESSION['zabita_takip_cs_idari_yaptirim_vezne_rapor'] = $row['idari_yaptirim_vezne_rapor'];
                    $_SESSION['zabita_takip_cs_kaydet'] = $row['cs_kaydet'];
                    $_SESSION['zabita_takip_cs_sil'] = $row['cs_sil'];
                    $_SESSION['zabita_takip_cs_excel'] = $row['cs_excel'];
                    $_SESSION['zabita_takip_cs_idari_yaptirim_tahsilat_rapor'] = $row['idari_yaptirim_tahsilat_rapor'];
                    $_SESSION['zabita_takip_cs_yetkili_islemleri'] = $row['cs_yetkili_islemleri'];
                    $_SESSION['zabita_takip_cs_ruhsatsiz_faaliyet'] = $row['cs_ruhsatsiz_faaliyet'];
                    $_SESSION['zabita_takip_cs_emniyete_sevk_edilmemis'] = $row['emniyete_sevk_edilmemis'];

                    $_SESSION['zabita_takip_iase_hukumlu'] = $row['iase_hukumlu'];
                    $_SESSION['zabita_takip_iase_yetkili_islemleri'] = $row['iase_yetkili_islemleri'];
                    $_SESSION['zabita_takip_iase_kaydet'] = $row['iase_kaydet'];
                    $_SESSION['zabita_takip_iase_sil'] = $row['iase_sil'];
                    $_SESSION['zabita_takip_iase_ebys'] = $row['iase_ebys'];
                    $_SESSION['zabita_takip_iase_excel'] = $row['iase_excel'];


                    $q_karakol_id = $this->query("SELECT
                    (SELECT
                    p_karakol.id
                    FROM
                    zabita_takip.p_karakol
                    WHERE p_karakol.personel_takip_id=zabita_personel_takip.zabita_merkezi.id) AS karakol_id,
                    zabita_personel_takip.zabita_merkezi.zabita_merkezi
                    FROM
                    zabita_personel_takip.personel_yer_degisiklik
                    INNER JOIN zabita_personel_takip.zabita_merkezi ON zabita_personel_takip.personel_yer_degisiklik.zabita_merkezi = zabita_personel_takip.zabita_merkezi.id
                    INNER JOIN zabita_personel_takip.personel ON zabita_personel_takip.personel_yer_degisiklik.personel_id = zabita_personel_takip.personel.id
                    WHERE (personel_yer_degisiklik.pasif=0 or personel_yer_degisiklik.pasif is null) and zabita_personel_takip.personel.tc='$tcno'");
                    $row_karakol_id = $this->fetch_assoc($q_karakol_id);
                    if (!empty($row_karakol_id['karakol_id'])) {
                        $_SESSION['zabita_takip_gorev_karakol_id'] = $row_karakol_id['karakol_id'];
                        $_SESSION['zabita_takip_gorev_karakol'] = $row_karakol_id['zabita_merkezi'];
                    }
                    if ($tcno == "13522172520") {
                        $_SESSION['zabita_takip_gorev_karakol_id'] = "12";
                        $_SESSION['zabita_takip_gorev_karakol'] = "Keçiören Merkezi";
                    }

                    $this->loginlog((int)$row['id'], $row['tc'], $row['adi'] . ' ' . $row['soyadi'], $uygulama_id, GetIP(), $ldapusername, $kaynak);
                    // header('Location: index.php');
                    return $array = array("hata" => 0, "success" => 1, "uygulama_id" => $uygulama_id, "erisim_durumu" => $erisim_durumu);

                } else if ($erisim_durumu == 2) {
                    $this->loginerror($tcno, GetIP(), $uygulama_id, $ldapusername, $kaynak);
                    return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => $uygulama_adi . " Yetki Formu Bulunmamaktadır", "erisim_durumu" => $erisim_durumu);

                } else if ($erisim_durumu == 0) {
                    $this->loginerror($tcno, GetIP(), $uygulama_id, $ldapusername, $kaynak);
                    return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => $uygulama_adi . " Yetkisi Bulunamadı", "erisim_durumu" => $erisim_durumu);

                }
            }
        }
    }

    function zabitatakip_tablet_login($tcno, $ldapusername, $uygulama_id, $uygulama_adi, $kaynak)
    {
        $tcno = rescape($tcno);
        $ldapusername = rescape($ldapusername);
        $uygulama_id = rescape($uygulama_id);
        $uygulama_adi = rescape($uygulama_adi);
        $kaynak = rescape($kaynak);
        if (empty($kaynak)) {
            return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => "Parametre Eksik", "erisim_durumu" => "");
        } else if ($kaynak == "ldap" and (empty($tcno) or empty($ldapusername) or empty($uygulama_id) or empty($uygulama_adi))) {
            return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => "Parametre Eksik", "erisim_durumu" => "");
        } else if ($kaynak == "edevlet" and (empty($tcno) or empty($uygulama_id) or empty($uygulama_adi))) {
            return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => "Parametre Eksik", "erisim_durumu" => "");
        } else {
            $q1 = $this->query("SELECT
                                            yetkili.id,
                                            yetkili.tc,
                                            yetkili.adi,
                                            yetkili.soyadi,
                                            yetkili.`password`,
                                            yetkili.salt,
                                            yetkili.tc_sorgulama,
                                            yetkili.gib_sorgulama,
                                            yetkili_mobil.denetim,
                                            yetkili_mobil.gbt,
                                            yetkili_mobil.pazarci,
                                            yetkili_mobil.kaydet,
                                            yetkili_mobil.sil,
                                            yetkili_faaliyet_istek.faaliyet_islemleri,
                                            yetkili_faaliyet_istek.gelen_faaliyetler,
                                            yetkili_faaliyet_istek.vazife_listesi,
                                            yetkili_faaliyet_istek.gelen_vazife_listesi,
                                            yetkili_faaliyet_istek.ayarlar,
                                            yetkili_faaliyet_istek.gorev_kategorisi,
                                            yetkili_faaliyet_istek.yetkili_islemleri,
                                            yetkili_faaliyet_istek.faaliyet_onayla,
                                            yetkili_faaliyet_istek.faaliyet_onay_kaldir,
                                            yetkili_faaliyet_istek.vazife_onayla,
                                            yetkili_faaliyet_istek.vazife_onay_kaldir,
                                            yetkili_faaliyet_istek.faaliyet_gunu_sil,
                                            yetkili_faaliyet_istek.faaliyet_detay_sil,
                                            yetkili_faaliyet_istek.vazife_gunu_sil,
                                            yetkili_faaliyet_istek.vazife_detay_sil,
                                            yetkili_faaliyet_istek.faaliyet_detay_ekle,
                                            yetkili_faaliyet_istek.faaliyet_tarih_arasi_raporu,
                                            yetkili_faaliyet_istek.faaliyet_gunu_ekle,
                                            yetkili_faaliyet_istek.vazife_gunu_ekle,
                                            yetkili_faaliyet_istek.vazife_detay_ekle,
                                            yetkili_faaliyet_istek.yeni_istek_ekle,
                                            yetkili_faaliyet_istek.yonlendirilmemis_istekler,
                                            yetkili_faaliyet_istek.yonlendirilen_istekler,
                                            yetkili_faaliyet_istek.karakol_birim_istekler,
                                            yetkili_faaliyet_istek.istek_kaydet,
                                            yetkili_faaliyet_istek.istek_sil,
                                            yetkili_faaliyet_istek.istek_sonuclandir,
                                            yetkili_faaliyet_istek.istek_onayla,
                                            yetkili_faaliyet_istek.istek_guncelle,
                                            yetkili_faaliyet_istek.istek_onay_kaldir,
                                            yetkili_faaliyet_istek.istek_yonlendir,
                                            yetkili_faaliyet_istek.istek_tumu,
                                            yetkili_faaliyet_istek.istek_icmali,
                                            yetkili_faaliyet_istek.ebys_birim_gelen,
                                            yetkili_faaliyet_istek.ebys_alt_birim_gelen,
                                            yetkili_faaliyet_istek.istek_karakol_yetki,
                                            yetkili_faaliyet_istek.pazarci_listesi,
                                            yetkili_faaliyet_istek.ceza_sicil_idari_yaptirim_teslim,
                                            yetkili_faaliyet_istek.denetimler,
                                            yetkili_faaliyet_istek.denetim_kaydet,
                                            yetkili_faaliyet_istek.denetim_sil,
                                            yetkili_faaliyet_istek.denetim_onayla,
                                            yetkili_faaliyet_istek.mobil_yetkili_islemleri,
                                            yetkili_faaliyet_istek.mazeretli_tezgah,
                                            yetkili_faaliyet_istek.denetimler as denetim_faaliyet,
                                            yetkili_faaliyet_istek.denetim_kaydet as denetim_kaydet_faaliyet,
                                            yetkili_faaliyet_istek.denetim_sil as denetim_sil_faaliyet,
                                            yetkili_faaliyet_istek.denetim_onayla as denetim_onayla_faaliyet
                                FROM 
                                zabita_takip.yetkili
                                LEFT JOIN zabita_takip.yetkili_mobil ON yetkili.id = yetkili_mobil.yetkili_id
                                LEFT JOIN zabita_takip.yetkili_faaliyet_istek ON yetkili.id = yetkili_faaliyet_istek.yetkili_id
                                WHERE yetkili.tc='$tcno' and yetkili.yetkili_durumu='1'  ");
            $rowc1 = $this->num_rows($q1);
            if ($rowc1 < 1) {
                $this->loginerror($tcno, GetIP(), $uygulama_id, $ldapusername, $kaynak);
                return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => $uygulama_adi . " Yetkisi Bulunamadı ");
            } else {
                $row = $this->fetch_assoc($q1);

                $erisim_durumu = "";

                ////$erisim_durumu = $this->erisim_kontrol($uygulama_id, 9, $tcno, $ldapusername);
                $erisim_durumu = 1;
                $erisim_durumu = 1;

                if ($erisim_durumu == 1) {
                    $_SESSION['zabita_takip_admin_id'] = $row['id'];
                    $_SESSION['zabita_takip_admin_tc'] = $row['tc'];
                    $_SESSION['zabita_takip_admin_name'] = $row['adi'] . ' ' . $row['soyadi'];

                    $_SESSION['zabita_takip_mobil_denetim'] = $row['denetim'];
                    $_SESSION['zabita_takip_mobil_gbt'] = $row['gbt'];
                    $_SESSION['zabita_takip_mobil_pazarci'] = $row['pazarci'];
                    $_SESSION['zabita_takip_mobil_kaydet'] = $row['kaydet'];
                    $_SESSION['zabita_takip_mobil_sil'] = $row['sil'];

                    $_SESSION['zabita_takip_tc_sorgulama'] = $row['tc_sorgulama'];
                    $_SESSION['zabita_takip_gib_sorgulama'] = $row['gib_sorgulama'];
                    $_SESSION['zabita_takip_mazeretli_tezgah'] = $row['mazeretli_tezgah'];

                    $_SESSION['zabita_takip_ebys_birim_gelen'] = $row['ebys_birim_gelen'];
                    $_SESSION['zabita_takip_ebys_alt_birim_gelen'] = $row['ebys_alt_birim_gelen'];

                    if (!empty($row['istek_karakol_yetki'])) {
                        $_SESSION['zabita_takip_yeni_istek_ekle'] = $row['yeni_istek_ekle'];
                        $_SESSION['zabita_takip_yonlendirilmemis_istekler'] = $row['yonlendirilmemis_istekler'];
                        $_SESSION['zabita_takip_yonlendirilen_istekler'] = $row['yonlendirilen_istekler'];
                        $_SESSION['zabita_takip_karakol_birim_istekler'] = $row['karakol_birim_istekler'];
                        $_SESSION['zabita_takip_istek_kaydet'] = $row['istek_kaydet'];
                        $_SESSION['zabita_takip_istek_sil'] = $row['istek_sil'];
                        $_SESSION['zabita_takip_istek_sonuclandir'] = $row['istek_sonuclandir'];
                        $_SESSION['zabita_takip_istek_onayla'] = $row['istek_onayla'];
                        $_SESSION['zabita_takip_istek_guncelle'] = $row['istek_guncelle'];
                        $_SESSION['zabita_takip_istek_onay_kaldir'] = $row['istek_onay_kaldir'];
                        $_SESSION['zabita_takip_istek_yonlendir'] = $row['istek_yonlendir'];
                        $_SESSION['zabita_takip_istek_tumu'] = $row['istek_tumu'];
                        $_SESSION['zabita_takip_istek_icmali'] = $row['istek_icmali'];
                        $_SESSION['zabita_takip_istek_karakol_yetki'] = $row['istek_karakol_yetki'];

                        $q_istek_karakol = $this->query("SELECT
                                                        p_karakol.id,
                                                        p_karakol.p_karakol
                                                        FROM
                                                        zabita_takip.p_karakol
                                                        WHERE p_karakol.id='" . $row['istek_karakol_yetki'] . "'");
                        $row_istek_karakol = $this->fetch_assoc($q_istek_karakol);

                        $_SESSION['zabita_takip_istek_karakol_adi'] = $row_istek_karakol['p_karakol'];
                    }

                    $_SESSION['zabita_takip_denetimler'] = $row['denetim_faaliyet'];
                    $_SESSION['zabita_takip_denetim_kaydet'] = $row['denetim_kaydet_faaliyet'];
                    $_SESSION['zabita_takip_denetim_sil'] = $row['denetim_sil_faaliyet'];
                    $_SESSION['zabita_takip_denetim_onayla'] = $row['denetim_onayla_faaliyet'];

                    $_SESSION['zabita_takip_faaliyet_islemleri'] = $row['faaliyet_islemleri'];
                    $_SESSION['zabita_takip_gelen_faaliyetler'] = $row['gelen_faaliyetler'];
                    $_SESSION['zabita_takip_vazife_listesi'] = $row['vazife_listesi'];
                    $_SESSION['zabita_takip_gelen_vazife_listesi'] = $row['gelen_vazife_listesi'];
                    $_SESSION['zabita_takip_ayarlar'] = $row['ayarlar'];
                    $_SESSION['zabita_takip_gorev_kategorisi'] = $row['gorev_kategorisi'];
                    $_SESSION['zabita_takip_yetkili_islemleri'] = $row['yetkili_islemleri'];
                    $_SESSION['zabita_takip_faaliyet_onayla'] = $row['faaliyet_onayla'];
                    $_SESSION['zabita_takip_faaliyet_onay_kaldir'] = $row['faaliyet_onay_kaldir'];
                    $_SESSION['zabita_takip_vazife_onayla'] = $row['vazife_onayla'];
                    $_SESSION['zabita_takip_vazife_onay_kaldir'] = $row['vazife_onay_kaldir'];

                    $_SESSION['zabita_takip_faaliyet_gunu_sil'] = $row['faaliyet_gunu_sil'];
                    $_SESSION['zabita_takip_faaliyet_detay_sil'] = $row['faaliyet_detay_sil'];
                    $_SESSION['zabita_takip_vazife_gunu_sil'] = $row['vazife_gunu_sil'];
                    $_SESSION['zabita_takip_vazife_detay_sil'] = $row['vazife_detay_sil'];
                    $_SESSION['zabita_takip_faaliyet_detay_ekle'] = $row['faaliyet_detay_ekle'];
                    $_SESSION['zabita_takip_faaliyet_tarih_arasi_raporu'] = $row['faaliyet_tarih_arasi_raporu'];
                    $_SESSION['zabita_takip_faaliyet_gunu_ekle'] = $row['faaliyet_gunu_ekle'];
                    $_SESSION['zabita_takip_vazife_gunu_ekle'] = $row['vazife_gunu_ekle'];
                    $_SESSION['zabita_takip_vazife_detay_ekle'] = $row['vazife_detay_ekle'];
                    $_SESSION['zabita_takip_pazarci_listesi'] = $row['pazarci_listesi'];
                    $_SESSION['zabita_takip_ceza_sicil_idari_yaptirim_teslim'] = $row['ceza_sicil_idari_yaptirim_teslim'];

                    $q_karakol_id = $this->query("SELECT
                                                            (SELECT
                                                            p_karakol.id
                                                            FROM
                                                            zabita_takip.p_karakol
                                                            WHERE p_karakol.personel_takip_id=zabita_personel_takip.zabita_merkezi.id) AS karakol_id,
                                                            zabita_personel_takip.zabita_merkezi.zabita_merkezi
                                                            FROM
                                                            zabita_personel_takip.personel_yer_degisiklik
                                                            INNER JOIN zabita_personel_takip.zabita_merkezi ON zabita_personel_takip.personel_yer_degisiklik.zabita_merkezi = zabita_personel_takip.zabita_merkezi.id
                                                            INNER JOIN zabita_personel_takip.personel ON zabita_personel_takip.personel_yer_degisiklik.personel_id = zabita_personel_takip.personel.id
                                                            WHERE (personel_yer_degisiklik.pasif=0 or personel_yer_degisiklik.pasif is null) and  zabita_personel_takip.personel.tc='$tcno'");
                    $row_karakol_id = $this->fetch_assoc($q_karakol_id);
                    if (!empty($row_karakol_id['karakol_id'])) {
                        $_SESSION['zabita_takip_gorev_karakol_id'] = $row_karakol_id['karakol_id'];
                        $_SESSION['zabita_takip_gorev_karakol'] = $row_karakol_id['zabita_merkezi'];
                    }
                    if ($tcno == "13522172520") {
                        $_SESSION['zabita_takip_gorev_karakol_id'] = "11";
                        $_SESSION['zabita_takip_gorev_karakol'] = "Etlik Zabıta Merkezi";
                    }

                    $sql_zabita_merkezi_yetki = "SELECT
                                                    yetkili_karakol.yetkili_id,
                                                    p_karakol.id,
                                                    p_karakol.p_karakol,
                                                    p_karakol.p_karakol_tur
                                                    FROM
                                                    zabita_takip.yetkili_karakol
                                                    INNER JOIN zabita_takip.p_karakol ON yetkili_karakol.karakol_id = p_karakol.id
                                                    WHERE yetkili_id='" . $row['id'] . "' and p_karakol_tur=1 and p_karakol.id!=10";
                    $q_zabita_merkezi_yetki = $this->query($sql_zabita_merkezi_yetki);
                    $count_zabita_merkezi_yetki = $this->num_rows($q_zabita_merkezi_yetki);
                    if ($count_zabita_merkezi_yetki > 0) {
                        $_SESSION['zabita_takip_zabita_merkezi'] = 1;
                    }
                    $sql_faaliyet_yetkileri = "SELECT
                                                        yetkili_karakol.yetkili_id,
                                                        p_karakol.id,
                                                        p_karakol.p_karakol,
                                                        p_karakol.p_karakol_tur,
                                                        yetkili_karakol.karakol_id
                                                        FROM
                                                        zabita_takip.yetkili_karakol
                                                        INNER JOIN zabita_takip.p_karakol ON yetkili_karakol.karakol_id = p_karakol.id
                                                        WHERE yetkili_id='" . $row['id'] . "'
                                                        ORDER BY karakol_id";
                    $q_faaliyet_yetkileri = $this->query($sql_faaliyet_yetkileri);
                    while ($row_faaliyet_yetkileri = $this->fetch_assoc($q_faaliyet_yetkileri)) {
                        if ($row_faaliyet_yetkileri['karakol_id'] == 1)  //MURACAAT AMIRLIGI
                        {
                            $_SESSION['zabita_takip_muracaat'] = 1;
                        } else if ($row_faaliyet_yetkileri['karakol_id'] == 4) //KUSAT
                        {
                            $_SESSION['zabita_takip_kusat'] = 1;
                        } else if ($row_faaliyet_yetkileri['karakol_id'] == 5) //OLCU AYAR
                        {
                            $_SESSION['zabita_takip_olcu_ayar'] = 1;
                        } else if ($row_faaliyet_yetkileri['karakol_id'] == 7) //CEZA SİCİL
                        {
                            $_SESSION['zabita_takip_ceza_sicil'] = 1;
                        } else if ($row_faaliyet_yetkileri['karakol_id'] == 8) //KAPAMA AMİRLİĞİ
                        {
                            $_SESSION['zabita_takip_kapama'] = 1;
                        } else if ($row_faaliyet_yetkileri['karakol_id'] == 9) //TEMİZLİK İŞLERİNDE GÖREVLİ EKİP
                        {
                            $_SESSION['zabita_takip_temizlik'] = 1;
                        } else if ($row_faaliyet_yetkileri['karakol_id'] == 10) //SEYYAR EKİP
                        {
                            $_SESSION['zabita_takip_seyyar'] = 1;
                        } else if ($row_faaliyet_yetkileri['karakol_id'] == 28) //Gece Nöbetçi Amirliği (1. Grup)
                        {
                            $_SESSION['zabita_takip_gece_denetim_1'] = 1;
                        } else if ($row_faaliyet_yetkileri['karakol_id'] == 29) //Gece Nöbetçi Amirliği (2. Grup)
                        {
                            $_SESSION['zabita_takip_gece_denetim_2'] = 1;
                        } else if ($row_faaliyet_yetkileri['karakol_id'] == 33) //Çevre Denetim ve Kontrol Amirliği
                        {
                            $_SESSION['zabita_takip_cevre_denetim'] = 1;
                        }
                    }

                    $this->loginlog((int)$row['id'], $row['tc'], $row['adi'] . ' ' . $row['soyadi'], $uygulama_id, GetIP(), $ldapusername, $kaynak);
                    // header('Location: index.php');
                    return $array = array("hata" => 0, "success" => 1, "uygulama_id" => $uygulama_id, "erisim_durumu" => $erisim_durumu);

                } else if ($erisim_durumu == 2) {
                    $this->loginerror($tcno, GetIP(), $uygulama_id, $ldapusername, $kaynak);
                    return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => $uygulama_adi . " Yetki Formu Bulunmamaktadır", "erisim_durumu" => $erisim_durumu);

                } else if ($erisim_durumu == 0) {
                    $this->loginerror($tcno, GetIP(), $uygulama_id, $ldapusername, $kaynak);
                    return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => $uygulama_adi . " Yetkisi Bulunamadı", "erisim_durumu" => $erisim_durumu);

                }
            }
        }
    }

    function zabitapazar_login($tcno, $ldapusername, $uygulama_id, $uygulama_adi, $kaynak)
    {
        $tcno = rescape($tcno);
        $ldapusername = rescape($ldapusername);
        $uygulama_id = rescape($uygulama_id);
        $uygulama_adi = rescape($uygulama_adi);
        $kaynak = rescape($kaynak);
        if (empty($kaynak)) {
            return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => "Parametre Eksik", "erisim_durumu" => "");
        } else if ($kaynak == "ldap" and (empty($tcno) or empty($ldapusername) or empty($uygulama_id) or empty($uygulama_adi))) {
            return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => "Parametre Eksik", "erisim_durumu" => "");
        } else if ($kaynak == "edevlet" and (empty($tcno) or empty($uygulama_id) or empty($uygulama_adi))) {
            return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => "Parametre Eksik", "erisim_durumu" => "");
        } else {
            $q1 = $this->query("SELECT * FROM zabita_takip.yetkili_pazar_buro WHERE tc='$tcno' and yetkili_durumu='1' ");
            $rowc1 = $this->num_rows($q1);
            if ($rowc1 < 1) {
                $this->loginerror($tcno, GetIP(), $uygulama_id, $ldapusername, $kaynak);
                return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => $uygulama_adi . " Yetkisi Bulunamadı");
            } else {
                $row = $this->fetch_assoc($q1);

                $erisim_durumu = "";

                ////$erisim_durumu = $this->erisim_kontrol($uygulama_id, 9, $tcno, $ldapusername);
                $erisim_durumu = 1;
                $erisim_durumu = 1;

                if ($erisim_durumu == 1) {
                    $_SESSION['zabita_pazar_buro_admin_id'] = (int)$row['id'];
                    $_SESSION['zabita_pazar_buro_admin_tc'] = $row['tc'];
                    $_SESSION['zabita_pazar_buro_admin_name'] = $row['adi'] . ' ' . $row['soyadi'];

                    $_SESSION['zabita_pazar_buro_pazarci_ekle'] = $row['pazarci_ekle'];
                    $_SESSION['zabita_pazar_buro_pazarci_duzenle'] = $row['pazarci_duzenle'];
                    $_SESSION['zabita_pazar_buro_encumene_sevk_edilmemis'] = $row['encumene_sevk_edilmemis'];
                    $_SESSION['zabita_pazar_buro_encumene_sevk_edilmis'] = $row['encumene_sevk_edilmis'];
                    $_SESSION['zabita_pazar_buro_pazar_ceza_listesi'] = $row['pazar_ceza_listesi'];
                    $_SESSION['zabita_pazar_buro_pazar_yeri_listesi'] = $row['pazar_yeri_listesi'];
                    $_SESSION['zabita_pazar_buro_pazar_faaliyet_fark'] = $row['pazar_faaliyet_fark'];
                    $_SESSION['zabita_pazar_buro_ayarlar'] = $row['ayarlar'];
                    $_SESSION['zabita_pazar_buro_yetkili_islemleri'] = $row['yetkili_islemleri'];
                    $_SESSION['zabita_pazar_buro_sms'] = $row['sms'];
                    $_SESSION['zabita_pazar_buro_kaydet'] = $row['kaydet'];
                    $_SESSION['zabita_pazar_buro_sil'] = $row['sil'];
                    $_SESSION['zabita_pazar_buro_guncelle'] = $row['guncelle'];
                    $_SESSION['zabita_pazar_buro_excel_rapor'] = $row['excel_rapor'];
                    $_SESSION['zabita_pazar_buro_encumene_sevk_et'] = $row['encumene_sevk_et'];
                    $_SESSION['zabita_pazar_buro_encumen_sevk_iptal'] = $row['encumen_sevk_iptal'];
                    $_SESSION['zabita_pazar_buro_mali_hizmetlere_sevk_edilmemis'] = $row['mali_hizmetlere_sevk_edilmemis'];
                    $_SESSION['zabita_pazar_buro_mali_hizmetlere_sevk_edilmis'] = $row['mali_hizmetlere_sevk_edilmis'];
                    $_SESSION['zabita_pazar_buro_mali_hizmetlere_sevk_et'] = $row['mali_hizmetlere_sevk_et'];
                    $_SESSION['zabita_pazar_buro_mali_hizmetlere_sevk_iptal'] = $row['mali_hizmetlere_sevk_iptal'];
                    $_SESSION['zabita_pazar_buro_gecici_pazar_yerleri'] = $row['gecici_pazar_yerleri'];
                    $_SESSION['zabita_pazar_buro_gecici_pazar_yerleri_iptal_edilen'] = $row['gecici_pazar_yerleri_iptal_edilen'];
                    $_SESSION['zabita_pazar_buro_gecici_pazar_yeri_iptal_et'] = $row['gecici_pazar_yeri_iptal_et'];
                    $_SESSION['zabita_pazar_buro_gecici_pazar_yeri_iptal_kaldir'] = $row['gecici_pazar_yeri_iptal_kaldir'];
                    $_SESSION['zabita_pazar_buro_gecici_pazar_yeri_tahsis_form'] = $row['gecici_pazar_yeri_tahsis_form'];
                    $_SESSION['zabita_pazar_buro_istatistik'] = $row['istatistik'];
                    $_SESSION['zabita_pazar_gib_sorgulama'] = $row['gib_sorgulama'];
                    $_SESSION['zabita_pazar_vefat_eden_sorgulama'] = $row['vefat_eden_sorgulama'];

                    $this->loginlog((int)$row['id'], $row['tc'], $row['adi'] . ' ' . $row['soyadi'], $uygulama_id, GetIP(), $ldapusername, $kaynak);
                    // header('Location: index.php');
                    return $array = array("hata" => 0, "success" => 1, "uygulama_id" => $uygulama_id, "erisim_durumu" => $erisim_durumu);

                } else if ($erisim_durumu == 2) {
                    $this->loginerror($tcno, GetIP(), $uygulama_id, $ldapusername, $kaynak);
                    return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => $uygulama_adi . " Yetki Formu Bulunmamaktadır", "erisim_durumu" => $erisim_durumu);

                } else if ($erisim_durumu == 0) {
                    $this->loginerror($tcno, GetIP(), $uygulama_id, $ldapusername, $kaynak);
                    return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => $uygulama_adi . " Yetkisi Bulunamadı", "erisim_durumu" => $erisim_durumu);

                }
            }
        }
    }

    function zabitapersonel_login($tcno, $ldapusername, $uygulama_id, $uygulama_adi, $kaynak)
    {
        $tcno = rescape($tcno);
        $ldapusername = rescape($ldapusername);
        $uygulama_id = rescape($uygulama_id);
        $uygulama_adi = rescape($uygulama_adi);
        $kaynak = rescape($kaynak);
        if (empty($kaynak)) {
            return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => "Parametre Eksik", "erisim_durumu" => "");
        } else if ($kaynak == "ldap" and (empty($tcno) or empty($ldapusername) or empty($uygulama_id) or empty($uygulama_adi))) {
            return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => "Parametre Eksik", "erisim_durumu" => "");
        } else if ($kaynak == "edevlet" and (empty($tcno) or empty($uygulama_id) or empty($uygulama_adi))) {
            return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => "Parametre Eksik", "erisim_durumu" => "");
        } else {
            $q1 = $this->query("SELECT * FROM zabita_personel_takip.yetkili WHERE tc='$tcno' and yetkili_durumu='1' ");
            $rowc1 = $this->num_rows($q1);
            if ($rowc1 < 1) {
                $this->loginerror($tcno, GetIP(), $uygulama_id, $ldapusername, $kaynak);
                return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => $uygulama_adi . " Yetkisi Bulunamadı");
            } else {
                $row = $this->fetch_assoc($q1);

                $erisim_durumu = "";

                ////$erisim_durumu = $this->erisim_kontrol($uygulama_id, 9, $tcno, $ldapusername);
                $erisim_durumu = 1;
                $erisim_durumu = 1;

                if ($erisim_durumu == 1) {
                    $_SESSION['zabita_personel_takip_admin_id'] = $row['id'];
                    $_SESSION['zabita_personel_takip_admin_name'] = $row['adi'] . "  " . $row['soyadi'];

                    $_SESSION['zabita_personel_takip_personel_islemleri'] = $row['personel_islemleri'];
                    $_SESSION['zabita_personel_takip_personel_izinleri'] = $row['personel_izinleri'];
                    $_SESSION['zabita_personel_takip_yer_puantaj'] = $row['puantaj'];
                    $_SESSION['zabita_personel_takip_yer_gorev_degisikligi'] = $row['yer_gorev_degisikligi'];
                    $_SESSION['zabita_personel_takip_raporlama'] = $row['raporlama'];
                    $_SESSION['zabita_personel_takip_grafik'] = $row['grafik'];
                    $_SESSION['zabita_personel_takip_sms'] = $row['sms'];
                    $_SESSION['zabita_personel_takip_ayarlar'] = $row['ayarlar'];
                    $_SESSION['zabita_personel_takip_yetkili_islemleri'] = $row['yetkili_islemleri'];
                    $_SESSION['zabita_personel_takip_personel_silme'] = $row['personel_silme'];
                    $_SESSION['zabita_personel_takip_izin_silme'] = $row['izin_silme'];
                    $_SESSION['zabita_personel_takip_yer_gorev_degisikligi_silme'] = $row['yer_gorev_degisikligi_silme'];
                    $_SESSION['zabita_personel_takip_seminer_egitim'] = $row['seminer_egitim'];
                    $_SESSION['zabita_personel_takip_personel_kaydet'] = $row['personel_kaydet'];
                    $_SESSION['zabita_personel_takip_izin_kaydet'] = $row['izin_kaydet'];
                    $_SESSION['zabita_personel_takip_yer_gorev_degisikligi_kaydet'] = $row['yer_gorev_degisikligi_kaydet'];
                    $_SESSION['zabita_personel_takip_puantaj_kaydet'] = $row['puantaj_kaydet'];
                    $_SESSION['zabita_personel_takip_seminer_egitim_kaydet'] = $row['seminer_egitim_kaydet'];
                    $_SESSION['zabita_personel_takip_seminer_egitim_sil'] = $row['seminer_egitim_sil'];
                    $_SESSION['zabita_personel_takip_nobet_cizelgesi'] = $row['nobet_cizelgesi'];
                    $_SESSION['zabita_personel_takip_nobet_cizelgesi_kaydet'] = $row['nobet_cizelgesi_kaydet'];


                    $_SESSION['zabita_personel_takip_tc'] = $row['tc'];
                    $_SESSION['zabita_personel_takip_cep_telefonu'] = $row['cep_telefonu'];

                    $this->loginlog((int)$row['id'], $row['tc'], $row['adi'] . ' ' . $row['soyadi'], $uygulama_id, GetIP(), $ldapusername, $kaynak);
                    // header('Location: index.php');
                    return $array = array("hata" => 0, "success" => 1, "uygulama_id" => $uygulama_id, "erisim_durumu" => $erisim_durumu);

                } else if ($erisim_durumu == 2) {
                    $this->loginerror($tcno, GetIP(), $uygulama_id, $ldapusername, $kaynak);
                    return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => $uygulama_adi . " Yetki Formu Bulunmamaktadır", "erisim_durumu" => $erisim_durumu);

                } else if ($erisim_durumu == 0) {
                    $this->loginerror($tcno, GetIP(), $uygulama_id, $ldapusername, $kaynak);
                    return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => $uygulama_adi . " Yetkisi Bulunamadı", "erisim_durumu" => $erisim_durumu);

                }
            }
        }
    }

    function harita_login($tcno, $ldapusername, $uygulama_id, $uygulama_adi, $kaynak)
    {
        $tcno = rescape($tcno);
        $ldapusername = rescape($ldapusername);
        $uygulama_id = rescape($uygulama_id);
        $uygulama_adi = rescape($uygulama_adi);
        $kaynak = rescape($kaynak);
        if (empty($kaynak)) {
            return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => "Parametre Eksik", "erisim_durumu" => "");
        } else if ($kaynak == "ldap" and (empty($tcno) or empty($ldapusername) or empty($uygulama_id) or empty($uygulama_adi))) {
            return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => "Parametre Eksik", "erisim_durumu" => "");
        } else if ($kaynak == "edevlet" and (empty($tcno) or empty($uygulama_id) or empty($uygulama_adi))) {
            return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => "Parametre Eksik", "erisim_durumu" => "");
        } else {
            $q1 = $this->query("SELECT * FROM harita.yetkili WHERE tc='$tcno' and yetkili_durumu='1' ");
            $rowc1 = $this->num_rows($q1);
            if ($rowc1 < 1) {
                $this->loginerror($tcno, GetIP(), $uygulama_id, $ldapusername, $kaynak);
                return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => $uygulama_adi . " Yetkisi Bulunamadı");
            } else {
                $row = $this->fetch_assoc($q1);

                $erisim_durumu = "";

                ////$erisim_durumu = $this->erisim_kontrol($uygulama_id, 9, $tcno, $ldapusername);
                $erisim_durumu = 1;
                $erisim_durumu = 1;

                if ($erisim_durumu == 1) {
                    $_SESSION['harita_admin_id'] = (int)$row['id'];
                    $_SESSION['harita_admin_tc'] = $row['tc'];
                    $_SESSION['harita_admin_name'] = $row['adi'] . ' ' . $row['soyadi'];
                    $_SESSION['harita_admin_yapden_bilgi'] = $row['yapden_bilgi'];
                    $_SESSION['harita_admin_ays_imar_belge'] = $row['ays_imar_belge'];
                    $_SESSION['harita_admin_ays_imar_proje'] = $row['ays_imar_proje'];
                    $_SESSION['harita_admin_ays_harita_belge'] = $row['ays_harita_belge'];
                    $_SESSION['harita_admin_tapu_sorgula'] = $row['tapu_sorgula'];
                    $_SESSION['harita_admin_tapu_sorgula'] = $row['tapu_sorgula'];
                    $_SESSION['harita_admin_yerlesim_yeri_oturanlar'] = $row['yerlesim_yeri_oturanlar'];
                    $_SESSION['harita_admin_bagimsiz_bolumler'] = $row['bagimsiz_bolumler'];
                    $_SESSION['harita_admin_yetkili_islemleri'] = $row['yetkili_islemleri'];
                    $_SESSION['harita_admin_katman_ayarlari'] = $row['katman_ayarlari'];
                    $_SESSION['harita_admin_disari_acik'] = $row['disari_acik'];
                    $_SESSION['harita_admin_park_rapor'] = $row['park_rapor'];

                    /*$yetkili_katmanlari[]="";
                    $yetkili_katmanlari_cizim[]="";*/
                    $q2 = $this->query("SELECT
                                            yetkili_katmanlar.id,
                                            yetkili_katmanlar.yetkili_id,
                                            yetkili_katmanlar.katman_id,
                                            katmanlar.aktif_pasif
                                            FROM
                                            harita.yetkili_katmanlar
                                            INNER JOIN harita.katmanlar 
                                            ON yetkili_katmanlar.katman_id = katmanlar.id 
                                            where yetkili_id=" . (int)$row['id'] . " and yetkili_katmanlar.aktif_pasif=1 and katmanlar.aktif_pasif=1
ORDER BY sira");

                    while ($row_katman = $this->fetch_assoc($q2)) {
                        $yetkili_katmanlari[] = $row_katman['katman_id'];
                    }

                    $q3 = $this->query("SELECT
                                            yetkili_cizim_katmanlar.id,
                                            yetkili_cizim_katmanlar.yetkili_id,
                                            yetkili_cizim_katmanlar.katman_id,
                                            yetkili_cizim_katmanlar.aktif_pasif
                                            FROM
                                            harita.yetkili_cizim_katmanlar
                                            INNER JOIN harita.katmanlar 
                                            ON yetkili_cizim_katmanlar.katman_id = katmanlar.id 
                                            where yetkili_id=" . (int)$row['id'] . " and yetkili_cizim_katmanlar.aktif_pasif=1 and katmanlar.aktif_pasif=1
ORDER BY sira");

                    while ($row_cizim = $this->fetch_assoc($q3)) {
                        $yetkili_katmanlari_cizim[] = $row_cizim['katman_id'];
                    }

                    $_SESSION['harita_yetkili_katmanlari'] = $yetkili_katmanlari;
                    $_SESSION['harita_yetkili_katmanlari_cizim'] = $yetkili_katmanlari_cizim;


                    $this->loginlog((int)$row['id'], $row['tc'], $row['adi'] . ' ' . $row['soyadi'], $uygulama_id, GetIP(), $ldapusername, $kaynak);
                    // header('Location: index.php');
                    return $array = array("hata" => 0, "success" => 1, "uygulama_id" => $uygulama_id, "erisim_durumu" => $erisim_durumu);

                } else if ($erisim_durumu == 2) {
                    $this->loginerror($tcno, GetIP(), $uygulama_id, $ldapusername, $kaynak);
                    return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => $uygulama_adi . " Yetki Formu Bulunmamaktadır", "erisim_durumu" => $erisim_durumu);

                } else if ($erisim_durumu == 0) {
                    $this->loginerror($tcno, GetIP(), $uygulama_id, $ldapusername, $kaynak);
                    return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => $uygulama_adi . " Yetkisi Bulunamadı", "erisim_durumu" => $erisim_durumu);

                }
            }
        }
    }

    function sosyal_paylasim_login($tcno, $ldapusername, $uygulama_id, $uygulama_adi, $kaynak)
    {
        $tcno = rescape($tcno);
        $ldapusername = rescape($ldapusername);
        $uygulama_id = rescape($uygulama_id);
        $uygulama_adi = rescape($uygulama_adi);
        $kaynak = rescape($kaynak);
        if (empty($kaynak)) {
            return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => "Parametre Eksik", "erisim_durumu" => "");
        } else if ($kaynak == "ldap" and (empty($tcno) or empty($ldapusername) or empty($uygulama_id) or empty($uygulama_adi))) {
            return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => "Parametre Eksik", "erisim_durumu" => "");
        } else if ($kaynak == "edevlet" and (empty($tcno) or empty($uygulama_id) or empty($uygulama_adi))) {
            return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => "Parametre Eksik", "erisim_durumu" => "");
        } else {

            $q1 = $this->query("SELECT * FROM sosyal_paylasim_magaza.yetkili WHERE tc='$tcno' and yetkili_durumu='1' ");
            $rowc1 = $this->num_rows($q1);
            if ($rowc1 < 1) {
                $this->loginerror($tcno, GetIP(), $uygulama_id, $ldapusername, $kaynak);
                return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => $uygulama_adi . " Yetkisi Bulunamadı");
            } else {
                $row = $this->fetch_assoc($q1);

                $erisim_durumu = "";

                ////$erisim_durumu = $this->erisim_kontrol($uygulama_id, 9, $tcno, $ldapusername);
                $erisim_durumu = 1;
                $erisim_durumu = 1;

                if ($erisim_durumu == 1) {
                    $_SESSION['spm_admin_id'] = $row['id'];
                    $_SESSION['spm_admin_name'] = $row['adi'] . ' ' . $row['soyadi'];
                    $_SESSION['spm_admin_tc'] = $row['tc'];
                    $_SESSION['spm_admin_cep_telefonu'] = $row['cep_telefonu'];
                    $_SESSION['spm_perm_basvurular'] = $row['basvurular'];
                    $_SESSION['spm_perm_toplu_sms'] = $row['toplu_sms'];
                    $_SESSION['spm_perm_ayarlar'] = $row['ayarlar'];
                    $_SESSION['spm_perm_stok'] = $row['stok'];
                    $_SESSION['spm_perm_yetkili'] = $row['yetkili'];
                    $_SESSION['spm_perm_sik_kullanilanlar'] = $row['sik_kullanilanlar'];
                    $_SESSION['spm_perm_gonderilen_excel_toplu_sms_silme'] = $row['gonderilen_excel_toplu_sms_silme'];
                    $_SESSION['spm_perm_rapor'] = $row['rapor'];

                    $this->loginlog((int)$row['id'], $row['tc'], $row['adi'] . ' ' . $row['soyadi'], $uygulama_id, GetIP(), $ldapusername, $kaynak);
                    // header('Location: index.php');
                    return $array = array("hata" => 0, "success" => 1, "uygulama_id" => $uygulama_id, "erisim_durumu" => $erisim_durumu);

                } else if ($erisim_durumu == 2) {
                    $this->loginerror($tcno, GetIP(), $uygulama_id, $ldapusername, $kaynak);
                    return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => $uygulama_adi . " Yetki Formu Bulunmamaktadır", "erisim_durumu" => $erisim_durumu);

                } else if ($erisim_durumu == 0) {
                    $this->loginerror($tcno, GetIP(), $uygulama_id, $ldapusername, $kaynak);
                    return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => $uygulama_adi . " Yetkisi Bulunamadı", "erisim_durumu" => $erisim_durumu);

                }
            }
        }
    }

    function kariyer_ofisi_login($tcno, $ldapusername, $uygulama_id, $uygulama_adi, $kaynak)
    {
        $tcno = rescape($tcno);
        $ldapusername = rescape($ldapusername);
        $uygulama_id = rescape($uygulama_id);
        $uygulama_adi = rescape($uygulama_adi);
        $kaynak = rescape($kaynak);
        if (empty($kaynak)) {
            return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => "Parametre Eksik", "erisim_durumu" => "");
        } else if ($kaynak == "ldap" and (empty($tcno) or empty($ldapusername) or empty($uygulama_id) or empty($uygulama_adi))) {
            return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => "Parametre Eksik", "erisim_durumu" => "");
        } else if ($kaynak == "edevlet" and (empty($tcno) or empty($uygulama_id) or empty($uygulama_adi))) {
            return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => "Parametre Eksik", "erisim_durumu" => "");
        } else {

            $q1 = $this->query("SELECT * FROM kariyer_ofisi.yetkili WHERE tc='$tcno' and yetkili_durumu='1' ");
            $rowc1 = $this->num_rows($q1);
            if ($rowc1 < 1) {
                $this->loginerror($tcno, GetIP(), $uygulama_id, $ldapusername, $kaynak);
                return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => $uygulama_adi . " Yetkisi Bulunamadı");
            } else {
                $row = $this->fetch_assoc($q1);

                $erisim_durumu = "";

                ////$erisim_durumu = $this->erisim_kontrol($uygulama_id, 9, $tcno, $ldapusername);
                $erisim_durumu = 1;
                $erisim_durumu = 1;

                if ($erisim_durumu == 1) {

                    $_SESSION['kbld_kariyerofisi_yetkili_durumu'] = $row['yetkili_durumu'];
                    $_SESSION['kbld_kariyerofisi_id'] = $row['id'];
                    $_SESSION['kbld_kariyerofisi_name'] = $row['adi'] . ' ' . $row['soyadi'];
                    $_SESSION['kbld_kariyerofisi_uye_islemleri'] = $row['uye_islemleri'];
                    $_SESSION['kbld_kariyerofisi_uye_islemleri_uye_sil'] = $row['uye_islemleri_uye_sil'];
                    $_SESSION['kbld_kariyerofisi_uye_notu_silme'] = $row['uye_notu_silme'];
                    $_SESSION['kbld_kariyerofisi_firma_islemleri'] = $row['firma_islemleri'];
                    $_SESSION['kbld_kariyerofisi_firma_silme'] = $row['firma_silme'];
                    $_SESSION['kbld_kariyerofisi_ilan_islemleri'] = $row['ilan_islemleri'];
                    $_SESSION['kbld_kariyerofisi_basvuru_islemleri'] = $row['basvuru_islemleri'];
                    $_SESSION['kbld_kariyerofisi_basvurular'] = $row['basvurular'];
                    $_SESSION['kbld_kariyerofisi_basvuru_yonlendirme'] = $row['basvuru_yonlendirme'];
                    $_SESSION['kbld_kariyerofisi_yonlendirilen_basvurulariniz'] = $row['yonlendirilen_basvurulariniz'];
                    $_SESSION['kbld_kariyerofisi_yonlendirme_guncelleme'] = $row['yonlendirme_guncelleme'];
                    $_SESSION['kbld_kariyerofisi_ayarlar'] = $row['ayarlar'];
                    $_SESSION['kbld_kariyerofisi_sabit_icerik'] = $row['sabit_icerik'];
                    $_SESSION['kbld_kariyerofisi_rehber_islemleri'] = $row['rehber_islemleri'];
                    $_SESSION['kbld_kariyerofisi_admin_duyuru'] = $row['admin_duyuru'];
                    $_SESSION['kbld_kariyerofisi_yetkili'] = $row['yetkili'];
                    $_SESSION['kbld_kariyerofisi_rapor_islemleri'] = $row['rapor_islemleri'];
                    $_SESSION['kbld_kariyerofisi_is_gecmisi'] = $row['is_gecmisi'];
                    $_SESSION['kbld_kariyerofisi_kisisel_performans_raporu'] = $row['kisisel_performans_raporu'];
                    $_SESSION['kbld_kariyerofisi_cv_goruntuleme'] = $row['cv_goruntuleme'];
                    $_SESSION['kbld_kariyerofisi_sms_islemleri'] = $row['sms_islemleri'];

                    $this->loginlog((int)$row['id'], $row['tc'], $row['adi'] . ' ' . $row['soyadi'], $uygulama_id, GetIP(), $ldapusername, $kaynak);
                    // header('Location: index.php');
                    return $array = array("hata" => 0, "success" => 1, "uygulama_id" => $uygulama_id, "erisim_durumu" => $erisim_durumu);

                } else if ($erisim_durumu == 2) {
                    $this->loginerror($tcno, GetIP(), $uygulama_id, $ldapusername, $kaynak);
                    return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => $uygulama_adi . " Yetki Formu Bulunmamaktadır", "erisim_durumu" => $erisim_durumu);

                } else if ($erisim_durumu == 0) {
                    $this->loginerror($tcno, GetIP(), $uygulama_id, $ldapusername, $kaynak);
                    return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => $uygulama_adi . " Yetkisi Bulunamadı", "erisim_durumu" => $erisim_durumu);

                }
            }
        }
    }

    function kbld_web_site_login($tcno, $ldapusername, $uygulama_id, $uygulama_adi, $kaynak)
    {
        $tcno = rescape($tcno);
        $ldapusername = rescape($ldapusername);
        $uygulama_id = rescape($uygulama_id);
        $uygulama_adi = rescape($uygulama_adi);
        $kaynak = rescape($kaynak);
        if (empty($kaynak)) {
            return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => "Parametre Eksik", "erisim_durumu" => "");
        } else if ($kaynak == "ldap" and (empty($tcno) or empty($ldapusername) or empty($uygulama_id) or empty($uygulama_adi))) {
            return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => "Parametre Eksik", "erisim_durumu" => "");
        } else if ($kaynak == "edevlet" and (empty($tcno) or empty($uygulama_id) or empty($uygulama_adi))) {
            return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => "Parametre Eksik", "erisim_durumu" => "");
        } else {

            $q1 = $this->query("SELECT * FROM kbld_web_site.yetkili WHERE tc='$tcno' and yetkili_durumu='1' ");
            $this->error();
            $rowc1 = $this->num_rows($q1);

            if ($rowc1 < 1) {
                $this->loginerror($tcno, GetIP(), $uygulama_id, $ldapusername, $kaynak);
                return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => $uygulama_adi . " Yetkisi Bulunamadı");
            } else {
                $row = $this->fetch_assoc($q1);
                $erisim_durumu = "";

                ////$erisim_durumu = $this->erisim_kontrol($uygulama_id, 9, $tcno, $ldapusername);
                $erisim_durumu = 1;
                $erisim_durumu = 1;
                if ($erisim_durumu == 1) {
                    $_SESSION['kbld_web_site_yetkili_durumu'] = $row['yetkili_durumu'];
                    $_SESSION['kbld_web_site_yetkili_id'] = $row['id'];
                    $_SESSION['kbld_web_site_yetkili_name'] = $row['adi'] . ' ' . $row['soyadi'];
                    $_SESSION['kbld_web_site_menu_yetkisi_edergi'] = $row['edergi'];
                    $_SESSION['kbld_web_site_menu_yetkisi_kecioren_gazetesi'] = $row['kecioren_gazetesi'];
                    $_SESSION['kbld_web_site_menu_yetkisi_faydali_link'] = $row['faydali_link'];
                    $_SESSION['kbld_web_site_menu_yetkisi_sanal_tur'] = $row['sanal_tur'];
                    $_SESSION['kbld_web_site_menu_yetkisi_talep_ve_oneriler'] = $row['talep_ve_oneriler'];
                    $_SESSION['kbld_web_site_menu_yetkisi_baskana_mesaj'] = $row['baskana_mesaj'];
                    $_SESSION['kbld_web_site_menu_yetkisi_baskan_yardimcilari'] = $row['baskan_yardimcilari'];
                    $_SESSION['kbld_web_site_menu_yetkisi_mudurluk_islemleri'] = $row['mudurluk_islemleri'];
                    $_SESSION['kbld_web_site_menu_yetkisi_mudurluk_islemleri_ekle'] = $row['mudurluk_islemleri_ekle'];
                    $_SESSION['kbld_web_site_menu_yetkisi_mudurluk_islemleri_duzenle'] = $row['mudurluk_islemleri_duzenle'];
                    $_SESSION['kbld_web_site_menu_yetkisi_mudurluk_faaliyetleri'] = $row['mudurluk_faaliyetleri'];
                    $_SESSION['kbld_web_site_menu_yetkisi_hizmet_standartlari'] = $row['hizmet_standartlari'];
                    $_SESSION['kbld_web_site_menu_yetkisi_genelgeler'] = $row['genelgeler'];
                    $_SESSION['kbld_web_site_menu_yetkisi_ak_masa'] = $row['ak_masa'];
                    $_SESSION['kbld_web_site_menu_yetkisi_ab_projeleri'] = $row['ab_projeleri'];
                    $_SESSION['kbld_web_site_menu_yetkisi_ab_projeleri_en'] = $row['ab_projeleri_en'];
                    $_SESSION['kbld_web_site_menu_yetkisi_banner'] = $row['banner'];
                    $_SESSION['kbld_web_site_menu_yetkisi_haber'] = $row['haber'];
                    $_SESSION['kbld_web_site_menu_yetkisi_duyuru'] = $row['duyuru'];
                    $_SESSION['kbld_web_site_menu_yetkisi_duyuru_en'] = $row['duyuru_en'];
                    $_SESSION['kbld_web_site_menu_yetkisi_projeler'] = $row['projeler'];
                    $_SESSION['kbld_web_site_menu_yetkisi_projeler_en'] = $row['projeler_en'];
                    $_SESSION['kbld_web_site_menu_yetkisi_tesis'] = $row['tesis'];
                    $_SESSION['kbld_web_site_menu_yetkisi_tesis_en'] = $row['tesis_en'];
                    $_SESSION['kbld_web_site_menu_yetkisi_popup'] = $row['popup'];
                    $_SESSION['kbld_web_site_menu_yetkisi_dosyalar'] = $row['dosyalar'];
                    $_SESSION['kbld_web_site_menu_yetkisi_galeri'] = $row['galeri'];
                    $_SESSION['kbld_web_site_menu_yetkisi_kardes_belediyeler'] = $row['kardes_belediyeler'];
                    $_SESSION['kbld_web_site_menu_yetkisi_kardes_belediyeler_en'] = $row['kardes_belediyeler_en'];
                    $_SESSION['kbld_web_site_menu_yetkisi_etkinlik'] = $row['etkinlik'];
                    $_SESSION['kbld_web_site_menu_yetkisi_performans_hedefleri'] = $row['performans_hedefleri'];
                    $_SESSION['kbld_web_site_menu_yetkisi_faaliyet_raporu'] = $row['faaliyet_raporu'];
                    $_SESSION['kbld_web_site_menu_yetkisi_stratejik_planlar'] = $row['stratejik_planlar'];
                    $_SESSION['kbld_web_site_menu_yetkisi_meclis_uyesi'] = $row['meclis_uyesi'];
                    $_SESSION['kbld_web_site_menu_yetkisi_meclis_gundemi'] = $row['meclis_gundemi'];
                    $_SESSION['kbld_web_site_menu_yetkisi_ebilbord'] = $row['ebilbord'];
                    $_SESSION['kbld_web_site_menu_yetkisi_sabit_icerik'] = $row['sabit_icerik'];
                    $_SESSION['kbld_web_site_menu_yetkisi_yetkili'] = $row['yetkili'];
                    $_SESSION['kbld_web_site_menu_yetkisi_yemek_listesi'] = $row['yemek_listesi'];
                    $_SESSION['kbld_web_site_menu_yetkisi_evde_dis_sagligi'] = $row['evde_dis_sagligi'];
                    $_SESSION['kbld_web_site_menu_yetkisi_barinak_hayvanlari'] = $row['barinak_hayvanlari'];
                    $_SESSION['kbld_web_site_menu_yetkisi_isaret_dili'] = $row['isaret_dili'];
                    $_SESSION['kbld_web_site_menu_yetkisi_ana_sayfa'] = $row['ana_sayfa'];
                    $_SESSION['kbld_web_site_menu_yetkisi_ihale'] = $row['ihale'];
                    $_SESSION['kbld_web_site_menu_yetkisi_full_yetkili'] = $row['full_yetkili'];
                    $_SESSION['kbld_web_site_menu_yetkisi_baskanla_fotograflar'] = $row['baskanla_fotograflar'];
                    $_SESSION['kbld_web_site_menu_yetkisi_genc_kecioren'] = $row['genc_kecioren'];
                    $_SESSION['kbld_web_site_menu_yetkisi_mudurluk_haberleri'] = $row['mudurluk_haberleri'];
                    $_SESSION['kbld_web_site_menu_yetkisi_mudurluk_duyuru'] = $row['mudurluk_duyuru'];
                    $_SESSION['kbld_web_site_menu_yetkisi_kultur_sanat_e_yayin'] = $row['kultur_sanat_e_yayin'];
                    $_SESSION['kbld_web_site_menu_yetkisi_basvuru_rehberi'] = $row['basvuru_rehberi'];
                    $_SESSION['kbld_web_site_menu_yetkisi_basvuru_rehberi_kategori'] = $row['basvuru_rehberi_kategori'];
                    $_SESSION['kbld_web_site_menu_yetkisi_gonul_belediyeciligi'] = $row['gonul_belediyeciligi'];
                    $_SESSION['kbld_web_site_menu_yetkisi_sms_islemleri'] = $row['sms_islemleri'];
                    $_SESSION['kbld_web_site_menu_yetkisi_haber_en'] = $row['haber_en'];
                    $_SESSION['kbld_web_site_menu_yetkisi_kiosk_menu_tiklama'] = $row['kiosk_menu_tiklama'];
                    $_SESSION['kbld_web_site_menu_yetkisi_sms_onayi'] = time();
                    $_SESSION['kbld_web_site_menu_yetkisi_bitki_dunyasi'] = $row['bitki_dunyasi'];
                    $_SESSION['kbld_web_site_menu_yetkisi_qr_olusturucu'] = $row['qr_olusturucu'];
                    $_SESSION['kbld_web_site_menu_yetkisi_staj_basvurusu'] = $row['staj_basvurusu'];
                    $_SESSION['kbld_web_site_menu_yetkisi_cem_basvurusu'] = $row['cem_basvurusu'];
                    $_SESSION['kbld_web_site_menu_yetkisi_market_listesi'] = $row['market_listesi'];
                    $_SESSION['kbld_web_site_menu_yetkisi_psiko_sosyal_danismanlik_merkezi'] = $row['psiko_sosyal_danismanlik_merkezi'];
                    $_SESSION['kbld_web_site_menu_yetkisi_basin_kiti'] = $row['basin_kiti'];
                    $_SESSION['kbld_web_site_menu_yetkisi_tmm_basvuru'] = $row['tmm_basvuru'];
                    $_SESSION['kbld_web_site_menu_yetkisi_burs_basvurusu'] = $row['burs_basvurusu'];
                    $_SESSION['kbld_web_site_menu_yetkisi_kisa_link_olusturma'] = $row['kisa_link_olusturma'];
                    $_SESSION['kbld_web_site_menu_yetkisi_sesli_kitap'] = $row['sesli_kitap'];
                    $_SESSION['kbld_web_site_menu_yetkisi_guldeste_oyun_merkezi'] = $row['guldeste_oyun_merkezi'];
                    $_SESSION['kbld_web_site_menu_yetkisi_guldeste_oyun_merkezi'] = $row['guldeste_oyun_merkezi'];
                    $_SESSION['kbld_web_site_menu_yetkisi_radyo_tv_yayin_akisi'] = $row['radyo_tv_yayin_akisi'];
                    $_SESSION['kbld_web_site_menu_yetkisi_radyo_tv_yayin_akisi_banner_ekle'] = $row['radyo_tv_yayin_akisi_banner_ekle'];
                    $_SESSION['kbld_web_site_menu_yetkisi_radyo_tv_yayin_akisi_podcast_ekle'] = $row['radyo_tv_yayin_akisi_podcast_ekle'];
                    $_SESSION['kbld_web_site_menu_yetkisi_radyo_tv_yayin_akisi_podcast_duzenle'] = $row['radyo_tv_yayin_akisi_podcast_duzenle'];
                    $_SESSION['kbld_web_site_menu_yetkisi_radyo_tv_yayin_akisi_banner_duzenle'] = $row['radyo_tv_yayin_akisi_banner_duzenle'];
                    $_SESSION['kbld_web_site_menu_yetkisi_radyo_tv_yayin_akisi_program_tanimla'] = $row['radyo_tv_yayin_akisi_program_tanimla'];
                    $_SESSION['kbld_web_site_menu_yetkisi_radyo_tv_yayin_akisi_tanimli_programlar'] = $row['radyo_tv_yayin_akisi_tanimli_programlar'];
                    $_SESSION['kbld_web_site_menu_yetkisi_radyo_tv_yayin_akisi_ozel_bilgi'] = $row['radyo_tv_yayin_akisi_ozel_bilgi'];
                    $_SESSION['kbld_web_site_menu_yetkisi_radyo_tv_yayin_akisi_kisi_ekle'] = $row['radyo_tv_yayin_akisi_kisi_ekle'];
                    $_SESSION['kbld_web_site_menu_yetkisi_radyo_tv_yayin_akisi_kisi_duzenle'] = $row['radyo_tv_yayin_akisi_kisi_duzenle'];
                    $_SESSION['kbld_web_site_menu_yetkisi_radyo_tv_radyo_video_canli_yayin'] = $row['radyo_tv_radyo_video_canli_yayin'];
                    $_SESSION['kbld_web_site_menu_yetkisi_park_islemleri'] = $row['park_islemleri'];
                    $_SESSION['kbld_web_site_menu_yetkisi_baskan_nerede'] = $row['baskan_nerede'];
                    $_SESSION['kbld_web_site_menu_yetkisi_sss'] = $row['sss'];
                    $_SESSION['kbld_web_site_menu_yetkisi_hes_sorgulama'] = $row['hes_sorgulama'];
                    $_SESSION['kbld_web_site_menu_yetkisi_yonetmelikler_yonelgeler'] = $row['yonetmelikler_yonergeler'];
                    $_SESSION['kbld_web_site_menu_yetkisi_etik_komisyonu'] = $row['etik_komisyonu'];
                    $_SESSION['kbld_web_site_menu_yetkisi_fetihcekilisi'] = $row['fetihcekilisi'];
                    $_SESSION['kbld_web_site_menu_yetkisi_huseyin_nihal_atsiz_ogrenci'] = $row['huseyin_nihal_atsiz_ogrenci'];
                    $_SESSION['kbld_web_site_menu_yetkisi_kvkk_metinleri'] = $row['kvkk_metinleri'];
                    $_SESSION['kbld_web_site_menu_yetkisi_tiyatro_oyunu'] = $row['tiyatro_oyunu'];
                    $_SESSION['kbld_web_site_menu_yetkisi_satin_alma'] = $row['satin_alma'];
                    $_SESSION['kbld_web_site_menu_yetkisi_kecikart'] = $row['kecikart'];

                    $this->loginlog((int)$row['id'], $row['tc'], $row['adi'] . ' ' . $row['soyadi'], $uygulama_id, GetIP(), $ldapusername, $kaynak);
                    // header('Location: index.php');
                    return $array = array("hata" => 0, "success" => 1, "uygulama_id" => $uygulama_id, "erisim_durumu" => $erisim_durumu);

                } else if ($erisim_durumu == 2) {
                    $this->loginerror($tcno, GetIP(), $uygulama_id, $ldapusername, $kaynak);
                    return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => $uygulama_adi . " Yetki Formu Bulunmamaktadır", "erisim_durumu" => $erisim_durumu);

                } else if ($erisim_durumu == 0) {
                    $this->loginerror($tcno, GetIP(), $uygulama_id, $ldapusername, $kaynak);
                    return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => $uygulama_adi . " Yetkisi Bulunamadı", "erisim_durumu" => $erisim_durumu);

                }
            }
        }
    }

    function sifir_atik_login($tcno, $ldapusername, $uygulama_id, $uygulama_adi, $kaynak)
    {
        $tcno = rescape($tcno);
        $ldapusername = rescape($ldapusername);
        $uygulama_id = rescape($uygulama_id);
        $uygulama_adi = rescape($uygulama_adi);
        $kaynak = rescape($kaynak);
        if (empty($kaynak)) {
            return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => "Parametre Eksik", "erisim_durumu" => "");
        } else if ($kaynak == "ldap" and (empty($tcno) or empty($ldapusername) or empty($uygulama_id) or empty($uygulama_adi))) {
            return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => "Parametre Eksik", "erisim_durumu" => "");
        } else if ($kaynak == "edevlet" and (empty($tcno) or empty($uygulama_id) or empty($uygulama_adi))) {
            return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => "Parametre Eksik", "erisim_durumu" => "");
        } else {

            $q1 = $this->query("SELECT * FROM sifiratik.yetkili WHERE tc='$tcno' and yetkili_durumu='1' ");
            $rowc1 = $this->num_rows($q1);
            if ($rowc1 < 1) {
                $this->loginerror($tcno, GetIP(), $uygulama_id, $ldapusername, $kaynak);
                return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => $uygulama_adi . " Yetkisi Bulunamadı");
            } else {
                $row = $this->fetch_assoc($q1);

                $erisim_durumu = "";

                ////$erisim_durumu = $this->erisim_kontrol($uygulama_id, 9, $tcno, $ldapusername);
                $erisim_durumu = 1;
                $erisim_durumu = 1;

                if ($erisim_durumu == 1) {


                    $_SESSION['sifiratik_admin_yetkili_durumu'] = $row['yetkili_durumu'];
                    $_SESSION['sifiratik_admin_id'] = $row['id'];
                    $_SESSION['sifiratik_admin_name'] = $row['adi'] . ' ' . $row['soyadi'];
                    $_SESSION['sifiratik_admin_ana_icerik'] = $row['ana_icerik'];
                    $_SESSION['sifiratik_admin_foto_galeri'] = $row['foto_galeri'];
                    $_SESSION['sifiratik_admin_video_galeri'] = $row['video_galeri'];
                    $_SESSION['sifiratik_admin_banner'] = $row['banner'];
                    $_SESSION['sifiratik_admin_popup'] = $row['popup'];
                    $_SESSION['sifiratik_admin_dosya'] = $row['dosya'];
                    $_SESSION['sifiratik_admin_ayarlar'] = $row['ayarlar'];
                    $_SESSION['sifiratik_admin_yetkili'] = $row['yetkili'];
                    $_SESSION['sifiratik_admin_haberdar_ol'] = $row['haberdar_ol'];
                    $_SESSION['sifiratik_admin_atigim_var'] = $row['atigim_var'];
                    $_SESSION['sifiratik_admin_okullara_ozel'] = $row['okullara_ozel'];


                    $this->loginlog((int)$row['id'], $row['tc'], $row['adi'] . ' ' . $row['soyadi'], $uygulama_id, GetIP(), $ldapusername, $kaynak);
                    // header('Location: index.php');
                    return $array = array("hata" => 0, "success" => 1, "uygulama_id" => $uygulama_id, "erisim_durumu" => $erisim_durumu);

                } else if ($erisim_durumu == 2) {
                    $this->loginerror($tcno, GetIP(), $uygulama_id, $ldapusername, $kaynak);
                    return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => $uygulama_adi . " Yetki Formu Bulunmamaktadır", "erisim_durumu" => $erisim_durumu);

                } else if ($erisim_durumu == 0) {
                    $this->loginerror($tcno, GetIP(), $uygulama_id, $ldapusername, $kaynak);
                    return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => $uygulama_adi . " Yetkisi Bulunamadı", "erisim_durumu" => $erisim_durumu);

                }
            }
        }
    }

    function terapi_merkezi_login($tcno, $ldapusername, $uygulama_id, $uygulama_adi, $kaynak)
    {
        $tcno = rescape($tcno);
        $ldapusername = rescape($ldapusername);
        $uygulama_id = rescape($uygulama_id);
        $uygulama_adi = rescape($uygulama_adi);
        $kaynak = rescape($kaynak);
        if (empty($kaynak)) {
            return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => "Parametre Eksik", "erisim_durumu" => "");
        } else if ($kaynak == "ldap" and (empty($tcno) or empty($ldapusername) or empty($uygulama_id) or empty($uygulama_adi))) {
            return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => "Parametre Eksik", "erisim_durumu" => "");
        } else if ($kaynak == "edevlet" and (empty($tcno) or empty($uygulama_id) or empty($uygulama_adi))) {
            return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => "Parametre Eksik", "erisim_durumu" => "");
        } else {

            $q1 = $this->query("SELECT * FROM terapimerkezi.yetkili WHERE tc='$tcno' and yetkili_durumu='1' ");
            $rowc1 = $this->num_rows($q1);
            if ($rowc1 < 1) {
                $this->loginerror($tcno, GetIP(), $uygulama_id, $ldapusername, $kaynak);
                return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => $uygulama_adi . " Yetkisi Bulunamadı");
            } else {
                $row = $this->fetch_assoc($q1);

                $erisim_durumu = "";

                ////$erisim_durumu = $this->erisim_kontrol($uygulama_id, 9, $tcno, $ldapusername);
                $erisim_durumu = 1;
                $erisim_durumu = 1;

                if ($erisim_durumu == 1) {


                    $_SESSION['aileterapimerkezi_admin_yetkili_durumu'] = $row['yetkili_durumu'];
                    $_SESSION['aileterapimerkezi_admin_id'] = $row['id'];
                    $_SESSION['aileterapimerkezi_admin_name'] = $row['adi'] . ' ' . $row['soyadi'];
                    $_SESSION['aileterapimerkezi_admin_ana_icerik'] = $row['ana_icerik'];
                    $_SESSION['aileterapimerkezi_admin_foto_galeri'] = $row['foto_galeri'];
                    $_SESSION['aileterapimerkezi_admin_video_galeri'] = $row['video_galeri'];
                    $_SESSION['aileterapimerkezi_admin_banner'] = $row['banner'];
                    $_SESSION['aileterapimerkezi_admin_popup'] = $row['popup'];
                    $_SESSION['aileterapimerkezi_admin_dosya'] = $row['dosya'];
                    $_SESSION['aileterapimerkezi_admin_ayarlar'] = $row['ayarlar'];
                    $_SESSION['aileterapimerkezi_admin_yetkili'] = $row['yetkili'];
                    $_SESSION['aileterapimerkezi_admin_haberdar_ol'] = $row['haberdar_ol'];
                    $_SESSION['aileterapimerkezi_admin_atigim_var'] = $row['atigim_var'];
                    $_SESSION['aileterapimerkezi_admin_okullara_ozel'] = $row['okullara_ozel'];

                    $_SESSION['aileterapimerkezi_admin_kategori_ekle'] = $row['kategori_ekle'];
                    $_SESSION['aileterapimerkezi_admin_kategori_duzenle'] = $row['kategori_duzenle'];
                    $_SESSION['aileterapimerkezi_admin_yeni_basvurular'] = $row['yeni_basvurular'];
                    $_SESSION['aileterapimerkezi_admin_iptal_olan_basvurular'] = $row['iptal_olan_basvurular'];
                    $_SESSION['aileterapimerkezi_admin_ulasilamayan_basvurular'] = $row['ulasilamayan_basvurular'];
                    $_SESSION['aileterapimerkezi_admin_devam_eden_basvurular'] = $row['devam_eden_basvurular'];
                    $_SESSION['aileterapimerkezi_admin_tamamlanan_basvurular'] = $row['tamamlanan_basvurular'];
                    $_SESSION['aileterapimerkezi_admin_yeni_randevular'] = $row['yeni_randevular'];
                    $_SESSION['aileterapimerkezi_admin_danisanlarim'] = $row['danisanlarim'];
                    $_SESSION['aileterapimerkezi_admin_randevu_verilecekler'] = $row['randevu_verilecekler'];
                    $_SESSION['aileterapimerkezi_admin_randevu_gecmisi'] = $row['randevu_gecmisi'];
                    $_SESSION['aileterapimerkezi_admin_randevu_takvimi'] = $row['randevu_takvimi'];


                    $this->loginlog((int)$row['id'], $row['tc'], $row['adi'] . ' ' . $row['soyadi'], $uygulama_id, GetIP(), $ldapusername, $kaynak);
                    // header('Location: index.php');
                    return $array = array("hata" => 0, "success" => 1, "uygulama_id" => $uygulama_id, "erisim_durumu" => $erisim_durumu);

                } else if ($erisim_durumu == 2) {
                    $this->loginerror($tcno, GetIP(), $uygulama_id, $ldapusername, $kaynak);
                    return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => $uygulama_adi . " Yetki Formu Bulunmamaktadır", "erisim_durumu" => $erisim_durumu);

                } else if ($erisim_durumu == 0) {
                    $this->loginerror($tcno, GetIP(), $uygulama_id, $ldapusername, $kaynak);
                    return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => $uygulama_adi . " Yetkisi Bulunamadı", "erisim_durumu" => $erisim_durumu);

                }
            }
        }
    }

    function kutuphaneler($tcno, $ldapusername, $uygulama_id, $uygulama_adi, $kaynak)
    {
        $tcno = rescape($tcno);
        $ldapusername = rescape($ldapusername);
        $uygulama_id = rescape($uygulama_id);
        $uygulama_adi = rescape($uygulama_adi);
        $kaynak = rescape($kaynak);
        if (empty($kaynak)) {
            return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => "Parametre Eksik", "erisim_durumu" => "");
        } else if ($kaynak == "ldap" and (empty($tcno) or empty($ldapusername) or empty($uygulama_id) or empty($uygulama_adi))) {
            return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => "Parametre Eksik", "erisim_durumu" => "");
        } else if ($kaynak == "edevlet" and (empty($tcno) or empty($uygulama_id) or empty($uygulama_adi))) {
            return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => "Parametre Eksik", "erisim_durumu" => "");
        } else {

            $q1 = $this->query("SELECT * FROM kutuphaneler.yetkili WHERE tc='$tcno' and yetkili_durumu='1' ");
            $rowc1 = $this->num_rows($q1);
            if ($rowc1 < 1) {
                $this->loginerror($tcno, GetIP(), $uygulama_id, $ldapusername, $kaynak);
                return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => $uygulama_adi . " Yetkisi Bulunamadı");
            } else {
                $row = $this->fetch_assoc($q1);

                $erisim_durumu = "";

                ////$erisim_durumu = $this->erisim_kontrol($uygulama_id, 9, $tcno, $ldapusername);
                $erisim_durumu = 1;
                $erisim_durumu = 1;

                if ($erisim_durumu == 1) {


                    $_SESSION['kutuphane_yetkili_durumu'] = $row['yetkili_durumu'];
                    $_SESSION['kutuphane_admin_id'] = $row['id'];
                    $_SESSION['kutuphane_admin_name'] = $row['adi'] . ' ' . $row['soyadi'];
                    $_SESSION['kutuphane_yetkili'] = $row['adi'] . ' ' . $row['yetkili'];


                    $this->loginlog((int)$row['id'], $row['tc'], $row['adi'] . ' ' . $row['soyadi'], $uygulama_id, GetIP(), $ldapusername, $kaynak);
                    // header('Location: index.php');
                    return $array = array("hata" => 0, "success" => 1, "uygulama_id" => $uygulama_id, "erisim_durumu" => $erisim_durumu);

                } else if ($erisim_durumu == 2) {
                    $this->loginerror($tcno, GetIP(), $uygulama_id, $ldapusername, $kaynak);
                    return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => $uygulama_adi . " Yetki Formu Bulunmamaktadır", "erisim_durumu" => $erisim_durumu);

                } else if ($erisim_durumu == 0) {
                    $this->loginerror($tcno, GetIP(), $uygulama_id, $ldapusername, $kaynak);
                    return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => $uygulama_adi . " Yetkisi Bulunamadı", "erisim_durumu" => $erisim_durumu);

                }
            }
        }
    }

    function parkbahce_login($tcno, $ldapusername, $uygulama_id, $uygulama_adi, $kaynak)
    {
        $tcno = rescape($tcno);
        $ldapusername = rescape($ldapusername);
        $uygulama_id = rescape($uygulama_id);
        $uygulama_adi = rescape($uygulama_adi);
        $kaynak = rescape($kaynak);
        if (empty($kaynak)) {
            return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => "Parametre Eksik", "erisim_durumu" => "");
        } else if ($kaynak == "ldap" and (empty($tcno) or empty($ldapusername) or empty($uygulama_id) or empty($uygulama_adi))) {
            return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => "Parametre Eksik", "erisim_durumu" => "");
        } else if ($kaynak == "edevlet" and (empty($tcno) or empty($uygulama_id) or empty($uygulama_adi))) {
            return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => "Parametre Eksik", "erisim_durumu" => "");
        } else {
            $q1 = $this->query("SELECT * FROM parkbahce.yetkili WHERE tc='$tcno' and yetkili_durumu='1' ");
            $rowc1 = $this->num_rows($q1);
            if ($rowc1 < 1) {
                $this->loginerror($tcno, GetIP(), $uygulama_id, $ldapusername, $kaynak);
                return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => $uygulama_adi . " Yetkisi Bulunamadı");
            } else {
                $row = $this->fetch_assoc($q1);

                $erisim_durumu = "";

                ////$erisim_durumu = $this->erisim_kontrol($uygulama_id, 9, $tcno, $ldapusername);
                $erisim_durumu = 1;
                $erisim_durumu = 1;

                if ($erisim_durumu == 1) {
                    $_SESSION['parkbahce_admin_id'] = (int)$row['id'];
                    $_SESSION['parkbahce_admin_tc'] = $row['tc'];
                    $_SESSION['parkbahce_admin_name'] = $row['adi'] . ' ' . $row['soyadi'];

                    $_SESSION['parkbahce_yetkili_islemleri'] = $row['yetkili_islemleri'];
                    $_SESSION['parkbahce_ayarlar'] = $row['ayarlar'];
                    $_SESSION['parkbahce_excel_rapor'] = $row['excel_rapor'];

                    $_SESSION['parkbahce_kaydet'] = $row['kaydet'];
                    $_SESSION['parkbahce_sil'] = $row['sil'];


                    $this->loginlog((int)$row['id'], $row['tc'], $row['adi'] . ' ' . $row['soyadi'], $uygulama_id, GetIP(), $ldapusername, $kaynak);
                    // header('Location: index.php');
                    return $array = array("hata" => 0, "success" => 1, "uygulama_id" => $uygulama_id, "erisim_durumu" => $erisim_durumu);

                } else if ($erisim_durumu == 2) {
                    $this->loginerror($tcno, GetIP(), $uygulama_id, $ldapusername, $kaynak);
                    return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => $uygulama_adi . " Yetki Formu Bulunmamaktadır", "erisim_durumu" => $erisim_durumu);

                } else if ($erisim_durumu == 0) {
                    $this->loginerror($tcno, GetIP(), $uygulama_id, $ldapusername, $kaynak);
                    return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => $uygulama_adi . " Yetkisi Bulunamadı", "erisim_durumu" => $erisim_durumu);

                }
            }
        }
    }

    function corona_login($tcno, $ldapusername, $uygulama_id, $uygulama_adi, $kaynak)
    {
        $tcno = rescape($tcno);
        $ldapusername = rescape($ldapusername);
        $uygulama_id = rescape($uygulama_id);
        $uygulama_adi = rescape($uygulama_adi);
        $kaynak = rescape($kaynak);
        if (empty($kaynak)) {
            return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => "Parametre Eksik", "erisim_durumu" => "");
        } else if ($kaynak == "ldap" and (empty($tcno) or empty($ldapusername) or empty($uygulama_id) or empty($uygulama_adi))) {
            return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => "Parametre Eksik", "erisim_durumu" => "");
        } else if ($kaynak == "edevlet" and (empty($tcno) or empty($uygulama_id) or empty($uygulama_adi))) {
            return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => "Parametre Eksik", "erisim_durumu" => "");
        } else {
            $q1 = $this->query("SELECT * FROM corona_destek.yetkili WHERE tc='$tcno' and yetkili_durumu='1' ");
            $rowc1 = $this->num_rows($q1);
            if ($rowc1 < 1) {
                $this->loginerror($tcno, GetIP(), $uygulama_id, $ldapusername, $kaynak);
                return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => $uygulama_adi . " Yetkisi Bulunamadı");
            } else {
                $row = $this->fetch_assoc($q1);

                $erisim_durumu = "";

                //$erisim_durumu = $this->erisim_kontrol($uygulama_id, 9, $tcno, $ldapusername);
                $erisim_durumu = 1;

                if ($erisim_durumu == 1) {
                    $_SESSION['corona_admin_id'] = (int)$row['id'];
                    $_SESSION['corona_admin_tc'] = $row['tc'];
                    $_SESSION['corona_admin_name'] = $row['adi'] . ' ' . $row['soyadi'];

                    $_SESSION['corona_yetkili_islemleri'] = $row['yetkili_islemleri'];
                    $_SESSION['corona_ayarlar'] = $row['ayarlar'];
                    $_SESSION['corona_excel_rapor'] = $row['excel_rapor'];

                    $_SESSION['corona_kaydet'] = $row['kaydet'];
                    $_SESSION['corona_sil'] = $row['sil'];


                    $this->loginlog((int)$row['id'], $row['tc'], $row['adi'] . ' ' . $row['soyadi'], $uygulama_id, GetIP(), $ldapusername, $kaynak);
                    // header('Location: index.php');
                    return $array = array("hata" => 0, "success" => 1, "uygulama_id" => $uygulama_id, "erisim_durumu" => $erisim_durumu);

                } else if ($erisim_durumu == 2) {
                    $this->loginerror($tcno, GetIP(), $uygulama_id, $ldapusername, $kaynak);
                    return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => $uygulama_adi . " Yetki Formu Bulunmamaktadır", "erisim_durumu" => $erisim_durumu);

                } else if ($erisim_durumu == 0) {
                    $this->loginerror($tcno, GetIP(), $uygulama_id, $ldapusername, $kaynak);
                    return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => $uygulama_adi . " Yetkisi Bulunamadı", "erisim_durumu" => $erisim_durumu);

                }
            }
        }
    }

    function stok_takip_login($tcno, $ldapusername, $uygulama_id, $uygulama_adi, $kaynak)
    {
        $tcno = rescape($tcno);
        $ldapusername = rescape($ldapusername);
        $uygulama_id = rescape($uygulama_id);
        $uygulama_adi = rescape($uygulama_adi);
        $kaynak = rescape($kaynak);
        if (empty($kaynak)) {
            return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => "Parametre Eksik", "erisim_durumu" => "");
        } else if ($kaynak == "ldap" and (empty($tcno) or empty($ldapusername) or empty($uygulama_id) or empty($uygulama_adi))) {
            return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => "Parametre Eksik", "erisim_durumu" => "");
        } else if ($kaynak == "edevlet" and (empty($tcno) or empty($uygulama_id) or empty($uygulama_adi))) {
            return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => "Parametre Eksik", "erisim_durumu" => "");
        } else {

            $q1 = $this->query("SELECT * FROM stok_takip.yetkili WHERE tc='$tcno' and yetkili_durumu='1' ");
            $rowc1 = $this->num_rows($q1);
            if ($rowc1 < 1) {
                $this->loginerror($tcno, GetIP(), $uygulama_id, $ldapusername, $kaynak);
                return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => $uygulama_adi . " Yetkisi Bulunamadı");
            } else {
                $row = $this->fetch_assoc($q1);

                $erisim_durumu = "";

                //$erisim_durumu = $this->erisim_kontrol($uygulama_id, 9, $tcno, $ldapusername);
                $erisim_durumu = 1;

                if ($erisim_durumu == 1) {


                    $_SESSION['stok_takip_yetkili_durumu'] = $row['yetkili_durumu'];
                    $_SESSION['stok_takip_yetkili_id'] = $row['id'];
                    $_SESSION['stok_takip_yetkili_name'] = $row['adi'] . ' ' . $row['soyadi'];

                    $_SESSION['stok_takip_yetkili_stok_islemleri'] = $row['stok_islemleri'];
                    $_SESSION['stok_takip_yetkili_urun_ekle'] = $row['urun_ekle'];
                    $_SESSION['stok_takip_yetkili_urun_duzenle'] = $row['urun_duzenle'];
                    $_SESSION['stok_takip_yetkili_stok_ekle'] = $row['stok_ekle'];
                    $_SESSION['stok_takip_yetkili_stok_girisleri'] = $row['stok_girisleri'];
                    $_SESSION['stok_takip_yetkili_stok_cikisi'] = $row['stok_cikisi'];
                    $_SESSION['stok_takip_yetkili_stok_cikislari'] = $row['stok_cikislari'];
                    $_SESSION['stok_takip_yetkili_ayarlar'] = $row['ayarlar'];
                    $_SESSION['stok_takip_yetkili_yetkili_islemleri'] = $row['yetkili_islemleri'];
                    $_SESSION['stok_takip_yetkili_urun_bazli_stok_cikislari'] = $row['urun_bazli_stok_cikislari'];


                    $this->loginlog((int)$row['id'], $row['tc'], $row['adi'] . ' ' . $row['soyadi'], $uygulama_id, GetIP(), $ldapusername, $kaynak);
                    // header('Location: index.php');
                    return $array = array("hata" => 0, "success" => 1, "uygulama_id" => $uygulama_id, "erisim_durumu" => $erisim_durumu);

                } else if ($erisim_durumu == 2) {
                    $this->loginerror($tcno, GetIP(), $uygulama_id, $ldapusername, $kaynak);
                    return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => $uygulama_adi . " Yetki Formu Bulunmamaktadır", "erisim_durumu" => $erisim_durumu);

                } else if ($erisim_durumu == 0) {
                    $this->loginerror($tcno, GetIP(), $uygulama_id, $ldapusername, $kaynak);
                    return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => $uygulama_adi . " Yetkisi Bulunamadı", "erisim_durumu" => $erisim_durumu);

                }
            }
        }
    }

    function barinak_takip_login($tcno, $ldapusername, $uygulama_id, $uygulama_adi, $kaynak)
    {
        $tcno = rescape($tcno);
        $ldapusername = rescape($ldapusername);
        $uygulama_id = rescape($uygulama_id);
        $uygulama_adi = rescape($uygulama_adi);
        $kaynak = rescape($kaynak);
        if (empty($kaynak)) {
            return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => "Parametre Eksik", "erisim_durumu" => "");
        } else if ($kaynak == "ldap" and (empty($tcno) or empty($ldapusername) or empty($uygulama_id) or empty($uygulama_adi))) {
            return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => "Parametre Eksik", "erisim_durumu" => "");
        } else if ($kaynak == "edevlet" and (empty($tcno) or empty($uygulama_id) or empty($uygulama_adi))) {
            return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => "Parametre Eksik", "erisim_durumu" => "");
        } else {

            $q1 = $this->query("SELECT * FROM barinak_takip.yetkili WHERE tc='$tcno' and yetkili_durumu='1' ");
            $rowc1 = $this->num_rows($q1);
            if ($rowc1 < 1) {
                $this->loginerror($tcno, GetIP(), $uygulama_id, $ldapusername, $kaynak);
                return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => $uygulama_adi . " Yetkisi Bulunamadı");
            } else {
                $row = $this->fetch_assoc($q1);

                $erisim_durumu = "";

                //$erisim_durumu = $this->erisim_kontrol($uygulama_id, 9, $tcno, $ldapusername);
                $erisim_durumu = 1;

                if ($erisim_durumu == 1) {


                    $_SESSION['barinak_takip_yetkili_durumu'] = $row['yetkili_durumu'];
                    $_SESSION['barinak_takip_yetkili_id'] = $row['id'];
                    $_SESSION['barinak_takip_yetkili_name'] = $row['adi'] . ' ' . $row['soyadi'];


                    $this->loginlog((int)$row['id'], $row['tc'], $row['adi'] . ' ' . $row['soyadi'], $uygulama_id, GetIP(), $ldapusername, $kaynak);
                    // header('Location: index.php');
                    return $array = array("hata" => 0, "success" => 1, "uygulama_id" => $uygulama_id, "erisim_durumu" => $erisim_durumu);

                } else if ($erisim_durumu == 2) {
                    $this->loginerror($tcno, GetIP(), $uygulama_id, $ldapusername, $kaynak);
                    return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => $uygulama_adi . " Yetki Formu Bulunmamaktadır", "erisim_durumu" => $erisim_durumu);

                } else if ($erisim_durumu == 0) {
                    $this->loginerror($tcno, GetIP(), $uygulama_id, $ldapusername, $kaynak);
                    return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => $uygulama_adi . " Yetkisi Bulunamadı", "erisim_durumu" => $erisim_durumu);

                }
            }
        }
    }

    function codam_login($tcno, $ldapusername, $uygulama_id, $uygulama_adi, $kaynak)
    {
        $tcno = rescape($tcno);
        $ldapusername = rescape($ldapusername);
        $uygulama_id = rescape($uygulama_id);
        $uygulama_adi = rescape($uygulama_adi);
        $kaynak = rescape($kaynak);
        if (empty($kaynak)) {
            return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => "Parametre Eksik", "erisim_durumu" => "");
        } else if ($kaynak == "ldap" and (empty($tcno) or empty($ldapusername) or empty($uygulama_id) or empty($uygulama_adi))) {
            return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => "Parametre Eksik", "erisim_durumu" => "");
        } else if ($kaynak == "edevlet" and (empty($tcno) or empty($uygulama_id) or empty($uygulama_adi))) {
            return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => "Parametre Eksik", "erisim_durumu" => "");
        } else {

            $q1 = $this->query("SELECT * FROM codam.yetkili WHERE tc='$tcno' and yetkili_durumu='1' ");
            $rowc1 = $this->num_rows($q1);
            if ($rowc1 < 1) {
                $this->loginerror($tcno, GetIP(), $uygulama_id, $ldapusername, $kaynak);
                return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => $uygulama_adi . " Yetkisi Bulunamadı");
            } else {
                $row = $this->fetch_assoc($q1);

                $erisim_durumu = "";

                //$erisim_durumu = $this->erisim_kontrol($uygulama_id, 9, $tcno, $ldapusername);
                $erisim_durumu = 1;

                if ($erisim_durumu == 1) {

                    $_SESSION['codam_admin_yetkili_durumu'] = $row['yetkili_durumu'];
                    $_SESSION['codam_admin_id'] = $row['id'];
                    $_SESSION['codam_admin_name'] = $row['adi'] . ' ' . $row['soyadi'];

                    $this->loginlog((int)$row['id'], $row['tc'], $row['adi'] . ' ' . $row['soyadi'], $uygulama_id, GetIP(), $ldapusername, $kaynak);
                    // header('Location: index.php');
                    return $array = array("hata" => 0, "success" => 1, "uygulama_id" => $uygulama_id, "erisim_durumu" => $erisim_durumu);

                } else if ($erisim_durumu == 2) {
                    $this->loginerror($tcno, GetIP(), $uygulama_id, $ldapusername, $kaynak);
                    return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => $uygulama_adi . " Yetki Formu Bulunmamaktadır", "erisim_durumu" => $erisim_durumu);

                } else if ($erisim_durumu == 0) {
                    $this->loginerror($tcno, GetIP(), $uygulama_id, $ldapusername, $kaynak);
                    return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => $uygulama_adi . " Yetkisi Bulunamadı", "erisim_durumu" => $erisim_durumu);

                }
            }
        }
    }

    function cocuk_egitim_merkezi_login($tcno, $ldapusername, $uygulama_id, $uygulama_adi, $kaynak)
    {
        $tcno = rescape($tcno);
        $ldapusername = rescape($ldapusername);
        $uygulama_id = rescape($uygulama_id);
        $uygulama_adi = rescape($uygulama_adi);
        $kaynak = rescape($kaynak);
        if (empty($kaynak)) {
            return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => "Parametre Eksik", "erisim_durumu" => "");
        } else if ($kaynak == "ldap" and (empty($tcno) or empty($ldapusername) or empty($uygulama_id) or empty($uygulama_adi))) {
            return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => "Parametre Eksik", "erisim_durumu" => "");
        } else if ($kaynak == "edevlet" and (empty($tcno) or empty($uygulama_id) or empty($uygulama_adi))) {
            return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => "Parametre Eksik", "erisim_durumu" => "");
        } else {

            $q1 = $this->query("SELECT * FROM cocuk_egitim_merkezi.yetkili WHERE tc='$tcno' and yetkili_durumu='1' ");
            $rowc1 = $this->num_rows($q1);
            if ($rowc1 < 1) {
                $this->loginerror($tcno, GetIP(), $uygulama_id, $ldapusername, $kaynak);
                return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => $uygulama_adi . " Yetkisi Bulunamadı");
            } else {
                $row = $this->fetch_assoc($q1);

                $erisim_durumu = "";

                //$erisim_durumu = $this->erisim_kontrol($uygulama_id, 9, $tcno, $ldapusername);
                $erisim_durumu = 1;

                if ($erisim_durumu == 1) {

                    $_SESSION['cocuk_egitim_merkezi_admin_yetkili_durumu'] = $row['yetkili_durumu'];
                    $_SESSION['cocuk_egitim_merkezi_admin_id'] = $row['id'];
                    $_SESSION['cocuk_egitim_merkezi_admin_name'] = $row['adi'] . ' ' . $row['soyadi'];

                    $this->loginlog((int)$row['id'], $row['tc'], $row['adi'] . ' ' . $row['soyadi'], $uygulama_id, GetIP(), $ldapusername, $kaynak);
                    // header('Location: index.php');
                    return $array = array("hata" => 0, "success" => 1, "uygulama_id" => $uygulama_id, "erisim_durumu" => $erisim_durumu);

                } else if ($erisim_durumu == 2) {
                    $this->loginerror($tcno, GetIP(), $uygulama_id, $ldapusername, $kaynak);
                    return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => $uygulama_adi . " Yetki Formu Bulunmamaktadır", "erisim_durumu" => $erisim_durumu);

                } else if ($erisim_durumu == 0) {
                    $this->loginerror($tcno, GetIP(), $uygulama_id, $ldapusername, $kaynak);
                    return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => $uygulama_adi . " Yetkisi Bulunamadı", "erisim_durumu" => $erisim_durumu);

                }
            }
        }
    }

    function ahmet_ali_sahin_cocuk_egitim_merkezi_login($tcno, $ldapusername, $uygulama_id, $uygulama_adi, $kaynak)
    {
        $tcno = rescape($tcno);
        $ldapusername = rescape($ldapusername);
        $uygulama_id = rescape($uygulama_id);
        $uygulama_adi = rescape($uygulama_adi);
        $kaynak = rescape($kaynak);
        if (empty($kaynak)) {
            return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => "Parametre Eksik", "erisim_durumu" => "");
        } else if ($kaynak == "ldap" and (empty($tcno) or empty($ldapusername) or empty($uygulama_id) or empty($uygulama_adi))) {
            return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => "Parametre Eksik", "erisim_durumu" => "");
        } else if ($kaynak == "edevlet" and (empty($tcno) or empty($uygulama_id) or empty($uygulama_adi))) {
            return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => "Parametre Eksik", "erisim_durumu" => "");
        } else {

            $q1 = $this->query("SELECT * FROM ahmet_ali_sahin_cocuk_egitim_merkezi.yetkili WHERE tc='$tcno' and yetkili_durumu='1' ");
            $rowc1 = $this->num_rows($q1);
            if ($rowc1 < 1) {
                $this->loginerror($tcno, GetIP(), $uygulama_id, $ldapusername, $kaynak);
                return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => $uygulama_adi . " Yetkisi Bulunamadı");
            } else {
                $row = $this->fetch_assoc($q1);

                $erisim_durumu = "";

                //$erisim_durumu = $this->erisim_kontrol($uygulama_id, 9, $tcno, $ldapusername);
                $erisim_durumu = 1;

                if ($erisim_durumu == 1) {

                    $_SESSION['ahmet_ali_sahin_cocuk_egitim_merkezi_login_admin_yetkili_durumu'] = $row['yetkili_durumu'];
                    $_SESSION['ahmet_ali_sahin_cocuk_egitim_merkezi_login_admin_id'] = $row['id'];
                    $_SESSION['ahmet_ali_sahin_cocuk_egitim_merkezi_login_admin_name'] = $row['adi'] . ' ' . $row['soyadi'];

                    $this->loginlog((int)$row['id'], $row['tc'], $row['adi'] . ' ' . $row['soyadi'], $uygulama_id, GetIP(), $ldapusername, $kaynak);
                    // header('Location: index.php');
                    return $array = array("hata" => 0, "success" => 1, "uygulama_id" => $uygulama_id, "erisim_durumu" => $erisim_durumu);

                } else if ($erisim_durumu == 2) {
                    $this->loginerror($tcno, GetIP(), $uygulama_id, $ldapusername, $kaynak);
                    return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => $uygulama_adi . " Yetki Formu Bulunmamaktadır", "erisim_durumu" => $erisim_durumu);

                } else if ($erisim_durumu == 0) {
                    $this->loginerror($tcno, GetIP(), $uygulama_id, $ldapusername, $kaynak);
                    return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => $uygulama_adi . " Yetkisi Bulunamadı", "erisim_durumu" => $erisim_durumu);

                }
            }
        }
    }

    function sms_genel_login($tcno, $ldapusername, $uygulama_id, $uygulama_adi, $kaynak)
    {
        $tcno = rescape($tcno);
        $ldapusername = rescape($ldapusername);
        $uygulama_id = rescape($uygulama_id);
        $uygulama_adi = rescape($uygulama_adi);
        $kaynak = rescape($kaynak);
        if (empty($kaynak)) {
            return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => "Parametre Eksik", "erisim_durumu" => "");
        } else if ($kaynak == "ldap" and (empty($tcno) or empty($ldapusername) or empty($uygulama_id) or empty($uygulama_adi))) {
            return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => "Parametre Eksik", "erisim_durumu" => "");
        } else if ($kaynak == "edevlet" and (empty($tcno) or empty($uygulama_id) or empty($uygulama_adi))) {
            return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => "Parametre Eksik", "erisim_durumu" => "");
        } else {

            $q1 = $this->query("SELECT * FROM sms_genel.yetkili WHERE tc='$tcno' and yetkili_durumu='1' ");
            $rowc1 = $this->num_rows($q1);
            if ($rowc1 < 1) {
                $this->loginerror($tcno, GetIP(), $uygulama_id, $ldapusername, $kaynak);
                return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => $uygulama_adi . " Yetkisi Bulunamadı");
            } else {
                $row = $this->fetch_assoc($q1);

                $erisim_durumu = "";

                //$erisim_durumu = $this->erisim_kontrol($uygulama_id, 9, $tcno, $ldapusername);
                $erisim_durumu = 1;

                if ($erisim_durumu == 1) {


                    $_SESSION['sms_genel_yetkili_durumu'] = $row['yetkili_durumu'];
                    $_SESSION['sms_genel_yetkili_id'] = $row['id'];
                    $_SESSION['sms_genel_yetkili_name'] = $row['adi'] . ' ' . $row['soyadi'];


                    $this->loginlog((int)$row['id'], $row['tc'], $row['adi'] . ' ' . $row['soyadi'], $uygulama_id, GetIP(), $ldapusername, $kaynak);
                    // header('Location: index.php');
                    return $array = array("hata" => 0, "success" => 1, "uygulama_id" => $uygulama_id, "erisim_durumu" => $erisim_durumu);

                } else if ($erisim_durumu == 2) {
                    $this->loginerror($tcno, GetIP(), $uygulama_id, $ldapusername, $kaynak);
                    return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => $uygulama_adi . " Yetki Formu Bulunmamaktadır", "erisim_durumu" => $erisim_durumu);

                } else if ($erisim_durumu == 0) {
                    $this->loginerror($tcno, GetIP(), $uygulama_id, $ldapusername, $kaynak);
                    return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => $uygulama_adi . " Yetkisi Bulunamadı", "erisim_durumu" => $erisim_durumu);

                }
            }
        }
    }

    function kecvet_login($tcno, $ldapusername, $uygulama_id, $uygulama_adi, $kaynak)
    {
        $tcno = rescape($tcno);
        $ldapusername = rescape($ldapusername);
        $uygulama_id = rescape($uygulama_id);
        $uygulama_adi = rescape($uygulama_adi);
        $kaynak = rescape($kaynak);
        if (empty($kaynak)) {
            return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => "Parametre Eksik", "erisim_durumu" => "");
        } else if ($kaynak == "ldap" and (empty($tcno) or empty($ldapusername) or empty($uygulama_id) or empty($uygulama_adi))) {
            return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => "Parametre Eksik", "erisim_durumu" => "");
        } else if ($kaynak == "edevlet" and (empty($tcno) or empty($uygulama_id) or empty($uygulama_adi))) {
            return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => "Parametre Eksik", "erisim_durumu" => "");
        } else {

            $q1 = $this->query("SELECT * FROM kecvet.yetkili WHERE tc='$tcno' and yetkili_durumu='1' ");
            $rowc1 = $this->num_rows($q1);
            if ($rowc1 < 1) {
                $this->loginerror($tcno, GetIP(), $uygulama_id, $ldapusername, $kaynak);
                return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => $uygulama_adi . " Yetkisi Bulunamadı");
            } else {
                $row = $this->fetch_assoc($q1);

                $erisim_durumu = "";

                //$erisim_durumu = $this->erisim_kontrol($uygulama_id, 9, $tcno, $ldapusername);
                $erisim_durumu = 1;

                if ($erisim_durumu == 1) {


                    $_SESSION['kecvet_login_yetkili_durumu'] = $row['yetkili_durumu'];
                    $_SESSION['kecvet_login_admin_id'] = $row['id'];
                    $_SESSION['kecvet_login_admin_name'] = $row['adi'] . ' ' . $row['soyadi'];


                    $this->loginlog((int)$row['id'], $row['tc'], $row['adi'] . ' ' . $row['soyadi'], $uygulama_id, GetIP(), $ldapusername, $kaynak);
                    // header('Location: index.php');
                    return $array = array("hata" => 0, "success" => 1, "uygulama_id" => $uygulama_id, "erisim_durumu" => $erisim_durumu);

                } else if ($erisim_durumu == 2) {
                    $this->loginerror($tcno, GetIP(), $uygulama_id, $ldapusername, $kaynak);
                    return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => $uygulama_adi . " Yetki Formu Bulunmamaktadır", "erisim_durumu" => $erisim_durumu);

                } else if ($erisim_durumu == 0) {
                    $this->loginerror($tcno, GetIP(), $uygulama_id, $ldapusername, $kaynak);
                    return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => $uygulama_adi . " Yetkisi Bulunamadı", "erisim_durumu" => $erisim_durumu);

                }
            }
        }
    }

    function faaliyet_raporu_login($tcno, $ldapusername, $uygulama_id, $uygulama_adi, $kaynak)
    {
        $tcno = rescape($tcno);
        $ldapusername = rescape($ldapusername);
        $uygulama_id = rescape($uygulama_id);
        $uygulama_adi = rescape($uygulama_adi);
        $kaynak = rescape($kaynak);
        if (empty($kaynak)) {
            return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => "Parametre Eksik", "erisim_durumu" => "");
        } else if ($kaynak == "ldap" and (empty($tcno) or empty($ldapusername) or empty($uygulama_id) or empty($uygulama_adi))) {
            return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => "Parametre Eksik", "erisim_durumu" => "");
        } else if ($kaynak == "edevlet" and (empty($tcno) or empty($uygulama_id) or empty($uygulama_adi))) {
            return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => "Parametre Eksik", "erisim_durumu" => "");
        } else {

            $q1 = $this->query("SELECT * FROM faaliyet_raporu.yetkili WHERE tc='$tcno' and yetkili_durumu='1' ");
            $rowc1 = $this->num_rows($q1);
            if ($rowc1 < 1) {
                $this->loginerror($tcno, GetIP(), $uygulama_id, $ldapusername, $kaynak);
                return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => $uygulama_adi . " Yetkisi Bulunamadı");
            } else {
                $row = $this->fetch_assoc($q1);

                $erisim_durumu = "";

                //$erisim_durumu = $this->erisim_kontrol($uygulama_id, 9, $tcno, $ldapusername);
                $erisim_durumu = 1;

                if ($erisim_durumu == 1) {


                    $_SESSION['faaliyet_raporu_admin_durumu'] = $row['yetkili_durumu'];
                    $_SESSION['faaliyet_raporu_admin_id'] = $row['id'];
                    $_SESSION['faaliyet_raporu_admin_name'] = $row['adi'] . ' ' . $row['soyadi'];
                    $_SESSION['faaliyet_raporu_admin_yetki_turu'] = $row['yetki_turu'];
                    $_SESSION['faaliyet_raporu_admin_yetki_mud_id'] = $row['mud_id'];
                    $_SESSION['faaliyet_raporu_admin_yetki_birim_id'] = $row['birim_id'];
                    $_SESSION['faaliyet_raporu_admin_yetki_alt_birim_id'] = $row['alt_birim_id'];


                    $this->loginlog((int)$row['id'], $row['tc'], $row['adi'] . ' ' . $row['soyadi'], $uygulama_id, GetIP(), $ldapusername, $kaynak);
                    // header('Location: index.php');
                    return $array = array("hata" => 0, "success" => 1, "uygulama_id" => $uygulama_id, "erisim_durumu" => $erisim_durumu);

                } else if ($erisim_durumu == 2) {
                    $this->loginerror($tcno, GetIP(), $uygulama_id, $ldapusername, $kaynak);
                    return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => $uygulama_adi . " Yetki Formu Bulunmamaktadır", "erisim_durumu" => $erisim_durumu);

                } else if ($erisim_durumu == 0) {
                    $this->loginerror($tcno, GetIP(), $uygulama_id, $ldapusername, $kaynak);
                    return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => $uygulama_adi . " Yetkisi Bulunamadı", "erisim_durumu" => $erisim_durumu);

                }
            }
        }
    }

    function teknoloji_merkezi_login($tcno, $ldapusername, $uygulama_id, $uygulama_adi, $kaynak)
    {
        $tcno = rescape($tcno);
        $ldapusername = rescape($ldapusername);
        $uygulama_id = rescape($uygulama_id);
        $uygulama_adi = rescape($uygulama_adi);
        $kaynak = rescape($kaynak);
        if (empty($kaynak)) {
            return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => "Parametre Eksik", "erisim_durumu" => "");
        } else if ($kaynak == "ldap" and (empty($tcno) or empty($ldapusername) or empty($uygulama_id) or empty($uygulama_adi))) {
            return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => "Parametre Eksik", "erisim_durumu" => "");
        } else if ($kaynak == "edevlet" and (empty($tcno) or empty($uygulama_id) or empty($uygulama_adi))) {
            return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => "Parametre Eksik", "erisim_durumu" => "");
        } else {

            $q1 = $this->query("SELECT * FROM teknoloji_merkezi.yetkili WHERE tc='$tcno' and yetkili_durumu='1' ");
            $rowc1 = $this->num_rows($q1);
            if ($rowc1 < 1) {
                $this->loginerror($tcno, GetIP(), $uygulama_id, $ldapusername, $kaynak);
                return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => $uygulama_adi . " Yetkisi Bulunamadı");
            } else {
                $row = $this->fetch_assoc($q1);

                $erisim_durumu = "";

                //$erisim_durumu = $this->erisim_kontrol($uygulama_id, 9, $tcno, $ldapusername);
                $erisim_durumu = 1;

                if ($erisim_durumu == 1) {
                    $_SESSION['teknoloji_merkezi_yetkili_yetki_durumu'] = $row['yetkili_durumu'];
                    $_SESSION['teknoloji_merkezi_yetkili_id'] = $row['id'];
                    $_SESSION['teknoloji_merkezi_yetkili_name'] = $row['adi'] . ' ' . $row['soyadi'];

                    $_SESSION['teknoloji_merkezi_yetkili_yetki_turu'] = $row['yetki_turu'];

                    $_SESSION['teknoloji_merkezi_yetkili_yetki_rezerv_basvuru'] = $row['rezerv_basvuru'];
                    $_SESSION['teknoloji_merkezi_yetkili_yetki_turnuva_basvuru'] = $row['turnuva_basvuru'];
                    $_SESSION['teknoloji_merkezi_yetkili_yetki_kurs_basvuru'] = $row['kurs_basvuru'];
                    $_SESSION['teknoloji_merkezi_yetkili_yetki_yarisma_basvuru'] = $row['yarisma_basvuru'];

                    $_SESSION['teknoloji_merkezi_yetkili_yetki_banner'] = $row['banner'];
                    $_SESSION['teknoloji_merkezi_yetkili_yetki_etkinlik'] = $row['etkinlik'];
                    $_SESSION['teknoloji_merkezi_yetkili_yetki_faaliyet'] = $row['faaliyet'];
                    $_SESSION['teknoloji_merkezi_yetkili_yetki_sabit'] = $row['sabit'];
                    $_SESSION['teknoloji_merkezi_yetkili_yetki_site_uyelik'] = $row['site_uyelik'];
                    $_SESSION['teknoloji_merkezi_yetkili_yetki_bulten_uyelik'] = $row['bulten_uyelik'];
                    $_SESSION['teknoloji_merkezi_yetkili_yetki_yorumlar'] = $row['yorumlar'];
                    $_SESSION['teknoloji_merkezi_yetkili_yetki_talep_istek'] = $row['talep_istek'];
                    $_SESSION['teknoloji_merkezi_yetkili_yetki_blog'] = $row['blog'];
                    $_SESSION['teknoloji_merkezi_yetkili_yetki_tatil'] = $row['tatil'];
                    $_SESSION['teknoloji_merkezi_yetkili_yetki_sorular'] = $row['sorular'];
                    $_SESSION['teknoloji_merkezi_yetkili_yetki_hakkimizda'] = $row['hakkimizda'];
                    $_SESSION['teknoloji_merkezi_yetkili_yetki_okul'] = $row['okul'];
                    $_SESSION['teknoloji_merkezi_yetkili_yetki_kategori'] = $row['kategori'];
                    $_SESSION['teknoloji_merkezi_yetkili_yetki_yetkili'] = $row['yetkili'];
                    $_SESSION['teknoloji_merkezi_yetkili_yetki_yetkili_giris_cikis'] = $row['yetkili_giris_cikis'];

                    $_SESSION['teknoloji_merkezi_yetkili_yetki_vr'] = $row['vr'];
                    $_SESSION['teknoloji_merkezi_yetkili_yetki_simulasyon'] = $row['simulasyon'];
                    $_SESSION['teknoloji_merkezi_yetkili_yetki_konferans'] = $row['konferans'];
                    $_SESSION['teknoloji_merkezi_yetkili_yetki_espor'] = $row['espor'];
                    $_SESSION['teknoloji_merkezi_yetkili_yetki_girisimcilik'] = $row['girisimcilik'];
                    $_SESSION['teknoloji_merkezi_yetkili_yetki_xbox'] = $row['xbox'];
                    $_SESSION['teknoloji_merkezi_yetkili_yetki_ps5'] = $row['ps5'];
                    $_SESSION['teknoloji_merkezi_yetkili_yetki_muzik'] = $row['muzik'];
                    $_SESSION['teknoloji_merkezi_yetkili_yetki_greenbox'] = $row['greenbox'];

                    $_SESSION['teknoloji_merkezi_yetkili_qr_code'] = $row['qr_code'];

                    $this->loginlog((int)$row['id'], $row['tc'], $row['adi'] . ' ' . $row['soyadi'], $uygulama_id, GetIP(), $ldapusername, $kaynak);
                    // header('Location: index.php');
                    return $array = array("hata" => 0, "success" => 1, "uygulama_id" => $uygulama_id, "erisim_durumu" => $erisim_durumu);

                } else if ($erisim_durumu == 2) {
                    $this->loginerror($tcno, GetIP(), $uygulama_id, $ldapusername, $kaynak);
                    return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => $uygulama_adi . " Yetki Formu Bulunmamaktadır", "erisim_durumu" => $erisim_durumu);

                } else if ($erisim_durumu == 0) {
                    $this->loginerror($tcno, GetIP(), $uygulama_id, $ldapusername, $kaynak);
                    return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => $uygulama_adi . " Yetkisi Bulunamadı", "erisim_durumu" => $erisim_durumu);

                }
            }
        }
    }

    function ev_temizlik_hizmetleri_login($tcno, $ldapusername, $uygulama_id, $uygulama_adi, $kaynak)
    {
        $tcno = rescape($tcno);
        $ldapusername = rescape($ldapusername);
        $uygulama_id = rescape($uygulama_id);
        $uygulama_adi = rescape($uygulama_adi);
        $kaynak = rescape($kaynak);
        if (empty($kaynak)) {
            return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => "Parametre Eksik", "erisim_durumu" => "");
        } else if ($kaynak == "ldap" and (empty($tcno) or empty($ldapusername) or empty($uygulama_id) or empty($uygulama_adi))) {
            return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => "Parametre Eksik", "erisim_durumu" => "");
        } else if ($kaynak == "edevlet" and (empty($tcno) or empty($uygulama_id) or empty($uygulama_adi))) {
            return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => "Parametre Eksik", "erisim_durumu" => "");
        } else {

            $q1 = $this->query("SELECT * FROM ev_temizlik_hizmetleri.yetkili WHERE tc='$tcno' and yetkili_durumu='1' ");
            $rowc1 = $this->num_rows($q1);
            if ($rowc1 < 1) {
                $this->loginerror($tcno, GetIP(), $uygulama_id, $ldapusername, $kaynak);
                return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => $uygulama_adi . " Yetkisi Bulunamadı");
            } else {
                $row = $this->fetch_assoc($q1);

                $erisim_durumu = "";

                //$erisim_durumu = $this->erisim_kontrol($uygulama_id, 9, $tcno, $ldapusername);
                $erisim_durumu = 1;

                if ($erisim_durumu == 1) {

                    $_SESSION['ev_temizlik_hizmetleri_admin_yetkili_durumu'] = $row['yetkili_durumu'];
                    $_SESSION['ev_temizlik_hizmetleri_admin_id'] = $row['id'];
                    $_SESSION['ev_temizlik_hizmetleri_admin_name'] = $row['adi'] . ' ' . $row['soyadi'];

                    $this->loginlog((int)$row['id'], $row['tc'], $row['adi'] . ' ' . $row['soyadi'], $uygulama_id, GetIP(), $ldapusername, $kaynak);
                    // header('Location: index.php');
                    return $array = array("hata" => 0, "success" => 1, "uygulama_id" => $uygulama_id, "erisim_durumu" => $erisim_durumu);

                } else if ($erisim_durumu == 2) {
                    $this->loginerror($tcno, GetIP(), $uygulama_id, $ldapusername, $kaynak);
                    return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => $uygulama_adi . " Yetki Formu Bulunmamaktadır", "erisim_durumu" => $erisim_durumu);

                } else if ($erisim_durumu == 0) {
                    $this->loginerror($tcno, GetIP(), $uygulama_id, $ldapusername, $kaynak);
                    return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => $uygulama_adi . " Yetkisi Bulunamadı", "erisim_durumu" => $erisim_durumu);

                }
            }
        }
    }

    function hasta_nakil_hizmetleri_login($tcno, $ldapusername, $uygulama_id, $uygulama_adi, $kaynak)

    {
        $tcno = rescape($tcno);
        $ldapusername = rescape($ldapusername);
        $uygulama_id = rescape($uygulama_id);
        $uygulama_adi = rescape($uygulama_adi);
        $kaynak = rescape($kaynak);
        if (empty($kaynak)) {
            return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => "Parametre Eksik", "erisim_durumu" => "");
        } else if ($kaynak == "ldap" and (empty($tcno) or empty($ldapusername) or empty($uygulama_id) or empty($uygulama_adi))) {
            return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => "Parametre Eksik", "erisim_durumu" => "");
        } else if ($kaynak == "edevlet" and (empty($tcno) or empty($uygulama_id) or empty($uygulama_adi))) {
            return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => "Parametre Eksik", "erisim_durumu" => "");
        } else {

            $q1 = $this->query("SELECT * FROM hasta_nakil_hizmetleri.yetkili WHERE tc='$tcno' and yetkili_durumu='1' ");
            $rowc1 = $this->num_rows($q1);
            if ($rowc1 < 1) {
                $this->loginerror($tcno, GetIP(), $uygulama_id, $ldapusername, $kaynak);
                return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => $uygulama_adi . " Yetkisi Bulunamadı");
            } else {
                $row = $this->fetch_assoc($q1);

                $erisim_durumu = "";

                //$erisim_durumu = $this->erisim_kontrol($uygulama_id, 9, $tcno, $ldapusername);
                $erisim_durumu = 1;

                if ($erisim_durumu == 1) {

                    $_SESSION['hasta_nakil_hizmetleri_admin_yetkili_durumu'] = $row['yetkili_durumu'];
                    $_SESSION['hasta_nakil_hizmetleri_admin_id'] = $row['id'];
                    $_SESSION['hasta_nakil_hizmetleri_admin_name'] = $row['adi'] . ' ' . $row['soyadi'];
                    $_SESSION['hasta_nakil_hizmetleri_randevu_ekle'] = $row['yetki_randevu_ekle'];
                    $_SESSION['hasta_nakil_hizmetleri_ayar_islemleri'] = $row['yetki_ayar_islemleri'];
                    $_SESSION['hasta_nakil_hizmetleri_yetkili_islemleri'] = $row['yetki_yetkili_islemleri'];

                    $this->loginlog((int)$row['id'], $row['tc'], $row['adi'] . ' ' . $row['soyadi'], $uygulama_id, GetIP(), $ldapusername, $kaynak);
                    // header('Location: index.php');
                    return $array = array("hata" => 0, "success" => 1, "uygulama_id" => $uygulama_id, "erisim_durumu" => $erisim_durumu);

                } else if ($erisim_durumu == 2) {
                    $this->loginerror($tcno, GetIP(), $uygulama_id, $ldapusername, $kaynak);
                    return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => $uygulama_adi . " Yetki Formu Bulunmamaktadır", "erisim_durumu" => $erisim_durumu);

                } else if ($erisim_durumu == 0) {
                    $this->loginerror($tcno, GetIP(), $uygulama_id, $ldapusername, $kaynak);
                    return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => $uygulama_adi . " Yetkisi Bulunamadı", "erisim_durumu" => $erisim_durumu);

                }
            }
        }
    }

    function imar_mudurlugu_takip_sistemi_login($tcno, $ldapusername, $uygulama_id, $uygulama_adi, $kaynak)
    {
        $tcno = rescape($tcno);
        $ldapusername = rescape($ldapusername);
        $uygulama_id = rescape($uygulama_id);
        $uygulama_adi = rescape($uygulama_adi);
        $kaynak = rescape($kaynak);
        if (empty($kaynak)) {
            return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => "Parametre Eksik", "erisim_durumu" => "");
        } else if ($kaynak == "ldap" and (empty($tcno) or empty($ldapusername) or empty($uygulama_id) or empty($uygulama_adi))) {
            return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => "Parametre Eksik", "erisim_durumu" => "");
        } else if ($kaynak == "edevlet" and (empty($tcno) or empty($uygulama_id) or empty($uygulama_adi))) {
            return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => "Parametre Eksik", "erisim_durumu" => "");
        } else {

            $q1 = $this->query("SELECT * FROM imar_mudurlugu_takip_sistemi.yetkili WHERE tc='$tcno' and yetkili_durumu='1' ");
            $rowc1 = $this->num_rows($q1);
            if ($rowc1 < 1) {
                $this->loginerror($tcno, GetIP(), $uygulama_id, $ldapusername, $kaynak);
                return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => $uygulama_adi . " Yetkisi Bulunamadı");
            } else {
                $row = $this->fetch_assoc($q1);

                $erisim_durumu = "";

                //$erisim_durumu = $this->erisim_kontrol($uygulama_id, 9, $tcno, $ldapusername);
                $erisim_durumu = 1;

                if ($erisim_durumu == 1) {

                    $_SESSION['imar_mudurlugu_takip_sistemi_admin_yetkili_durumu'] = $row['yetkili_durumu'];
                    $_SESSION['imar_mudurlugu_takip_sistemi_admin_id'] = $row['id'];
                    $_SESSION['imar_mudurlugu_takip_sistemi_admin_name'] = $row['adi'] . ' ' . $row['soyadi'];


                    $_SESSION['imar_mudurlugu_takip_sistemi_dosya_talep'] = $row['dosya_talep'];
                    $_SESSION['imar_mudurlugu_takip_sistemi_dosya_talepleri'] = $row['dosya_talepleri'];
                    $_SESSION['imar_mudurlugu_takip_sistemi_yetkili'] = $row['yetkili'];
                    $_SESSION['imar_mudurlugu_takip_sistemi_ayarlar'] = $row['ayarlar'];


                    $this->loginlog((int)$row['id'], $row['tc'], $row['adi'] . ' ' . $row['soyadi'], $uygulama_id, GetIP(), $ldapusername, $kaynak);
                    // header('Location: index.php');
                    return $array = array("hata" => 0, "success" => 1, "uygulama_id" => $uygulama_id, "erisim_durumu" => $erisim_durumu);

                } else if ($erisim_durumu == 2) {
                    $this->loginerror($tcno, GetIP(), $uygulama_id, $ldapusername, $kaynak);
                    return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => $uygulama_adi . " Yetki Formu Bulunmamaktadır", "erisim_durumu" => $erisim_durumu);

                } else if ($erisim_durumu == 0) {
                    $this->loginerror($tcno, GetIP(), $uygulama_id, $ldapusername, $kaynak);
                    return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => $uygulama_adi . " Yetkisi Bulunamadı", "erisim_durumu" => $erisim_durumu);

                }
            }
        }
    }

    function anket_uygulamasi_login($tcno, $ldapusername, $uygulama_id, $uygulama_adi, $kaynak)
    {
        $tcno = rescape($tcno);
        $ldapusername = rescape($ldapusername);
        $uygulama_id = rescape($uygulama_id);
        $uygulama_adi = rescape($uygulama_adi);
        $kaynak = rescape($kaynak);
        if (empty($kaynak)) {
            return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => "Parametre Eksik", "erisim_durumu" => "");
        } else if ($kaynak == "ldap" and (empty($tcno) or empty($ldapusername) or empty($uygulama_id) or empty($uygulama_adi))) {
            return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => "Parametre Eksik", "erisim_durumu" => "");
        } else if ($kaynak == "edevlet" and (empty($tcno) or empty($uygulama_id) or empty($uygulama_adi))) {
            return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => "Parametre Eksik", "erisim_durumu" => "");
        } else {

            $q1 = $this->query("SELECT * FROM anket_uygulamasi.yetkili WHERE tc='$tcno' and yetkili_durumu='1' ");
            $rowc1 = $this->num_rows($q1);
            if ($rowc1 < 1) {
                $this->loginerror($tcno, GetIP(), $uygulama_id, $ldapusername, $kaynak);
                return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => $uygulama_adi . " Yetkisi Bulunamadı");
            } else {
                $row = $this->fetch_assoc($q1);

                $erisim_durumu = "";

                //$erisim_durumu = $this->erisim_kontrol($uygulama_id, 9, $tcno, $ldapusername);
                $erisim_durumu = 1;

                if ($erisim_durumu == 1) {


                    $_SESSION['anket_uygulamasi_admin_durumu'] = $row['yetkili_durumu'];
                    $_SESSION['anket_uygulamasi_admin_id'] = $row['id'];
                    $_SESSION['anket_uygulamasi_admin_name'] = $row['adi'] . ' ' . $row['soyadi'];
                    $_SESSION['anket_uygulamasi_admin_yetki_turu'] = $row['yetki_turu'];
                    $_SESSION['anket_uygulamasi_admin_yetki_mud_id'] = $row['mud_id'];
                    $_SESSION['anket_uygulamasi_admin_yetki_birim_id'] = $row['birim_id'];
                    $_SESSION['anket_uygulamasi_admin_yetki_alt_birim_id'] = $row['alt_birim_id'];


                    $this->loginlog((int)$row['id'], $row['tc'], $row['adi'] . ' ' . $row['soyadi'], $uygulama_id, GetIP(), $ldapusername, $kaynak);
                    // header('Location: index.php');
                    return $array = array("hata" => 0, "success" => 1, "uygulama_id" => $uygulama_id, "erisim_durumu" => $erisim_durumu);

                } else if ($erisim_durumu == 2) {
                    $this->loginerror($tcno, GetIP(), $uygulama_id, $ldapusername, $kaynak);
                    return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => $uygulama_adi . " Yetki Formu Bulunmamaktadır", "erisim_durumu" => $erisim_durumu);

                } else if ($erisim_durumu == 0) {
                    $this->loginerror($tcno, GetIP(), $uygulama_id, $ldapusername, $kaynak);
                    return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => $uygulama_adi . " Yetkisi Bulunamadı", "erisim_durumu" => $erisim_durumu);

                }
            }
        }
    }

    function aile_cocuk_login($tcno, $ldapusername, $uygulama_id, $uygulama_adi, $kaynak)
    {
        $tcno = rescape($tcno);
        $ldapusername = rescape($ldapusername);
        $uygulama_id = rescape($uygulama_id);
        $uygulama_adi = rescape($uygulama_adi);
        $kaynak = rescape($kaynak);
        if (empty($kaynak)) {
            return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => "Parametre Eksik", "erisim_durumu" => "");
        } else if ($kaynak == "ldap" and (empty($tcno) or empty($ldapusername) or empty($uygulama_id) or empty($uygulama_adi))) {
            return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => "Parametre Eksik", "erisim_durumu" => "");
        } else if ($kaynak == "edevlet" and (empty($tcno) or empty($uygulama_id) or empty($uygulama_adi))) {
            return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => "Parametre Eksik", "erisim_durumu" => "");
        } else {

            $q1 = $this->query("SELECT * FROM ailecocuk.yetkili WHERE tc='$tcno' and yetkili_durumu='1' ");
            $rowc1 = $this->num_rows($q1);
            if ($rowc1 < 1) {
                $this->loginerror($tcno, GetIP(), $uygulama_id, $ldapusername, $kaynak);
                return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => $uygulama_adi . " Yetkisi Bulunamadı");
            } else {
                $row = $this->fetch_assoc($q1);

                $erisim_durumu = "";

                //$erisim_durumu = $this->erisim_kontrol($uygulama_id, 9, $tcno, $ldapusername);
                $erisim_durumu = 1;

                if ($erisim_durumu == 1) {


                    $_SESSION['aile_cocuk_admin_yetkili_durumu'] = $row['yetkili_durumu'];
                    $_SESSION['aile_cocuk_admin_id'] = $row['id'];
                    $_SESSION['aile_cocuk_admin_name'] = $row['adi'] . ' ' . $row['soyadi'];
                    $_SESSION['aile_cocuk_admin_ana_icerik'] = $row['ana_icerik'];
                    $_SESSION['aile_cocuk_admin_foto_galeri'] = $row['foto_galeri'];
                    $_SESSION['aile_cocuk_admin_video_galeri'] = $row['video_galeri'];
                    $_SESSION['aile_cocuk_admin_banner'] = $row['banner'];
                    $_SESSION['aile_cocuk_admin_popup'] = $row['popup'];
                    $_SESSION['aile_cocuk_admin_dosya'] = $row['dosya'];
                    $_SESSION['aile_cocuk_admin_ayarlar'] = $row['ayarlar'];
                    $_SESSION['aile_cocuk_admin_yetkili'] = $row['yetkili'];
                    $_SESSION['aile_cocuk_admin_haberdar_ol'] = $row['haberdar_ol'];
                    $_SESSION['aile_cocuk_admin_atigim_var'] = $row['atigim_var'];
                    $_SESSION['aile_cocuk_admin_okullara_ozel'] = $row['okullara_ozel'];

                    $_SESSION['aile_cocuk_admin_kategori_ekle'] = $row['kategori_ekle'];
                    $_SESSION['aaile_cocuk_admin_kategori_duzenle'] = $row['kategori_duzenle'];
                    $_SESSION['aile_cocuk_admin_yeni_basvurular'] = $row['yeni_basvurular'];
                    $_SESSION['aile_cocuk_admin_iptal_olan_basvurular'] = $row['iptal_olan_basvurular'];
                    $_SESSION['aile_cocuk_admin_ulasilamayan_basvurular'] = $row['ulasilamayan_basvurular'];
                    $_SESSION['aile_cocuk_admin_devam_eden_basvurular'] = $row['devam_eden_basvurular'];
                    $_SESSION['aile_cocuk_admin_tamamlanan_basvurular'] = $row['tamamlanan_basvurular'];
                    $_SESSION['aile_cocuk_admin_yeni_randevular'] = $row['yeni_randevular'];
                    $_SESSION['aile_cocuk_admin_danisanlarim'] = $row['danisanlarim'];
                    $_SESSION['aile_cocuk_admin_randevu_verilecekler'] = $row['randevu_verilecekler'];
                    $_SESSION['aile_cocuk_admin_randevu_gecmisi'] = $row['randevu_gecmisi'];
                    $_SESSION['aile_cocuk_admin_randevu_takvimi'] = $row['randevu_takvimi'];


                    $this->loginlog((int)$row['id'], $row['tc'], $row['adi'] . ' ' . $row['soyadi'], $uygulama_id, GetIP(), $ldapusername, $kaynak);
                    // header('Location: index.php');
                    return $array = array("hata" => 0, "success" => 1, "uygulama_id" => $uygulama_id, "erisim_durumu" => $erisim_durumu);

                } else if ($erisim_durumu == 2) {
                    $this->loginerror($tcno, GetIP(), $uygulama_id, $ldapusername, $kaynak);
                    return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => $uygulama_adi . " Yetki Formu Bulunmamaktadır", "erisim_durumu" => $erisim_durumu);

                } else if ($erisim_durumu == 0) {
                    $this->loginerror($tcno, GetIP(), $uygulama_id, $ldapusername, $kaynak);
                    return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => $uygulama_adi . " Yetkisi Bulunamadı", "erisim_durumu" => $erisim_durumu);

                }


            }
        }
    }

    function kecikart_login($tcno, $ldapusername, $uygulama_id, $uygulama_adi, $kaynak)
    {
        $tcno = rescape($tcno);
        $ldapusername = rescape($ldapusername);
        $uygulama_id = rescape($uygulama_id);
        $uygulama_adi = rescape($uygulama_adi);
        $kaynak = rescape($kaynak);
        if (empty($kaynak)) {
            return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => "Parametre Eksik", "erisim_durumu" => "");
        } else if ($kaynak == "ldap" and (empty($tcno) or empty($ldapusername) or empty($uygulama_id) or empty($uygulama_adi))) {
            return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => "Parametre Eksik", "erisim_durumu" => "");
        } else if ($kaynak == "edevlet" and (empty($tcno) or empty($uygulama_id) or empty($uygulama_adi))) {
            return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => "Parametre Eksik", "erisim_durumu" => "");
        } else {

            $q1 = $this->query("SELECT * FROM kecikart.yetkili WHERE tc='$tcno' and yetkili_durumu='1' ");
            $rowc1 = $this->num_rows($q1);
            if ($rowc1 < 1) {
                $this->loginerror($tcno, GetIP(), $uygulama_id, $ldapusername, $kaynak);
                return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => $uygulama_adi . " Yetkisi Bulunamadı");
            } else {
                $row = $this->fetch_assoc($q1);

                $erisim_durumu = "";

                //$erisim_durumu = $this->erisim_kontrol($uygulama_id, 9, $tcno, $ldapusername);
                $erisim_durumu = 1;

                if ($erisim_durumu == 1) {


                    $_SESSION['kecikart_admin_yetkili_durumu'] = $row['yetkili_durumu'];
                    $_SESSION['kecikart_admin_id'] = $row['id'];
                    $_SESSION['kecikart_admin_name'] = $row['adi'] . ' ' . $row['soyadi'];
                    $_SESSION['kecikart_admin_ana_icerik'] = $row['ana_icerik'];
                    $_SESSION['kecikart_admin_foto_galeri'] = $row['foto_galeri'];
                    $_SESSION['kecikart_admin_video_galeri'] = $row['video_galeri'];
                    $_SESSION['kecikart_admin_banner'] = $row['banner'];
                    $_SESSION['kecikart_admin_popup'] = $row['popup'];
                    $_SESSION['kecikart_admin_dosya'] = $row['dosya'];
                    $_SESSION['kecikart_admin_ayarlar'] = $row['ayarlar'];
                    $_SESSION['kecikart_admin_yetkili'] = $row['yetkili'];
                    $_SESSION['kecikart_admin_haberdar_ol'] = $row['haberdar_ol'];
                    $_SESSION['kecikart_admin_atigim_var'] = $row['atigim_var'];
                    $_SESSION['kecikart_admin_okullara_ozel'] = $row['okullara_ozel'];

                    $_SESSION['kecikart_admin_kategori_ekle'] = $row['kategori_ekle'];
                    $_SESSION['kecikart_admin_kategori_duzenle'] = $row['kategori_duzenle'];
                    $_SESSION['kecikart_admin_yeni_basvurular'] = $row['yeni_basvurular'];
                    $_SESSION['kecikart_admin_iptal_olan_basvurular'] = $row['iptal_olan_basvurular'];
                    $_SESSION['kecikart_admin_ulasilamayan_basvurular'] = $row['ulasilamayan_basvurular'];
                    $_SESSION['kecikart_admin_devam_eden_basvurular'] = $row['devam_eden_basvurular'];
                    $_SESSION['kecikart_admin_tamamlanan_basvurular'] = $row['tamamlanan_basvurular'];
                    $_SESSION['kecikart_admin_yeni_randevular'] = $row['yeni_randevular'];
                    $_SESSION['kecikart_admin_danisanlarim'] = $row['danisanlarim'];
                    $_SESSION['kecikart_admin_randevu_verilecekler'] = $row['randevu_verilecekler'];
                    $_SESSION['kecikart_admin_randevu_gecmisi'] = $row['randevu_gecmisi'];
                    $_SESSION['kecikart_admin_randevu_takvimi'] = $row['randevu_takvimi'];


                    $this->loginlog((int)$row['id'], $row['tc'], $row['adi'] . ' ' . $row['soyadi'], $uygulama_id, GetIP(), $ldapusername, $kaynak);
                    // header('Location: index.php');
                    return $array = array("hata" => 0, "success" => 1, "uygulama_id" => $uygulama_id, "erisim_durumu" => $erisim_durumu);

                } else if ($erisim_durumu == 2) {
                    $this->loginerror($tcno, GetIP(), $uygulama_id, $ldapusername, $kaynak);
                    return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => $uygulama_adi . " Yetki Formu Bulunmamaktadır", "erisim_durumu" => $erisim_durumu);

                } else if ($erisim_durumu == 0) {
                    $this->loginerror($tcno, GetIP(), $uygulama_id, $ldapusername, $kaynak);
                    return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => $uygulama_adi . " Yetkisi Bulunamadı", "erisim_durumu" => $erisim_durumu);

                }
            }
        }
    }

    function ziyaretci_takip_login($tcno, $ldapusername, $uygulama_id, $uygulama_adi, $kaynak)
    {
        $tcno = rescape($tcno);
        $ldapusername = rescape($ldapusername);
        $uygulama_id = rescape($uygulama_id);
        $uygulama_adi = rescape($uygulama_adi);
        $kaynak = rescape($kaynak);
        if (empty($kaynak)) {
            return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => "Parametre Eksik", "erisim_durumu" => "");
        } else if ($kaynak == "ldap" and (empty($tcno) or empty($ldapusername) or empty($uygulama_id) or empty($uygulama_adi))) {
            return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => "Parametre Eksik", "erisim_durumu" => "");
        } else if ($kaynak == "edevlet" and (empty($tcno) or empty($uygulama_id) or empty($uygulama_adi))) {
            return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => "Parametre Eksik", "erisim_durumu" => "");
        } else {

            $q1 = $this->query("SELECT * FROM ziyaretci_takip.yetkili WHERE tc='$tcno' and yetkili_durumu='1' ");
            $rowc1 = $this->num_rows($q1);
            if ($rowc1 < 1) {
                $this->loginerror($tcno, GetIP(), $uygulama_id, $ldapusername, $kaynak);
                return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => $uygulama_adi . " Yetkisi Bulunamadı");
            } else {
                $row = $this->fetch_assoc($q1);

                $erisim_durumu = "";

                //$erisim_durumu = $this->erisim_kontrol($uygulama_id, 9, $tcno, $ldapusername);
                $erisim_durumu = 1;

                if ($erisim_durumu == 1) {


                    $_SESSION['ziyaretci_takip_admin_yetkili_durumu'] = $row['yetkili_durumu'];
                    $_SESSION['ziyaretci_takip_admin_id'] = $row['id'];
                    $_SESSION['ziyaretci_takip_admin_name'] = $row['adi'] . ' ' . $row['soyadi'];


                    $_SESSION['ziyaretci_takip_menu_yetkisi_ziyaret_islemleri'] = $row['ziyaret_islemleri'];
                    $_SESSION['ziyaretci_takip_menu_yetkisi_cikis_bilgisi_guncelle'] = $row['cikis_bilgisi_guncelle'];
                    $_SESSION['ziyaretci_takip_menu_yetkisi_ayarlar'] = $row['ayarlar'];
                    $_SESSION['ziyaretci_takip_menu_yetkisi_rapor_islemleri'] = $row['rapor_islemleri'];
                    $_SESSION['ziyaretci_takip_menu_yetkisi_duyuru_islemleri'] = $row['duyuru_islemleri'];
                    $_SESSION['ziyaretci_takip_menu_yetkisi_yetkili_yonetimi'] = $row['yetkili_yonetimi'];


                    $this->loginlog((int)$row['id'], $row['tc'], $row['adi'] . ' ' . $row['soyadi'], $uygulama_id, GetIP(), $ldapusername, $kaynak);
                    // header('Location: index.php');
                    return $array = array("hata" => 0, "success" => 1, "uygulama_id" => $uygulama_id, "erisim_durumu" => $erisim_durumu);

                } else if ($erisim_durumu == 2) {
                    $this->loginerror($tcno, GetIP(), $uygulama_id, $ldapusername, $kaynak);
                    return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => $uygulama_adi . " Yetki Formu Bulunmamaktadır", "erisim_durumu" => $erisim_durumu);

                } else if ($erisim_durumu == 0) {
                    $this->loginerror($tcno, GetIP(), $uygulama_id, $ldapusername, $kaynak);
                    return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => $uygulama_adi . " Yetkisi Bulunamadı", "erisim_durumu" => $erisim_durumu);

                }
            }
        }
    }

    function badam_login($tcno, $ldapusername, $uygulama_id, $uygulama_adi, $kaynak)
    {
        $tcno = rescape($tcno);
        $ldapusername = rescape($ldapusername);
        $uygulama_id = rescape($uygulama_id);
        $uygulama_adi = rescape($uygulama_adi);
        $kaynak = rescape($kaynak);
        if (empty($kaynak)) {
            return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => "Parametre Eksik", "erisim_durumu" => "");
        } else if ($kaynak == "ldap" and (empty($tcno) or empty($ldapusername) or empty($uygulama_id) or empty($uygulama_adi))) {
            return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => "Parametre Eksik", "erisim_durumu" => "");
        } else if ($kaynak == "edevlet" and (empty($tcno) or empty($uygulama_id) or empty($uygulama_adi))) {
            return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => "Parametre Eksik", "erisim_durumu" => "");
        } else {

            $q1 = $this->query("SELECT * FROM badam.yetkili WHERE tc='$tcno' and yetkili_durumu='1' ");
            $rowc1 = $this->num_rows($q1);
            if ($rowc1 < 1) {
                $this->loginerror($tcno, GetIP(), $uygulama_id, $ldapusername, $kaynak);
                return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => $uygulama_adi . " Yetkisi Bulunamadı");
            } else {
                $row = $this->fetch_assoc($q1);

                $erisim_durumu = "";

                //$erisim_durumu = $this->erisim_kontrol($uygulama_id, 9, $tcno, $ldapusername);
                $erisim_durumu = 1;

                if ($erisim_durumu == 1) {


                    $_SESSION['badam_admin_yetkili_durumu'] = $row['yetkili_durumu'];
                    $_SESSION['badam_admin_id'] = $row['id'];
                    $_SESSION['badam_admin_name'] = $row['adi'] . ' ' . $row['soyadi'];
                    $_SESSION['badam_admin_ana_icerik'] = $row['ana_icerik'];
                    $_SESSION['badam_admin_foto_galeri'] = $row['foto_galeri'];
                    $_SESSION['badam_admin_video_galeri'] = $row['video_galeri'];
                    $_SESSION['badam_admin_banner'] = $row['banner'];
                    $_SESSION['badam_admin_popup'] = $row['popup'];
                    $_SESSION['badam_admin_dosya'] = $row['dosya'];
                    $_SESSION['badam_admin_ayarlar'] = $row['ayarlar'];
                    $_SESSION['badam_admin_yetkili'] = $row['yetkili'];
                    $_SESSION['badam_admin_haberdar_ol'] = $row['haberdar_ol'];
                    $_SESSION['badam_admin_atigim_var'] = $row['atigim_var'];
                    $_SESSION['badam_admin_okullara_ozel'] = $row['okullara_ozel'];
                    $_SESSION['badam_admin_basvuru_takip'] = $row['basvuru_takip'];

                    $_SESSION['badam_admin_kategori_ekle'] = $row['kategori_ekle'];
                    $_SESSION['badam_admin_kategori_duzenle'] = $row['kategori_duzenle'];
                    $_SESSION['badam_admin_yeni_basvurular'] = $row['yeni_basvurular'];
                    $_SESSION['badam_admin_iptal_olan_basvurular'] = $row['iptal_olan_basvurular'];
                    $_SESSION['badam_admin_ulasilamayan_basvurular'] = $row['ulasilamayan_basvurular'];
                    $_SESSION['badam_admin_devam_eden_basvurular'] = $row['devam_eden_basvurular'];
                    $_SESSION['badam_admin_tamamlanan_basvurular'] = $row['tamamlanan_basvurular'];
                    $_SESSION['badam_admin_yeni_randevular'] = $row['yeni_randevular'];
                    $_SESSION['badam_admin_danisanlarim'] = $row['danisanlarim'];
                    $_SESSION['badam_admin_randevu_verilecekler'] = $row['randevu_verilecekler'];
                    $_SESSION['badam_admin_randevu_gecmisi'] = $row['randevu_gecmisi'];
                    $_SESSION['badam_admin_randevu_takvimi'] = $row['randevu_takvimi'];

                    $_SESSION['badam_admin_master'] = $row['master'];
                    $_SESSION['badam_admin_uzman'] = $row['uzman'];


                    $this->loginlog((int)$row['id'], $row['tc'], $row['adi'] . ' ' . $row['soyadi'], $uygulama_id, GetIP(), $ldapusername, $kaynak);
                    // header('Location: index.php');
                    return $array = array("hata" => 0, "success" => 1, "uygulama_id" => $uygulama_id, "erisim_durumu" => $erisim_durumu);

                } else if ($erisim_durumu == 2) {
                    $this->loginerror($tcno, GetIP(), $uygulama_id, $ldapusername, $kaynak);
                    return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => $uygulama_adi . " Yetki Formu Bulunmamaktadır", "erisim_durumu" => $erisim_durumu);

                } else if ($erisim_durumu == 0) {
                    $this->loginerror($tcno, GetIP(), $uygulama_id, $ldapusername, $kaynak);
                    return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => $uygulama_adi . " Yetkisi Bulunamadı", "erisim_durumu" => $erisim_durumu);

                }
            }
        }
    }

    function cocuksanatmuzesi_login($tcno, $ldapusername, $uygulama_id, $uygulama_adi, $kaynak)
    {
        $tcno = rescape($tcno);
        $ldapusername = rescape($ldapusername);
        $uygulama_id = rescape($uygulama_id);
        $uygulama_adi = rescape($uygulama_adi);
        $kaynak = rescape($kaynak);
        if (empty($kaynak)) {
            return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => "Parametre Eksik", "erisim_durumu" => "");
        } else if ($kaynak == "ldap" and (empty($tcno) or empty($ldapusername) or empty($uygulama_id) or empty($uygulama_adi))) {
            return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => "Parametre Eksik", "erisim_durumu" => "");
        } else if ($kaynak == "edevlet" and (empty($tcno) or empty($uygulama_id) or empty($uygulama_adi))) {
            return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => "Parametre Eksik", "erisim_durumu" => "");
        } else {

            $q1 = $this->query("SELECT * FROM cocuksanatmuzesi.yetkili WHERE tc='$tcno' and yetkili_durumu='1' ");
            $rowc1 = $this->num_rows($q1);
            if ($rowc1 < 1) {
                $this->loginerror($tcno, GetIP(), $uygulama_id, $ldapusername, $kaynak);
                return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => $uygulama_adi . " Yetkisi Bulunamadı");
            } else {
                $row = $this->fetch_assoc($q1);

                $erisim_durumu = "";

                //$erisim_durumu = $this->erisim_kontrol($uygulama_id, 9, $tcno, $ldapusername);
                $erisim_durumu = 1;

                if ($erisim_durumu == 1) {


                    $_SESSION['cocuksanatmuzesi_admin_yetkili_durumu'] = $row['yetkili_durumu'];
                    $_SESSION['cocuksanatmuzesi_admin_id'] = $row['id'];
                    $_SESSION['cocuksanatmuzesi_admin_name'] = $row['adi'] . ' ' . $row['soyadi'];
                    $_SESSION['cocuksanatmuzesi_admin_ana_icerik'] = $row['ana_icerik'];
                    $_SESSION['cocuksanatmuzesi_admin_foto_galeri'] = $row['foto_galeri'];
                    $_SESSION['cocuksanatmuzesi_admin_video_galeri'] = $row['video_galeri'];
                    $_SESSION['cocuksanatmuzesi_admin_banner'] = $row['banner'];
                    $_SESSION['cocuksanatmuzesi_admin_popup'] = $row['popup'];
                    $_SESSION['cocuksanatmuzesi_admin_dosya'] = $row['dosya'];
                    $_SESSION['cocuksanatmuzesi_admin_ayarlar'] = $row['ayarlar'];
                    $_SESSION['cocuksanatmuzesi_admin_yetkili'] = $row['yetkili'];
                    /*$_SESSION['badam_admin_haberdar_ol'] = $row['haberdar_ol'];
                    $_SESSION['badam_admin_atigim_var'] = $row['atigim_var'];
                    $_SESSION['badam_admin_okullara_ozel'] = $row['okullara_ozel'];
                    $_SESSION['badam_admin_basvuru_takip'] = $row['basvuru_takip'];*/


                    $this->loginlog((int)$row['id'], $row['tc'], $row['adi'] . ' ' . $row['soyadi'], $uygulama_id, GetIP(), $ldapusername, $kaynak);
                    // header('Location: index.php');
                    return $array = array("hata" => 0, "success" => 1, "uygulama_id" => $uygulama_id, "erisim_durumu" => $erisim_durumu);

                } else if ($erisim_durumu == 2) {
                    $this->loginerror($tcno, GetIP(), $uygulama_id, $ldapusername, $kaynak);
                    return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => $uygulama_adi . " Yetki Formu Bulunmamaktadır", "erisim_durumu" => $erisim_durumu);

                } else if ($erisim_durumu == 0) {
                    $this->loginerror($tcno, GetIP(), $uygulama_id, $ldapusername, $kaynak);
                    return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => $uygulama_adi . " Yetkisi Bulunamadı", "erisim_durumu" => $erisim_durumu);

                }
            }
        }
    }

    function netmasa_login($tcno, $ldapusername, $uygulama_id, $uygulama_adi, $kaynak)
    {
        "netmasa_login";

        $tcno = rescape($tcno);
        $ldapusername = rescape($ldapusername);
        $uygulama_id = rescape($uygulama_id);
        $uygulama_adi = rescape($uygulama_adi);
        $kaynak = rescape($kaynak);
        if (empty($kaynak)) {
            return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => "Parametre Eksik", "erisim_durumu" => "");
        } else if ($kaynak == "ldap" and (empty($tcno) or empty($ldapusername) or empty($uygulama_id) or empty($uygulama_adi))) {
            return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => "Parametre Eksik", "erisim_durumu" => "");
        } else if ($kaynak == "edevlet" and (empty($tcno) or empty($uygulama_id) or empty($uygulama_adi))) {
            return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => "Parametre Eksik", "erisim_durumu" => "");
        } else {

            $q1 = $this->query("SELECT * FROM ailecocuk.yetkili WHERE tc='$tcno' and yetkili_durumu='1' ");
            $rowc1 = $this->num_rows($q1);
            if ($rowc1 < 1) {
                $this->loginerror($tcno, GetIP(), $uygulama_id, $ldapusername, $kaynak);
                return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => $uygulama_adi . " Yetkisi Bulunamadı");
            } else {
                $row = $this->fetch_assoc($q1);

                $erisim_durumu = "";

                //$erisim_durumu = $this->erisim_kontrol($uygulama_id, 9, $tcno, $ldapusername);
                $erisim_durumu = 1;

                if ($erisim_durumu == 1) {


                    $_SESSION['netmasa_admin_yetkili_durumu'] = $row['yetkili_durumu'];
                    $_SESSION['netmasa_admin_id'] = $row['id'];
                    $_SESSION['netmasa_admin_name'] = $row['adi'] . ' ' . $row['soyadi'];
                    $_SESSION['netmasa_admin_ana_icerik'] = $row['ana_icerik'];
                    $_SESSION['netmasa_admin_foto_galeri'] = $row['foto_galeri'];
                    $_SESSION['netmasa_admin_video_galeri'] = $row['video_galeri'];
                    $_SESSION['netmasa_admin_banner'] = $row['banner'];
                    $_SESSION['netmasa_admin_popup'] = $row['popup'];
                    $_SESSION['netmasa_admin_dosya'] = $row['dosya'];
                    $_SESSION['netmasa_admin_ayarlar'] = $row['ayarlar'];
                    $_SESSION['netmasa_admin_yetkili'] = $row['yetkili'];
                    $_SESSION['netmasa_admin_haberdar_ol'] = $row['haberdar_ol'];
                    $_SESSION['netmasa_admin_atigim_var'] = $row['atigim_var'];
                    $_SESSION['netmasa_admin_okullara_ozel'] = $row['okullara_ozel'];

                    $_SESSION['netmasa_admin_kategori_ekle'] = $row['kategori_ekle'];
                    $_SESSION['netmasa_admin_kategori_duzenle'] = $row['kategori_duzenle'];
                    $_SESSION['netmasa_admin_yeni_basvurular'] = $row['yeni_basvurular'];
                    $_SESSION['netmasa_admin_iptal_olan_basvurular'] = $row['iptal_olan_basvurular'];
                    $_SESSION['netmasa_admin_ulasilamayan_basvurular'] = $row['ulasilamayan_basvurular'];
                    $_SESSION['netmasa_admin_devam_eden_basvurular'] = $row['devam_eden_basvurular'];
                    $_SESSION['netmasa_admin_tamamlanan_basvurular'] = $row['tamamlanan_basvurular'];
                    $_SESSION['netmasa_admin_yeni_randevular'] = $row['yeni_randevular'];
                    $_SESSION['netmasa_admin_danisanlarim'] = $row['danisanlarim'];
                    $_SESSION['netmasa_admin_randevu_verilecekler'] = $row['randevu_verilecekler'];
                    $_SESSION['netmasa_admin_randevu_gecmisi'] = $row['randevu_gecmisi'];
                    $_SESSION['netmasa_admin_randevu_takvimi'] = $row['randevu_takvimi'];


                    $this->loginlog((int)$row['id'], $row['tc'], $row['adi'] . ' ' . $row['soyadi'], $uygulama_id, GetIP(), $ldapusername, $kaynak);
                    // header('Location: index.php');
                    return $array = array("hata" => 0, "success" => 1, "uygulama_id" => $uygulama_id, "erisim_durumu" => $erisim_durumu);

                } else if ($erisim_durumu == 2) {
                    $this->loginerror($tcno, GetIP(), $uygulama_id, $ldapusername, $kaynak);
                    return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => $uygulama_adi . " Yetki Formu Bulunmamaktadır", "erisim_durumu" => $erisim_durumu);

                } else if ($erisim_durumu == 0) {
                    $this->loginerror($tcno, GetIP(), $uygulama_id, $ldapusername, $kaynak);
                    return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => $uygulama_adi . " Yetkisi Bulunamadı", "erisim_durumu" => $erisim_durumu);

                }


            }
        }
    }

    function referans_takip_login($tcno, $ldapusername, $uygulama_id, $uygulama_adi, $kaynak)
    {
        $tcno = rescape($tcno);
        $ldapusername = rescape($ldapusername);
        $uygulama_id = rescape($uygulama_id);
        $uygulama_adi = rescape($uygulama_adi);
        $kaynak = rescape($kaynak);
        if (empty($kaynak)) {
            return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => "Parametre Eksik", "erisim_durumu" => "");
        } else if ($kaynak == "ldap" and (empty($tcno) or empty($ldapusername) or empty($uygulama_id) or empty($uygulama_adi))) {
            return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => "Parametre Eksik", "erisim_durumu" => "");
        } else if ($kaynak == "edevlet" and (empty($tcno) or empty($uygulama_id) or empty($uygulama_adi))) {
            return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => "Parametre Eksik", "erisim_durumu" => "");
        } else {

            $q1 = $this->query("SELECT * FROM referans_takip.yetkili WHERE tc='$tcno' and yetkili_durumu='1' ");
            $rowc1 = $this->num_rows($q1);
            if ($rowc1 < 1) {
                $this->loginerror($tcno, GetIP(), $uygulama_id, $ldapusername, $kaynak);
                return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => $uygulama_adi . " Yetkisi Bulunamadı");
            } else {
                $row = $this->fetch_assoc($q1);

                $erisim_durumu = "";

                //$erisim_durumu = $this->erisim_kontrol($uygulama_id, 9, $tcno, $ldapusername);
                $erisim_durumu = 1;

                if ($erisim_durumu == 1) {

                    $_SESSION['referans_takip_admin_yetkili_durumu'] = $row['yetkili_durumu'];
                    $_SESSION['referans_takip_admin_id'] = $row['id'];
                    $_SESSION['referans_takip_admin_name'] = $row['adi'] . ' ' . $row['soyadi'];

                    $this->loginlog((int)$row['id'], $row['tc'], $row['adi'] . ' ' . $row['soyadi'], $uygulama_id, GetIP(), $ldapusername, $kaynak);
                    // header('Location: index.php');
                    return $array = array("hata" => 0, "success" => 1, "uygulama_id" => $uygulama_id, "erisim_durumu" => $erisim_durumu);

                } else if ($erisim_durumu == 2) {
                    $this->loginerror($tcno, GetIP(), $uygulama_id, $ldapusername, $kaynak);
                    return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => $uygulama_adi . " Yetki Formu Bulunmamaktadır", "erisim_durumu" => $erisim_durumu);

                } else if ($erisim_durumu == 0) {
                    $this->loginerror($tcno, GetIP(), $uygulama_id, $ldapusername, $kaynak);
                    return $array = array("hata" => 1, "success" => 0, "uygulama_id" => $uygulama_id, "hata_aciklama" => $uygulama_adi . " Yetkisi Bulunamadı", "erisim_durumu" => $erisim_durumu);

                }
            }
        }
    }
}

$login_kontrol = new login_kontrol("10.1.1.203", "bim_takip_hs", ".XDs8K)1m65jF0@o", "bim_takip");
?>