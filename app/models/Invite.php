<?php

use Phalcon\Mvc\Model;

class Invite extends Model
{
	public function initialize()
  {
  	$this->setSource("invites");
  }

	public $invite_id;
	public $invite_code;
}
