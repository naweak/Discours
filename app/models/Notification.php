<?php

use Phalcon\Mvc\Model;

class Notification extends Model
{
	public function initialize()
  {
  	$this->setSource("notifications");
  }
	
	public function beforeValidation()
  {
		foreach (["post_id"] as $element)
		{
			if ($this->$element == NULL)
			{
				$this->$element = "";
			}
		}
	}

	public $notification_id;
	public $recipient;
  public $time;
  public $is_read;
	public $post_id;
	public $text;
  
  public function notify ($recipient,  $text = "", $post_id = 0, $parent_topic = 0)
	{
		$this->recipient = $recipient;
    $this->time = time();
		$this->text = $text;
    $this->is_read = 0;
		$this->post_id = $post_id;
		$this->parent_topic = $parent_topic;
		$result = $this->save();
    return $result;
	}
}
