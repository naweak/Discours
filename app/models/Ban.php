<?php

use Phalcon\Mvc\Model;

class Ban extends Model
{
	public function initialize()
  {
  	$this->setSource("bans");
  }

	public $ban_id;
	public $ip;
	public $expires;
}
