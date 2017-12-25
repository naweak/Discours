<?php
header("Content-Type: text/plain");
echo @file_get_contents(DEFAULT_TWIG_FILESYSTEM."/default.html");
?>