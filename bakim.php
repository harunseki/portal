<?php http_response_code(503); ?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Bakım Çalışması</title>

    <!-- Cache engelleme -->
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate" />
    <meta http-equiv="Pragma" content="no-cache" />
    <meta http-equiv="Expires" content="0" />

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- İstersen otomatik yenileme (ör: 60 sn) -->
    <!-- <meta http-equiv="refresh" content="60"> -->

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            padding: 0;
            font-family: Arial, Helvetica, sans-serif;
            background-color: #f4f6f8;
            color: #333;
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100vh;
        }

        .maintenance-box {
            background: #ffffff;
            padding: 40px;
            max-width: 520px;
            width: 90%;
            text-align: center;
            border-radius: 10px;
            box-shadow: 0 6px 18px rgba(0,0,0,0.12);
        }

        .maintenance-box h1 {
            margin-bottom: 20px;
            font-size: 22px;
            color: #2c3e50;
            line-height: 1.4;
        }

        .maintenance-box p {
            font-size: 16px;
            line-height: 1.6;
        }

        .maintenance-box .note {
            margin-top: 20px;
            font-size: 14px;
            color: #777;
        }

        .icon {
            font-size: 50px;
            margin-bottom: 15px;
            color: #e67e22;
        }

        @media (max-width: 480px) {
            .maintenance-box {
                padding: 25px;
            }

            .maintenance-box h1 {
                font-size: 20px;
            }
        }
    </style>
</head>

<body>

<div class="maintenance-box">

    <div class="icon">⚙️</div>
    <img src="img/logo-cankaya.png" style="height:60px;">
    <h1>
        Sistemimizde planlı bakım çalışması yapılmaktadır
    </h1>

    <p>
        Bu süre zarfında hizmet verilememektedir.<br>
        Çalışmalar en kısa sürede tamamlanacaktır.
    </p>

    <div class="note">
        Anlayışınız için teşekkür ederiz.<br>
        Lütfen daha sonra tekrar deneyiniz.
    </div>

</div>

</body>
</html>