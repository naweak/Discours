<?php
$start_microtime = microtime(true);
function benchmark () {global $start_microtime; return microtime(true) - $start_microtime;}

if (!isset($_SERVER["HTTP_CF_CONNECTING_IP"]))
{
	die("Cloudflare not detected!");
}
$GLOBALS["client_ip"] = $_SERVER["HTTP_CF_CONNECTING_IP"];

session_cache_expire (7*24*60); // minutes
ini_set("session.save_path", __DIR__."/../sessions");
ini_set('session.gc_maxlifetime', 7*24*3600);
ini_set('session.cookie_lifetime', 7*24*3600);

define ("MYSQL_HOST", "localhost");

define ("MYSQL_DATABASE", "abc");
define ("MYSQL_USERNAME", "def");
define ("MYSQL_PASSWORD", "ghi");

define ("MOD_PASSWORD", "abcdefghi");

define ("TELEGRAM_CHANNEL", "abcde");
define ("TELEGRAM_TOKEN", "123:ABC");

define ("ROOT_DIR", __DIR__);
define ("LIB_DIR",  ROOT_DIR."/library");
define ("BACKUP_DIR", ROOT_DIR."/../backup");
define ("TWIG_HTML_FILENAME", "template.html");
define ("DEFAULT_TWIG_FILESYSTEM",  ROOT_DIR."/templates/default");

define ("PHALCON_URL", "");

try
{
	$pdo = new PDO("mysql:host=".MYSQL_HOST.";dbname=".MYSQL_DATABASE, MYSQL_USERNAME, MYSQL_PASSWORD);
	$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
}
catch(PDOException $e)
{
	die ("Connection failed: ".$e->getMessage());
}

function require_session ()
{
	if (session_status() == PHP_SESSION_NONE)
	{
    session_start();
	}
}

function require_bundle ()
{
	require_session();
  require_once LIB_DIR."/library.php";
}

function require_phalcon ()
{
  require_once "phalcon/public/index.php";
}
?>