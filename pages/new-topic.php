<?php
$start_microtime = microtime(true);

require "../config.php";
require_bundle();

echo render
(
  array
  (
    "display_new_topic_form" => true
  )
);

//echo microtime(true);
echo "<!--";
echo microtime(true)-$start_microtime;
echo "-->";
?>