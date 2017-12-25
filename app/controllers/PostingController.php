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
		
		// Text
    /* Somehow accepts one-letter strings like "a" */
    if (mb_strlen($text) < $min_text_length)
    {
        $this->error("Текст слишком короткий!");
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
		
		// ADD BAN EXPIRATION!!!!!
		
		$active_ban = Ban::findFirst
		(
    [
			"ip = :ip:",
			
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
		
		// Process uploaded file
		//if ($this->request->hasFiles()) // doesn't check if file was uploaded or not
		if (is_uploaded_file($_FILES["userfile"]["tmp_name"]))
		{
			//$this->error("has file!");
			$files = $this->request->getUploadedFiles();
			$file = $files[0];
			
			$this->process_file($file, $post);
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
		
		$post->forum_id = $forum_id;
		$post->parent_topic = $parent_topic;
		$post->creation_time = $time;
		$post->ip = $ip;
		$post->ord = $ord;
		$post->order_in_topic = $order_in_topic;
		$post->text = $text;
		$post->title = $title;
		$post->name = $name;
		
		$result = $post->save();
	
		if ($result)
		{
			if ($parent_topic)
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
			
			if ($request->getPost("ajax"))
			{
				$output = ["success" => true];
				
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
		
		else
		{
			foreach ($post->getMessages() as $message)
			{
				echo $message->getMessage(), "<br/>";
			}
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
		$file_extension = $file->getExtension();
		$max_file_size = 5 * 1048576; // in bytes
		
		$allowed_extensions = ["jpg", "jpeg", "png", "gif", "bmp"];
		
		if (!in_array(strtolower($file_extension), $allowed_extensions))
		{
			$this->error("Unknown file extension ($file_extension)! Allowed file types: ".join(", ", $allowed_extensions));
		}
		
		if ($file_size > $max_file_size)
		{
			$this->error("File size ($file_size) exceeded the maximum of $max_file_size");
		}
		
		$identify_output = exec("identify $tmp_name");
		if (!$identify_output)
		{
			$this->error("Cannot identify image type!");
		}

		$thumb_path = tempnam(sys_get_temp_dir(), "thumb");
		exec("convert -thumbnail 150x150 $tmp_name $thumb_path");

		list($thumb_w, $thumb_h) = getimagesize($thumb_path);
		
		$curl_output = exec("curl --upload-file $tmp_name https://transfer.sh/image.$file_extension");
		$file_url = $curl_output;
		
		$curl_output = exec("curl --upload-file $thumb_path https://transfer.sh/image.$file_extension");
		$thumb_url = $curl_output;
		
		/*echo $file_url."<br>";
		echo $thumb_url."<br><br>";
		echo "<img src='$thumb_url'>";*/
		
		$post->file_url = $file_url;
		$post->thumb_url = $thumb_url;
		
		$post->thumb_w = $thumb_w;
		$post->thumb_h = $thumb_h;
	}

}