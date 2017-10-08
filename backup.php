<?php
session_start();
require "config.php";
require "connect.php";
require "library.php";

if (!is_mod())
{
  die("Restricted");
}

$filename='db_'.time().'.sql';

$cmd = "(mysqldump discours --user=".MYSQL_USERNAME." --password=".MYSQL_PASSWORD." --single-transaction > absolute/$filename) 2>&1";

//echo $cmd."<br>";

$result = exec($cmd, $output);

//var_dump ($result);
var_dump ($output);

if(empty($output))
{
  echo "Backup ok!";
}

else
{
  echo "Error!";
}
?>