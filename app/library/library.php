<?php
function microtime_from_start ()
{
	global $start_microtime;
	echo microtime(true)-$start_microtime;
}

$default_cache_time = 24*60*60;
$memcached = new Memcached();
$memcached->addServer("localhost", 11211);

function cache_set ($name, $data, $time = DEFAULT_CACHE_TIME) // time in seconds
{
 global $memcached;
 return $memcached->set($name, $data, $time);
}

function cache_get ($name, $func = null, $time = DEFAULT_CACHE_TIME)
{
 global $memcached;
 if ($func != null) // if func is defined
 {
   if (!is_callable($func)) // check it's really a function
   {
     trigger_error("\$func must be callable!", E_USER_ERROR);
   }
   if ($memcached->get($name) === false) // if there is no data in cache
   {
    $result = $func();
    cache_set($name, $result, $time); // call the function and write the new data
    return $result;
   }
 } 
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

function pdo ($encoding = MYSQL_ENCODING)
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
  $twig_data["domain"] = $GLOBALS["domain"];
  
  if (isset(domain_array()["template"])) // only show pages which are optimized for this domain
  {
    if ($twig_template != domain_array()["template"])
    {
      header("HTTP/1.0 404 Not Found");
	    die("404 Not Found");
    }
  }
  
  require_session();
  
  $twig_data["is_admin"] = is_admin();
  
  if (is_admin_fake(session_id()))
  {
    $twig_data["is_admin"] = true;
  }
  
  if (user_id())
  {
    $twig_data["user_id"] = user_id();
    $twig_data["username"] = username_from_session();
  }
  
  if (class_exists("Notification")) // sometimes error pages are rendered earlier than the Notification class is loaded
  {
    $notifications = Notification::find
    (
      [
        "is_read = 0 AND
        (
          recipient_session_id = :recipient_session_id: OR 
          recipient_user_id = :recipient_user_id:
        )",
        "bind" =>
        [
          "recipient_session_id" => session_id(),
          "recipient_user_id" => user_id()
        ]
      ]
    );
    $twig_data["notifications_unread"] = count($notifications);
  }
  
  if (function_exists("get_identity"))
  {
    $twig_data["identity"]    = get_identity();
    $twig_data["identity_js"] = get_identity_js();
  }
  
  if (is_json() and is_admin())
  {
    $twig_data["benchmark"] = benchmark();
    header("Content-Type: application/json");
    return json_encode($twig_data);
  }
    
	require_once ROOT_DIR."/vendor/autoload.php";
	$loader = new Twig_Loader_Filesystem($twig_filesystem);
	$twig = new Twig_Environment($loader);
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
				$text = preg_replace("/(.?)&gt;&gt;([0-9]+)/i",
														 //"$1<a onclick='link_click(".$data["parent_topic"].",$2);'>Ответ на пост #$2</a>",
														 //"$1<a onclick='link_click(".$data["parent_topic"].",$2);' class='preview'>Ответ на пост #$2</a>",
														 
														 //"$1<a class='preview' onclick='link_click(".$data["parent_topic"].",$2);' topic_id='".$data["parent_topic"]."' order_in_topic='$2'>Ответ на пост #$2</a>",
														 "$1<a class='preview' href='/topic/{$data["parent_topic"]}#$2' topic_id='".$data["parent_topic"]."' order_in_topic='$2'>Ответ на пост #$2</a>",
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
    $text = preg_replace("/\^([^\n]*?)\^/iu", "<strike>$1</strike>", $text);
	
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

function username_from_session ()
{
  require_session();

	if (isset($_SESSION["username"]))
	{
			return $_SESSION["username"];
	}

	else
	{
			return false;
	}
}

function is_mod () // should be replaced by is_admin() globally
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

function is_admin ()
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

function error_page ($params) // 404 Not Found
{
  if (isset($params["code"]) and $params["code"] == 404)
  {
	  header("HTTP/1.0 404 Not Found");
  }
  
  $header  = isset($params["header"]) ? $params["header"]   : "Вы попали на страницу 404";
  $message = isset($params["message"]) ? $params["message"] : "Это значит, что контент, который вы пытаетесь найти, был удален, либо вовсе никогда не существовал.
		Вы можете <a href='/contact'>связаться</a> с нами, но вряд ли это поможет.
		Поэтому лучше посмотрите видеоролик сверху и успокойтесь.";
  $media = isset($params["media"]) ? $params["media"] : "<div align='center'><iframe src='//coub.com/embed/12hxbn?muted=false&autostart=false&originalSize=false&startWithHD=false' allowfullscreen='true' frameborder='0' width='480' height='270'></iframe></div><br>";
  
	echo "<!--".benchmark()."-->";
	ob_start();
	?>
	<h2><?php echo $header; ?></h2>
	<content>
    <?php echo $media; ?>
		<?php echo $message; ?>
	</content>
	<?php
	$html = ob_get_contents();
	ob_end_clean();
	$twig_data = array
	(
		"html" => $html,
		"final_title" => "Ошибка!"
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

function ru_M ($timestamp)
{
  $month = date("m", $timestamp );
  switch( $month )
  {
    case  1: { $month='янв'; } break;
    case  2: { $month='фев'; } break;
    case  3: { $month='мар'; } break;
    case  4: { $month='апр'; } break;
    case  5: { $month='мая'; } break;
    case  6: { $month='июн'; } break;
    case  7: { $month='июл'; } break;
    case  8: { $month='авг'; } break;
    case  9: { $month='сен'; } break;
    case 10: { $month='окт'; } break;
    case 11: { $month='ноя'; } break;
    case 12: { $month='дек'; } break;
  }
  return $month;
}

function smart_time_format ($timestamp) // currently in use
{
	if (date("d M y", $timestamp) == date("d M y"))
	{
		$dm = "Сегодня";
	}
  else
  {
    $dm = date("d ", $timestamp);
    $dm .= ru_M($timestamp);
  }
  if (date("Y", $timestamp) != date("Y"))
  {
    $y = " ".date("Y", $timestamp)." ";
  }
  else
  {
    $y = "";
  }
  $t = date("H:i", $timestamp);
	return $dm.$y." в ".$t;
}

function is_using_cloudflare ()
{
  /*if (isset($GLOBALS["cloudflare"]))
  {
    return true;
  }
  
  else
  {
    return false;
  }*/
  $mod_cloudflare_enabled = in_array("mod_cloudflare", apache_get_modules());
  if ($mod_cloudflare_enabled)
  {
    return true;
  }
  else
  {
    return false;
  }
}

function cloudflare_country_code ()
{
  if (!is_using_cloudflare())
  {
    return false;
  }
  
  if (isset($_SERVER["HTTP_CF_IPCOUNTRY"]))
  {
    return $_SERVER["HTTP_CF_IPCOUNTRY"];
  }
}

function is_same_author ($post_x, $post_y)
{
  if
  (
    (
      $post_x->session_id == $post_y->session_id and
      $post_x->session_id != "none" and
      $post_x->session_id != ""
    )
    or
    (
      $post_x->user_id == $post_y->user_id and
      $post_x->user_id != -1 and
      $post_x->user_id != 0
    )
  )
  {
    return true;
  }
  else
  {
    return false;
  }
}

function is_admin_fake ($session_id)
{
  $session_id_md5 = md5($session_id);
  if (in_array($session_id_md5, ADMIN_FAKES_MD5))
  {
    return true;
  }
  else
  {
    return false;
  }
}

function require_if_exists ($path)
{
  if (file_exists($path))
  {
    require($path);
  }
}

function domain_array ()
{
  return $GLOBALS["domains"][$GLOBALS["domain"]];
}

function full_forum_href ($forum_slug, $forum_id)
{
  if (isset($forum_slug) and $forum_slug != "" and $forum_slug != false)
  {
    $url = "/$forum_slug/";
  }
  else
  {
    $url = "/forum/".$forum_id;
  }
  return $url;
}

function recaptcha ()
{
  echo "<div class='g-recaptcha' data-sitekey='".RECAPTCHA_PUBLIC_KEY."'></div>";
}

function check_recaptcha ($response = null, $secret = null)
{
  if ($response == null)
  {
    $response = $_POST["g-recaptcha-response"];
  }
  
  if ($secret == null)
  {
    $secret = RECAPTCHA_PRIVATE_KEY;
  }
  
  $url = 'https://www.google.com/recaptcha/api/siteverify';
  $data = array
  (
    'secret' => $secret,
    'response' => $_POST["g-recaptcha-response"]
  );
  $options = array
  (
    'http' => array
    (
      'method' => 'POST',
      'content' => http_build_query($data)
    )
  );
  $context = stream_context_create($options);
  $verify = @file_get_contents($url, false, $context);
  $captcha_result = json_decode($verify);

  if (isset($captcha_result->success) and $captcha_result->success == true)
  {
    return true;
  }
  else
  {
    return false;
  }
}

function is_ip_verified ()
{
  return cache_get($GLOBALS["client_ip"]."_verified");
}

function get_client_ip ()
{
  return $GLOBALS["client_ip"];
}

function write_log ($params)
{
  $log_path = ROOT_DIR."/logs/log.txt";
  @file_put_contents($log_path, "Y: ".date("d.m. H:i:s")." $params\n", FILE_APPEND | LOCK_EX);
}

function is_json ()
{
  if (isset($_GET["json"]))
  {
    return true;
  }
  
  else
  {
    return false;
  }
}

function validate_captcha_tag ($tag)
{
  if (ctype_alnum($tag) and mb_strlen($tag) <= 10)
  {
    return true;
  }
  
  else
  {
    return false;
  }
}
?>