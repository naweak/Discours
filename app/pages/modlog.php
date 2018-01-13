<?php
//die("Temporary unavailable due to migration to Phalcon framework");
ob_start();

try
{
	$pdo = new PDO("mysql:host=".MYSQL_HOST.";dbname=".MYSQL_DATABASE, MYSQL_USERNAME, MYSQL_PASSWORD);
	$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
}
catch(PDOException $e)
{
	die ("Connection failed: ".$e->getMessage());
}
?>      
<content>
    <?php
    $sql = $pdo->query("SELECT COUNT(*) FROM bans");
    $sql->execute();
    $result = $sql->fetch();
  
    //var_dump($result);
    echo "Total bans: {$result[0]}<br>";
    ?>
  
    <table width=100% border style="margin-top:1em;">
        <tr>
            <td>Номер&nbsp;действия</td>
            <td>Модератор</td>
            <td>Номер&nbsp;поста</td>
            <td width="100%">Текст поста</td>
            <td>Причина</td>
        </tr>
        <?php
      //$sql = _query("SELECT * FROM modlog");
      $sql = $pdo->query("SELECT * FROM modlog ORDER BY action_id DESC LIMIT 20");
      $sql->execute();
      $result = $sql->fetchAll();
      
      //while ($row = _fetch_assoc($sql))      
      foreach ($result as $row)
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