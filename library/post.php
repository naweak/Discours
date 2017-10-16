<?php
// This script is executed from forum.php

$ip = mysql_real_escape_string($GLOBALS["client_ip"]);
$time = time();

$is_banned = false;

$sql = mysql_query("SELECT * FROM bans WHERE ip = '$ip'"); // add 'expires'!
if (mysql_num_rows($sql))
{
  $is_banned = true;
  
  $ban_row = mysql_fetch_assoc($sql);
  $ban_id = $ban_row["ban_id"];
}

//send_message_to_telegram_channel("@".TELEGRAM_CHANNEL, "is_banned: $is_banned", TELEGRAM_TOKEN);

if (isset($_POST["submit"]))
{
    $max_chars = 15000;
  
    $text = $_POST["text"];
    $parent_topic = intval($_POST["parent_topic"]);

    $error = null;
  
    // filter cross-site form submissions?
  
    if ($is_banned)
    {
      $error = "Ваш IP находится в бан-листе";
    }

    if ($parent_topic) // reply to topic
    {
        //$error = "Reply to topic";
        $sql = mysql_query("SELECT * FROM posts WHERE post_id = '$parent_topic' AND parent_topic = 0");

        if (!mysql_num_rows($sql))
        {
            $error = "Parent topic not found";
        }
      
        $sql = mysql_query("SELECT * FROM posts WHERE ip = '$ip' AND ($time-creation_time) < 30");
      
        if (mysql_num_rows($sql))
        {
          $error = "Вы отвечаете в темы слишком часто!";
        }
    }
  
    else // new topic
    {
      $sql = mysql_query("SELECT * FROM posts WHERE parent_topic = 0 AND ip = '$ip' AND ($time-creation_time) < 30*60");
      
      if (mysql_num_rows($sql))
      {
        $error = "Вы создаете темы слишком часто!";
      }
    }
  
    /* Somehow accepts one-letter strings like "a" */
    if (mb_strlen($text) < 3)
    {
        $error = "Текст слишком короткий!";
    }

    if (mb_strlen($text) > $max_chars)
    {
        $error = "Текст слишком длинный (>$max_chars)!";
    }
  
    //$error = "Wipe!";
    //$error = $text;

    if (!$error)
    {
        //echo "inserting...";
        $text = mysql_real_escape_string($text);
        $creation_time = time();
        $ord = round(microtime(true) * 1000);
        //$ip = $_SERVER["HTTP_CF_CONNECTING_IP"];

        mysql_query("INSERT INTO posts (post_id, parent_topic, creation_time, ip, ord, text) VALUES ('', '$parent_topic', '$creation_time', '$ip', '$ord', '$text')");

        if ($parent_topic)
        {
            mysql_query("UPDATE posts SET ord = '$ord' WHERE post_id = '$parent_topic'");
        }

        echo "<script>document.location = document.location;</script>";
      
        if ($parent_topic == 0) // new topic
        {
          $telegram_message = "НОВАЯ ТЕМА: https://discou.rs/topic/".mysql_insert_id().":";
        }
      
        else // reply to topic
        {
          $telegram_message = "Ответ в тему: https://discou.rs/topic/".$parent_topic.":";
        }
      
        /*
        $text_for_telegram = $text;
        $text_for_telegram = str_replace("\\r", "", $text_for_telegram);
        $text_for_telegram = str_replace("\\n", " ", $text_for_telegram);
        */
      
        $text_for_telegram = $_POST["text"];
        $text_for_telegram = str_replace("\r", "", $text_for_telegram);
        $text_for_telegram = str_replace("\n", " ", $text_for_telegram);
      
        //$text_for_telegram .= " *bold text* ";

        $r = 10;
        $telegram_message .= "\n".str_repeat("-", $r)."\n".mb_strimwidth($text_for_telegram, 0, 200, "...", "utf-8")."\n".str_repeat("-", $r)."\n";
      
        send_message_to_telegram_channel("@".TELEGRAM_CHANNEL, $telegram_message, TELEGRAM_TOKEN);
    }

    else
    {
        $posting_error = $error; // will be passed to twig
        $declined_text = $text;
      
        echo "<span style='color:red;'>$error</span>";

        if ($parent_topic)
        {
            // do the same with JS turned off
            echo "<script type='text/javascript'>$(function() {reply_to_topic(" . $parent_topic . ");});</script>";
        }
    }
}

if ($is_banned)
{
  echo "<div style='text-align:center;font-weight:bold;'>Ваш IP находится в бан-листе (#$ban_id). Для разбана обратитесь в телеграм-конференцию.</div>";
}
?>