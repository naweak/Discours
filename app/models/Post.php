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
		foreach (["title", "name", "file_url", "thumb_url", "thumb_w", "thumb_h"] as $element)
		{
			if ($this->$element == NULL)
			{
				$this->$element = "";
			}
		}
	}
	
	function to_array ($external = false)
	{
		//$VotingController = new VotingController();
		//$LikeController   = new LikeController();
		
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
		
		$text_formatted_from_cache_name = "text_formatted_".$this->post_id;
		$text_formatted_from_cache = cache_get($text_formatted_from_cache_name);
		
		if (!$text_formatted_from_cache)
		{
			$text_formatted = markup($this->text,
				["forum_id" => $this->forum_id,
				 "parent_topic" => $this->parent_topic]
			);
			cache_set($text_formatted_from_cache_name, $text_formatted, 7*24*60*60);
		}
		
		else
		{
			$text_formatted = cache_get($text_formatted_from_cache_name);
		}
		
		$output = array
		(
			"post_id" => $this->post_id,
			"parent_topic" => $this->parent_topic,
			"forum_id" => $this->forum_id,
			"forum_title" => $forum_obj->title,
			"order_in_topic" => $this->order_in_topic,
			"time_formatted" => smart_time_format($this->creation_time),
			"name_formatted" => anti_xss($this->name),
			"text_formatted" => $text_formatted,
			
			"file_url"  => $this->file_url,
			"thumb_url" => $this->thumb_url,
			
			"thumb_w" => $this->thumb_w,
			"thumb_h" => $this->thumb_h,
		);
		
		if ($this->parent_topic == 0)
		{
			$output["title_formatted"] = ($this->title != "") ? str_replace(" ", "&nbsp;", anti_xss($this->title)) : "Тема без заголовка";
		}
		
		/*if ($this->parent_topic == 0)
		{
			$output["voting_html"] = $VotingController->html($this->post_id);
		}
		
		else
		{
			$output["like_html"] = $LikeController->html($this->post_id);
		}*/
		
		return $output;
	}

	public $post_id;
  public $forum_id;
  public $parent_topic;
  public $creation_time;
  public $ip;
  public $ord;
	public $order_in_topic;
  public $text;
  public $title;
  public $name;
	public $file_url;
	public $thumb_url;
	public $thumb_w;
	public $thumb_h;
}
