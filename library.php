<?php
date_default_timezone_set("Europe/Kaliningrad"); // bag in server settings, in fact it's Moscow time

$client_ip = $_SERVER["REMOTE_ADDR"];

$client_ip = $_SERVER["HTTP_CF_CONNECTING_IP"];
if (!isset($_SERVER["HTTP_CF_CONNECTING_IP"]))
{
	die("Cloudflare not detected!");
}

function render ($twig_data, $twig_filesystem)
{
	/* Filter 'include' tag! */

	$twig_template = "default";

	require_once "vendor/autoload.php";
	$loader = new Twig_Loader_Filesystem($twig_filesystem);
	$twig = new Twig_Environment($loader);
	return $twig->render("$twig_template.html", $twig_data);
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
    /*$text = str_replace("&", "&amp;", $text);
    $text = str_replace("<", "&lt;", $text);
    $text = str_replace(">", "&gt;", $text);*/
		$text = anti_xss($text);

    // ([^\n]*)
    //$text = preg_replace("/((^|\n)&gt;([^\n]*))/i", "<quote>$1</quote>", $text);
  	$text = preg_replace("/(^|\n)&gt;([^\n]*)/i", "$1<quote>&gt;$2</quote>", $text); // add ">" using ::before selector
	
    $text = str_replace("\n", "<br>\n", $text);
  
    //$text = preg_replace("/&lt;b&gt;(.*?)&lt;\/b&gt;/i", "<b>$1</b>", $text);
    // new lines are not supported
    $text = preg_replace("/&lt;b&gt;([^\n]*?)&lt;\/b&gt;/iu", "<b>$1</b>", $text);
		$text = preg_replace("/&lt;i&gt;([^\n]*?)&lt;\/i&gt;/iu", "<i>$1</i>", $text);
  
    // RGhost
    $text = preg_replace("/^http(s)?:\/\/rgho.st\/([a-zA-Z0-9]{6,10})((<br>| )*)/u", "<img class='embedded' src='HTTPS://rgho.st/$2/thumb.png'>", $text, 1);

    // Tinypic
    $text = preg_replace("/^http(s)?:\/\/i([0-9]{1,3}).tinypic.com\/([a-zA-Z0-9.]{5,15})((<br>| )*)/u", "<img class='embedded' src='HTTPS://i$2.tinypic.com/$3'>", $text, 1);
  
    // Imgur
    $text = preg_replace("/^http(s)?:\/\/imgur.com\/([a-zA-Z0-9]{5,15})((<br>| )*)/u", "<img class='embedded' src='HTTPS://i.imgur.com/$2.jpg'>", $text, 1);
    $text = preg_replace("/^http(s)?:\/\/i.imgur.com\/([a-zA-Z0-9]{5,15}).([a-z]{3})((<br>| )*)/u", "<img class='embedded' src='HTTPS://i.imgur.com/$2.jpg'>", $text, 1);
  
		// Links
    $text = preg_replace('!(((f|ht)tp(s)?://)[-a-zA-Zа-яА-Я()0-9@:%_+.~#?&;//=]+)!u', '<a href="$1" target="_blank">$1</a>', $text);

    $lines = explode("<br>", $text);
    
    if (count($lines) > $lines_to_show) // trim text
    {
      $text = implode("<br>", array_slice($lines, 0, $lines_to_show));
			
      $text .= "<br><span style='color:grey;cursor:pointer;' onclick='$(this).next().css(\"display\", \"block\");$(this).css(\"display\", \"none\");'>Комментарий слишком длинный. Нажмите здесь для просмотра.</span>";
      
      $text .= "<div style='display:none; padding:0px;'>";
      $text .= implode("<br>", array_slice($lines, $lines_to_show, 999));
      $text .= "</div>";
    }
  
    return $text;
}

function send_message_to_telegram_channel ($chatID, $message, $token)
{
    $url = "https://api.telegram.org/bot" . $token . "/sendMessage?chat_id=@" . $chatID;
    $url = $url . "&text=" . urlencode($message);
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
