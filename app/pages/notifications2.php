<?php
ob_start();

if (!is_mod())
{
  echo "Please log in";
  return false;
}

function echo_notification ($notification)
{
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
}
?>
<content class="notifications">

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
  padding: 0px 0px;
  margin: 4px 0px;
}
  
notification:hover
{
}

.is_unread
{
  background: yellow;
  padding: 2px 2px;
}
</style>

<h2 style="text-align:center;">Новые уведомления</h2>
  
<?php
$notifications = Notification::find
(
[
  "order" => "notification_id DESC",
  "limit" => 500 # 50
]
);

// select unique parent_topic from new notifications
$unique_parent_topics = [];
$notifications_by_parent_topic = [];
foreach ($notifications as $notification)
{
  if (!$notification->is_read)
  {
    $notification_parent_topic = $notification->parent_topic;
    if (!in_array($notification_parent_topic, $unique_parent_topics))
    {
      if ($notification_parent_topic == 0)
      {
        array_unshift($unique_parent_topics, $notification_parent_topic);
      }
      else
      {
        array_push($unique_parent_topics, $notification_parent_topic);
      }
    }

    $notifications_by_parent_topic[$notification_parent_topic][] = $notification;
  }
}

// group notifications by parent_topic
foreach ($unique_parent_topics as $parent_topic)
{
  if (!$parent_topic) // new topics
  {
    echo "<div>New topics:</div>";
  }
  
  else // replies to a topic
  {
    echo "<div style='background:yellow;'>Replies to topic <a href='/topic/$parent_topic' target='_blank' style='color:blue;' onclick=\"this.style.color='violet';\">#$parent_topic</a></div>";
  }
  
  foreach (array_reverse($notifications_by_parent_topic[$parent_topic]) as $notification)
  {
    $notification_post_object = Post::findFirst(["post_id = :post_id:", "bind" => ["post_id" => $notification->post_id]]);
    $order_in_topic = $notification_post_object->order_in_topic;
    
    if (!$parent_topic) // new topic
    {
      echo "<div style='background:yellow;'>New topic <a href='/topic/".$notification->post_id."' target='_blank' style='color:blue;' onclick=\"this.style.color='violet';\">#".$notification->post_id."</a></div>";
    }
    
    else // reply to a topic
    {
      echo "<div>notification #$order_in_topic</div>";
    }
  }
  
  echo "<br>";
}
  
if (empty($unique_parent_topics))
{
  echo "<center>Нет новых уведомлений!</center>";
}
  
//echo benchmark();

?>

<h2 style="text-align:center;">Прочитанные уведомления</h2>
  
<?php
  
foreach ($notifications as $notification)
{
  if ($notification->is_read)
  {
    echo_notification($notification);
    echo "<hr style='margin: 0px 0;'>";
  }
}

$pdo = pdo();
//$query = $pdo->query("UPDATE notifications SET is_read = 1");

echo benchmark();
?>
  
</content>
<?php
$html = ob_get_contents();
ob_end_clean();

$twig_data = array
(
  "final_title" => "Уведомления",
  "html" => $html
);

$twig_template = "test";
$twig_template = "default";
echo render($twig_data, ROOT_DIR."/app/templates/$twig_template", $twig_template);
?>