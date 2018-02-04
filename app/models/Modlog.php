<?php

use Phalcon\Mvc\Model;

class Modlog extends Model
{
	public function initialize()
  {
  	$this->setSource("modlog");
  }

	public $action_id;
	public $mod_id;
	public $timestamp;
	public $post_id;
	public $text_sample;
	public $ip;
	public $reason;
	public $ban_id;
}
