<?php

use Phalcon\Mvc\Model;

class Post extends Model
{
	
	public function initialize()
  {
  	$this->setSource("posts");
  }
	
	public function beforeValidation()
  {
		foreach (["deleted_by", "user_id", "session_id", "display_username", "title", "name", "flag", "file_url", "thumb_url", "thumb_w", "thumb_h", "reply_to"] as $element)
		{
			if ($this->$element == NULL)
			{
				$this->$element = "";
			}
		}
	}
  
  function get_text_formatted ()
  {
    if ($this->deleted_by == 0)
    {
      $text_formatted_from_cache_name = "text_formatted_".$this->post_id;
      $text_formatted_from_cache = cache_get($text_formatted_from_cache_name);
      if ($text_formatted_from_cache === false)
      {
        $text_formatted = markup($this->text,
          ["forum_id" => $this->forum_id,
           "parent_topic" => $this->parent_topic]
        );
        cache_set($text_formatted_from_cache_name, $text_formatted, 24*60*60); // save formatted text in cache
      }
      else
      {
        $text_formatted = $text_formatted_from_cache;
      }
    }
    else
    {
      $text_formatted = "Post was deleted";
    }
    return $text_formatted;
  }
  
  function get_plain_text ($limit = 0, $trim_marker = "")
  {
    $text_formatted = $this->get_text_formatted();
    $plain_text = strip_tags($text_formatted);
    if ($limit != 0)
    {
      return mb_strimwidth($plain_text, 0, $limit, $trim_marker);
    }
    else
    {
      return $plain_text;
    }
  }
	
  // Used for caching (it's too hard to cache objects in PHP)
  // It's for private use only because of sensitive data that needs to be cached
	function to_array ($external = false) // WARNING! Outputs sensitive data like user_id and session_id!
	{
    $forum_title_from_cache_name = "forum_title_".$this->forum_id;
    $forum_slug_from_cache_name = "forum_slug_".$this->forum_id;
    $forum_title_from_cache = cache_get($forum_title_from_cache_name);
    $forum_slug_from_cache = cache_get($forum_slug_from_cache_name);
    if
    (
      $forum_title_from_cache === false or
      $forum_slug_from_cache === false
    )
    {
      $forum_obj = Forum::findFirst
      (
        [
        "forum_id = :forum_id:",

        "bind" =>
        [
          "forum_id" => $this->forum_id
        ]
        ]
      );
      $forum_title = $forum_obj->title;
      $forum_slug  = $forum_obj->slug;
      cache_set($forum_title_from_cache_name, $forum_title, 24*60*60); // save forum title in cache
      cache_set($forum_slug_from_cache_name,  $forum_slug, 24*60*60); // save forum title in cache
    }
    else
    {
      $forum_title = $forum_title_from_cache;
      $forum_slug  = $forum_slug_from_cache;
    }

    $text_formatted = $this->get_text_formatted();
    
    if ($this->deleted_by)
    {
      $modlog_action = Modlog::findFirst
      (
        [
          "post_id = :post_id:",
          "bind" => ["post_id" => $this->post_id]
        ]
      );
      $text_formatted = "<span class='deleted_post'>
      Пост удален модератором.
      Номер действия в модлоге: ".$modlog_action->action_id.".</span>";
    }
    
    $post_id = $this->post_id;
    $parent_topic = $this->parent_topic;
    $reply_to = $this->reply_to;
    
    // Reply_to post_id:
    $reply_to_post_id = cache_get("reply_to_post_id_".$this->parent_topic."_".$this->reply_to,
    function () use ($parent_topic, $reply_to)
    {
      $reply_to_object = Post::findFirst
      (
      [
        "parent_topic = :parent_topic: AND order_in_topic = :order_in_topic:",
        "bind" =>
        [
          "parent_topic" => $parent_topic,
          "order_in_topic" => $reply_to
        ]
      ]
      );
      if ($reply_to_object)
      {
        return $reply_to_object->post_id;
      }
      else
      {
        return false;
      }
    });
    
    // Reply_to session_id:
    $reply_to_session_id = cache_get("post_{$post_id}_reply_to_session_id",
    function () use ($parent_topic, $reply_to)
    {
      $reply_to_object = Post::findFirst
      ([
        "parent_topic = :parent_topic: AND order_in_topic = :order_in_topic:",
        "bind" =>
        [
          "parent_topic" => $parent_topic,
          "order_in_topic" => $reply_to
        ]
     ]);
     if ($reply_to_object)
     {   
      return $reply_to_object->session_id;
     }
    });
    
    // Reply_to user_id:
    $reply_to_user_id = cache_get("xpxpost_{$post_id}_reply_to_user_id",
    function () use ($parent_topic, $reply_to)
    {
      $reply_to_object = Post::findFirst
      ([
        "parent_topic = :parent_topic: AND order_in_topic = :order_in_topic:",
        "bind" =>
        [
          "parent_topic" => $parent_topic,
          "order_in_topic" => $reply_to
        ]
     ]);
     if ($reply_to_object)
     {
      return $reply_to_object->user_id;
     }
    });

		$output =
		[
			"post_id" => $this->post_id,
			"parent_topic" => $this->parent_topic,
			"forum_id" => $this->forum_id,
			"order_in_topic" => $this->order_in_topic,
      "reply_to" => $this->reply_to,
      "reply_to_post_id" => $reply_to_post_id,
      
			"time_formatted" => smart_time_format($this->creation_time),
      "time_formatted_en" => date("d.m.y H:i:s", $this->creation_time),
      
			"name_formatted" => anti_xss($this->name),
      "flag" => anti_xss($this->flag),
			"text_formatted" => $text_formatted,
      
      "author_formatted" => anti_xss("Анонимно"),
      //"author_href" => "https://ru.wikipedia.org/wiki/Rozen_Maiden",
      //"author_icon_href" => "/LJuser.svg",
      
      "session_id" => $this->session_id, // will be hidden in assign_post_properties() function
      "user_id" => $this->user_id, //  this also is hidden in assign_post_properties()
      "reply_to_session_id" => $reply_to_session_id, // and this
      "reply_to_user_id" => $reply_to_user_id, // and also this
			
			"file_url"  => $this->file_url,
			"thumb_url" => $this->thumb_url,
      "file_name" => basename($this->file_url),
			
			"thumb_w" => $this->thumb_w,
			"thumb_h" => $this->thumb_h,
      
      "ord" => $this->ord,
		];
    
    if ($forum_slug_from_cache == "int") // /int/
    {
      $output["author_formatted"] = "Anonymous";
    }
    
    if ($this->display_username)
    {
      $author_user_id = $this->user_id;
      $author_username = cache_get("user_id_".$this->user_id."_username",
      function () use ($author_user_id)
      {
        $author_object = User::findFirst
        (
          [
          "user_id = :user_id:",
          "bind" => ["user_id" => $author_user_id]
          ]
        );
        if ($author_object)
        {
          return $author_object->username;
        }
        else
        {
          return "user_not_found";
        }
      }
      );
      
      $output["author_formatted"] = $author_username;
      $output["is_anonymous"] = false;
      
      if ($author_username == "zefirov")
      {
        $output["author_formatted"] = "<span style='color:red;'>" . $author_username . "</span>";
      }
    }
    
    else
    {
      $output["is_anonymous"] = true;
    }
		
		$output["forum_title"] = $forum_title;
    $output["forum_slug"] = $forum_slug;
    $output["forum_href"] = full_forum_href(@$forum_slug, $this->forum_id);
		
		if ($this->parent_topic == 0)
		{
			$output["title_formatted"] = str_replace(" ", "&nbsp;", anti_xss($this->title));
		}
    
    $plain_text = strip_tags($text_formatted);
    
    $lines = explode("\n", $plain_text);
    
    $max_lines_to_show = MAX_LINES_TO_SHOW;
    $max_chars_to_show = MAX_CHARS_TO_SHOW;
    
    if (mb_strlen($plain_text) > $max_chars_to_show)
		{
			$output["text_preview"] = str_replace("\n", "<br>", mb_substr($plain_text, 0, $max_chars_to_show));
		}
    
    elseif (count($lines) > $max_lines_to_show)
    {
      $output["text_preview"] = "";
      $lines_to_show = [];
      for ($i = 0; $i < $max_lines_to_show; $i++)
      {
        array_push($lines_to_show, $lines[$i]);
      }
      while (end($lines_to_show) === "")
      {
          array_pop($lines_to_show);
      }
      for($i = 0;isset($lines_to_show[$i]);$i++)
      {
        $output["text_preview"] .= $lines_to_show[$i];
        if(isset($lines_to_show[$i+1]))
        {
          $output["text_preview"] .= "<br>";
        }
      }
    }
		
		return $output;
	}
	
	function delete_files ()
	{
		$file_path  = UPLOAD_DIR."/".basename($this->file_url);
		$thumb_path = UPLOAD_DIR."/".basename($this->thumb_url);
		if (is_file($file_path))
		{
			unlink($file_path);
		}
		if (is_file($thumb_path))
		{
			unlink($thumb_path);
		}
	
    $this->file_url = "";
    $this->thumb_url = "";
    $this->thumb_w = 0;
    $this->thumb_h = 0;
    $this->save();
	}

	public $post_id;
  public $forum_id;
  public $parent_topic;
  public $creation_time;
  public $deleted_by;
  public $ip;
	public $user_id;
	public $session_id;
  public $display_username;
  public $ord;
	public $order_in_topic;
  public $reply_to;
  public $text;
  public $title;
  public $name;
  public $flag;
	public $file_url;
	public $thumb_url;
	public $thumb_w;
	public $thumb_h;
}
