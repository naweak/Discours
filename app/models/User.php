<?php

use Phalcon\Mvc\Model;

class User extends Model
{
	public function initialize()
  {
  	$this->setSource("users");
  }

	public $user_id;
	public $username;
	public $password_hash;
  public $registration_time;
}
