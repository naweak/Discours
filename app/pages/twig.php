<?php
$seconds_to_cache = 3600;
$ts = gmdate("D, d M Y H:i:s", time() + $seconds_to_cache) . " GMT";
header("Expires: $ts");
header("Pragma: cache");
header("Cache-Control: max-age=$seconds_to_cache");

header("Content-Type: text/plain");
echo @file_get_contents(DEFAULT_TWIG_FILESYSTEM."/".TWIG_HTML_FILENAME);
?>