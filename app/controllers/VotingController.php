<?php

use Phalcon\Mvc\Controller;
use Phalcon\Http\Request;

class VotingController extends Controller
{

	public function voteAction()
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
		
		$last_votes = Vote::find
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
		
		if (count($last_votes) > 0)
		{
			if ($request->getPost("ajax"))
			die (json_encode(array("error" => "error")));
			else
			return $this->response->redirect($_SERVER['HTTP_REFERER']);
		}
		
		$vote = new Vote();
		$vote->seed = $request->getPost("seed");
		$vote->ip = $GLOBALS["client_ip"];
		$vote->time = time();
		
		if ($request->getPost("up"))
		{
			$vote->vote = 1;
			$telegram_message = "UPVOTE for ".$vote->seed;
		}
		
		else
		{
			$vote->vote = -1;
			$telegram_message = "DOWNVOTE for ".$vote->seed;
		}
		
		$result = $vote->save();
		
		if ($result)
		{
			//send_message_to_telegram_channel("@discours_votes", $telegram_message, TELEGRAM_TOKEN);
			
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
		$votes = Vote::find
		(
    [
			"seed = :seed:",
			
			"bind" => ["seed" => $seed]
    ]
		);
		
		$result = 0;
	
		foreach ($votes as $vote)
		{
			$result += $vote->vote;
		}
		
		return $result;
	}
	
	public function html ($seed)
	{
		$html = "<form method='post' class='voting_form' style='display:inline;' action='".PHALCON_URL."/voting/vote'>";
		$html .= "<input type='hidden' name='seed' value='$seed'>";
		$html .= "<input type='submit' name='down' value='-' onclick=\"window.submit='down';\">";
		$html .= " ";
		
		$result = $this->result($seed);
		
		if ($result == 0)
		{
			$html .= "<span>";
		}
		
		elseif ($result > 0)
		{
			$html .= "<span style='color:green;'>";
		}
		
		else
		{
			$html .= "<span style='color:red;'>";
		}
		
		$html .= $result;
		$html .= "</span>";
		
		$html .= " ";
		$html .= "<input type='submit' name='up' value='+' onclick=\"window.submit='up';\">";
		$html .= "</form>";
		
		return $html;
	}

}
