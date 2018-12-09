<?php
$pdo = pdo();

/*echo "  <!-- START ".$topic->post_id." ".benchmark()." -->\n";
$sql = $pdo->prepare("SELECT * FROM posts WHERE parent_topic = 68");
$sql->execute();
echo "  <!-- END ".$topic->post_id." ".benchmark()." -->\n";
die();*/

ob_start();
?>
<style type="text/css">
p
{
  margin-bottom:1em;
}
</style>

<content class="page" style="margin-top:2em;">
  <h2 style="text-align:center;">Правила</h2>
  
  <div>
    Вы обязуетесь не размещать информацию, противоречащую действующему законодательству РФ, Украины, Нидерландов, США и страны Вашего пребывания.
  </div>
  
</content>
<?php
$html = ob_get_contents();
ob_end_clean();

$twig_data = array
(
  "html" => $html,
  "final_title" => "Правила"
);

echo render($twig_data);
?>