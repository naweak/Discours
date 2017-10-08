<?php
// Reply template

function reply_template ()
{
global $row;
global $reply_row;
global $theme;
?>
<reply>
  <text>
    <?php echo markup($reply_row["text"]); ?>
  </text>
  
  <!--<footnote class="time">Опубликовано <?php echo how_long_ago(time()-$reply_row["creation_time"]); ?></footnote>-->
  
  <?php if ($theme != "light") { ?>
  <footnote class="time"><?php echo time_format($reply_row["creation_time"]); ?></footnote>
  <footnote class="reply_action" onclick="reply_to_topic(<?php echo $row["post_id"]; ?>);">[Ответить]</footnote>
  <?php } else { ?>
  <div style="font-size:0.75em; margin-top:0.5em; padding:0px;">
    <a href="javascript:;" onclick="reply_to_topic(<?php echo $row["post_id"]; ?>);">Ответить</a>
  </div>
  <?php } ?>

  <?php
  if(is_mod())
  {
    ?>
    <form method="post" action="/delete.php" id="<?php echo $reply_row["post_id"]; ?>_delete_form" target="_blank"><input type="hidden" name="n" value="<?php echo $reply_row["post_id"]; ?>"></form>
    <div class="delete" onclick="delete_post(<?php echo $reply_row["post_id"]; ?>);">[Удалить]</div>
    <?php
  }
  ?>

</reply>
<?php
}
?>