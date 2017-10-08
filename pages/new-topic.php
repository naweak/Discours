<?php
$start_microtime = microtime(true);

session_start();
require "../config.php";
require "../connect.php";
require "../library.php";

echo render(
  array
  (
    "display_new_topic_form" => true
  ),
  
  "../twig_templates/default/");

//echo microtime(true);
echo "<!--";
echo microtime(true)-$start_microtime;
echo "-->";
?>