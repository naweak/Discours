<?php
require "../config.php";
require_bundle();

if (!is_mod())
{
  die("Restricted");
}

/* DO NOT DELETE FROM DB, JUST FLAG AS DELETED!!! */

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
    
    $mysql_affected_rows = mysql_affected_rows();
    $message = "Post deleted rows: $mysql_affected_rows";
    
    $ban_id = 0;
    
    if ($mysql_affected_rows > 0)
    {
      if ($ban_user)
      {
        $expires = strtotime("+1 month");
        mysql_query("INSERT INTO bans (ban_id, ip, expires) VALUES ('', '$ip', '$expires')");
        
        $ban_id = mysql_insert_id();
        
        $message .= "USER BANNED ($ip)!\n";
      }
      
      mysql_query("INSERT INTO modlog (action_id, mod_id, timestamp, post_id, text_sample, ip, reason, ban_id)
      VALUES ('', '$mod_id', '$timestamp', '$post_id', '$text_sample', '$ip', '$reason', '$ban_id')");
    }
    
    // Delete all by this user
    if (isset($_POST["delete_all_by_user"])) // define a timeframe!
    {
      if ($ip != "")
      {
        $sql = mysql_query("SELECT * FROM posts WHERE ip = '$ip' AND ($timestamp - creation_time) < 24*60*60");
        while ($row = mysql_fetch_assoc($sql))
        {
          $message .= "Post: {$row['post_id']}<br>";
          
          $text_sample = mysql_real_escape_string($row["text"]);
          $ip = mysql_real_escape_string($row["ip"]);
          
          mysql_query("INSERT INTO modlog (action_id, mod_id, timestamp, post_id, text_sample, ip, reason, ban_id)
          VALUES ('', '$mod_id', '$timestamp', '{$row['post_id']}', '$text_sample', '$ip', 'Удаление вайпа', '$ban_id')");
          
          mysql_query("DELETE FROM posts WHERE post_id = '{$row['post_id']}'");
          
          $mysql_affected_rows = mysql_affected_rows();
          $message = "WIPE deleted rows: $mysql_affected_rows";
        }
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
      <input type="checkbox" name="delete_all_by_user" id="checkbox2"> <label for="checkbox2">Delete all posts by this user (anti-WIPE only) (&lt; 24 hours from now)</label>
      <br> Причина бана: <input type="text" name="reason" placeholder="Reason" value="вайп">
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