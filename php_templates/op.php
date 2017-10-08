<?php
// OP post template (Topic body)

function op_template ()
{
global $row;
global $theme;
?>

<post>
  <?php if (true) { ?>
  <a class="description topic_title" href="/topic/<?php echo $row["post_id"]; ?>">Тема #<?php echo $row["post_id"]; ?></a>
  <?php } ?>
  
  <text>
    <?php echo markup($row["text"]); ?>
  </text>
  
  <!--<footnote class="time">Опубликовано <?php echo how_long_ago(time() - $row["creation_time"]); ?></footnote>-->
  
  <?php if ($theme != "light") { ?>
  <footnote class="time"><?php echo time_format($row["creation_time"]); ?></footnote>
  <?php } ?>
  
  <?php if ($theme != "light") { ?>
  <input type="button" class="submit" value="Ответить" style="" onclick="reply_to_topic(<?php echo $row["post_id"]; ?>);">
  <?php } else { ?>
  <div style="font-size:0.75em; margin-top:0.5em; padding:0px;">
    <a href="javascript:;" onclick="reply_to_topic(<?php echo $row["post_id"]; ?>);">Ответить</a>
  </div>
  <?php } ?>

  <?php
  if(is_mod())
  {
    ?>
    <form method="post" action="/delete.php" id="<?php echo $row["post_id"]; ?>_delete_form" target="_blank"><input type="hidden" name="n" value="<?php echo $row["post_id"]; ?>"></form>
    <div class="delete" onclick="delete_post(<?php echo $row["post_id"]; ?>);">[Удалить]</div>
    <?php
  }
  ?>
</post>

<?php
}
?>