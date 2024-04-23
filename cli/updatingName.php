<?php

include __DIR__ . "/../env.".$argv[1].".php";
$fileContent = file_get_contents(__DIR__ . "/../traduire-sans-migraine.php");
$fileContent = str_replace("PLACEHOLDER_NAME", TSM__NAME, $fileContent);
file_put_contents(__DIR__ . "/../traduire-sans-migraine.php", $fileContent);