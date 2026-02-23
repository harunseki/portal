<?php
$orderCode   = $_POST['orderCode'] ?? '';
$amount      = $_POST['amount'] ?? '';
$callbackUrl = $_POST['callbackUrl'] ?? '';
$returnUrl   = $_POST['returnUrl'] ?? '';

if (!$orderCode || !$amount || !$callbackUrl || !$returnUrl) {
    die('Eksik banka isteği');
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Fake Bank POS</title>
</head>
<body style="text-align:center;margin-top:50px">

<h2>FAKE BANK POS</h2>
<p><b>Order:</b> <?= htmlspecialchars($orderCode) ?></p>
<p><b>Tutar:</b> <?= number_format($amount,2,',','.') ?> TL</p>

<form method="post" action="<?= htmlspecialchars($callbackUrl) ?>" id="bankForm">
    <input type="hidden" name="orderCode" value="<?= $orderCode ?>">
    <input type="hidden" name="amount" value="<?= $amount ?>">
    <input type="hidden" name="bankRef" value="BANK<?= rand(10000,99999) ?>">
    <input type="hidden" name="status" id="status">

    <input type="hidden" name="returnUrl" value="<?= htmlspecialchars($returnUrl) ?>">
</form>

<button onclick="pay('SUCCESS')" style="padding:10px 20px;">
    ✅ Ödeme Başarılı
</button>

<button onclick="pay('FAIL')" style="padding:10px 20px;">
    ❌ Ödeme Başarısız
</button>

<script>
    function pay(status) {
        document.getElementById('status').value = status;
        document.getElementById('bankForm').submit();
    }
</script>

</body>
</html>