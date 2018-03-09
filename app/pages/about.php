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

<content style="margin-top:2em;">

  <p>
  Есть три основных категории контента, которые здесь, <i>мягко говоря</i>, не приветствуются:
  </p>

  <p>
  <ol style="margin:none;font-weight:bold;list-style-position:inside;">
    <li>Порно, гуро и прочий explicit content</li>
    <li>Спам</li>
    <li>Вайпы</li>
    <li  style="color:red;">Флуд</li>
  </ol>
  </p>
  
  <p>
  Почему? В двух словах: чтобы у Дискурса была перспектива.
  </p>

  Более подробно можно прочитать в <a href="/faq">FAQ</a>.
  
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