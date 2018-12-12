<?php

use Phalcon\Mvc\Controller;
use Phalcon\Http\Request;

class PostingController extends Controller
{

	public function postAction()
	{
    session_start();
    $pdo = pdo();
    
		$request = new Request();
		
		$reply_delay     = 5;
		$new_topic_delay = 1*60;
		$min_title_length = 2;
		$max_title_length = 255;
		$max_name_length = 25;
		$min_text_length = 2; // for words like "no"
		$max_text_length = 15000;
		$max_replies_in_topic = 500;
		$allow_sage = true;
		
		$forum_id     = intval($request->getPost("forum_id"));
		$parent_topic = intval($request->getPost("parent_topic"));
    $reply_to     = intval($request->getPost("reply_to")); // not post_id, but order_in_topic
    $title        = $request->getPost("title");
		$name         = $request->getPost("name");
		$text         = $request->getPost("text");
    $flag         = "";
    $display_username = false;
    
    $is_wakaba = $request->getPost("task");
    
    if ($request->getPost("field3") and $forum_id == 14) // title enabled in /old/
    {
      $title = $request->getPost("field3");
    }
    else // disabled otherwise
    {
      $title = "";
    }
		
		if ($request->getPost("parent")) // Dollchan Extension Tools patch
		{
			$parent_topic = $request->getPost("parent");
		}
    
    if (!$parent_topic) // if creating new topic
    {
      $reply_to = 0; // cannot be replying to a post
    }
		
		$ip = $GLOBALS["client_ip"];
		$time = time();
		
		if (!$request->isPost())
		{
			$this->error("Must be POST data");
		}
		
    $allowed_host = $_SERVER['SERVER_NAME'];
    $host = parse_url($_SERVER['HTTP_REFERER'], PHP_URL_HOST);
    if(substr($host, 0 - strlen($allowed_host)) != $allowed_host)
    {
    	$this->error("Некорректный HTTP-referer!");
    }
		
		if
		(
			in_array($forum_id, array(9, 11, 20))
			and !$parent_topic
			and !is_admin()
		)
		{
			$this->error("Только администраторы могут создавать темы на этом форуме.");
		}
		
		// Text
    /* Somehow accepts one-letter strings like "a" */
    if (mb_strlen($text) < $min_text_length)
    {
			if (!isset($_FILES["userfile"]["tmp_name"]) or !is_uploaded_file($_FILES["userfile"]["tmp_name"]))
			{
				$this->error("Текст слишком короткий!");
			}
    }

    if (mb_strlen($text) > $max_text_length)
    {
    	$this->error("Текст слишком длинный (>$max_text_length)!");
    }
  
    // Title:
    if (mb_strlen($title) < $min_title_length and $title != "")
    {
    	$this->error("Заголовок слишком короткий (<$min_title_length)!");
    }
  
    if (mb_strlen($title) > $max_title_length)
    {
    	$this->error("Заголовок слишком длинный (>$max_title_length)!");
    }
  
    // Name:
    if (mb_strlen($name) > $max_name_length )
    {
    	$this->error("Имя слишком длинное (>$max_name_length )!");
    }
    
    // Check if user exists
    if (user_id())
    {
      $user_object = User::findFirst
      ([
        "user_id = :user_id:",
        "bind" => ["user_id" => user_id()]
      ]);
      
      if (!$user_object)
      {
        session_destroy();
        $this->error("Пользователь удален.");
      }
    }
    
    // More verifications for admin
    /*$check_challenge = (user_id() and !is_admin()) ? false : true;
    $check_banlist = (user_id() and !is_admin()) ? false : true;
    $check_blacklist = user_id() ? false : true;
    //$check_ip_verification = (user_id() and !is_admin()) ? false : true;
    $check_ip_verification = false;
    $check_ipv4 = (user_id() and !is_admin()) ? false : true;*/
    
    $check_challenge = false;
    $check_banlist = false;
    $check_blacklist = false;
    $check_ip_verification = false;
    $check_ipv4 = false;
    
    if (!function_exists("get_challenge_answer"))
    {
      $check_challenge = false;
    }
    
    // Check Tor:
    function is_tor ($ip)
    {
      $tor_file_path = ROOT_DIR."/app/config/tor.txt";
      if (file_exists($tor_file_path))
      {
        $tor_nodes = file($tor_file_path, FILE_IGNORE_NEW_LINES);
        if (in_array($ip, $tor_nodes))
        {
          return true;
        }
      }
      return false;
    }
    
    $is_tor = is_tor($ip);
    
    // Challenge check:
    if ($check_challenge)
    {
      if (function_exists("get_identity"))
      {
        $challenge_answer = $request->getPost("challenge_answer");
        $real_answer = get_challenge_answer (get_identity());

        if ($challenge_answer != $real_answer)
        {
          $this->error("Не пройдена проверка против вайпа. Пожалуйста, ПЕРЕЗАГРУЗИТЕ СТРАНИЦУ, включите JavaScript или обратитесь за помощью: https://".MAIN_HOST."/contact");
        }
      }
    }
    
    // Check IP verification:
    if ($check_ip_verification)
    {
      if (!is_ip_verified()) // If IP is not verified
      {
        $last_post_object = Post::findFirst
        (
        [
          "ip = :ip:",
          "bind" =>
          [
            "ip" => $ip
          ],
          "order" => "post_id DESC"
        ]
        );
        if (!$last_post_object) // If user hasn't made any posts
        {
          $this->error("Пожалуйста, введите <a href='//".MAIN_HOST."/verify' target='_blank'>капчу</a>.");
        }
      }
    }

    // All sorts of IP checks:
    if ($check_blacklist)
    {  
      // Blacklist check:
      require_if_exists(CONFIG_DIR."/check_ip.php");
      if (function_exists("check_ip"))
      {
        $check_ip_result = check_ip($ip, ["forum_id" => $forum_id]);
        if
        (
          (isset($check_ip_result["blocked"]) and $check_ip_result["blocked"] == true)
          or
          isset($check_ip_result["reason"])
        )
        {
          $this->error("Ваш IP находится в черном списке.".(isset($check_ip_result["reason"]) ? " Причина: {$check_ip_result["reason"]}." : "")."\n\nДля разблокировки рекомендуем <a href='//".MAIN_HOST."/login' target='_blank'>войти</a> или <a href='//".MAIN_HOST."/register' target='_blank'>зарегистрироваться</a>.");
        }
      }
    }
    
    // Invite-only check:
    if (!user_id() and INVITE_ONLY) // If anonymous and invite-only mode is on
    {
      $this->error("Для защиты от вайпов у нас теперь вход только по инвайтам из некоторых стран. Рекомендуем <a href='//".MAIN_HOST."/login' target='_blank'>войти</a> или <a href='//".MAIN_HOST."/register' target='_blank'>зарегистрироваться</a>.");
    }
		
    // Banlist check:
    if ($check_banlist)
    {
      $active_ban = Ban::findFirst
      (
      [
        "ip = :ip: AND expires > $time",

        "bind" =>
        [
          "ip" => $ip
        ]
      ]
      );
      if ($active_ban)
      {
        $ban_id = $active_ban->ban_id;
        $this->error("Ваш IP находится в бан-листе (бан #$ban_id). <a href='//".MAIN_HOST."/register' target='_blank'>Регистрация</a> позволяет писать с заблокированных IP.");
      }
    }
    
    if ($check_ipv4)
    {
      if(!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4))
      {    
        $this->error("Пожалуйста, используйте IPv4. <a href='//".MAIN_HOST."/register' target='_blank'>Регистрация</a> позволяет писать с IPv6.");
      }
    }
		
    // Get forum object:
		$forum_obj = Forum::findFirst
		(
    [
			"forum_id = :forum_id:",
			
			"bind" =>
			[
				"forum_id" => $forum_id
			]
    ]
		);
		
    // Check forum object
		if (!$forum_obj)
		{
			$this->error("Форум не найден.");
		}
    
    // Captcha check:
    $check_captcha = true;
    
    if ($check_captcha)
    {
      $captcha_tag  = $request->getPost("captcha_tag");
      $captcha_text = $request->getPost("captcha_text");
      
      if (!validate_captcha_tag($captcha_tag))
      {
        $this->error("Invalid captcha tag.");
      }
      
      if (!isset($_SESSION["captcha_$captcha_tag"]))
      {
        $this->error("Код капчи не найден в базе.");
      }
      
      if ($captcha_text == "")
      {
        $this->error("Пожалуйста, введите капчу.");
      }
      
      if ($_SESSION["captcha_$captcha_tag"] != mb_strtolower($captcha_text))
      {
        $this->error("Капча введена неверно.");
      }
      
      unset($_SESSION["captcha_$captcha_tag"]);
    }
    
    if ($forum_obj->slug == "test" and !is_admin())
		{ 
      $this->error("Этот форум закрыт для постинга.");
		}
    
    if ($forum_obj->slug == "pr" and !in_array(user_id(), [1]))
    {
      $this->error("Этот форум закрыт для постинга.");
    }
		
		if ($parent_topic) {$title = "";}
		
		$parent_topic_object = Post::findFirst
		(
		[
			"post_id = '$parent_topic' AND parent_topic = 0"
		]
		);
    
    if ($forum_obj->slug == "int")
    {
      $cf_country_code = cloudflare_country_code();
      if ($cf_country_code)
      {
        $flag = $cf_country_code;
      }
    }
    
    /*if (user_id() and $request->getPost("sign"))
    {
      $display_username = true;
    }*/
    
    // Proccess Wakaba links
    if ($is_wakaba)
    {
      $GLOBALS["links_in_text"] = 0; // "global" does not work in preg_replace_callback
      $GLOBALS["new_reply_to_post_id"]  = 0;
      $text = preg_replace_callback
      (
          "/>>([0-9]+)/",
          function ($matches)
          {
            $GLOBALS["links_in_text"]++;
            if ($GLOBALS["links_in_text"] > 1)
            {
              $this->error("В сообщении может быть только одна >>ссылка.");
            }
            $GLOBALS["new_reply_to_post_id"] = $matches[1];
            return "";
          },
          $text
      );
      if ($GLOBALS["new_reply_to_post_id"])
      {
        $new_reply_to_post_id = $GLOBALS["new_reply_to_post_id"]; // "global" command does not work by some reason
        if ($parent_topic) // if replying to topic
        {
          if ($parent_topic_object->post_id != $new_reply_to_post_id) // if not replying to OP-post
          {
            $new_reply_to_object = Post::findFirst
            (
            [
              "post_id = :post_id:",
              "bind" => ["post_id" => $new_reply_to_post_id]
            ]
            );
            
            if ($new_reply_to_object->parent_topic != $parent_topic_object->post_id)
            {
              $this->error("Пост, на который вы даете >>ссылку, должен быть в этой же теме.");
            }

            $reply_to = $new_reply_to_object->order_in_topic;
          }
        }
      }
    }
		
		// Reply to existing topic
    if ($parent_topic)
    {
			if (!$parent_topic_object)
			{
				$this->error("Тема не найдена!");
			}
      
      $last_reply_object = Post::findFirst
      (
      [
        "ip = :ip: AND parent_topic != 0",
        "bind" =>
        [
          "ip" => $ip
        ],
        "order" => "post_id DESC"
      ]
      );
			
			if ($last_reply_object)
			{
				$last_reply_age = $time - $last_reply_object->creation_time;

				if ($last_reply_age < $reply_delay)
				{
					$this->error("Вы отвечаете в темы слишком часто.");
				}
        
        if ($last_reply_object->text == $text and
            $text != "" and // lets posts several images in a row
            $last_reply_age < 60)
        {
          $this->error("Вы уже публиковали это сообщение.");
        }
			}
		}
		
		// New topic
    else
    {
      /*if ($forum_obj->slug == "1chan")
      {
        $new_topic_delay = 3*60;
      }*/
      
      $last_topic_object = Post::findFirst
      (
      [
        "ip = :ip: AND parent_topic = 0",
        "bind" =>
        [
          "ip" => $ip
        ],
        "order" => "post_id DESC"
      ]
      );
      
			if ($last_topic_object)
			{
				$last_topic_age = $time - $last_topic_object->creation_time;
				
				if ($last_topic_age < $new_topic_delay)
				{
          if (!is_admin())
          {
					  $this->error("Вы создаете темы слишком часто (осталось ждать ".($new_topic_delay-$last_topic_age)." сек.)");
          }
				}
			}
		}

    /* BEGIN ANTI-WIPE */
    function posts_by_ip_in_the_last_n_seconds ($ip, $n)
    {
      $posts = Post::find
      (
      [
        //"ip = :ip: AND (:time: - creation_time < $n)",
        "ip = :ip: AND creation_time > ".(time() - $n),
        "bind" =>
        [
          "ip" => $ip,
        ],
        "order" => "post_id DESC"
      ]
      );
      
      return count($posts);
    }
    
    $hourly_limit = 100; // max posts per hour per IP
    
    if (posts_by_ip_in_the_last_n_seconds($ip, 60*60) > $hourly_limit)
    {
      $this->error("С вашего IP было опубликовано больше $hourly_limit постов за последний час. В связи с непрекращающимися вайпами нам пришлось ввести ограничение на публикацию большого числа постов. Просим извинения за доставленные неудобства и надеемся на понимание. Для снятия защиты пожалуйста обратитесь в Telegram (@zefirov) или по адресу: https://".MAIN_HOST."/contact");
    }
    
    //$this->comment .= "Posts_by_ip_in_the_last_n_seconds took " . ($b - $a) . " to execute";
    /* END ANTI-WIPE */
		
		$topic_replies = Post::find
		(
    [
			"parent_topic = :parent_topic:",
			
			"bind" =>
			[
				"parent_topic" => $parent_topic,
			]
    ]
		);
		
		$order_in_topic = count($topic_replies) + 1;
    
    if ($parent_topic)
    {
      $sql = $pdo->prepare("SELECT COUNT(*) FROM posts WHERE parent_topic = :parent_topic AND deleted_by = 0");
      $sql->bindParam(":parent_topic", $parent_topic, PDO::PARAM_INT);
      $sql->execute();
      $total_posts_in_topic = $sql->fetchColumn();
      
      if ($total_posts_in_topic > $max_replies_in_topic)
      {
        $this->error("Тема закрыта! Нельзя добавлять больше $max_replies_in_topic ответов.");
      }
    }
    
    if ($reply_to)
    {
      $reply_to_object = Post::findFirst
      (
      [
        "parent_topic = '$parent_topic' AND order_in_topic = '$reply_to'"
      ]
      );
      
      if (!$reply_to_object)
      {
        $this->error("Вы отвечаете на несуществующий пост");
      }
    }
		
		// Create post object
		$post = new Post();
		$post->forum_id = $forum_id; // will be used in process_file()
		$post->parent_topic = $parent_topic;
		
		// Process uploaded file
		if (isset($_FILES["userfile"]["tmp_name"]) and is_uploaded_file($_FILES["userfile"]["tmp_name"]))
		{
      if ($forum_obj->slug == "1chan")
			{
				$this->error("В этом разделе нельзя прикреплять картинки!");
			}
      
      if ($is_tor)
      {
        $this->error("С Tor нельзя загружать картинки.");
      }
			
			$files = $this->request->getUploadedFiles();
			$file = $files[0];
			
			$this->process_file($file, $post);
		}
		
		else
		{
			if (!$parent_topic and $forum_id != 11 and $forum_id != 12) // changelog
			{
				$this->error("Прикрепите картинку для создания темы.");
			}
		}
		
		// Save new post
		$ord = round(microtime(true) * 1000);
    
		/*if ($parent_topic == 62538) // make a sticky topic
		{
				$ord = $ord * 2;
		}*/
		
		$post->creation_time = $time;
		$post->ip = $ip;
		
		$post->user_id = user_id();
		$post->session_id = session_id();
		
		$post->ord = $ord;
		$post->order_in_topic = $order_in_topic;
    $post->reply_to = $reply_to;
		$post->text = $text;
		$post->title = $title;
		$post->name = $name;
    $post->flag = $flag;
    $post->display_username = $display_username;
		
		$result = $post->save();
		
		if (!$result) // report errors if saving went wrong
		{
			foreach ($post->getMessages() as $message)
			{
				echo $message->getMessage(), "<br/>";
			}
		}
		
		if ($parent_topic) // update parent topic's ord
		{
			if // bump if...
			(
					!$allow_sage // sage disallowed
					or (isset($_FILES["userfile"]["tmp_name"]) and is_uploaded_file($_FILES["userfile"]["tmp_name"])) // or post has file
					or ($allow_sage and !$request->getPost("sage")) // sage is alloed AND request contains sage
			)
			{
				if ($parent_topic != 18520) // topic to report posts
				{
					$parent_topic_object->ord = $ord;

					$result = $parent_topic_object->save();
					if (!$result)
					{
						foreach ($post->getMessages() as $message)
						{
							echo $message->getMessage(), "<br/>";
						}

						die("Error updating parent topic");
					}
				}
			}
			
			else // sage
			{
				$post->ord = $parent_topic_object->ord;
				$result = $post->save();
				
				if (!$result) // report errors if saving went wrong
				{
					foreach ($post->getMessages() as $message)
					{
						echo $message->getMessage(), "<br/>";
					}
				}
			}
		}

    ### Notifications ###    
    $admin_notified = false;
    $topic_id_for_notification = $parent_topic ? $parent_topic : $post->post_id;
   
    if (is_admin_fake(session_id()))
    {
      $admin_notified = true;
    }
    if ($parent_topic and is_admin_fake($parent_topic_object->session_id))
    {
      $admin_notified = true;
    }
    if ($reply_to and is_admin_fake($reply_to_object->session_id))
    {
      $admin_notified = true;
    }
    if (is_admin())
    {
      $admin_notified = true;
    }

    // Notify OP:
    if ($parent_topic)
    {
      if(!is_same_author($parent_topic_object, $post)) // if not replying to my own topic
      {
        $notification = new Notification(); // notify OP
        $notification->notify
        (
          $parent_topic_object->session_id,
          $parent_topic_object->user_id,
          "Replying to OP",
          $post->post_id,
          $topic_id_for_notification
        );
        if ($parent_topic_object->user_id == 1)
        {
          $admin_notified = true;
        }
      }
    }

    // Notify author of reply I'm replying to:
    if ($reply_to) // if replying to reply
    {
      if (!is_same_author($reply_to_object, $post)) // if not replying to my own reply
      {
        if (!is_same_author($parent_topic_object, $reply_to_object)) // if OP hasn't already been notified
        {
          $notification = new Notification(); // notify author of reply I'm replying to
          $notification->notify
          (
            $reply_to_object->session_id,
            $reply_to_object->user_id,
            "Replying to reply",
            $post->post_id,
            $topic_id_for_notification
          );
          if ($reply_to_object->user_id == 1)
          {
            $admin_notified = true;
          }
        }
      }
    }

    // Notify admin:
    if (!$admin_notified) // if admin not notified yet
    {
      $admin_notification = new Notification(); // notify admin
      $admin_notification->notify("", 1, "Notification for admin", $post->post_id, $topic_id_for_notification);
    }
    
    $related_notification_query_bind =
    [
      "topic_id" => $parent_topic,
      "recipient_session_id" => session_id(),
      "recipient_user_id" => user_id()
    ];
    if (!$reply_to) // if replying to OP-post
    {
      $related_notification_query_bind["post_id"]  = $parent_topic;
    }
    else // if replying to reply
    {
      $related_notification_query_bind["post_id"]  = $reply_to_object->post_id;
    }
    $related_notification = Notification::findFirst
    (
      [
        "topic_id = :topic_id: AND post_id = :post_id: AND
        (
          recipient_session_id = :recipient_session_id: OR
          recipient_user_id = :recipient_user_id:
        )
        ",
        "bind" => $related_notification_query_bind
      ]
    );
    if ($related_notification)
    {
      $related_notification->is_read = 1;
      $related_notification->save();
    }
    ### / Notifications ###
		
		if ($forum_id == 11 and $parent_topic == 0) // if posting to Changelog
		{
			send_message_to_telegram_channel("@DiscoursChangelog", $post->get_plain_text()."\nОбсудить: https://".MAIN_HOST."/topic/".$post->post_id, TELEGRAM_TOKEN);
		}
		
		//page_cache_delete("forum_".$forum_obj->forum_id); // delete page cache
		cache_delete("forum_".$forum_obj->forum_id); // delete page cache
		cache_delete("forum_1"); // delete main page cache
		//exec("curl --max-time 0.01 https://domain/forum/?forum=".$forum_obj->forum_id); // update page cache
	
		if ($request->getPost("ajax")) // report success to user
		{
			$output = array();
			$output["success"] = true;
			$output["post_id"] = $post->post_id;
			//$output["benchmark"] = benchmark();
      header("Benchmark: ".benchmark());
      if (isset($this->comment) and $this->comment != "")
      {
        $output["comment"] = $this->comment; 
      }
			echo json_encode($output);
		}

		else
		{
      $redirect_url = $_SERVER["HTTP_REFERER"];
      
      if ($is_wakaba)
      {
        if ($forum_id == 1)
        {
          if (!$parent_topic) // new topic
          {
            $redirect_url = "/b/";
          }
          else // replying to topic
          {
            $redirect_url = "/b/res/$parent_topic.html";
          }
        }
      }
      
			return $this->response->redirect($redirect_url);
		}
  }
	
	function error ($error)
	{
		$request = new Request();
			
		if ($request->getPost("ajax"))
		{
			echo json_encode(["error" => $error]);
		}
			
		else
		{
			$filtered_text = anti_xss($request->getPost("text"));

      $html =
      "
      <content class='posting_error'>
        <div style='font-size:200%;text-align:center;'>Ошибка</div>
        <h2 style='font-size:inherit;text-align:center;'>$error</h2>
        <p>Для вашего удобства пост находится в текстовом поле:</p>
        <textarea style='width:100%;height:100px;'>$filtered_text</textarea>
      </content>
      ";
				
			$twig_data = array
			(
				"html" => $html
			);
      
      $twig_template = "default";
      if (isset(domain_array()["template"]))
      {
        $twig_template = domain_array()["template"];
      }

      $twig_filesystem = TWIG_TEMPLATES_DIR."/$twig_template";
      echo render($twig_data, $twig_filesystem, $twig_template);
		}
			
		exit();
	}
	
	function process_file ($file, $post)
	{
		$tmp_name = $file->getTempName();
		
		if (!$tmp_name)
		{
			$this->error("Cannot upload file!");
			return false;
		}
		
		$file_name = $file->getName();
		$file_size = $file->getSize(); // file size in bytes
		$file_type = $file->getRealType();
		$file_extension = strtolower($file->getExtension());
		$max_file_size = 7 * 1048576; // in bytes
    $max_file_width  = 8192;
    $max_file_height = 8192;
    $remove_exif = true;
		
		$allowed_extensions = ["jpg", "jpeg", "png", "gif", "bmp"];
		
		if (!in_array($file_extension, $allowed_extensions))
		{
			$this->error("Unknown file extension ($file_extension)! Allowed file types: ".join(", ", $allowed_extensions));
		}
		
		if ($file_size > $max_file_size)
		{
			$this->error("File size ($file_size) exceeded the maximum of $max_file_size");
		}

		$exif_imagetype = exif_imagetype($tmp_name);
		if (!$exif_imagetype)
		{
			$this->error("Загруженный файл не является изображением.");
		}

    list($file_w, $file_h) = getimagesize($tmp_name);
    if ($file_w > $max_file_width or $file_h > $max_file_height)
    {
      $this->error("Image too large ($max_file_width x $max_file_height max)");
    }
    
    if ($remove_exif)
    {
    	if (in_array($file_extension, array("jpg", "jpeg"))) // if JPEG image
    	{
    		exec("convert $tmp_name -strip $tmp_name"); // remove EXIF
    	}
    }

		$thumb_path = tempnam(sys_get_temp_dir(), "thumb");
		if ($post->forum_id != 3)
		{
			//exec("convert -thumbnail 150x150 $tmp_name\[0] $thumb_path"); // [0] means 1st frame
      exec("convert -thumbnail 300x300 $tmp_name\[0] $thumb_path"); // [0] means 1st frame
		}
		else // /test/
		{
      $browser_max_w = 150;
      $browser_max_h = 150;
      
      /*if ($post->parent_topic)
      {
        $browser_max_w = 150;
        $browser_max_h = 150;
      }*/
      
      // image in browser will be 50% smaller because of CSS
      $dimensions = ($browser_max_w*2)."x".($browser_max_h*2);
			exec("convert -thumbnail $dimensions $tmp_name\[0] $thumb_path"); // [0] means 1st frame
		}

		list($thumb_w, $thumb_h) = getimagesize($thumb_path);

    $thumb_w = intval($thumb_w / 2);
		$thumb_h = intval($thumb_h / 2);
		
		//function random_str ($length, $keyspace = "0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ")
    function random_str ($length, $keyspace = "0123456789abcdefghijklmnopqrstuvwxyz")
		{
				$str = "";
				$max = mb_strlen($keyspace, "8bit") - 1;
				for ($i = 0; $i < $length; ++$i) {
						$str .= $keyspace[random_int(0, $max)];
				}
				return $str;
		}
		$time = time();
		//$random_str = random_str(12);
    $random_str = time().random_int(100, 999);
		
		//$new_file_name  = "{$time}_{$rand}.".$file_extension;
		//$new_thumb_name = "{$time}_{$rand}_thumb.".$file_extension;
		$new_file_name  = "{$random_str}.".$file_extension;
		$new_thumb_name = "{$random_str}_thumb.".$file_extension;
		
		copy ($tmp_name, UPLOAD_DIR."/".$new_file_name);
		copy ($thumb_path, UPLOAD_DIR."/".$new_thumb_name);
		
		$file_url  = FILE_PROTOCOL."://".FILE_HOST."/files/$new_file_name";
		$thumb_url = FILE_PROTOCOL."://".FILE_HOST."/files/$new_thumb_name";
		
		$post->file_url = $file_url;
		$post->thumb_url = $thumb_url;
		
		$post->thumb_w = $thumb_w;
		$post->thumb_h = $thumb_h;
	}
  
  private $comment; // used to include any data into JSON output
  
  public function initialize()
  {
  	$this->comment = ""; // defined to use concatenation without issuing a warning
  }

}