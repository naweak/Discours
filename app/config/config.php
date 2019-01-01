<?php
// This is the configuration for discou.rs server
// Please change to your Project's values

$start_microtime = microtime(true);

// GENERAL DATA:
define ("MAIN_HOST", "discou.rs"); // 1.2.3.4 or localhost
define ("FILE_HOST", "dristatic.cf"); // in most cases, same as above
define ("FILE_PROTOCOL", "https"); // http or https

date_default_timezone_set("Europe/Moscow");

define ("DEFAULT_CACHE_TIME", 24*60*60);
define ("MAX_LINES_TO_SHOW", 10); // maximum numer of post lines to show on a page
define ("MAX_CHARS_TO_SHOW", 700); // maximum number of post characters to show on a page

// Эти сессии используются для тестирования системы уведомлений;
define ("ADMIN_FAKES_MD5", []); // MD5 hashes of session_id strings

// SECRET DATA:
$passwords = json_decode(file_get_contents(__DIR__."/passwords.txt"), true);

define ("MYSQL_HOST", "localhost");
define ("MYSQL_ENCODING", "utf8mb4");

define ("MYSQL_DATABASE", $passwords['mysql_database']);
define ("MYSQL_USERNAME", $passwords['mysql_username']);
define ("MYSQL_PASSWORD", $passwords['mysql_password']);

define ("TELEGRAM_CHANNEL", $passwords["telegram_channel"]);
define ("TELEGRAM_TOKEN", $passwords["telegram_token"]);

define ("RECAPTCHA_PUBLIC_KEY", $passwords["recaptcha_public_key"]);
define ("RECAPTCHA_PRIVATE_KEY", $passwords["recaptcha_private_key"]);
///////////////

function benchmark () {global $start_microtime; return microtime(true) - $start_microtime;}

define ("ROOT_DIR", __DIR__."/../.."); // Phalcon root directory (with "app", "public" and other directories)
define ("LIB_DIR",  ROOT_DIR."/app/library");
define ("LIB_FILE",  LIB_DIR."/library.php");
define ("CONFIG_DIR",  ROOT_DIR."/app/config");
define ("PUBLIC_DIR",  ROOT_DIR."/public");
define ("UPLOAD_DIR", PUBLIC_DIR."/files");
define ("BACKUP_DIR", ROOT_DIR."/backup");
define ("TWIG_TEMPLATES_DIR", ROOT_DIR."/app/templates/");
define ("TWIG_HTML_FILENAME", "template.html");
define ("DEFAULT_TWIG_FILESYSTEM",  TWIG_TEMPLATES_DIR."/default");
define ("ROUTES_FILE", CONFIG_DIR."/routes.php");
define ("USE_ROUTES", true);

require "cloudflare.php"; // Verify CloudFlare IP

$require_path = CONFIG_DIR."/invite_only.php"; // Invite-only component
if (file_exists($require_path))
{
  require_once($require_path);
}
else
{
  define ("INVITE_ONLY", false);
}

$require_path = CONFIG_DIR."/challenge.php"; // Identity component
if (file_exists($require_path))
{
  require_once($require_path);
}

$GLOBALS["domains"] = // List of domains allowed
[
  MAIN_HOST => []
];

$domains_file = __DIR__."/domains.php"; // custom list of domain can go here
if (file_exists($domains_file))
{
  require_once($domains_file);
}

$GLOBALS["domain"] = $_SERVER["HTTP_HOST"];

// Works with both array elements and subarrays
if (!in_array($GLOBALS["domain"], $GLOBALS["domains"]) and !isset($domains[$GLOBALS["domain"]]))
{
  die("Unknown domain: ".$GLOBALS["domain"].". Request via HTTPs: ".(isset($_SERVER["HTTPS"]) ? "yes" : "no"));
}

$session_lifetime = 365; // Days
session_cache_expire ($session_lifetime*24*60); // Minutes
ini_set("session.save_path", ROOT_DIR."/sessions");
ini_set("session.gc_maxlifetime", $session_lifetime*24*3600);
ini_set("session.cookie_lifetime", $session_lifetime*24*3600);

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
  require_once LIB_FILE;
}
?>