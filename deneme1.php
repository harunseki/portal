<?php
$ip = '10.2.200.20';
$ports = [4370, 5005, 80, 8080];

foreach ($ports as $port) {
    echo "Testing $ip:$port ... ";
    $fp = @fsockopen($ip, $port, $errno, $errstr, 3);
    if ($fp) {
        echo "✅ OPEN\n";
        fclose($fp);
    } else {
        echo "❌ CLOSED ($errstr)\n";
    }
}