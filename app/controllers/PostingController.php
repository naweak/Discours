<?php

use Phalcon\Mvc\Controller;
use Phalcon\Http\Request;

class PostingController extends Controller
{

	public function postAction()
	{
    session_start();
    
		$request = new Request();
		
		$reply_delay     = 5;
		$new_topic_delay = 10*60;
		$min_title_length = 3;
		$max_title_length = 255;
		$max_name_length = 25;
		$min_text_length = 3;
		$max_text_length = 15000;
		$max_replies_in_topic = 500;
		$allow_sage = true;
		
		$forum_id     = intval($request->getPost("forum_id"));
		$parent_topic = intval($request->getPost("parent_topic"));
    $reply_to     = intval($request->getPost("reply_to"));
		//$title        = $request->getPost("title");
    $title        = "";
		$name         = $request->getPost("name");
		$text         = $request->getPost("text");
    
    if ($request->getPost("field3")) // wakaba
    {
      $title = $request->getPost("field3");
    }
		
		if ($request->getPost("parent")) // Dollchan Extension Tools patch
		{
			$parent_topic = $request->getPost("parent");
		}
		
		if ($forum_id == 12)
		{
			$new_topic_delay = 3*60;
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
		
		if(!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4))
		{    
  		$this->error("Пожалуйста, используйте IPv4.");
		}
		
		if
		(
			//($forum_id == 9 or $forum_id == 11)
			in_array($forum_id, array(9, 11))
			and !$parent_topic
			and user_id() != 1
		)
		{
			$this->error("Только администраторы могут создавать темы на этом форуме.");
		}
		
		if ($forum_id == 3) // /test/
		{ 
      if (user_id() != 1)
      {
			  $this->error("Этот форум закрыт для постинга.");
      }
		}
		
		// Text
    /* Somehow accepts one-letter strings like "a" */
    if (mb_strlen($text) < $min_text_length)
    {
			if (!is_uploaded_file($_FILES["userfile"]["tmp_name"]))
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
			$this->error("Ваш IP находится в бан-листе (бан #$ban_id). Для разбана обратитесь в телеграм-конференцию.");
		}
		
		$tor_file_path = ROOT_DIR."/app/config/tor.txt";
		if (file_exists($tor_file_path))
		{
			$tor_nodes = file($tor_file_path, FILE_IGNORE_NEW_LINES);
			if (in_array($ip, $tor_nodes))
			{
				$this->error("В связи с постоянными вайпами постинг с Tor закрыт. Благодаря этому на Дискурсе нет обязательной капчи. Пожалуйста, отнеситесь с пониманием.");
			}
		}
		
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
		
		if (!$forum_obj)
		{
			$this->error("Форум не найден.");
		}
		
		if ($parent_topic) {$title = "";}
		
		// Last topic object
		$last_topic = Post::findFirst
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
		
		// Last reply object
		$last_reply = Post::findFirst
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
		
		// Parent topic object
		$parent_topic_obj = Post::findFirst
		(
		[
			"post_id = '$parent_topic' AND parent_topic = 0"
		]
		);
		
		// Reply to existing topic
    if ($parent_topic)
    {
			if (!$parent_topic_obj)
			{
				$this->error("Тема не найдена!");
			}
			
			// CHECK FOR BUMP LIMIT!!!
			
			if ($last_reply)
			{
				$last_reply_age = $time-$last_reply->creation_time;

				//echo $last_reply_age;
				if ($last_reply_age < $reply_delay)
				{
					$this->error("Вы отвечаете в темы слишком часто!");
				}
			}
		}
		
		// New topic
    else
    {
			/*******/
			/*$last_topics = Post::find // different from $last_topic
			(
			[
				"ip = :ip: AND parent_topic = 0 WHERE $time-creation_time < 60*60",

				"bind" =>
				[
					"ip" => $ip
				],

				"order" => "post_id DESC"
			]
			);/*
			// how long does the user have to wait?
			/*******/
			
			if ($last_topic)
			{
				$last_topic_age = $time-$last_topic->creation_time;
				
				//echo $last_topic_age;
				if ($last_topic_age < $new_topic_delay)
				{
					$this->error("Вы создаете темы слишком часто (осталось ждать ".($new_topic_delay-$last_topic_age)." сек.)");
				}
			}
		}
		
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
		
		if ($order_in_topic > $max_replies_in_topic and $parent_topic)
		{
			$this->error("Тема закрыта! Нельзя добавлять больше $max_replies_in_topic ответов.");
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
		
		// url:
		/*$text_lines = explode("\n", $text);
		$last_line = end($text_lines);
		if (preg_match("/^url:[ ]*(.*)/i", $last_line, $matches))
		{
			$url = $matches[1];
			if (filter_var($url, FILTER_VALIDATE_URL))
			{
				$this->error("URL: $url");
			}
		}*/
		
		// Process uploaded file
		if (is_uploaded_file($_FILES["userfile"]["tmp_name"]))
		{
			if ($forum_id == 12)
			{
				$this->error("В этом разделе нельзя прикреплять картинки!");
			}
			
			//$this->error("has file!");
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
		/*if ($parent_topic == 10670) // sticky
		{
				$ord = $ord*2;
		}*/
		if ($forum_id == 11 or $forum_id == 12)
		{
			if ($parent_topic)
			{
				$ord = $parent_topic_obj->ord;
			}
		}
		
		$post->creation_time = $time;
		$post->ip = $ip;
		
		$post->user_id = 0;
		$post->session_id = session_id();
		
		$post->ord = $ord;
		$post->order_in_topic = $order_in_topic;
    $post->reply_to = $reply_to;
		$post->text = $text;
		$post->title = $title;
		$post->name = $name;
		
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
					or is_uploaded_file($_FILES["userfile"]["tmp_name"]) // or post has file
					or ($allow_sage and !$request->getPost("sage")) // sage is alloed AND request contains sage
			)
			{
				if ($parent_topic != 18520) // topic to report posts
				{
					$parent_topic_obj->ord = $ord;

					$result = $parent_topic_obj->save();
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
				$post->ord = $parent_topic_obj->ord;
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

		if (!is_mod()) // notification
		{
			$notification = new Notification();
			$post_id = $post->post_id;
			$forum_title = anti_xss($forum_obj->title);
			if (!$parent_topic) // new topic
			{
				$notification_text = "[$ip] - <b>$forum_title</b>: <span style='color:green;'>НОВАЯ ТЕМА</span> 
				<a href='/topic/$post_id' target='_blank' style='color:blue;' onclick=\"this.style.color='violet';\">#$post_id</a>";
			}
			else // reply to topic
			{
				$notification_text = "[$ip] - <b>$forum_title</b>: ответ в тему
				<a href='/topic/$parent_topic' target='_blank' style='color:blue;' onclick=\"this.style.color='violet';\">#$parent_topic</a>";
			}
			
			$notification->notify(1, $notification_text, $post_id, $parent_topic);
		}
		
		if ($forum_id == 11 and $parent_topic == 0) // if posting to changelog
		{
			send_message_to_telegram_channel("@DiscoursChangelog", $text . "\nОбсудить: https://".MAIN_HOST."/topic/".$post->post_id, TELEGRAM_TOKEN);
		}
		
		/*elseif ($parent_topic == 0) // posting to Discours Topics
		{
			send_message_to_telegram_channel("@DiscoursTopics", "https://discou.rs/topic/".$post->post_id, TELEGRAM_TOKEN);
		}*/
		
		//page_cache_delete("forum_".$forum_obj->forum_id); // delete page cache
		cache_delete("forum_".$forum_obj->forum_id); // delete page cache
		cache_delete("forum_1"); // delete main page cache
		//exec("curl --max-time 0.01 https://domain/forum/?forum=".$forum_obj->forum_id); // update page cache
	
		if ($request->getPost("ajax")) // report success to user
		{
			$output = array();
			$output["success"] = true;
			$output["post_id"] = $post->post_id;
			$output["benchmark"] = benchmark();
			echo json_encode($output);
		}

		else
		{
			return $this->response->redirect($_SERVER['HTTP_REFERER']);
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
				
			$html = "<content>";
			$html .= "<h2>Ошибка!</h2>";
			$html .= "<b>$error</b>";
			$html .= "<br><br>";
			$html .= "Для вашего удобства ваш пост находится в текстовом поле:";
			$html .= "<textarea style='width:100%;height:100px;'>$filtered_text</textarea>";
			$html .= "</content>";
				
			$twig_data = array
			(
				"html" => $html
			);
			echo render($twig_data);
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
		$max_file_size = 5 * 1048576; // in bytes
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
			// 300x300
			exec("convert -thumbnail 300x300 $tmp_name\[0] $thumb_path"); // [0] means 1st frame
		}

		list($thumb_w, $thumb_h) = getimagesize($thumb_path);
		
		/*if ($post->forum_id == 3)
		{
			$thumb_w = intval($thumb_w / 2);
			$thumb_h = intval($thumb_h / 2);
		}*/
    $thumb_w = intval($thumb_w / 2);
		$thumb_h = intval($thumb_h / 2);
		
		function random_str ($length, $keyspace = "0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ")
		{
				$str = "";
				$max = mb_strlen($keyspace, "8bit") - 1;
				for ($i = 0; $i < $length; ++$i) {
						$str .= $keyspace[random_int(0, $max)];
				}
				return $str;
		}
		$time = time();
		$rand = random_int(1000, 9999);
		$rand_str = random_str(8);
		
		//$new_file_name  = "{$time}_{$rand}.".$file_extension;
		//$new_thumb_name = "{$time}_{$rand}_thumb.".$file_extension;
		$new_file_name  = "{$rand_str}.".$file_extension;
		$new_thumb_name = "{$rand_str}_thumb.".$file_extension;
		
		copy ($tmp_name, UPLOAD_DIR."/".$new_file_name);
		copy ($thumb_path, UPLOAD_DIR."/".$new_thumb_name);
		
		$file_url  = FILE_PROTOCOL."://".FILE_HOST."/files/$new_file_name";
		$thumb_url = FILE_PROTOCOL."://".FILE_HOST."/files/$new_thumb_name";
		
		$post->file_url = $file_url;
		$post->thumb_url = $thumb_url;
		
		$post->thumb_w = $thumb_w;
		$post->thumb_h = $thumb_h;
	}

}