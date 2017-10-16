<?php
require "../config.php";
require_bundle();

ob_start();
?>      
<content>
    <table width=100% border style="margin-top:1em;">
        <tr>
            <td>Номер&nbsp;действия</td>
            <td>Модератор</td>
            <td>Номер&nbsp;поста</td>
            <td width="100%">Текст поста</td>
            <td>Причина</td>
        </tr>
        <?php
      $sql = mysql_query("SELECT * FROM modlog");
        
      while ($row = mysql_fetch_assoc($sql))
      {
        echo "<tr>";
        echo "<td>{$row["action_id"]}</td>";
        echo "<td>{$row["mod_id"]}</td>";
        echo "<td>{$row["post_id"]}</td>";
        
        $text_sample = $row["text_sample"];
        $text_sample = str_replace("\r", "", $text_sample);
        $text_sample = strip_tags($text_sample);
        
        $text_sample = mb_strimwidth($text_sample, 0, 25, "...", "utf-8");
        
        $reason = $row["reason"];
        $reason = str_replace(" ", "&nbsp;", $reason);
        
        echo "<td>$text_sample</td>";
        echo "<td>$reason</td>";
        
        echo "</tr>";
      }
      ?>
    </table>
</content>
<?php
$html = ob_get_contents();
ob_end_clean();

$twig_data = array
(
  "html" => $html
);

echo render($twig_data);

exit();
?>

<!------------>

<!DOCTYPE html>
<html>
  <head>
    <meta charset="UTF-8">
    <title>Discours — модлог</title>
    
    <link rel="stylesheet" type="text/css" href="/first.css"> <?php // replace by file_get_contents? ?>
  </head>
  
  <body>
    <div align="center">
      
      <h1>Модлог</h1>
      
      <div style="width:50%; text-align:left;">
      </div>
      
      <div style="width:50%; text-align:left;">
      <table width=100% border>
        <tr>
          <td>Номер действия</td>
          <td>Модератор</td>
          <td>Номер поста</td>
          <td width="100%">Текст поста</td>
          <td>Причина</td>
        </tr>
      <?php
      $sql = mysql_query("SELECT * FROM modlog");
        
      while ($row = mysql_fetch_assoc($sql))
      {
        echo "<tr>";
        echo "<td>{$row["action_id"]}</td>";
        echo "<td>{$row["mod_id"]}</td>";
        echo "<td>{$row["post_id"]}</td>";
        
        $text_sample = $row["text_sample"];
        $text_sample = str_replace("\r", "", $text_sample);
        $text_sample = strip_tags($text_sample);
        
        $text_sample = mb_strimwidth($text_sample, 0, 25, "...", "utf-8");
        
        $reason = $row["reason"];
        $reason = str_replace(" ", "&nbsp;", $reason);
        
        echo "<td>$text_sample</td>";
        echo "<td>$reason</td>";
        
        echo "</tr>";
      }
      ?>
      </table>
      </div>
    </div>
  </body>
</html>