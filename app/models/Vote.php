<?php

use Phalcon\Mvc\Model;

class Vote extends Model
{
	public function initialize()
  {
  	$this->setSource("votes");
  }

	public $vote_id;
	public $seed;
	public $ip;
	public $time;
	public $vote;
}
