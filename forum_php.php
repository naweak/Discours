<?php
session_start();
require "config.php";
require "connect.php";
require "library.php";

require "php_templates/op.php";
require "php_templates/reply.php";

$theme = "light";
?>

<!DOCTYPE html>
<html>
  <head>
    <meta charset="UTF-8">
    <title>Дискурс — анонимный форум</title>
    
    <link href="https://fonts.googleapis.com/css?family=Arimo" rel="stylesheet">
    <style type="text/css">
    <?php echo @file_get_contents("base.css"); ?>
    </style>
    <style type="text/css">
    <?php if ($theme == "light") {echo @file_get_contents("light.css");} ?>
    </style>

    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>
    
    <script type="text/javascript">
    function on_resize ()
    {
      var form_width = ($(document).width()/4)-75;
      var form_height = form_width*(1/1.8);
      $("#text").css("width", form_width+"px");
      $("#text").css("height", form_height+"px");
    }
      
    $(window).resize(function()
    {
      on_resize();
    });
      
    $(document).ready(function()
    {
      on_resize();
      
      $("img.embedded").css("cursor", "pointer");
      $("img.embedded").click(function()
      {
        var win = window.open(this.src, "_blank");
      });
      
      $("textarea.reply").focusin(function()
      {
        $(this).next().css("display", "block");
      });
      
      $("textarea.reply").focusout(function()
      {
        if ($(this).val() == "")
        {
          $(this).next().css("display", "none");
        }
      });
      
      <?php
      if (isset($_GET["topic"]))
      {
         echo "reply_to_topic(".intval($_GET["topic"]).");";
      }
      ?>
    });
    </script>

    <style type="text/css">
      body
      {
        text-align: center;
      }
      
      .header
      {
        display: block;
        font-size: 2em;
        margin-top: 0.67em;
        margin-bottom: 0.67em;
        margin-left: 0;
        margin-right: 0;
        font-weight: bold;
        color: inherit;
      }
      
      content
      {
        width: 50%;
        display: block;
        margin: 0 auto;
        text-align: left;
      }
      
      omitted_replies
      {
        display: none;
      }
    </style>
      
    <script type="text/javascript">
    <?php echo @file_get_contents("php_templates/functions.js"); ?>
    </script>
  </head>

  <body>
      <a class="header" href="/">Добро пожаловать на Дискурс</a>
    
      <content class="description">
        <b>Дискурс</b> — это самый свободный форум русскоязычного интернета.
        Если вам интересно, вы можете немного почитать <a href="/principles" target="_blank">о нашем проекте</a>.<br>
        <br>
        Для связи с администрацией используйте
        <a href="https://t.me/discoursanonymous" target="_blank">конференцию</a> в Telegram
        или специальную&nbsp;<a href="/topic/68" target="_blank">тему</a>. Также админу можно писать в
        <a href="https://t.me/zefirov" target="_blank">ЛС</a>.
        <br>
        <br>
        На данный момент у нас есть: <a href="https://discou.rs/topic/510" target="_blank">постинг&nbsp;картинок</a>,
        <a href="/modlog" target="_blank">модлог</a>,
        <a href="/good" target="_blank">каталог качественных тем</a>,
        <a href="https://wiki.1chan.ca/Discou.rs" target="_blank">статья&nbsp;на&nbsp;Колчевики</a>,
        <a href="https://t.me/discours_posts" target="_blank">канал</a> в Telegram и
        <a href="https://github.com/DiscoursProject/Discours" target="_blank">GitHub</a>.
      </content>
    
      <?php
      if (isset($_GET["topic"]))
      {
        ?>
        <content style="text-align:center;"><a href="/" style="color:#16A085;font-weight:bold;">Вы просматриваете тему #<?php echo intval($_GET["topic"]) ?>. Нажмите здесь для перехода наверх.</a></content>
        <?php
      }
      ?>
    
      <content>
      <?php require "post.php"; ?>
      </content>
    
      <?php if (false) { ?>
      <content>
        <post_with_replies>
          post form
        </post_with_replies>
      </content>
      <?php } ?>
      
      <div style="width:50%; text-align:left;">
        <form class="post" method="post" action="">
          <div id="form_additional_info" style="margin-bottom:0.5em;"></div>
          
          <?php
          $parent_topic = "";
          if (isset($_GET["topic"]))
          {
            $parent_topic = intval($_GET["topic"]);
          }
          ?>
          
          <input type="hidden" name="parent_topic" id="parent_topic" value="<?php echo $parent_topic; ?>">
          <textarea name="text" id="text" placeholder="Напишите здесь что-нибудь..."><?php if(isset($error) and $error != null) {echo strip_tags($text);} ?></textarea>
          <br>
          <div align="right"><input type="submit" class="submit" name="submit" value="Отправить"></div>
        </form>
      </div>
    
      <?php
      if (isset($_GET["good"]))
      {
      ?>
    
      <content>
      <post_with_replies style="padding:10px; text-align:center;">
        <b style="color:green;">Вы просматриваете исключительно качественные темы</b>
      </post_with_replies>
      </content>
    
      <?php
      }
      ?>

      <content>
        <?php
        $t = ""; // optional part of SQL query
        
        if (isset($_GET["topic"]))
        {
          $t = "AND post_id = ".intval($_GET["topic"]);
        }
        
        if (isset($_GET["good"]))
        {
          $good_posts = array(68, 659, 579, 748, 761, 746, 639, 959, 966, 1115, 1139);
          
          $t = "AND post_id IN (".join(", ", $good_posts).")";
        }
        
        $sql = mysql_query("SELECT * FROM posts WHERE parent_topic = 0 $t ORDER BY ord DESC");
        
        while ($row = mysql_fetch_assoc($sql))
        {
          echo "<post_with_replies>";
          
          op_template();
          
          $replies_query = mysql_query("SELECT * FROM posts WHERE parent_topic = '".$row["post_id"]."'");
          
          $total_replies = mysql_num_rows($replies_query);
          
          $replies_to_show = 3;
          
          if (isset($_GET["topic"]))
          {
            $replies_to_show = 9999;
          }
          
          if ($total_replies > $replies_to_show)
          {
            $show_omitted_text = "Показать все $total_replies ответов";
            //echo "<post style='text-align:center; cursor:pointer;' onclick='show_omitted({$row['post_id']});'>Показать все ответы ($total_replies)</post>";
            echo "<expand onclick='show_omitted({$row['post_id']});'>$show_omitted_text</expand>";
          }
            
          if ($total_replies > $replies_to_show)
          echo "<omitted_replies id='omitted_{$row["post_id"]}'>";
          
          $n = 0;
          while ($reply_row = mysql_fetch_assoc($replies_query))
          {
            if ($theme == "light")
            {
              echo "<div class='hr'></div>";
            }
            
            reply_template();
            
            if ($total_replies > $replies_to_show and $n == $total_replies - $replies_to_show - 1)
            echo "</omitted_replies>";
            
            $n++;
          }
          
          if ($theme == "light")
          {
          ?>
          <div class="hr"></div>
          <!--<input type="text" class="reply" placeholder="Написать ответ...">-->
        
          <form method="post" action="">
          <input type="hidden" name="parent_topic" id="parent_topic" value="<?php echo $row["post_id"]; ?>">
            
          <textarea_container>
            <textarea class="reply" name="text" placeholder="Написать ответ..."></textarea>
            <input type="submit" name="submit" value="Отправить">
          </textarea_container>
          </form>
        
          <?php
          }
          
          echo "</post_with_replies>";
        }
        ?>
      </content>
    
    <footer>
      Данные предоставлены на <?php echo date("Y-m-d H:i:s"); ?>
    </footer>
  </body>
</html>