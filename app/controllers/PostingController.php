<?php

use Phalcon\Mvc\Controller;
use Phalcon\Http\Request;

class PostingController extends Controller
{

	public function postAction()
	{
		$request = new Request();
		
		$reply_delay     = 5;
		$new_topic_delay = 10*60;
		$min_title_length = 3;
		$max_title_length = 255;
		$max_name_length = 25;
		$min_text_length = 3;
		$max_text_length = 15000;
		
		$forum_id     = intval($request->getPost("forum_id"));
		$parent_topic = intval($request->getPost("parent_topic"));
		$title        = $request->getPost("title");
		$name         = $request->getPost("name");
		$text         = $request->getPost("text");
		
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
		
		if ($forum_id == 9)
		{
			$this->error("Только администраторы могут создавать темы.");
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
		
		// Create post object
		$post = new Post();
		$post->forum_id = $forum_id; // will be used in process_file()
		$post->parent_topic = $parent_topic;
		
		// Process uploaded file
		if (is_uploaded_file($_FILES["userfile"]["tmp_name"]))
		{
			//$this->error("has file!");
			$files = $this->request->getUploadedFiles();
			$file = $files[0];
			
			$this->process_file($file, $post);
		}
		
		else
		{
			if (!$parent_topic)
			{
				$this->error("Прикрепите картинку для создания темы.");
			}
		}
		
		// Save new post
		$ord = round(microtime(true) * 1000);
		if (!$parent_topic)
		{
			if ($parent_topic == 10670)
			{
				//$ord = $ord*2;
			}
		}
		
		$post->creation_time = $time;
		$post->ip = $ip;
		$post->ord = $ord;
		$post->order_in_topic = $order_in_topic;
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
			$parent_topic_obj->ord = $ord;

			$parent_result = $parent_topic_obj->save();
			if (!$parent_result)
			{
				foreach ($post->getMessages() as $message)
				{
					echo $message->getMessage(), "<br/>";
				}

				die("Error updating parent topic");
			}
		}
		
		//if (!is_mod() or true) // notification
		if (!is_mod()) // notification
		{
			$notification = new Notification();
			$post_id = $post->post_id;
			$forum_title = anti_xss($forum_obj->title);
			
			if (!$parent_topic) // new topic
			{
				$notification->notify
				(1,
				 $post_id,
				"[$ip] - $forum_title: <span style='color:green;'>NEW TOPIC!!!</span> 
				<a href='/topic/$post_id' target='_blank' style='color:blue;' onclick=\"this.style.color='violet';\">#$post_id</a>"
				);
			}
			
			else // reply to topic
			{
				$notification->notify
				(1,
				 $post_id,
				"[$ip] - $forum_title: New reply to topic 
				<a href='/topic/$parent_topic' target='_blank' style='color:blue;' onclick=\"this.style.color='violet';\">#$parent_topic</a>"
				);
			}
		}
	
		if ($request->getPost("ajax")) // report success to user
		{
			$output = ["success" => true];

			/* HTML output */
			$post_array = $post->to_array();
			if ($parent_topic)
			{
				$twig_data["block"] = "reply";
				$twig_data["reply"] = $post_array;
			}
			else
			{
				$twig_data["block"] = "topic_with_replies";
				$twig_data["topic"] = $post_array;
			}
			$html = render($twig_data);
			$output["html"] = $html;
			/* / HTML output */

			$output["benchmark"] = benchmark();
			
			// Remove this? HTML is already included
			if ($parent_topic)
			{
				$output["reply"] = $post->to_array();
			}

			else
			{
				$output["topic"] = $post->to_array();
			}

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
			
		die();
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
    $max_file_width  = 4096;
    $max_file_height = 4096;
		
		$allowed_extensions = ["jpg", "jpeg", "png", "gif", "bmp"];
		
		if (!in_array($file_extension, $allowed_extensions))
		{
			$this->error("Unknown file extension ($file_extension)! Allowed file types: ".join(", ", $allowed_extensions));
		}
		
		if ($file_size > $max_file_size)
		{
			$this->error("File size ($file_size) exceeded the maximum of $max_file_size");
		}
		
		//copy ($tmp_name, "/tmp/uploaded_".time().".".$file_extension);
		/*$identify_output = exec("identify $tmp_name"); // crashes
		if (!$identify_output)
		{
			$this->error("Cannot identify image type!");
		}*/
		/*if ($file_type == "application/x-bzip2") // Large image attack prevention
		{
			$this->error("Smart enought, ain't ya?");
		}*/
		$exif_imagetype = exif_imagetype($tmp_name);
		if (!$exif_imagetype)
		{
			$this->error("Загруженный файл не является изображением.");
		}
		
		//$identify_output = exec("identify -format '%[fx:w]x%[fx:h]' $tmp_name"); // crashes
		//$this->error($identify_output);
    list($file_w, $file_h) = getimagesize($tmp_name);
    if ($file_w > $max_file_width or $file_h > $max_file_height)
    {
      $this->error("Image too large ($max_file_width x $max_file_height max)");
    }

		$thumb_path = tempnam(sys_get_temp_dir(), "thumb");
		if ($post->forum_id != 3)
		{
			exec("convert -thumbnail 150x150 $tmp_name $thumb_path");
		}
		else
		{
			//test forum
			//exec("convert -resize 150x150 -level 0%,100%,0.8 -density 300 -sharpen 1x1 -quality 100 $tmp_name $thumb_path");
			if (!$post->parent_topic) // new topic
			{
				$square_size = 150;
			}
			else // reply
			{
				$square_size = 100;
			}
			exec("convert $tmp_name -resize '{$square_size}^>' -gravity center -crop {$square_size}x{$square_size}+0+0 -strip $thumb_path"); // square
			
			//exec("convert $tmp_name -resize 150 -density 300 $thumb_path");
		}

		list($thumb_w, $thumb_h) = getimagesize($thumb_path);
		
		$time = time();
		$rand = random_int(1000, 9999);
		
		$new_file_name  = "{$time}_{$rand}.".$file_extension;
		$new_thumb_name = "{$time}_{$rand}_thumb.".$file_extension;
		$new_path = ROOT_DIR."/../public/files";
		
		copy ($tmp_name, $new_path."/".$new_file_name);
		copy ($thumb_path, $new_path."/".$new_thumb_name);
		
		$file_url  = "https://discou.rs/files/$new_file_name";
		$thumb_url = "https://discou.rs/files/$new_thumb_name";
		
		$post->file_url = $file_url;
		$post->thumb_url = $thumb_url;
		
		$post->thumb_w = $thumb_w;
		$post->thumb_h = $thumb_h;
	}

}