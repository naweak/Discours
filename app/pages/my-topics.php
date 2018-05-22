<?php
session_start();
ob_start();

$limit = 50;
?>
<h2>Мои темы</h2>
<content>
<post_with_replies>
<post>
  
<?php
$topics = Post::Find
(
[
  "parent_topic = 0 AND
  (
    session_id = :session_id:
    OR
    (
    :user_id: != 0
    AND
    user_id = :user_id:
    )
  )",
  "limit" => $limit,
  "order" => "post_id DESC",
  "bind" =>
  [
    "session_id" => session_id(),
    "user_id" => user_id()
  ]
]
);
  
echo "<!-- ".benchmark()." -->";

$shown = count($topics) == $limit ? $limit : count($topics);
echo "<div align='center' style='font-size:14px; margin-bottom:0.5em;'>Показаны $shown последних созданных вами тем</div>";
  
echo "<table style='width: 100%; table-layout: fixed;'>";
foreach ($topics as $topic)
{
  echo "<!-- X ".benchmark()." -->";
  $topic_array = $topic->to_array();
  $text_formatted = $topic_array["text_formatted"];
  echo "<!-- Y ".benchmark()." -->";
  
  echo "<tr>";
    //echo "<td style='width:0; padding-right:10px;'>";
    //  echo str_replace(" ", "&nbsp;", smart_time_format($topic->creation_time));
    //echo "</td>";
  
    echo "<td width='100%'>";
      echo "<div style='width: 100%; white-space: nowrap; text-overflow: ellipsis; overflow: hidden; '>";
      echo "<a href='/topic/".$topic->post_id."' target='_blank'>";
      echo mb_strimwidth(strip_tags($text_formatted), 0, 100, "");
      echo "</a>";
      echo "</div>";
    echo "</td>";
  echo "</tr>";
}
echo "</table>";

echo "<!-- ".benchmark()." -->";
?>

</post>
</post_with_replies>
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