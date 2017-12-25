<?php

use Phalcon\Mvc\Model;

class Like extends Model
{
	public function initialize()
  {
  	$this->setSource("likes");
  }

	public $like_id;
	public $seed;
	public $ip;
	public $time;
}
