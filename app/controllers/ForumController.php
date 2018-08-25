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
		
		$pageviews_from_cache_name = "pageviews_".date("d-m");  
    $pageviews_from_cache = cache_get($pageviews_from_cache_name, function () {return 0;});
    cache_set($pageviews_from_cache_name, intval($pageviews_from_cache)+1);
    if (!is_json())
    {
		  echo "<!-- P/V: $pageviews_from_cache -->\n";
    }
		
		$use_page_cache = true;
		if (isset($_GET["fresh"]) or isset($_GET["page"]) or isset($_GET["order"]))
		{
			$use_page_cache = false;
		}
		
		if (isset($_GET["forum"]))
		{
			$forum_id = intval($_GET["forum"]);
		}
		
		else
		{
      if (isset(domain_array()["default_forum_id"]))
      {
        $forum_id = domain_array()["default_forum_id"];
      }
      else
      {
        $forum_id = 1;
      }
		}

		$query_annex = "";
		$offset = 0;
		$query_bind = [];
    
    if (!isset($_GET["topic"])) // get $forum_obj while showing forum
    {
      $slug = (isset($_GET["slug"]) and $_GET["slug"] != "") ?
        $_GET["slug"]: // if true
        false; // if false
      
      if ($slug != false) // identify by $slug
      {
        $forum_obj_query = "slug = :slug:";
        $forum_obj_bind = ["slug" => $slug];
      }
      else // identify by $forum_id
      {
        $forum_obj_query = "forum_id = :forum_id:";
        $forum_obj_bind  = ["forum_id" => $forum_id];
      }      
      $forum_obj = Forum::findFirst // get $forum_obj
      (
      [
        $forum_obj_query,
        "bind" => $forum_obj_bind
      ]
      );
      if ($slug and $forum_obj)
      {
        $forum_id = $forum_obj->forum_id;
      }
    }
    
    else // get $forum_obj while showing topic
    {
			$forum_obj = Forum::findFirst
			(
			[
				"forum_id = (SELECT Post.forum_id FROM Post WHERE post_id = :topic_id: AND deleted_by = 0 LIMIT 1)",

				"bind" =>
				[
					"topic_id" => intval($_GET["topic"])
				]
			]
			);
    }
    
    if (!$forum_obj) // forum or topic not found (404)
		{
			if (isset($topic_id) and $topic_id == 1)
			{
				first_topic_error_page();
				exit();
			}
			
			error_page(["code" => 404]);
			exit();
		}
    
    if ($forum_obj->forum_id == 3 and !is_mod()) # /test/
		{
			header("HTTP/1.0 403 Forbidden");
			die("403 Forbidden");
		}
    
    if (isset(domain_array()["forum_ids"])) // check whether forum is allowed for this domain
    {
      if (!in_array($forum_obj->forum_id, domain_array()["forum_ids"]))
      {
        header("HTTP/1.0 404 Not Found");
			  die("404 Not Found");
      }
    }
    
    if ($forum_obj->forum_id == 14 and
        isset($_GET["topic_id"]) and
        mb_strpos($_SERVER["REQUEST_URI"], "topic")) # /old/
		{
			header("Location: /old/res/".intval($_GET["topic_id"]).".html");
			die();
		}
    
    $twig_template = "default";
		if ($forum_obj->forum_id == 3)
		{
      if (file_exists(ROOT_DIR."/app/templates/test/template.html"))
      {
			  $twig_template = "test";
      }
      else
      {
        //die("Test template not set");
        $twig_template = "default";
      }
		}
		if ($forum_obj->forum_id == 14)
		{
			$twig_template = "wakaba";
		}
    if (isset(domain_array()["template"]))
    {
      $twig_template = domain_array()["template"];
    }
    
    function assign_post_properties (&$post) // highlight my own posts, replies to my posts, etc.
    {
      if ($post["session_id"] == session_id())
      {
        $post["my_post"] = true;
      }
      
      if (user_id())
      {
        if (user_id() == $post["user_id"])
        {
          $post["my_post"] = true;
        }
      }
      
      if ($post["reply_to_session_id"] == session_id())
      {
        $post["reply_to_my_post"] = true;
      }
      
      if (user_id())
      {
        if (user_id() == $post["reply_to_user_id"] )
        {
          $post["reply_to_my_post"] = true;
        }
      }
        
      $post["session_id"] = false; // erase sensitive data to make it impossible to show in the template
      $post["user_id"]    = false;
      $post["reply_to_session_id"] = false;
      $post["reply_to_user_id"] = false;
    }
    
    function assign_post_properties_to_twig_data (&$twig_data) // proccess $twig_data elements with the function above
    {
      foreach ($twig_data["topics"] as &$topic)
      {
        assign_post_properties($topic);
        foreach ($topic["replies"] as &$reply)
        {
          assign_post_properties($reply);
        }
      }
    }

		if (!isset($_GET["topic"])) // show forum (default action)
		{
			if ($use_page_cache) // try to render from cache
			{
				$twig_data = cache_get("forum_".$forum_id);
				if ($twig_data !== false)
				{
          assign_post_properties_to_twig_data($twig_data);
          $twig_data["invite_only"] = INVITE_ONLY;
          if (!is_json())
          {
					  echo "<!-- GOT DATA FROM CACHE ".benchmark()." -->\n";
					  echo "<!-- RENDERING STARTED ".benchmark()." -->\n";
          }
					$rendered = render($twig_data, ROOT_DIR."/app/templates/$twig_template", $twig_template);
					if (!is_json())
          {
            echo "<!-- RENDERING FINISHED ".benchmark()." -->\n";
          }
					echo $rendered;
					exit();
				}
			}
			
			$query_annex = "AND forum_id = :forum_id:";
			$query_bind["forum_id"] = $forum_id;
			
			if ($forum_id == 1) // Главная
			{
				$query_annex = "AND forum_id NOT IN (3, 6, 12, 14, 19)";
				unset($query_bind["forum_id"]);
			}
				
			if (isset ($_GET["page"]))
			{
				$offset = $default_limit * abs(intval($_GET["page"])-1);
			}
		}
    
    else // show topic
    {
      $topic_id = intval($_GET["topic"]);
      $query_annex = " AND post_id = :post_id:";
      $query_bind["post_id"] = $topic_id;
    }
		
		$topics_order = "ord DESC";
    
    //if ($forum_obj->slug == "changelog")
    if (in_array($forum_obj->slug, ["changelog", "1chan"]))
    {
      $topics_order = "creation_time DESC";
    }
		
		if (isset($_GET["order"]) and $_GET["order"] == "new")
		{
			echo "<div align='center'><h2>Сортировка по времени создания темы работает только на первой странице!</h2></div>";
			$topics_order = "creation_time DESC";
		}
		
		$topics = Post::find
		(
    [
			"parent_topic = 0 AND deleted_by = 0 $query_annex",
			"order" => $topics_order,
			"limit" => $default_limit,
			"offset" => $offset,
			
			"bind" => $query_bind
    ]
		);
		
		echo "<!-- TOPICS QUERY EXECUTED: ".benchmark()." -->\n";
    
    if (isset($topics[0]))
    {
      $topic = $topics[0]; // topic object
    }
 
		$twig_data = array
		(
			"topics" => array(),
			
			"replies_to_show" => $replies_to_show,
			"default_limit" => $default_limit,
			"limit" => $limit,
			
			"forum_id" => $forum_obj->forum_id,
			"forum_title" => $forum_obj->title,
			//"final_title" => (isset($topic_id) and $topic_id != 0 and $topic->title) ? anti_xss($topic->title) : anti_xss($forum_obj->title),
      "final_title" => anti_xss($forum_obj->title),
      "forum_href" => full_forum_href($forum_obj->slug, $forum_obj->forum_id),
			
			"meta" => [],
			"file_host" => FILE_HOST,
			
			"is_mod" => is_mod(),
      "invite_only" => INVITE_ONLY
		);
		
		if (isset($posting_error))
		{
			$twig_data["posting_error"] = $posting_error;
		}
		
		if (isset($declined_text))
		{
			$twig_data["declined_text"] = anti_xss($declined_text);
		}
		
		if (isset($topic_id)) // show topic
		{
			$twig_data["topic_id"] = $topic_id;
			$twig_data["replies_to_show"] = 9000;
			
			if ($topic->title) // if topic has title, use it as description
			{
				$twig_data["meta"]["description"] = anti_xss($topic->title);
			}
			elseif (mb_strlen($topic->text) > 3) // otherwise, use topic text
			{
				$trim_chars = 250;
				$text_summary = mb_strlen($topic->text) > $trim_chars ? mb_substr($topic->text,0,$trim_chars)."..." : $topic->text;
				$twig_data["meta"]["description"] = anti_xss($text_summary);
			}
			
			if ($topic->file_url) // set preview image
			{
				$twig_data["meta"]["image"] = $topic->file_url;
			}
      
      $twig_data["final_title"] = $twig_data["meta"]["description"];
		}

		foreach ($topics as $topic)
		{
			$topic_array = $topic->to_array();
			$topic_array["replies"] = array();
			
			$omit_replies = true;
			if (isset($topic_id))
			{
				$omit_replies = false;
			}
	
			$replies = Post::find
			(
			[
        "parent_topic = :parent_topic: AND deleted_by = 0",
				"order" => $omit_replies ? "creation_time DESC" : "creation_time",
				"limit" => $omit_replies ? "3" : "",

				"bind" => ["parent_topic" => $topic->post_id]
			]
			);
			
			if (isset($topic_id) and $topic_id)
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
				$sql = $pdo->prepare("SELECT COUNT(*) FROM posts WHERE parent_topic = :parent_topic AND deleted_by = 0");
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
		
		if (!isset($_GET["page"])) // only cache first page
		{
			if (!isset($topic_id)) // only cache forum page
			{
				$twig_data["template"] = $twig_template; // will be used later when the data is restored from cache
				cache_set("forum_".$forum_obj->forum_id, $twig_data, 24*60*60);
			}
		}
    
		assign_post_properties_to_twig_data($twig_data);
		echo "<!-- RENDERING STARTED ".benchmark()." -->\n";
		$rendered = render($twig_data, ROOT_DIR."/app/templates/$twig_template", $twig_template);
		echo "<!-- RENDERING FINISHED ".benchmark()." -->\n";
		echo $rendered;
	}

}