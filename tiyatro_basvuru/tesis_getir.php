<?php
ob_start();
session_start();

require_once("../mysql.php");
require_once("../class/functions.php");

require_once ($_SERVER['DOCUMENT_ROOT']."/htmlpurifier/library/HTMLPurifier.auto.php");
$purifier = new HTMLPurifier($config);

$tesis_id = $purifier->purify(rescape($_GET['tesis_id']));
?>
<div class="row">
    <?php
    if($tesis_id){
        $id = $purifier->purify(rescape($_GET['kategori']));

        $sql=" AND tesis.id IN (SELECT tesis FROM tesis_kategori WHERE kategori='$tesis_id' )";
    }
    $sql_arama = "SELECT * FROM tesis WHERE id!='' $sql ORDER BY sira ASC";
    $q = $dba->query($sql_arama);
    $link = "tesisler.html";
    $link_get = "";
    foreach ($_GET as $key => $value) {
        $key = $purifier->purify(rescape($key));
        $value = $purifier->purify(rescape($value));
        if ($key != "page" and $key != "x") {
            $link_get = $link_get . "&" . $key . "=" . $value;
        }
    }
    $paging = new nicePaging();
    $result = $paging->pagerQuery($sql_arama, 15);
    echo $dba->error();
    while ($rowh = mysqli_fetch_assoc($result)) { ?>
        <div class="col-md-4 col-sm-6">
            <style>
                .yazi-alt h5 a {
                    bottom: 10px;
                    position: absolute;
                }
            </style>
            <div class="news-post image-post">
                <div class="thumb">
                    <img src="https://www.kecioren.bel.tr/images/tesis/mini/<?= strip($rowh['photo']) ?>" alt="<?= strip($rowh['baslik']) ?>" style="height: 299px;"></div>
                <div class="news-post-txt yazi-alt">
                    <h5><a href="<?= chtext($rowh['baslik']) ?>-<?= (int)$rowh['id'] ?>-sosyal-tesis.html" title="<?= strip($rowh['baslik']) ?>"><?= strip($rowh['baslik']) ?></a></h5>
                </div>
            </div>
        </div>
    <?php } ?>
</div>
<div class="row" >
    <div class="site-pagination">
        <nav aria-label="Page navigation">
            <ul class="pagination">
                <?php
                $paging->setMaxPages(5);
                echo $paging->createPaging($link, $link_get);
                ?>
            </ul>
        </nav>
    </div>
</div>
