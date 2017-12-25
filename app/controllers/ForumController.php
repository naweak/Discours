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
		
		$twig_data = array
		(
			"topics" => array(),
			
			"replies_to_show" => $replies_to_show,
			"default_limit" => $default_limit,
			"limit" => $limit,
			
			"forum_id" => $forum_obj->forum_id,
			"forum_title" => $forum_obj->title,
			
			"phalcon_url" => PHALCON_URL,
			
			"is_mod" => is_mod()
		);
		
		if (!$forum_obj)
		{
			error_page (404);
		}
		
		$twig_data["forum_id"] = $forum_id;

		$query_annex = "";
		$query_bind = [];

		if (isset($_GET["topic"])) // show topic
		{
			$topic_id = intval($_GET["topic"]);
			$twig_data["topic_id"] = $topic_id;
			$twig_data["replies_to_show"] = 9000;
			$query_annex = " AND post_id = :post_id:";
			$query_bind["post_id"] = $topic_id;
		}

		else // or show forum (default action)
		{
			$query_annex = " AND forum_id = :forum_id:";
			$query_bind["forum_id"] = $forum_id;
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
		}
		
		$twig_template = "default";
		$twig_template = "test";
		
		echo render($twig_data, DEFAULT_TWIG_FILESYSTEM, $twig_template);
	}

}
