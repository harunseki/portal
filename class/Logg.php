<?php

class Logger
{
    function log($logtext)
    {
        $today = date("d.m.Y");
        $fp = fopen("logg/" . $today . '-log.txt', 'a+');
        fwrite($fp, $logtext);
        fclose($fp);
    }
}

?>