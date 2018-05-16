<?php
// No longer in use, redirects configured in VirtualHost
// See example-apache-host.txt

/*if (strpos($GLOBALS["domain"], "www.") === 0) // www.example.com -> example.com
{
  header("Location: https://".str_replace("www.", "", $GLOBALS["domain"]).$_SERVER["REQUEST_URI"]);
  exit();
}

if(empty($_SERVER['HTTPS']) || $_SERVER['HTTPS'] == "off") // HTTP -> HTTPs
{
  header("Location: https://".$GLOBALS["domain"].$_SERVER["REQUEST_URI"]);
  exit();
}

if (!in_array($GLOBALS["domain"], $domains)) // Check whether domain is allowed
{
  die("Unknown domain: ".$GLOBALS["domain"]);
}*/
?>