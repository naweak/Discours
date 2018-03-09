<?php
date_default_timezone_set("Europe/Kaliningrad"); // bug in server settings, in fact it's Moscow time

/*if (!isset($_SERVER["HTTP_CF_CONNECTING_IP"]))
{
	die("Cloudflare not detected!");
}

$GLOBALS["client_ip"] = $_SERVER["HTTP_CF_CONNECTING_IP"];*/

function microtime_from_start ()
{
	global $start_microtime;
	echo microtime(true)-$start_microtime;
}

$memcached = new Memcached();
$memcached->addServer("localhost", 11211);

function cache_set ($name, $data, $time = 24*60*60) // time in seconds
{
 global $memcached;
 return $memcached->set($name, $data, $time);
}

function cache_get ($name)
{
 global $memcached;
 return $memcached->get($name);
}

function cache_delete ($name)
{
 global $memcached;
 return $memcached->delete($name);
}

function page_cache_set ($name, $data)
{
	if(!preg_match("/^[a-zA-Z0-9_-]*$/", $name)) {die("Incorrect page name!");}
	return @file_put_contents(ROOT_DIR."/cache/".$name, $data);
}

function page_cache_get ($name)
{
	if(!preg_match("/^[a-zA-Z0-9_-]*$/", $name)) {die("Incorrect page name!");}
	return @file_get_contents(ROOT_DIR."/cache/".$name);
}

function page_cache_delete ($name)
{
	if(!preg_match("/^[a-zA-Z0-9_-]*$/", $name)) {die("Incorrect page name!");}
	return @unlink(ROOT_DIR."/cache/".$name);
}

function pdo ($encoding = "utf8")
{
	try
	{
		$pdo = new PDO("mysql:host=".MYSQL_HOST.";dbname=".MYSQL_DATABASE.";charset=".$encoding, MYSQL_USERNAME, MYSQL_PASSWORD);
		$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		return $pdo;
	}
	catch(PDOException $e)
	{
		die ("Connection failed: ".$e->getMessage());
	}
}

function render ($twig_data, $twig_filesystem = DEFAULT_TWIG_FILESYSTEM, $twig_template = "default")
{
	function last_file ($str)
	{
		$all_files = glob($str, 1);
		$last_file = end($all_files);
		return basename($last_file);
	}

	$twig_data["js_file"]  = last_file(ROOT_DIR."/public/assets/".$twig_template."_*.js");
	$twig_data["css_file"] = last_file(ROOT_DIR."/public/assets/".$twig_template."_*.css");

	require_once ROOT_DIR."/vendor/autoload.php";
	$loader = new Twig_Loader_Filesystem($twig_filesystem);
	$twig = new Twig_Environment($loader);
	//return $twig->render("$twig_template.html", $twig_data);
	return $twig->render(TWIG_HTML_FILENAME, $twig_data);
}

function render_from_string ($template, $data)
{
	require_once ROOT_DIR."/vendor/autoload.php";
	$templates = array("template" => $template);
	$twig = new Twig_Environment(new Twig_Loader_Array($templates));
	echo $twig->render("template", $data);
}

function anti_xss ($text)
{
	$text = str_replace("&", "&amp;", $text);
  $text = str_replace("<", "&lt;", $text);
  $text = str_replace(">", "&gt;", $text);
	return $text;
}

function markup ($text, $data = null)
{
    #$lines_to_show = 10; // no longer used
		$forum_template = "default";
		if ($data["forum_id"] == 14)
		{
			$forum_template = "wakaba";
		}
  
    $text = str_replace("\r", "", $text); // remove /r's
	
		$text = trim($text, "\n"); // remove newlines from the start and the end
  
    // Anti-XSS
		$text = anti_xss($text);
	
		if (isset($data["parent_topic"]) and $data["parent_topic"] != null)
		{
			if ($forum_template == "default") // normal link
			{
				$text = preg_replace("/(^|\n)&gt;&gt;([0-9]+)/i",
													 //"$1<a onclick='link_click(".$data["parent_topic"].",$2);'>&gt;&gt;Ответ на пост #$2</a>",
														 "$1<a onclick='link_click(".$data["parent_topic"].",$2);'>Ответ на пост #$2</a>",
													 $text); // >>123
			}
			elseif ($forum_template == "wakaba") // wakaba link
			{
				$text = preg_replace("/(^|\n)&gt;&gt;([0-9]+)/i",
									 "$1<a href=''>&gt;&gt;$2</a>",
									 $text); // >>123
			}
		}

    // ([^\n]*)
  	//$text = preg_replace("/(^|\n)&gt;([^\n]*)/i", "$1<quote>&gt;$2</quote>", $text); // add ">" using ::before selector
		$text = preg_replace("/(^|\n)&gt;([^\n]*)/i", "$1<quote>$2</quote>", $text); // add ">" using ::before selector
	
    $text = str_replace("\n", "<br>\n", $text);
  
    // new lines are not supported
    $text = preg_replace("/&lt;b&gt;([^\n]*?)&lt;\/b&gt;/iu", "<b>$1</b>", $text); // bold
		$text = preg_replace("/\*\*([^\n]*?)\*\*/iu", "<b>$1</b>", $text);
		$text = preg_replace("/\[b\]([^\n]*?)\[\/b\]/iu", "<b>$1</b>", $text);
	
		$text = preg_replace("/&lt;i&gt;([^\n]*?)&lt;\/i&gt;/iu", "<i>$1</i>", $text); // italic
		$text = preg_replace("/\*([^\n]*?)\*/iu", "<i>$1</i>", $text);
		$text = preg_replace("/\[i\]([^\n]*?)\[\/i\]/iu", "<i>$1</i>", $text);
	
		$text = preg_replace("/&lt;u&gt;([^\n]*?)&lt;\/u&gt;/iu", "<u>$1</u>", $text); // underline
		$text = preg_replace("/\[u\]([^\n]*?)\[\/u\]/iu", "<u>$1</u>", $text);
	
		$text = preg_replace("/&lt;s&gt;([^\n]*?)&lt;\/s&gt;/iu", "<strike>$1</strike>", $text); // strike
		$text = preg_replace("/\[s\]([^\n]*?)\[\/s\]/iu", "<strike>$1</strike>", $text);
	
		$text = preg_replace("/%%([^\n]*?)%%/iu", "<span class='spoiler'>$1</span>", $text); // spoiler
		$text = preg_replace("/\[spoiler\]([^\n]*?)\[\/spoiler\]/iu", "<span class='spoiler'>$1</span>", $text);

		// Links
    $text = preg_replace('!(((f|ht)tp(s)?://)[-a-zA-Zа-яА-Я()0-9@:%_+.~#?&;//=]+)!u', '<a href="$1" target="_blank">$1</a>', $text);

    return $text;
}

function send_message_to_telegram_channel ($chatID, $message, $token)
{
    $url = "https://api.telegram.org/bot" . $token . "/sendMessage?chat_id=@" . $chatID;
    $url = $url . "&text=" . urlencode($message);
		$url = $url . "&disable_web_page_preview=true";
    $ch = curl_init();
    $optArray = array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true
    );
    curl_setopt_array($ch, $optArray);
    $result = curl_exec($ch);
    curl_close($ch);

    return $result;
}

function user_id ()
{
	require_session();

	if (isset($_SESSION["user_id"]))
	{
			return intval($_SESSION["user_id"]);
	}

	else
	{
			return 0;
	}
}

function is_mod ()
{
	if (user_id() == 1)
	{
			return true;
	}

	else
	{
			return false;
	}
}

function error_page ($code) // 404 Not Found
{
	header("HTTP/1.0 404 Not Found");
	echo "<!--".benchmark()."-->";
	ob_start();
	?>
	<h2>Вы попали на страницу 404</h2>
	<content>
		<div align="center">
			<iframe src="//coub.com/embed/12hxbn?muted=false&autostart=false&originalSize=false&startWithHD=false" allowfullscreen="true" frameborder="0" width="480" height="270"></iframe>
		</div>
		<br>
		Это значит, что контент, который вы пытаетесь найти, был удален, либо вовсе никогда не существовал.
		Вы можете <a href="/contact">связаться</a> с нами, но вряд ли это поможет.
		Поэтому лучше посмотрите видеоролик сверху и успокойтесь.
	</content>
	<?php
	$html = ob_get_contents();
	ob_end_clean();
	$twig_data = array
	(
		"html" => $html,
		"final_title" => "Не найдено!"
	);
	echo render($twig_data);
	echo "<!--".benchmark()."-->";
	exit();
}

function first_topic_error_page ()
{
	ob_start();
	?>
	<h2>Вы пытались найти первую тему!</h2>
	<content>
		<div align="center">
			<a href="https://www.youtube.com/watch?v=r-aUrGMBuc8" target="_blank">
			<img src="https://i.ytimg.com/vi/r-aUrGMBuc8/hqdefault.jpg" alt="Putin" style="height:250px;">
			</a>
		</div>
		<br>
		Но ее здесь нет. <a href="http://lurkmore.to/АПЛ_«Курск»" target="_blank">Она&nbsp;утонула</a>, чего нам очень жаль.
		Администрация Дискурса впредь делает всё возможное, чтобы подобная ситуация не повторилась (регулярные
		<a href="https://ru.wikipedia.org/wiki/Резервное_копирование" target="_blank">бэкапы</a>, например).
		А «первой темой» можно считать <a href="/topic/20">тему&nbsp;номер&nbsp;20</a>.<br>
		<br>
		Серьезно, мы учимся на ошибках, и после этого досадного провала был даже введен новый принцип —
		<a href="https://discou.rs/principles">принцип&nbsp;долговечности</a>, согласно которому старые темы не удаляются,
		а остаются в назидание потомкам.
	</content>
	<?php
	$html = ob_get_contents();
	ob_end_clean();
	$twig_data = array
	(
		"html" => $html
	);
	echo render($twig_data);
	exit();
}

function how_long_ago ($age)
{
    if ($age < 1 * 60) return "менее минуты назад";
    if ($age < 2 * 60) return "менее двух минут назад";
    if ($age < 3 * 60) return "менее трех минут назад";
    if ($age < 4 * 60) return "менее четырех минут назад";
    if ($age < 5 * 60) return "менее пяти минут назад";
    if ($age < 60 * 60)
    {
        return "менее часа назад";
    }
    return "более часа назад";
}

function time_format ($timestamp)
{	
	$postDate = date( "d.m.Y", $timestamp );
	$postMinute = date( "H:i", $timestamp );

	if ($postDate == date('d.m.Y')) {
		// Если сегодня
		$datetime = 'Cегодня в ';
	} else if ($postDate == date('d.m.Y', strtotime('-1 day'))) {
		// Если вчера
		$datetime = 'Вчера в ';
	} else {
		// Иначе
		$fulldate = date( "j # Y в ", $timestamp );
		$mon = date("m", $timestamp );
		switch( $mon ) {
			case  1: { $mon='Января'; } break;
			case  2: { $mon='Февраля'; } break;
			case  3: { $mon='Марта'; } break;
			case  4: { $mon='Апреля'; } break;
			case  5: { $mon='Мая'; } break;
			case  6: { $mon='Июня'; } break;
			case  7: { $mon='Июля'; } break;
			case  8: { $mon='Августа'; } break;
			case  9: { $mon='Сентября'; } break;
			case 10: { $mon='Октября'; } break;
			case 11: { $mon='Ноября'; } break;
			case 12: { $mon='Декабря'; } break;
		}
		$datetime = str_replace( '#', $mon, $fulldate );
	}
	return $datetime.$postMinute;
}

function smart_time_format ($timestamp)
{
	$dm = date("d M", $timestamp);
	if ($dm == date("d M"))
	{
		$dm = "Сегодня";
	}
	return $dm." @ ".date("H:i", $timestamp);
}
?>