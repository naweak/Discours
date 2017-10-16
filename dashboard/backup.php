<?php
require "../config.php";
require_bundle();

if (!is_mod())
{
  die("Restricted");
}

$filename='db_'.time().'.sql';

$cmd = "(mysqldump discours --user=".MYSQL_USERNAME." --password=".MYSQL_PASSWORD." --single-transaction > ../absolute/$filename) 2>&1";

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