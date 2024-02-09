<?php

include "../env.global.php";
$fileContent = file_get_contents(__DIR__ . "/../traduire-sans-migraine.php");
$fileContent = str_replace("PLACEHOLDER_VERSION", TSM__VERSION, $fileContent);
file_put_contents(__DIR__ . "/../traduire-sans-migraine.php", $fileContent);