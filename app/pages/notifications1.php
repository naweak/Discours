<?php
ob_start();
?>
<content style="margin-top:20px;">

<?php
if (!is_mod())
{
  echo "Please log in";
  return false;
}

$notifications = Notification::find
(
[
  "",

  "bind" =>
  [
  ],

  "order" => "notification_id DESC",
  "limit" => 100 # 50
]
);
  
?>
<script type="text/javascript">
function notification_reply (elem, order_in_topic)
{
  $(elem).next().find("textarea_container").css("display", "block");
  if (order_in_topic)
  {
    console.log("ord");
    $(elem).next().find("textarea").html("&gt;&gt;"+order_in_topic+"\n");
  }
  $(elem).next().find("textarea").focus();
}
</script>
  
<style type="text/css">
notification
{
  padding: 4px 0px;
}
  
notification:hover
{
}
 
.is_read
{
  /*background: #eee;*/
}

.is_unread
{
  background: yellow;
}
</style>
<?php

echo "<hr style='margin: 0px 0;'>";

foreach ($notifications as $notification)
{
  //$is_read = $notification->is_read;
  $text = $notification->text;

  $post = Post::findFirst
  (
  [
    "post_id = :post_id:",
    "bind" =>
    [
      "post_id" => $notification->post_id
    ]
  ]
  );
  
  if ($notification->is_read)
  {
    $notification_class = "is_read";
  }
  else
  {
    $notification_class = "is_unread";
  }
  
  echo "<notification class='$notification_class' style='display:block;'>";

  echo $notification->text;
  echo "<div>".anti_xss($post->text)."</div>";
  
  if ($post->parent_topic) // reply to topic
  {
    $reply_form_parent_topic = $post->parent_topic;
    $order_in_topic = $post->order_in_topic;
  }
  else // topic
  {
    $reply_form_parent_topic = $post->post_id;
    $order_in_topic = 0;
  }
  ?>
  
  <a href="javascript:;" onclick="notification_reply(this, <?php echo $order_in_topic; ?>);" class="notification_reply_link">Ответить</a>
  
  <form class="reply_form notification_form" method="post" action="/posting/post">
    <input type="hidden" name="forum_id" value="<?php echo $post->forum_id; ?>">
    <input type="hidden" name="parent_topic" id="parent_topic" value="<?php echo $reply_form_parent_topic; ?>">

    <input type="hidden" name="text" value="">

    <textarea_container style="padding: 0px; display: none;" >
      <textarea style="padding-top: 2px; font-size: 15px; line-height: 15px; height:20px; margin-bottom:0px; background:transparent;" class="reply" name="text" id="text_<?php echo $post->post_id; ?>" placeholder="Написать ответ..."></textarea>

      <div class="controls" style="margin-top: 7px;">
        <div style="float:left;">
          <input id="reply_userfile_<?php echo $post->post_id; ?>" name="userfile" type="file" class="inputfile">
          <label for="reply_userfile_<?php echo $post->post_id; ?>" class="attach_button"><i class="fa fa-picture-o" aria-hidden="true"></i> Прикрепить картинку</label>
        </div>

        <div align="right">
          <input id="reply_submit_<?php echo $post->post_id; ?>" type="submit" class="inputfile">
          <label for="reply_submit_<?php echo $post->post_id; ?>" class="inputfile_label submit_button button is-small
          is-outlined                                   
          ">Отправить</label>
        </div>
      </div>
    </textarea_container>
  </form>
  <?php
  
  echo "</notification>";

  echo "<hr style='margin: 0px 0;'>";

  //$notification->is_read = 1;
  //$notification->save();
}
  
/* ########## */
try
{
  $pdo = new PDO("mysql:host=".MYSQL_HOST.";dbname=".MYSQL_DATABASE, MYSQL_USERNAME, MYSQL_PASSWORD);
  $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
}
catch(PDOException $e)
{
  die ("Connection failed: ".$e->getMessage());
}
//$query = $pdo->query("UPDATE notifications SET is_read = 1");
/* ########## */

echo benchmark();
?>
  
</content>
<?php
$html = ob_get_contents();
ob_end_clean();

$twig_data = array
(
  "html" => $html
);

$twig_template = "test";
echo render($twig_data, ROOT_DIR."/app/templates/$twig_template", $twig_template);
?>