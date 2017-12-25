<?php
die("Temporary unavailable due to migration to Phalcon framework");
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
        $reason = strip_tags($reason);
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
?>