<?php
require_bundle();

//////////////
try
{
	$pdo = new PDO("mysql:host=".MYSQL_HOST.";dbname=".MYSQL_DATABASE, MYSQL_USERNAME, MYSQL_PASSWORD);
	$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
}
catch(PDOException $e)
{
	die ("Connection failed: ".$e->getMessage());
}
//////////////

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
    $error = "Post_id cannot be empty!"; # if empty, all topics are deleted
  }
  
  if ($_POST["reason"] == "")
  {
    $error = "Reason cannot be empty!";
  }
	
	function update_topic_ord ($post_id)
	{
		global $pdo;
		
		$last_reply_row = $pdo->query("SELECT * FROM posts WHERE parent_topic = '$post_id' ORDER BY ord DESC")->fetch();
		echo $last_reply_row["ord"] . "<br>";

		$query_string = "UPDATE posts SET ord = '{$last_reply_row['ord']}' WHERE post_id = '$post_id'";
		echo $query_string . "<br>";

		echo $pdo->query("UPDATE posts SET ord = '".$last_reply_row['ord']."' WHERE post_id = '".$row['parent_topic']."' AND parent_topic = 0")->execute();
	}
        
  if (empty($error))
  {
    $mod_id = $_SESSION["user_id"];
    $timestamp = time();
    
    $sql = mysql_query("SELECT * FROM posts WHERE post_id = '$post_id'");
    $row = mysql_fetch_assoc($sql);
    $text_sample = mysql_real_escape_string($row["text"]);
    $ip = mysql_real_escape_string($row["ip"]);
    $reason = mysql_real_escape_string($_POST["reason"]);
    
    $query = "DELETE FROM posts WHERE post_id = '$post_id' OR parent_topic = '$post_id'";
    $sql = mysql_query($query);
		
		////////////////
		if ($row['parent_topic'] != 0) // reply to thread
		{
			update_topic_ord($row['parent_topic']);
		}
    ////////////////
    
    $mysql_affected_rows = mysql_affected_rows();
    $message = "Post deleted rows: $mysql_affected_rows<br>";
    
    $ban_id = 0;
    
    if ($mysql_affected_rows > 0)
    {
      if ($ban_user)
      {
        $expires = strtotime("+10 days");
        if (isset($_POST["delete_all_by_user"]))
        {
          $expires = strtotime("+10 years");
        }
        
        mysql_query("INSERT INTO bans (ban_id, ip, expires) VALUES ('', '$ip', '$expires')");
        
        $ban_id = mysql_insert_id();
        
        $message .= "USER BANNED ($ip) until ".date(DATE_ATOM, $expires)."!\n";
      }
      
      mysql_query("INSERT INTO modlog (action_id, mod_id, timestamp, post_id, text_sample, ip, reason, ban_id)
      VALUES ('', '$mod_id', '$timestamp', '$post_id', '$text_sample', '$ip', '$reason', '$ban_id')");
    }
    
    // Delete all by this user
    if (isset($_POST["delete_all_by_user"])) // define a timeframe!
    {
      if ($ip != "")
      {
        $sql = mysql_query("SELECT * FROM posts WHERE ip = '$ip' AND ($timestamp - creation_time) < $delete_all_by_user_hours*60*60");
        $wipe_deleted_rows = 0;
        while ($row = mysql_fetch_assoc($sql))
        {
          $message .= "Post: {$row['post_id']}<br>";
          
          $text_sample = mysql_real_escape_string($row["text"]);
          $ip = mysql_real_escape_string($row["ip"]);
          
          mysql_query("INSERT INTO modlog (action_id, mod_id, timestamp, post_id, text_sample, ip, reason, ban_id)
          VALUES ('', '$mod_id', '$timestamp', '{$row['post_id']}', '$text_sample', '$ip', 'Удаление вайпа', '$ban_id')");
          
          mysql_query("DELETE FROM posts WHERE post_id = '{$row['post_id']}'");
					
					////////////////
          /*if ($row['parent_topic'] != 0) // reply to thread
          {
            $last_reply_row = $pdo->query("SELECT * FROM posts WHERE parent_topic = '".$row['parent_topic']."' ORDER BY ord DESC")->fetch();
            echo $last_reply_row["ord"] . "<br>";
						
						echo "UPDATE posts SET ord = '".$last_reply_row['ord']."' WHERE post_id = '".$row['parent_topic']."'";
						echo "<br>";
						
            $pdo->query("UPDATE posts SET ord = '".$last_reply_row['ord']."' WHERE post_id = '".$row['parent_topic']."'")->execute();
          }*/
					
					if ($row['parent_topic'] != 0) // reply to thread
					{
						update_topic_ord($row['parent_topic']);
					}
          ////////////////
          
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
      <input type="checkbox" name="delete_all_by_user" id="checkbox2"> <label for="checkbox2">Delete all posts by this user (anti-WIPE only) (&lt; <?php echo $delete_all_by_user_hours; ?> hours from now)</label>
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