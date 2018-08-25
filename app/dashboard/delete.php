<?php
// Rewrite this script using Phalcon MVC

require_bundle();

$pdo = pdo();

if (!is_mod())
{
  die("Restricted");
}

$delete_all_by_user_hours = 72;

if (isset($_POST["submit"]))
{
  $error = "";
  
  $post_id = intval($_POST["post_id"]);
  $ban_user = isset($_POST["ban_user"]);
  $reason = $_POST["reason"];
        
  if ($post_id == 0)
  {
    $error = "Post_id cannot be empty!"; // if empty, all topics are deleted
  }
  
  if ($reason == "")
  {
    $error = "Reason cannot be empty!";
  }
	
	if (mb_strlen($reason) > 256)
	{
		$error = "Reason too long!";
	}
	
	function update_topic_ord ($topic_id)
	{
		$pdo = pdo(); // really bad code
		
		$last_reply_row = $pdo->query("SELECT * FROM posts WHERE parent_topic = '$topic_id' AND deleted_by = 0 ORDER BY ord DESC")->fetch();
		
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
    $message = "";
    
		$row = $pdo->query("SELECT * FROM posts WHERE post_id = '$post_id'")->fetch();
		
		$text_sample = $row["text"];
    $ip = $row["ip"];
		
		$post_object = Post::findFirst(["post_id = :post_id:", "bind" => ["post_id" => $post_id]]);
		if (!$post_object)
		{
			die("Post not found!");
		}
    
    $message .= "Post user_id: ".$post_object->user_id."<br>";
    
		$post_object->delete_files();
  
		//$query = $pdo->prepare("DELETE FROM posts WHERE post_id = '$post_id' OR parent_topic = '$post_id'");
    $query = $pdo->prepare("UPDATE posts SET deleted_by = '$mod_id' WHERE post_id = '$post_id' OR parent_topic = '$post_id'");
		$query->execute();
		$affected_rows = $query->rowCount();
    $message .= "Post deleted rows: $affected_rows<br>";
		
		if ($row['parent_topic'] != 0) // deleted a reply to a topic
		{
			update_topic_ord($row['parent_topic']);
		}
		
		$pdo->query("DELETE FROM notifications WHERE topic_id = '$post_id' OR post_id = '$post_id'");
    
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
				$query = $pdo->prepare("INSERT INTO bans (ip, expires) VALUES (:ip, :expires)");
				$query->execute(["ip" => $ip, "expires" => $expires]);
        
				$ban_id = $pdo->lastInsertId();
        
        $message .= "USER BANNED until ".date(DATE_ATOM, $expires)."!\n";
        write_log("$ip BANNED until ".date(DATE_ATOM, $expires));
				
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
			$modlog->unlawful = "";
			$modlog->save();
			
			cache_delete("forum_".$post_object->forum_id); // delete page cache
      cache_delete("forum_1"); // delete main page cache
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
					
					$post_object = Post::findFirst(["post_id = :post_id:", "bind" => ["post_id" => $row['post_id']]]);
					if (!$post_object)
					{
						die("Post not found!");
					}
          
					$post_object->delete_files();
					
          $query = $pdo->prepare("UPDATE posts SET deleted_by = '$mod_id' WHERE post_id = :post_id");
					$query->execute(["post_id" => $row['post_id']]);

          $pdo->query("DELETE FROM notifications WHERE topic_id = '{$row['post_id']}' OR post_id = '{$row['post_id']}'");
					
					if ($row['parent_topic'] != 0) // reply to topic
					{
						update_topic_ord($row['parent_topic']);
					}
          
          else // topic
          {
            // delete all child posts
            $child_posts = Post::find(["parent_topic = :post_id:", "bind" => ["post_id" => $row['post_id']]]);
            foreach ($child_posts as $child_post)
            {
              echo "CHILD POST <u>NOT</u> DELETED (".$child_post->post_id.")!<br>";
            }
          }
          
          $modlog = new Modlog();
					$modlog->mod_id = $mod_id;
					$modlog->timestamp = $timestamp;
					$modlog->post_id = $row['post_id'];
					$modlog->text_sample = $text_sample;
					$modlog->ip = $ip;
					$modlog->reason = "Удаление вайпа";
					$modlog->ban_id = $ban_id;
					$modlog->unlawful = "";
					$modlog->save();
					
					cache_delete("forum_".$row['forum_id']); // delete page cache
          cache_delete("forum_1"); // delete main page cache
          
          $wipe_deleted_rows++;
        }
        
        $message .= "WIPE deleted rows: $wipe_deleted_rows";
      }
    }
  }
}

ob_start();
?>

<script>
document.addEventListener('DOMContentLoaded', function()
{
  $(document).on("change", "#checkbox2", function()
  {
    if(this.checked)
    {
      $("#checkbox1").prop("checked", true);
      $("#reason").val("вайп");
    }
  });
});
</script>

<h2>Удалить пост</h2>

<content>
  <post_with_replies>
  <post>
  
  <div>
    <?php if (isset($message)) {echo str_replace("\n", "<br>", $message);} ?>
  </div>
  
  <div style="color:red;">
    <?php if (isset($error)) {echo str_replace("\n", "<br>", $error);} ?>
  </div>
  
  <a href="/backup" target="_blank">Make a backup!</a>

  <form action="" method="post">
      <input type="checkbox" name="ban_user" id="checkbox1"> <label for="checkbox1">Ban user?</label>
      <br>
		<input type="checkbox" name="delete_all_by_user" id="checkbox2"> <label for="checkbox2">Delete all recent posts by this user (anti-WIPE only) <b>+Permaban</b></label>
      <br>

      <table>
        <tr>
          <td style="padding-right:5px;">
            Причина бана:
          </td>
          
          <td>
            <select name="reason" id="reason">
              <option value="" selected="selected">- укажите причину -</option>
              <option value="вайп">вайп</option>
              <option value="спам">спам</option>
              <option value="порно">порно</option>
              <option value="шок-контент">шок-контент</option>
              <option value="флуд">флуд</option>
            </select>
          </td>
        </tr>
        
        <tr>
          <td>
            Номер поста:
          </td>
          
          <td>
            <input type="text" name="post_id" value="<?php if(isset($_POST["n"])) {echo intval($_POST["n"]);} ?>" style="width:100%;">
          </td>
        </tr>
        
        <tr>
          <td colspan="2">
            <div align="center">
              <input type="submit" name="submit" value="Отправить">
            </div>
          </td>
        </tr>
      </table>
    
    </form>
    
    </post>
    </post_with_replies>
</content>
<?php
$html = ob_get_contents();
ob_end_clean();

$twig_data = array
(
  "html" => $html,
	"final_title" => "Удалить пост"
);

echo render($twig_data);
?>