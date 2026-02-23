<?php
ini_set('memory_limit','-1');
set_time_limit(0);

$serverName = "10.1.1.245";
$database = "proneta";
$username = "dbm";
$password = "1234qwER!";

$tc = $_POST['tc'] ?? '';

try {
    $pdo = new PDO("sqlsrv:Server=$serverName;Database=$database;Encrypt=no", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e){
    die(json_encode(['error' => $e->getMessage()]));
}

$sql = "
;WITH CihazLoglari AS (
    SELECT p.Tckimlikno, p.AdSoyad,
           CASE WHEN DATEADD(HOUR,3,l.TransmissionDateTime)<'2025-07-29'
                THEN DATEADD(HOUR,3,l.DateTime)
                ELSE DATEADD(HOUR,3,l.TransmissionDateTime)
           END AS Tarih,
           CASE WHEN l.type=1 THEN 'Giriş'
                WHEN l.type=2 THEN 'Çıkış' END AS HareketTuru,
           l.type AS LogType
    FROM dbo.PdksCardLog l
    INNER JOIN dbo.Personel p ON p.Id = l.PersonelId
    WHERE l.type IN (1,2)
    " . ($tc !== '' ? "AND p.Tckimlikno = :tc1" : "") . "
),
HesaplananLoglar AS (
    SELECT p.Tckimlikno, p.AdSoyad,
           CASE WHEN DATEADD(HOUR,3,l.TransmissionDateTime)<'2025-07-29'
                THEN DATEADD(HOUR,3,l.DateTime)
                ELSE DATEADD(HOUR,3,l.TransmissionDateTime)
           END AS Tarih,
           ROW_NUMBER() OVER (PARTITION BY p.Id, CONVERT(date,l.TransmissionDateTime)
                              ORDER BY l.TransmissionDateTime) AS SiraNo,
           l.type AS LogType
    FROM dbo.PdksCardLog l
    INNER JOIN dbo.Personel p ON p.Id = l.PersonelId
    WHERE l.type NOT IN (1,2)
    " . ($tc !== '' ? "AND p.Tckimlikno = :tc2" : "") . "
),
HesaplananSonuc AS (
    SELECT Tckimlikno, AdSoyad, Tarih,
           CASE WHEN SiraNo=1 THEN 'Giriş'
                WHEN SiraNo %2=1 THEN 'Giriş'
                ELSE 'Çıkış' END AS HareketTuru,
           LogType
    FROM HesaplananLoglar
)
SELECT * FROM (
    SELECT * FROM CihazLoglari
    UNION ALL
    SELECT * FROM HesaplananSonuc
) AS TUMU
ORDER BY AdSoyad, Tarih
";

$stmt = $pdo->prepare($sql);
if($tc!==''){
    $stmt->bindValue(':tc1', $tc, PDO::PARAM_STR);
    $stmt->bindValue(':tc2', $tc, PDO::PARAM_STR);
}
$stmt->execute();

// Satır satır çekiyoruz
$result = [];
while($row = $stmt->fetch(PDO::FETCH_ASSOC)){
    $result[] = $row;
}

header('Content-Type: application/json');
echo json_encode($result);
