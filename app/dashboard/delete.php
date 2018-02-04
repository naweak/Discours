<?php
require_bundle();

$pdo = pdo();

if (!is_mod())
{
  die("Restricted");
}

/* DO NOT DELETE FROM DB, JUST FLAG AS DELETED!!! */

$delete_all_by_user_hours = 72;

if (isset($_POST["submit"]))
{
  $error = "";
  
  $post_id = intval($_POST["post_id"]);
  $ban_user = isset($_POST["ban_user"]);
        
  if ($post_id == 0)
  {
    $error = "Post_id cannot be empty!"; // if empty, all topics are deleted
  }
  
  if ($_POST["reason"] == "")
  {
    $error = "Reason cannot be empty!";
  }
	
	if (mb_strlen($_POST["reason"]) > 256)
	{
		$error = "Reason too long!";
	}
	
	function update_topic_ord ($topic_id)
	{
		global $pdo;
		$last_reply_row = $pdo->query("SELECT * FROM posts WHERE parent_topic = '$topic_id' ORDER BY ord DESC")->fetch();
		
		if ($last_reply_row) // topic has replies
		{
			$pdo->query("UPDATE posts SET ord = '{$last_reply_row['ord']}' WHERE post_id = '$topic_id' AND parent_topic = 0")->execute();
		}
		
		else // topic is empty
		{
			$topic_row = $pdo->query("SELECT * FROM posts WHERE post_id = '$topic_id' AND parent_topic = 0")->fetch();
			$pdo->query("UPDATE posts SET ord = '".($topic_row['creation_time']*1000)."' WHERE post_id = '$topic_id' AND parent_topic = 0");
		}
	}
        
  if (empty($error))
  {
    $mod_id = $_SESSION["user_id"];
    $timestamp = time();
    
		$row = $pdo->query("SELECT * FROM posts WHERE post_id = '$post_id'")->fetch();
		
		$text_sample = $row["text"];
    $ip = $row["ip"];
    $reason = $_POST["reason"];
    
		$query = $pdo->prepare("DELETE FROM posts WHERE post_id = '$post_id' OR parent_topic = '$post_id'");
		$query->execute();
		$affected_rows = $query->rowCount();
		
    $message = "Post deleted rows: $affected_rows<br>";
		
		if ($row['parent_topic'] != 0) // deleted a reply to a topic
		{
			update_topic_ord($row['parent_topic']);
		}
		
		$pdo->query("DELETE FROM notifications WHERE post_id = '$post_id'");
    
    $ban_id = 0;
    
    if ($affected_rows > 0)
    {
      if ($ban_user)
      {
        $expires = strtotime("+10 days");
        if (isset($_POST["delete_all_by_user"]))
        {
          $expires = strtotime("+10 years");
        }

				$query = $pdo->prepare("INSERT INTO bans (ban_id, ip, expires) VALUES ('', :ip, :expires)");
				$query->execute(["ip" => $ip, "expires" => $expires]);
        
				$ban_id = $pdo->lastInsertId();
        
        $message .= "USER BANNED until ".date(DATE_ATOM, $expires)."!\n";
				
				if ($_SESSION["user_id"] == 1)
				{
					$message .= "IP: $ip\n";
				}
      }
			
			$modlog = new Modlog();
			$modlog->mod_id = $mod_id;
			$modlog->timestamp = $timestamp;
			$modlog->post_id = $post_id;
			$modlog->text_sample = $text_sample;
			$modlog->ip = $ip;
			$modlog->reason = $reason;
			$modlog->ban_id = $ban_id;
			$modlog->save();
    }
    
    // Delete all by this user
    if (isset($_POST["delete_all_by_user"]))
    {
      if ($ip != "")
      {
				$wipe_deleted_rows = 0;
				
				$query = $pdo->prepare("SELECT * FROM posts WHERE ip = :ip AND ($timestamp - creation_time) < $delete_all_by_user_hours*60*60");
				$query->execute(["ip" => $ip]);
				
				foreach ($query->fetchAll(PDO::FETCH_ASSOC) as $row)
        {
          $message .= "Post: {$row['post_id']}<br>";
          
					$text_sample = $row["text"];
    			$ip = $row["ip"];
					
					$modlog = new Modlog();
					$modlog->mod_id = $mod_id;
					$modlog->timestamp = $timestamp;
					$modlog->post_id = $row['post_id'];
					$modlog->text_sample = $text_sample;
					$modlog->ip = $ip;
					$modlog->reason = "Удаление вайпа";
					$modlog->ban_id = $ban_id;
					$modlog->save();
					
					$query = $pdo->prepare("DELETE FROM posts WHERE post_id = :post_id");
					$query->execute(["post_id" => $row['post_id']]);
					
					$pdo->query("DELETE FROM notifications WHERE post_id = '{$row['post_id']}'");
					
					if ($row['parent_topic'] != 0) // reply to thread
					{
						update_topic_ord($row['parent_topic']);
					}
          
          $wipe_deleted_rows++;
        }
        
        $message .= "WIPE deleted rows: $wipe_deleted_rows";
      }
    }
  }
}

ob_start();
?>
<h1>Удалить пост</h1>

<content>
  <div>
    <?php if (isset($message)) {echo str_replace("\n", "<br>", $message);} ?>
  </div>
  
  <div style="color:red;">
    <?php if (isset($error)) {echo str_replace("\n", "<br>", $error);} ?>
  </div>
  
  <a href="/backup" target="_blank">Backup!</a>

  <form action="" method="post">
      <input type="checkbox" name="ban_user" id="checkbox1"> <label for="checkbox1">Ban user?</label>
      <br>
		<input type="checkbox" name="delete_all_by_user" id="checkbox2"> <label for="checkbox2">Delete all posts by this user (anti-WIPE only) (&lt; <?php echo $delete_all_by_user_hours; ?> hours from now) <b>Пермабан</b></label>
      <br> Причина бана:
      <!--<input type="text" name="reason" placeholder="Reason" value="вайп">-->
      <select name="reason">
				<option value="" selected="selected">- укажите причину -</option>
        <option value="вайп">вайп</option>
        <option value="спам">спам</option>
        <option value="порно">порно</option>
      </select>
      <br> Номер поста:  <input type="text" name="post_id" value="<?php if(isset($_POST["n"])) {echo intval($_POST["n"]);} ?>"> <input type="submit" name="submit" value="Отправить">
  </form>
</content>
<?php
$html = ob_get_contents();
ob_end_clean();

$twig_data = array
(
  "html" => $html
);

echo render($twig_data);
?>