<?php

if (preg_match('/_(images|thumbs)\/([0-9a-f]{32})\//', $_SERVER["REQUEST_URI"], $matches)) {
    $silo = $matches[1];
    $hash = $matches[2];
    $ha = substr($hash, 0, 2);
    header("Content-Type: image/jpeg");
    print(file_get_contents("data/$silo/$ha/$hash"));
} elseif (
    preg_match('/.*\.(jpg|jpeg|gif|png|ico|svg|js|css)/', $_SERVER["REQUEST_URI"])
    && file_exists($_SERVER["DOCUMENT_ROOT"] . $_SERVER["REQUEST_URI"])
) {
    return false;
} else {
    require_once("index.php");
}
