<?php
$conn = oci_connect("KULTURSOSYAL", "yKsWQ+12+14", "cnkdb20-scan/ORCL");
if ($conn) {
    echo "Oracle bağlantısı başarılı!";
} else {
    $e = oci_error();
    echo "Bağlantı hatası: " . $e['message'];
}
?>
