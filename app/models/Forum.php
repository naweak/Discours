<?php

use Phalcon\Mvc\Model;

class Forum extends Model
{
	public function initialize()
  {
  	$this->setSource("forums");
  }

	public $forum_id;
	public $title;
  public $slug;
}
