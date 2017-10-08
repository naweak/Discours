<?php
/* Dirty hack, will switch to PDO or some other library later */

function mysql_connect ($server, $username, $password)
{
  return mysqli_connect($server, $username, $password);
}

function mysql_select_db ($db)
{
  global $mysql_connection;
  return mysqli_select_db($mysql_connection, $db);
}

function mysql_real_escape_string ($string)
{
  global $mysql_connection;
  return mysqli_real_escape_string($mysql_connection, $string);
}

function mysql_query ($string)
{
  global $mysql_connection;
  return mysqli_query($mysql_connection, $string);
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
  global $mysql_connection;
  return mysqli_insert_id($mysql_connection);
}

function mysql_affected_rows ()
{
  global $mysql_connection;
  return mysqli_affected_rows($mysql_connection);
}

/* ------------------------------- */

if (!defined("MYSQL_USERNAME"))
{
  die("Please edit config.php file (rename config_example.php)!");
}

$mysql_connection = mysql_connect(MYSQL_SERVER, MYSQL_USERNAME, MYSQL_PASSWORD);

if (!$mysql_connection)
{
  die("Cannot connect to MySQL!");
}

if (!mysql_select_db(MYSQL_DATABASE))
{
  die("Cannot select DB!");
}
?>