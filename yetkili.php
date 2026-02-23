<?php
$file="yetkili";
require_once("inc/header.php");
require_once("inc/menu1.php");
require_once "inc/kontrol.php";
?>
<aside class="right-side">
    <section class="content-header">
        <div class="row">
            <div class="col-xs-6">
                <h2>Yetkili İşlemleri</h2>
            </div>
        </div>
    </section>
    <section class="content">
        <?php
        if ($file) {
            if(empty($x) or $x==1 ) require_once("$file/x1.php");
            else require_once("$file/x". $purifier->purify(rescape((int)$_GET['x'])) .".php");
        }
        ?>
    </section>
</aside>
<?php require_once("inc/footer.php"); ?>