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

function render ($twig_data, $twig_filesystem = DEFAULT_TWIG_FILESYSTEM)
{
	$twig_template = "default";
	
	if (!JS_CACHING)
	{
		$twig_data["js"]  = @file_get_contents(ROOT_DIR."/templates/default/default.js");
	}
	
	if (!CSS_CACHING)
	{
		$twig_data["css"] = @file_get_contents(ROOT_DIR."/templates/default/default.css");
	}

	require_once ROOT_DIR."/vendor/autoload.php";
	$loader = new Twig_Loader_Filesystem($twig_filesystem);
	$twig = new Twig_Environment($loader);
	return $twig->render("$twig_template.html", $twig_data);
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

function markup ($text)
{
    $lines_to_show = 10;
  
    $text = str_replace("\r", "", $text); // remove /r's
	
		$text = trim($text, "\n"); // remove newlines from the start and the end
  
    // Anti-XSS
		$text = anti_xss($text);

    // ([^\n]*)
  	$text = preg_replace("/(^|\n)&gt;([^\n]*)/i", "$1<quote>&gt;$2</quote>", $text); // add ">" using ::before selector
	
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
	
    // Imgur
    $text = preg_replace("/^http(s)?:\/\/imgur.com\/([a-zA-Z0-9]{5,15})((<br>|\n| )*)/u", "<img class='embedded' src='HTTPS://i.imgur.com/$2.jpg'>", $text, 1);
    $text = preg_replace("/^http(s)?:\/\/i.imgur.com\/([a-zA-Z0-9]{5,15}).([a-z]{3})((<br>|\n| )*)/u", "<img class='embedded' src='HTTPS://i.imgur.com/$2.jpg'>", $text, 1);
  
		// Links
    $text = preg_replace('!(((f|ht)tp(s)?://)[-a-zA-Zа-яА-Я()0-9@:%_+.~#?&;//=]+)!u', '<a href="$1" target="_blank">$1</a>', $text);
  
		// Smiles
		$text = preg_replace("/:(sheez):/iu", "<img src='https://1chan.ca/img/$1.png' width='30px' height='30px'>", $text);
		$text = preg_replace("/:(rage):/iu", "<img src='https://1chan.ca/img/$1.png' width='28px' height='30px'>", $text);
	
		// Smiles (animated)
		$text = preg_replace("/:(nyan):/iu", "<img src='https://1chan.ca/img/$1.gif' width='40px' height='40px'>", $text);
		$text = preg_replace("/:(popka):/iu", "<img src='https://1chan.ca/img/$1.gif' width='45px' height='35px'>", $text);
	
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

function is_mod()
{
    if (isset($_SESSION["user_id"]) and $_SESSION["user_id"] == 1)
    {
        return true;
    }

    else
    {
        return false;
    }
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
?>
