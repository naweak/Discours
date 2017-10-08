<?php
session_start();
require "config.php";
require "connect.php";
require "library.php";

require "post.php";

##### SANDBOX MODE!!!

//echo @file_get_contents("twig_templates/default.css");

$default_limit = 25;
$replies_to_show = 3;

$limit = $default_limit;

if (isset($_GET["show_all"]))
{
  $limit = 100; // will be replaced by pagination
}

$t = "";

if (isset($_GET["topic"]))
{
  $topic_id = intval($_GET["topic"]);
  $replies_to_show = 9999;
  $t = "AND post_id = ".$topic_id;
}
        
if (isset($_GET["good"]))
{
  $good_posts = array(68, 659, 579, 748, 761, 746, 639, 959, 966, 1115, 1139);
          
  $t = "AND post_id IN (".join(", ", $good_posts).")";
}

$topics_sql = mysql_query("SELECT * FROM posts WHERE parent_topic = 0 $t ORDER BY ord DESC LIMIT $limit");

$topics = array();

while ($topic_row = mysql_fetch_assoc($topics_sql))
{
  $replies = array();
  
  $replies_sql = mysql_query("SELECT * FROM posts WHERE parent_topic = '".$topic_row["post_id"]."'");
  
  while ($reply_row = mysql_fetch_assoc($replies_sql))
  {
    array_push($replies,
               array
               (
                 "post_id" => $reply_row["post_id"],
                 "text_formatted" => markup($reply_row["text"])
               )
              );
  }
  
  array_push($topics,
             array
             (
               "post_id" => $topic_row["post_id"],
               //"text" => $row["text"],
               "text_formatted" => markup($topic_row["text"]),
               
               "replies" => $replies
             )
            );
}

$twig_data = array
(
  "topics" => $topics,
  
  "replies_to_show" => $replies_to_show,
  "default_limit" => $default_limit,
  "limit" => $limit
);

if (isset($topic_id))
{
  $twig_data["topic_id"] = $topic_id;
}

if (isset($posting_error))
{
  $twig_data["posting_error"] = $posting_error;
}

if (isset($declined_text))
{
  //$declined_text = trim($declined_text, "\n");
  $declined_text = anti_xss($declined_text);
  /*$declined_text = str_replace("&", "&amp;", $declined_text);
  $declined_text = str_replace("<", "&lt;", $declined_text);
  $declined_text = str_replace(">", "&gt;", $declined_text);*/
  $declined_text = str_replace("\n", "<br>", $declined_text);
  
  $twig_data["declined_text"] = $declined_text;
}

/* ----------------------------- */
/* Filter 'include' tag! */

$twig_template = "default";

require_once "vendor/autoload.php";
$loader = new Twig_Loader_Filesystem("twig_templates/$twig_template/");
$twig = new Twig_Environment($loader);
echo $twig->render("$twig_template.html", $twig_data);
?>