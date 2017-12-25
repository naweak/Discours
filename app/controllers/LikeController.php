<?php

use Phalcon\Mvc\Controller;
use Phalcon\Http\Request;

class LikeController extends Controller
{

	public function likeAction()
	{
		$request = new Request();
		if (!$request->isPost())
		{
			die("Must be POST data");
		}
		
		if(!filter_var($GLOBALS["client_ip"], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4))
		{    
			die("Please use IPv4");
		}
		
		$last_likes = Like::find
		(
    [
			"seed = :seed: and ip = :ip:",
			
			"bind" =>
			[
				"seed" => $request->getPost("seed"),
				"ip"   => $GLOBALS["client_ip"]
			]
    ]
		);

		if (count($last_likes) > 0)
		{
			if ($request->getPost("ajax"))
			die (json_encode(array("error" => "error")));
			else
			return $this->response->redirect($_SERVER['HTTP_REFERER']);
		}
		
		$like = new Like();
		$like->seed = $request->getPost("seed");
		$like->ip = $GLOBALS["client_ip"];
		$like->time = time();
		
		$result = $like->save();
		
		$telegram_message = "LIKE for ".$like->seed;
		
		if ($result)
		{
			//send_message_to_telegram_channel("@discours_likes", $telegram_message, TELEGRAM_TOKEN);
			
			if ($request->getPost("ajax"))
			echo json_encode(array("result" => $this->result($request->getPost("seed"))));
			else
			return $this->response->redirect($_SERVER['HTTP_REFERER']);
		}
		
		else
		{
			foreach ($vote->getMessages() as $message)
			{
				echo $message->getMessage(), "<br/>";
			}
		}
	}
	
	public function result ($seed)
	{
		$likes = Like::find
		(
    [
			"seed = :seed:",
			
			"bind" => ["seed" => $seed]
    ]
		);
		
		return count($likes);
	}
	
	public function html ($seed)
	{
		$html = "<form method='post' class='like_form' action='".PHALCON_URL."/like/like' style='display:inline;'>";
		$html .= "<input type='hidden' name='seed' value='$seed'>";
		
		$result = $this->result($seed);
		
		$result_to_show = "";
		$a_style = "";
		
		if ($result)
		{
			$result_to_show = $result."&nbsp;";
			$a_style = "font-weight:bold;";
		}
		
		$html .= "<a href='javascript:;' onclick=\"$(this).parents('form:first').submit();\" style='text-decoration:none;$a_style'><span>$result_to_show</span><i class='fa fa-heart-o' style='font-weight:inherit;'></i></a>";
		
		//$html .= " ";
		//$html .= "<input type='submit' name='like' value='Like'>";
		$html .= "</form>";
		
		return $html;
	}

}
