<?php
require "config.php";
require_bundle();
require_phalcon();

require LIB_DIR."/post.php";

$default_limit = 25;
$replies_to_show = 3;

$limit = $default_limit;

if (isset($_GET["show_all"]))
{
  $limit = 100; // will be replaced by pagination
}

$forum_id = 1;

if (isset($_GET["forum"]))
{
  $forum_id = intval($_GET["forum"]);
}

$query_annex = "";

if (isset($_GET["topic"]))
{
  $topic_id = intval($_GET["topic"]);
  $replies_to_show = 9999;
  $query_annex .= " AND post_id = $topic_id ";
}

else
{
  $query_annex .= " AND forum_id = '$forum_id'";
}

$topics_sql = mysql_query("SELECT * FROM posts WHERE parent_topic = 0 $query_annex ORDER BY ord DESC LIMIT $limit");

$topics = array();

//$VotingController = new VotingController();
//$LikeController   = new LikeController();

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
                 "text_formatted" => markup($reply_row["text"]),
                 
                 //"like_html" => $LikeController->html($reply_row["post_id"]),
               )
              );
  }
  
  array_push($topics,
             array
             (
               "post_id" => $topic_row["post_id"],
               "forum_id" => $topic_row["forum_id"],
               "title_formatted" => ($topic_row["title"] != "") ? str_replace(" ", "&nbsp;", anti_xss($topic_row["title"])) : "Тема без заголовка",
               "name_formatted" => anti_xss($topic_row["name"]),
               
               //"voting_html" => $VotingController->html($topic_row["post_id"]),
               
               "text_formatted" => markup($topic_row["text"]),
               
               "file_url" => $topic_row["file_url"],
               "thumb_url" => $topic_row["thumb_url"],
               
               "replies" => $replies
             )
            );
}

$twig_data = array
(
  "topics" => $topics,
  
  "replies_to_show" => $replies_to_show,
  "default_limit" => $default_limit,
  "limit" => $limit,
  "forum_id" => $forum_id,
  
  "is_mod" => is_mod()
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
  $declined_text = anti_xss($declined_text);
  $twig_data["declined_text"] = $declined_text;
}

$twig_data["phalcon_url"] = PHALCON_URL;

/* ----------------------------- */

$template_id = 0;

if (isset($_POST["template_id"]))
{
  $template_id = intval($_POST["template_id"]);
}

if ($template_id)
{
  $sql = mysql_query("SELECT * FROM templates WHERE template_id = '$template_id'");
  
  if (mysql_num_rows($sql))
  {
    $row = mysql_fetch_assoc($sql);
    echo render_from_string ($row["html"], $twig_data);
  }
}

else
{
  echo render($twig_data);
}
?>