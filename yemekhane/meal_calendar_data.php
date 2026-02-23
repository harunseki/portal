<?php
require_once "../../class/mysql.php";
header('Content-Type: application/json');

$tckn = $_SESSION['tckn'] ?? null;
if (!$tckn) {
    echo json_encode([]);
    exit;
}

/*
Tablo sütunları:
id, tckn, cardNumber, kayitTarihi, updateTarihi, startDate, finishDate
*/

$sql = "
SELECT startDate, finishDate
FROM meal_orders
WHERE tckn = ?
  AND finishDate >= CURDATE()
ORDER BY startDate ASC
LIMIT 1
";

$stmt = $dba->prepare($sql);
$stmt->bind_param("s", $tckn);
$stmt->execute();

$result = $stmt->get_result();
$order  = $result->fetch_assoc();

if (!$order) {
    echo json_encode([]);
    exit;
}

$start = new DateTime($order['startDate']);
$end   = new DateTime($order['finishDate']);
$today = new DateTime(date('Y-m-d'));

$events = [];

while ($start <= $end) {
    if ($start < $today) {
        $status = 'past';
    } elseif ($start == $today) {
        $status = 'today';
    } else {
        $status = 'future';
    }

    $events[] = [
        'date'   => $start->format('Y-m-d'),
        'status' => $status
    ];

    $start->modify('+1 day');
}

echo json_encode($events);