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
  
echo "<table style='width: 100%; table-layout: fixed;'>";
foreach ($topics as $topic)
{ 
  echo "<tr>";
    //echo "<td style='width:0; padding-right:10px;'>";
    //  echo str_replace(" ", "&nbsp;", smart_time_format($topic->creation_time));
    //echo "</td>";
  
    echo "<td width='100%'>";
      echo "<div style='width: 100%; white-space: nowrap; text-overflow: ellipsis; overflow: hidden; '>";
      echo "<a href='/topic/".$topic->post_id."'>";
      echo $topic->get_plain_text(500);
      echo "</a>";
      echo "</div>";
    echo "</td>";
  echo "</tr>";
}
echo "</table>";
  
if ($shown > 0)
{
  $message = "Показано $shown последних созданных вами тем";
}
  
else
{
  $message = "Вы еще не создали ни одной темы";
}

echo "<div align='center' style='font-size:14px; margin:0.5em 0em;'>$message</div>";

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
$twig_template = "default";
if (isset(domain_array()["template"]))
{
  $twig_template = domain_array()["template"];
}
$twig_filesystem = TWIG_TEMPLATES_DIR."/$twig_template";
echo render($twig_data, $twig_filesystem, $twig_template);
exit();