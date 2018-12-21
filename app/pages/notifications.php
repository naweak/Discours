<?php
require_session();
$notifications = Notification::find
(
  [
    "is_read = 0 AND
    (
      recipient_session_id = :recipient_session_id: OR 
      recipient_user_id = :recipient_user_id:
    )",
    "bind" =>
    [
      "recipient_session_id" => session_id(),
      "recipient_user_id" => user_id()
    ],
    "order" => "notification_id DESC",
    "limit" => 1000
  ]
);

if (isset($_POST["read_all"]))
{
  foreach ($notifications as $notification)
  {
    $notification->is_read = 1;
    $notification->save();
  }
  $notifications = [];
}

echo "<!-- SESSION_ID: ".session_id()." -->\n";

$notifications_by_topic_id = [];
foreach ($notifications as $notification)
{
  $notifications_by_topic_id[$notification->topic_id][] = $notification;
}
?>

<style type="text/css">
h2
{
  margin-top: 0.5em;
  margin-bottom: 0.5em;
}
  
.notifications
{
  display: flex;
  flex-direction: column;
}

.notifications .row
{
  display: flex;
  flex-wrap: nowrap;
  padding: 0px;
}
  
.notifications .row:hover
{
  /* color: #ea0000; */
}
  
.notifications .row .caption
{
  flex-grow: 1;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  padding: 0px;
}
  
.notifications .row .action
{
  text-align: center;
  white-space: nowrap;
  font-weight: bold;
  padding: 0px;
  margin-left: 10px;
}
</style>

<content>
<post_with_replies>
<post>
  <h2 align="center">Уведомления</h2>
  
    <?php
    if (is_admin())
    {
      $pdo = pdo();
      $beginning_of_this_day = strtotime("midnight", time());
      $sql = $pdo->query("SELECT COUNT(post_id) FROM posts WHERE creation_time >= $beginning_of_this_day");
      $row = $sql->fetch();
      $posts_today = $row[0];
      echo "<style>h2 {margin-bottom: 0em;}</style>";
      echo "<div align='center'>Постов за сегодня: $posts_today</div>";
    }
    ?>

    <div class="notifications">
      <?php
      foreach ($notifications_by_topic_id as $topic_id => $notifications_in_given_topic)
      {
        $topic_object = Post::findFirst
        (
          [
            "post_id = :post_id:",
            "bind" => ["post_id" => $topic_id]
          ]
        );

        if ($topic_object)
        {
          $plain_text = $topic_object->get_plain_text();
        }
        
        if ($plain_text == "")
        {
          $plain_text = "Тема без текста";
        }
        
        $is_new_topic = false;
        
        foreach ($notifications_in_given_topic as $notification)
        {
          if ($topic_id == $notification->post_id)
          {
            $is_new_topic = true;
          }
        }
        
        ?>
        <a href="/notification/view?id=<?php echo end($notifications_in_given_topic)->notification_id; ?>">
          <div class="row">
            <div class="caption">
              <?php
              if ($is_new_topic)
              {
                ?>
                <span style="font-weight:bold;">[Новая тема]</span>
                <?php
              }
              ?>
              <?php echo $plain_text; ?>
            </div>
            <div class="action">
              (<?php echo count($notifications_in_given_topic); ?> ответов)
            </div>
          </div>
        </a>
        <?php
      }
      
      if (!count($notifications))
      {
        ?><div align="center" style="padding:0;margin-bottom:1em;">Нет новых уведомлений</div><?php
      }
      
      else
      {
        ?>
        <form method="post" action="" style="text-align:center;margin-top:1em;">
          <input type="submit" name="read_all" class="button is-small" value="Отметить все как прочитанные">
        </form>
        <?php
      }
      ?>
    </div>
  
    <!--<div align="center" style="color:grey;">  
      Время обработки запроса: <?php echo benchmark(); ?> мс.
    </div>-->
  </post>
  </post_with_replies>
</content>
<?php
$html = ob_get_contents();
ob_end_clean();
$twig_data = array
(
  "html" => $html,
  "final_title" => "Уведомления"
);
$twig_template = "default";
if (isset(domain_array()["template"]))
{
  $twig_template = domain_array()["template"];
}
$twig_filesystem = TWIG_TEMPLATES_DIR."/$twig_template";
echo render($twig_data, $twig_filesystem, $twig_template);
?>