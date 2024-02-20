<?php
include "../env.global.php";
$file = fopen(__DIR__ . "/../languages/traduire-sans-migraine.pot", "w");
if (!$file) {
    die("Could not open file");
}
fwrite($file, "msgid \"\"\n");
fwrite($file, "msgstr \"\"\n");
fwrite($file, "\"Project-Id-Version: ".TSM__NAME." ".TSM__VERSION."\\n\"\n");
fwrite($file, "\"Report-Msgid-Bugs-To: https://wordpress.org/support/plugin/Traduire-Sans-Migraine\\n\"\n");
fwrite($file, "\"Last-Translator: Rayan <rayan@seo-sans-migraine.frfr>\\n\"\n");
fwrite($file, "\"Language-Team: EN\\n\"\n");
fwrite($file, "\"MIME-Version: 1.0\\n\"\n");
fwrite($file, "\"Content-Type: text/plain; charset=UTF-8\\n\"\n");
fwrite($file, "\"Content-Transfer-Encoding: 8bit\\n\"\n");
fwrite($file, "\"POT-Creation-Date: ".date("Y-m-d") . "T". date("H:i:sZ")."\\n\"\n");
fwrite($file, "\"X-Generator: Seo Sans Migraine\\n\"\n");
fwrite($file, "\"X-Domain: ".TSM__TEXT_DOMAIN."\\n\"\n");

function readFilesThroughDir($dir, $msgIds = ["PLACEHOLDER_VERSION" => true]) {
    global $file;
    $includes = scandir($dir);
    foreach ($includes as $include) {
        if ($include === "." || $include === "..") {
            continue;
        }
        $path = $dir . "/" . $include;
        if (is_dir($path)) {
            $msgIds = readFilesThroughDir($path, $msgIds);
            continue;
        }
        $content = file_get_contents($path);
        $regex = '/TextDomain::__\(\"([^"]*)\"/';
        preg_match_all($regex, $content, $matches);
        if (count($matches[1]) > 0) {
            fwrite($file, "# " . $include . "\n");
            foreach ($matches[1] as $match) {
                if (isset($msgIds[$match])) {
                    continue;
                }
                fwrite($file, "msgid \"" . $match . "\"\n");
                fwrite($file, "msgstr \"\"\n\n");
                $msgIds[$match] = true;
            }
        }
        $regex = '/TextDomain::_n\(\"([^"]*)\"/';
        preg_match_all($regex, $content, $matches);
        if (count($matches[1]) > 0) {
            fwrite($file, "# " . $include . "\n");
            foreach ($matches[1] as $match) {
                if (isset($msgIds[$match])) {
                    continue;
                }
                fwrite($file, "msgid \"" . $match . "\"\n");
                fwrite($file, "msgstr \"\"\n\n");
                $msgIds[$match] = true;
            }
        }

        $regex = '/^ \* ([^:]+): (.+)/m';
        preg_match_all($regex, $content, $matches);
        if (count($matches[2]) > 0) {
            foreach ($matches[2] as $index => $match) {
                if (isset($msgIds[$match])) {
                    continue;
                }
                fwrite($file, "# " . $matches[1][$index] . "\n");
                fwrite($file, "msgid \"" . $match . "\"\n");
                fwrite($file, "msgstr \"\"\n\n");
                $msgIds[$match] = true;
            }
        }
    }
    return $msgIds;
}

readFilesThroughDir(__DIR__ . "/..");
fclose($file);