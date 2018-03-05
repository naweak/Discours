<?php

use Phalcon\Mvc\Controller;

class ForumController extends Controller
{

	public function indexAction()
	{
		$pdo = pdo();
	
		$default_limit = 20;
		$replies_to_show = 3;
		$limit = $default_limit;
		
		if (isset($_GET["forum"]))
		{
			$forum_id = intval($_GET["forum"]);
		}
		
		else
		{
			$forum_id = 0;
		}

		$query_annex = "";
		$offset = 0;
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
				$query_annex = " AND forum_id != 3 AND forum_id != 6 AND forum_id != 12 AND forum_id != 14";
				unset($query_bind["forum_id"]);
			}
				
			if (isset ($_GET["page"]))
			{
				$offset = $default_limit * abs(intval($_GET["page"])-1);
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
			if ($topic_id == 1)
			{
				first_topic_error_page();
				exit();
			}
			
			error_page(404);
			exit();
		}
		
		$topics = Post::find
		(
    [
			"parent_topic = 0 $query_annex",
			"order" => "ord DESC",
			"limit" => $default_limit,
			"offset" => $offset,
			
			"bind" => $query_bind
    ]
		);
		
		$twig_data = array
		(
			"topics" => array(),
			
			"replies_to_show" => $replies_to_show,
			"default_limit" => $default_limit,
			"limit" => $limit,
			
			"forum_id" => $forum_obj->forum_id,
			"forum_title" => $forum_obj->title,
			"final_title" => $forum_obj->title,
			
			"meta" => array(),
			
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
		
		if (!isset($topic_id)) // forum page
		{
			$description = "Дискурс — ".anti_xss($forum_obj->title);
		}
		
		else // topic page
		{
			$topic = $topics[0];
			
			if ($topic->title)
			{
				//$description = "Дискурс — ".anti_xss($topic->title);
				$twig_data["meta"]["description"] = "Дискурс — ".anti_xss($topic->title);
			}
			
			elseif (mb_strlen($topic->text) > 3)
			{
				$trim_chars = 250;
				$text_summary = mb_strlen($topic->text) > $trim_chars ? mb_substr($topic->text,0,$trim_chars)."..." : $topic->text;
				//$description = "Дискурс — ".anti_xss($text_summary);
				$twig_data["meta"]["description"] = "Дискурс — ".anti_xss($text_summary);
			}
			
			if ($topic->file_url)
			{
				$twig_data["meta"]["image"] = $topic->file_url;
			}
		}
		
		foreach ($topics as $topic)
		{
			$topic_array = $topic->to_array();
			$topic_array["replies"] = array();
			
			$omit_replies = false;
			
			if (in_array($forum_obj->forum_id, array(3, 14))) // /test/, /old/
			{
				if (!$topic_id)
				{
					$omit_replies = true;
				}
			}
			
			$replies = Post::find
			(
			[
				"parent_topic = :parent_topic:",
				"order" => $omit_replies ? "creation_time DESC" : "creation_time",
				"limit" => $omit_replies ? "3" : "",

				"bind" => ["parent_topic" => $topic->post_id]
			]
			);
			
			foreach ($replies as $reply)
			{
				array_push ($topic_array["replies"], $reply->to_array());
			}
			
			if ($omit_replies)
			{
				$topic_array["replies"] = array_reverse($topic_array["replies"]);
				
				/*$result = $pdo->prepare("SELECT post_id FROM posts WHERE parent_topic = :parent_topic");
				$result ->bindParam(":parent_topic", $topic->post_id, PDO::PARAM_INT);
				$result->execute();*/
				$sql = $pdo->prepare("SELECT COUNT(*) FROM posts WHERE parent_topic = :parent_topic");
				$sql->bindParam(":parent_topic", $topic->post_id, PDO::PARAM_INT);
				$sql->execute();
				$total_posts_in_topic = $sql->fetchColumn();
				if ($total_posts_in_topic > $replies_to_show)
				{
					$omitted_replies = $total_posts_in_topic - $replies_to_show;
					$topic_array["omitted_replies"] = $omitted_replies;
				}
			}
			
			array_push ($twig_data["topics"], $topic_array);
			
			echo "<!-- ".$topic->post_id." ".benchmark()." -->\n";
		}
		
		/*if (isset($description))
		{
			$twig_data["description"] = $description;
		}*/
		
		$query = $pdo->query("SELECT COUNT(*) FROM notifications WHERE is_read = 0");
		$twig_data["notifications_unread"] = $query->fetchColumn();
		
		$twig_template = "default";

		if ($forum_obj->forum_id == 3)
		{
			$twig_template = "test";
		}
		
		if ($forum_obj->forum_id == 14)
		{
			$twig_template = "wakaba";
		}
		
		/*if (isset($_GET["wakaba"]))
		{
			$twig_template = "wakaba";
		}*/
		
		if ($forum_obj->forum_id == 3) {echo "<!-- ".benchmark()." -->";}
		
		echo render($twig_data, ROOT_DIR."/app/templates/$twig_template", $twig_template);
		
		if ($forum_obj->forum_id or true) {echo "<!-- ".benchmark()." -->";}
	}

}