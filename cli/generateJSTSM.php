<?php

$newFileJs = fopen(__DIR__ . "/../front/Main/tsm.js", "w");

function readFilesThroughDir($dir) {
    global $newFileJs;
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
        if (strstr($include, ".js") === false || strstr($include, "tsm.min.js") !== false || strstr($include, "tsm.js") !== false)   {
            continue;
        }
        $content = "// " . $path . PHP_EOL;
        $content .= file_get_contents($path);
        $content = str_replace("export ", "", $content);
        $content = preg_replace('/import (.*)/', '', $content);
        fwrite($newFileJs, $content . PHP_EOL);
    }
}

readFilesThroughDir(__DIR__ . "/../front/compiled");
fclose($newFileJs);