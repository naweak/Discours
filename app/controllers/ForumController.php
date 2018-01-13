<?php

use Phalcon\Mvc\Controller;

class ForumController extends Controller
{

	public function indexAction()
	{		
		$default_limit = 25;
		$replies_to_show = 3;
		$limit = $default_limit;
		
		if (isset($_GET["forum"]))
		{
			$forum_id = intval($_GET["forum"]);
		}
		
		else
		{
			$forum_id = 1;
		}

		$query_annex = "";
		$query_bind = [];

		if (isset($_GET["topic"])) // show topic
		{
			$topic_id = intval($_GET["topic"]);
			$query_annex = " AND post_id = :post_id:";
			$query_bind["post_id"] = $topic_id;
			
			$forum_obj = Forum::findFirst
			(
			[
				"forum_id = (SELECT Post.forum_id FROM Post WHERE post_id = :topic_id: LIMIT 1)",

				"bind" =>
				[
					"topic_id" => $topic_id
				]
			]
			);
		}

		else // or show forum (default action)
		{
			$query_annex = " AND forum_id = :forum_id:";
			$query_bind["forum_id"] = $forum_id;
			
			if ($forum_id == 1) // Главная
			{
				$query_annex = " AND forum_id != 3 AND forum_id != 6";
				unset($query_bind["forum_id"]);
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
		}
		
		if (!$forum_obj)
		{
			error_page (404);
		}
		
		$twig_data = array
		(
			"topics" => array(),
			
			"replies_to_show" => $replies_to_show,
			"default_limit" => $default_limit,
			"limit" => $limit,
			
			"forum_id" => $forum_obj->forum_id,
			"forum_title" => $forum_obj->title,
			"final_title" => $forum_obj->title,
			
			"phalcon_url" => PHALCON_URL,
			
			"is_mod" => is_mod()
		);
		
		if (isset($topic_id))
		{
			$twig_data["topic_id"] = $topic_id;
			$twig_data["replies_to_show"] = 9000;
		}
		/******/
		// transfer to array declaration
		if (isset($posting_error))
		{
			$twig_data["posting_error"] = $posting_error;
		}
		if (isset($declined_text))
		{
			$declined_text = anti_xss($declined_text);
			$twig_data["declined_text"] = $declined_text;
		}
		/******/
		
		$topics = Post::find
		(
    [
			"parent_topic = 0 $query_annex",
			"order" => "ord DESC",
			"limit" => $default_limit,
			
			"bind" => $query_bind
    ]
		);
		
		foreach ($topics as $topic)
		{
			$topic_array = $topic->to_array();
			$topic_array["replies"] = array();
			
			$replies = Post::find
			(
			[
				"parent_topic = :parent_topic:",
				"order" => "creation_time",

				"bind" => ["parent_topic" => $topic->post_id]
			]
			);
			
			foreach ($replies as $reply)
			{
				array_push ($topic_array["replies"], $reply->to_array());
			}
			
			array_push ($twig_data["topics"], $topic_array);
			
			echo "<!-- ".$topic->post_id." ".benchmark()." -->\n";
		}
		
		/* ########## */
		try
		{
			$pdo = new PDO("mysql:host=".MYSQL_HOST.";dbname=".MYSQL_DATABASE, MYSQL_USERNAME, MYSQL_PASSWORD);
			$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		}
		catch(PDOException $e)
		{
			die ("Connection failed: ".$e->getMessage());
		}
		$query = $pdo->query("SELECT COUNT(*) FROM notifications WHERE is_read = 0");
		$twig_data["notifications_unread"] = $query->fetchColumn();
		/* ########## */
		
		$twig_template = "default";

		if ($forum_obj->forum_id == 3)
		{
			$twig_template = "test";
		}
		
		if ($forum_obj->forum_id == 3) {echo "<!-- ".benchmark()." -->";}
		
		echo render($twig_data, ROOT_DIR."/templates/$twig_template", $twig_template);
		
		if ($forum_obj->forum_id or true) {echo "<!-- ".benchmark()." -->";}
	}

}