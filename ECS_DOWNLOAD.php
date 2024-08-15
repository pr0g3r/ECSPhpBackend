<?php

$filename = $_GET['path'];
$file = substr($filename, strrpos($filename, '/') + 1);

header("Content-type: Application/octet-stream");
header("Content-Disposition: attachment; filename=${file}");
header("Content-Description: My Download :)");
header("Content-Length: ". filesize($filename));
readfile($filename);
