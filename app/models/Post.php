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
		foreach (["deleted_by", "user_id", "session_id", "title", "name", "file_url", "thumb_url", "thumb_w", "thumb_h", "reply_to"] as $element)
		{
			if ($this->$element == NULL)
			{
				$this->$element = "";
			}
		}
	}
	
	function to_array ($external = false)
	{
		if ($this->parent_topic == 0) // get forum title for topic
		{
			$forum_title_from_cache_name = "forum_title_".$this->forum_id;
			$forum_title_from_cache = cache_get($forum_title_from_cache_name);
			if ($forum_title_from_cache === false)
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
				cache_set($forum_title_from_cache_name, $forum_title, 24*60*60); // save forum title in cache
			}
			else
			{
				$forum_title = $forum_title_from_cache;
			}
		}

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
		
		$output = array
		(
			"post_id" => $this->post_id,
			"parent_topic" => $this->parent_topic,
			"forum_id" => $this->forum_id,
			"order_in_topic" => $this->order_in_topic,
      "reply_to" => $this->reply_to,
			"time_formatted" => smart_time_format($this->creation_time),
			"name_formatted" => anti_xss($this->name),
			"text_formatted" => $text_formatted,
			
			"file_url"  => $this->file_url,
			"thumb_url" => $this->thumb_url,
			
			"thumb_w" => $this->thumb_w,
			"thumb_h" => $this->thumb_h,
		);
    
    if ($this->deleted_by)
    {
      $modlog_action = Modlog::findFirst
      (
        [
          "post_id = :post_id:",
          "bind" => ["post_id" => $this->post_id]
        ]
      );
      $output["text_formatted"] = "<span class='deleted_post'>
      Пост удален модератором.
      Номер действия в модлоге: ".$modlog_action->action_id.".</span>";
    }
		
		if (isset($forum_title))
		{
			$output["forum_title"] = $forum_title;
		}
		
		if ($this->parent_topic == 0)
		{
			$output["title_formatted"] = str_replace(" ", "&nbsp;", anti_xss($this->title));
		}
    
    //$plain_text = strip_tags($text_formatted);
    $plain_text = strip_tags($output["text_formatted"]);
    $e = explode("\n", $plain_text);
    
    $max_lines_to_show = 5;
    $max_chars_to_show = 700;
    
    if (count($e) > $max_lines_to_show)
    {
      $output["text_preview"] = "";
      for ($i = 0; $i < $max_lines_to_show; $i++)
      {
        $output["text_preview"] .= $e[$i] . "<br>";
      }
    }
    
    elseif (mb_strlen($text_formatted) > $max_chars_to_show)
    //elseif (mb_strlen($output["text_formatted"]) > $max_chars_to_show)
		{
			$output["text_preview"] = mb_substr($plain_text, 0, $max_chars_to_show);
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
  public $ord;
	public $order_in_topic;
  public $reply_to;
  public $text;
  public $title;
  public $name;
	public $file_url;
	public $thumb_url;
	public $thumb_w;
	public $thumb_h;
}
