<?php

use Phalcon\Mvc\Controller;

class ForumController extends Controller
{

	public function indexAction()
	{
		//ignore_user_abort(true); // used for cURL
		
		$pdo = pdo();
	
		$default_limit = 20;
		$replies_to_show = 3;
		$limit = $default_limit;
		
		$use_page_cache = true;
		if (isset($_GET["fresh"]) or isset($_GET["page"]))
		{
			$use_page_cache = false;
		}
		
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

		if (!isset($_GET["topic"])) // show forum (default action)
		{
			///////////////
			if ($use_page_cache) // try to render from cache
			{
				/*$page_cache = page_cache_get("forum_".$forum_id);
				if ($page_cache !== false)
				{
					echo "<!-- GOT PAGE FROM CACHE ".benchmark()." -->\n";
					echo $page_cache;
					exit();
				}*/
				$twig_data = cache_get("forum_".$forum_id);
				if ($twig_data !== false)
				{
					echo "<!-- GOT DATA FROM CACHE ".benchmark()." -->\n";
					$twig_template = $twig_data["template"];
					echo "<!-- RENDERING STARTED ".benchmark()." -->\n";
					$rendered = render($twig_data, ROOT_DIR."/app/templates/$twig_template", $twig_template);
					echo "<!-- RENDERING FINISHED ".benchmark()." -->\n";
					echo $rendered;
					exit();
				}
			}
			///////////////
			
			$query_annex = "AND forum_id = :forum_id:";
			$query_bind["forum_id"] = $forum_id;
			
			if ($forum_id == 1) // Главная
			{
				$query_annex = "AND forum_id NOT IN (3, 6, 12, 14)";
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
			
			$description = "Дискурс — ".anti_xss($forum_obj->title); // use forum title as description
		}
		
		else // or show topic
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
			
			$topic = $topics[0];
			
			if ($topic->title) // if topic has title, use it as description
			{
				$twig_data["meta"]["description"] = "Дискурс — ".anti_xss($topic->title);
			}
			elseif (mb_strlen($topic->text) > 3) // otherwise, use topic text
			{
				$trim_chars = 250;
				$text_summary = mb_strlen($topic->text) > $trim_chars ? mb_substr($topic->text,0,$trim_chars)."..." : $topic->text;
				$twig_data["meta"]["description"] = "Дискурс — ".anti_xss($text_summary);
			}
			
			if ($topic->file_url) // set preview image
			{
				$twig_data["meta"]["image"] = $topic->file_url;
			}
		}
		
		if (!$forum_obj) // forum or topic not found (404)
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
		
		echo "<!-- TOPICS QUERY EXECUTED: ".benchmark()." -->\n";
		
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
		
		if (isset($posting_error))
		{
			$twig_data["posting_error"] = $posting_error;
		}
		
		if (isset($declined_text))
		{
			$twig_data["declined_text"] = anti_xss($declined_text);
		}
		
		if (isset($topic_id))
		{
			$twig_data["topic_id"] = $topic_id;
			$twig_data["replies_to_show"] = 9000;
		}

		foreach ($topics as $topic)
		{
			$topic_array = $topic->to_array();
			$topic_array["replies"] = array();
			
			//$omit_replies = false;
			$omit_replies = true;
			/*if (in_array($forum_obj->forum_id, array(3, 14))) // /test/, /old/
			{
				if (!$topic_id)
				{
					$omit_replies = true;
				}
			}*/
			if (isset($topic_id))
			{
				$omit_replies = false;
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
			
			if ($topic_id)
			{
				echo "<!-- TOPIC REPLIES EXECUTED: ".benchmark()." -->\n";
			}
			
			foreach ($replies as $reply)
			{
				array_push ($topic_array["replies"], $reply->to_array());
			}
			
			if ($omit_replies)
			{
				$topic_array["replies"] = array_reverse($topic_array["replies"]);
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
			
			echo "<!-- GOT REPLIES FOR TOPIC #".$topic->post_id." ".benchmark()." -->\n";
		}

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
		
		if (!isset($_GET["page"])) // only cache first page
		{
			if (!isset($topic_id)) // only cache forum page
			{
				$twig_data["template"] = $twig_template; // will be used later when the data is restored from cache
				//page_cache_set("forum_".$forum_obj->forum_id, $rendered);	
				cache_set("forum_".$forum_obj->forum_id, $twig_data, 24*60*60);
			}
		}
		
		echo "<!-- RENDERING STARTED ".benchmark()." -->\n";
		$rendered = render($twig_data, ROOT_DIR."/app/templates/$twig_template", $twig_template);
		echo "<!-- RENDERING FINISHED ".benchmark()." -->\n";
		echo $rendered;
	}

}