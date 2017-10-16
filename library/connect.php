<?php
/* Dirty hack, will switch to PDO or some other library later */

function mysql_connect ($server, $username, $password)
{
  return mysqli_connect($server, $username, $password);
}

function mysql_select_db ($db)
{
  return mysqli_select_db($GLOBALS["mysql_connection"], $db);
}

function mysql_real_escape_string ($string)
{
  return mysqli_real_escape_string($GLOBALS["mysql_connection"], $string);
}

function mysql_query ($string)
{
  return mysqli_query($GLOBALS["mysql_connection"], $string);
}

function mysql_num_rows ($query)
{
  return mysqli_num_rows($query);
}

function mysql_fetch_assoc ($query)
{
  return mysqli_fetch_assoc($query);
}

function mysql_insert_id ()
{
  return mysqli_insert_id($GLOBALS["mysql_connection"]);
}

function mysql_affected_rows ()
{
  return mysqli_affected_rows($GLOBALS["mysql_connection"]);
}

/* ------------------------------- */

if (!defined("MYSQL_USERNAME"))
{
  die("Please edit config.php file (rename config_example.php)!");
}

$GLOBALS["mysql_connection"] = mysql_connect(MYSQL_SERVER, MYSQL_USERNAME, MYSQL_PASSWORD);

if (!$GLOBALS["mysql_connection"])
{
  die("Cannot connect to MySQL!");
}

if (!mysql_select_db(MYSQL_DATABASE))
{
  die("Cannot select DB!");
}
?>