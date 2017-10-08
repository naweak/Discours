<?php
session_start();
require "config.php";
require "connect.php";
require "library.php";

if (!is_mod())
{
  die("Restricted");
}

if (isset($_POST["submit"]))
{
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
        
  if (!isset($error))
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
    $message = "MySQL affected rows: $mysql_affected_rows";
    
    if ($mysql_affected_rows > 0)
    {
      if ($ban_user)
      {
        $expires = strtotime("+100 years");
        mysql_query("INSERT INTO bans (ban_id, ip, expires) VALUES ('', '$ip', '$expires')");
        
        echo "USER BANNED!<br>";
      }
      
      mysql_query("INSERT INTO modlog (action_id, mod_id, timestamp, post_id, text_sample, ip, reason)
      VALUES ('', '$mod_id', '$timestamp', '$post_id', '$text_sample', '$ip', '$reason')");
    }
    
    // Delete all by this user
    if (isset($_POST["delete_all_by_user"])) // define a timeframe!
    {
      if ($ip != "")
      {
        $sql = mysql_query("SELECT * FROM posts WHERE ip = '$ip'");
        while ($row = mysql_fetch_assoc($sql))
        {
          echo "Post: {$row['post_id']}<br>";
          
          $text_sample = mysql_real_escape_string($row["text"]);
          $ip = mysql_real_escape_string($row["ip"]);
          
          mysql_query("INSERT INTO modlog (action_id, mod_id, timestamp, post_id, text_sample, ip, reason)
          VALUES ('', '$mod_id', '$timestamp', '{$row['post_id']}', '$text_sample', '$ip', 'Удаление вайпа')");
          
          mysql_query("DELETE FROM posts WHERE post_id = '{$row['post_id']}'");
        }
      }
    }
  }
}
?>

<!DOCTYPE html>
<html>
  <head>
    <meta charset="UTF-8">
    <title>Discours — удалить пост</title>
    
    <style type="text/css">
    </style>
    
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>
    
  <body>
    <div align="center">
      
      <h1>Удалить пост</h1>
      
      <a href="/backup.php" target="_blank">Backup!</a>
      
      <div style="width:50%; text-align:center;">
      <?php
      if (isset($message))
      {
        echo $message;
      }
      ?>
      </div>
      
      <div style="width:50%; text-align:center;">
        <form action="" method="post">
        <input type="checkbox" name="ban_user" id="checkbox1"> <label for="checkbox1">Ban user?</label>
        <br>
        <input type="checkbox" name="delete_all_by_user" id="checkbox2"> <label for="checkbox2">Delete all posts by this user (anti-WIPE only)</label>
        <br>
        <input type="text" name="reason" placeholder="Reason" value="вайп">
        <br>
        Номер поста: <input type="text" name="post_id" value="<?php if(isset($_POST["n"])) {echo intval($_POST["n"]);} ?>"> <input type="submit" name="submit" value="Отправить">
        </form>
      </div>
    </div>
  </body>
</html>