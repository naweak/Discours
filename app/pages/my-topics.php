<?php
session_start();
ob_start();

$limit = 10;
?>
<h2>Мои темы</h2>
<content>
  
<?php
$topics = Post::Find
(
[
  "parent_topic = 0 AND session_id = :session_id:",
  "limit" => $limit,
  "order" => "post_id DESC",
  "bind" =>
  [
    "session_id" => session_id()
  ]
]
);
  
$shown = count($topics) == $limit ? $limit : count($topics);
echo "<p>Показаны $shown последних тем за вашим авторством.</p><br>";
  
echo "<table width='100%'>";
foreach ($topics as $topic)
{
  echo "<tr>";
    echo "<td style='width:0; padding-right:10px;'>";
      echo str_replace(" ", "&nbsp;", smart_time_format($topic->creation_time));
    echo "</td>";
  
    echo "<td width='100%'>";
      echo "<a href='/topic/".$topic->post_id."' target='_blank'>";
      echo mb_strimwidth(strip_tags($topic->text), 0, 50, "...");
      echo "</a>";
    echo "</td>";
  echo "</tr>";
}
echo "</table>";
?>

</content>
<?php
$html = ob_get_contents();
ob_end_clean();
$twig_data = array
(
  "html" => $html,
  "final_title" => "Мои темы"
);
echo render($twig_data);
exit();