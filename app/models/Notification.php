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
	public $recipient_session_id;
  public $recipient_user_id; // -1 means session-only notification
  public $time;
  public $text;
  public $is_read;
	public $post_id;
  public $topic_id;
  
  public function notify ($recipient_session_id, $recipient_user_id, $text = "", $post_id = 0, $topic_id = 0)
	{
    if ($recipient_session_id == "")
    {
      $recipient_session_id = "none";
    }
    
    if ($recipient_user_id == 0)
    {
      $recipient_user_id = -1; // -1 means session-only notification
    }
    
		$this->recipient_session_id = $recipient_session_id;
    $this->recipient_user_id    = $recipient_user_id;
    $this->time = time();
		$this->text = $text;
    $this->is_read = 0;
		$this->post_id = $post_id;
		$this->topic_id = $topic_id;
		$result = $this->save();
    return $result;
	}
}
