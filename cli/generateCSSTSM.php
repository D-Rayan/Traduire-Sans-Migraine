<?php

$newFileCss = fopen(__DIR__ . "/../front/Main/tsm.css", "w");

function readFilesThroughDir($dir) {
    global $newFileCss;
    $includes = scandir($dir);
    foreach ($includes as $include) {
        if ($include === "." || $include === "..") {
            continue;
        }
        $path = $dir . "/" . $include;
        if (is_dir($path)) {
            readFilesThroughDir($path);
            continue;
        }
        if (strstr($include, ".css") === false || strstr($include, "tsm.min.css") !== false || strstr($include, "tsm.css") !== false)   {
            continue;
        }
        $content = file_get_contents($path);
        fwrite($newFileCss, $content);
    }
}

readFilesThroughDir(__DIR__ . "/../front");
fclose($newFileCss);