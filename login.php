<?php
session_start();
require "config.php";
require "connect.php";
require "library.php";

if (isset($_POST["submit"]))
{
  if ($_POST["password"] == MOD_PASSWORD)
  {
    $_SESSION["user_id"] = 1;
  }
}

if (isset($_POST["logout"]))
{
  session_destroy();
}

if (is_mod())
{
  $message = "You are mod!";
}
?>

<!DOCTYPE html>
<html>
  <head>
    <meta charset="UTF-8">
    <title>Discours — вход</title>
    
    <style type="text/css">
    </style>
    
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>
    
  <body>
    <div align="center">
      
      <h1>Вход</h1>
      
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
          <input type="password" name="password" value=""><br>
          <input type="submit" name="submit" value="Отправить">
          <input type="submit" name="logout" value="Выход">
        </form>
      </div>
    </div>
  </body>
</html>