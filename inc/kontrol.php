<?php
$currentFile = basename(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
$moduleKey   = pathinfo($currentFile, PATHINFO_FILENAME);

require_permission($moduleKey);