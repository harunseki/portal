<?php
ob_start();
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$file = "basvuru";
require_once "inc/header.php";
require_once "inc/menu1.php";

require_once "class/mysql.php";

$id = (int)($_GET['id'] ?? 0);
$ldap_username = $_SESSION['ldap_username'] ?? '';

$stmt = $dba->prepare("
SELECT isim, hedef_url, parametreler, style
FROM mod_moduller
WHERE id = ? AND aktif = 1
");

$stmt->bind_param("i", $id);
$stmt->execute();

$modul = $stmt->get_result()->fetch_assoc();

if (!$modul) {
    die("Modül bulunamadı");
}

$iframe_url = $modul['hedef_url'];
$iframe_style = $modul['style'];
$params = [];

if (!empty($modul['parametreler'])) {
    $ids = array_map('intval', explode(',', $modul['parametreler']));
    $placeholders = implode(',', array_fill(0, count($ids), '?'));

    $types = str_repeat('i', count($ids));

    $stmt2 = $dba->prepare("SELECT session_key FROM session_parameters WHERE id IN ($placeholders)");

    $stmt2->bind_param($types, ...$ids);
    $stmt2->execute();
    $result2 = $stmt2->get_result();

    while ($row = $result2->fetch_assoc()) {
        $sessionKey = $row['session_key'];

        if (isset($_SESSION[$sessionKey])) {
            $params[$row['session_key']] = $_SESSION[$sessionKey];
        }
    }
}

if (!empty($params)) {
    $iframe_url .= '?' . http_build_query($params);
}

$parsed = parse_url($iframe_url);
$origin = $parsed['scheme'] . '://' . $parsed['host']
        . (isset($parsed['port']) ? ':' . $parsed['port'] : '');


?>
<aside class="right-side">
    <section class="content-header clearfix">
        <div class="pull-left">
            <h2><?= htmlspecialchars($modul['isim']) ?></h2>
        </div>
    </section>
    <section class="content" style="padding: 0;">
        <iframe id="child" src="<?= htmlspecialchars($iframe_url) ?>" style="<?= $iframe_style ?>" sandbox="allow-scripts allow-same-origin allow-forms allow-popups allow-popups-to-escape-sandbox allow-downloads">
        </iframe>
    </section>
</aside>
    <script>
        /*document.addEventListener("DOMContentLoaded", function () {

            const iframe = document.getElementById("child");
            const childOrigin = new URL(iframe.src).origin;

            let childReady = false;

            // 1️⃣ Mesaj dinleyici
            window.addEventListener("message", function (event) {

                // Origin kontrolü
                if (event.origin !== childOrigin) return;

                // Format kontrolü
                if (!event.data || typeof event.data !== "object") return;
                if (event.data.app !== "MY_IFRAME_APP") return;

                console.log("Child mesajı:", event.data);

                switch (event.data.type) {

                    case "READY":
                        childReady = true;
                        sendHello();
                        break;

                    case "ACK":
                        console.log("Child HELLO mesajını aldı.");
                        break;

                    case "RESIZE":
                        iframe.style.height = event.data.height + "px";
                        break;

                }

            });

            // 2️⃣ Mesaj gönderme fonksiyonu
            function sendHello() {

                if (!childReady) return;

                iframe.contentWindow.postMessage(
                    {
                        app: "MY_IFRAME_APP",
                        type: "HELLO",
                        text: "Merhaba iframe!"
                    },
                    childOrigin
                );
            }

        });*/
    </script>

<?php require_once "inc/footer.php"; ?>