<?php
ob_start();

$pdo = pdo("utf8");
?>
<content>
		<h2 style="text-align:center;">Журнал действий модераторов</h2>
	
    <?php
    $sql = $pdo->query("SELECT COUNT(*) FROM bans");
    $sql->execute();
    $result = $sql->fetch();
  
    echo "Всего банов: {$result[0]}<br>";
    ?>
  
    <table width=100% border style="margin-top:1em;">
        <tr>
            <!--<td>Номер&nbsp;действия</td>-->
						<td>Дата</td>
            <!--<td>Модератор</td>-->
            <td>Номер&nbsp;поста</td>
            <td width="100%">Текст поста</td>
            <td>Причина</td>
        </tr>
        <?php
			$offset = 0;
			$logs_per_page = 20;
			
			if (isset ($_GET["p"]))
			{
				$offset = $logs_per_page * abs(intval($_GET["p"])-1);
			}
			
			$sql = $pdo->prepare("SELECT COUNT(*) FROM modlog");
			$sql->execute();
			$modlog_size = $sql->fetchColumn();
			
      $sql = $pdo->query("SELECT * FROM modlog ORDER BY action_id DESC LIMIT $logs_per_page OFFSET $offset");
      $sql->execute();
      $result = $sql->fetchAll();
 
      foreach ($result as $row)
      {
        echo "<tr>";
        //echo "<td>{$row["action_id"]}</td>";
				echo "<td>".date("d.m.y", $row["timestamp"])."</td>";
        //echo "<td>{$row["mod_id"]}</td>";
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
				
				if ($row["unlawful"])
				{
					?>
					<tr>
						<td colspan="100">
							<span style="color:red;">
								Бан неправомерен. <?php echo anti_xss($row["unlawful"]); ?>
							</span>
						</td>
					</tr>
					<?php
				}
      }
      ?>
    </table>
	
		<form action="" method="get" style="margin-top:15px;">
			Переход на страницу (max. <?php echo intval($modlog_size/$logs_per_page); ?>):
			<input type="number" name="p"> <input type="submit" value="Go">
		</form>
</content>
<?php
$html = ob_get_contents();
ob_end_clean();

$twig_data = array
(
  "html" => $html,
	"final_title" => "Модлог"
);

echo render($twig_data);
?>