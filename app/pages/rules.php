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
  <h2>Правила</h2>
  
  <p>
  На Дискурсе нет модераторского произвола, и все действия модераторов записываются в
  <a href="/modlog" target="_blank">модлог</a> для того, чтобы можно было пресекать случаи злоупотребления.
  Тем не менее, отсутствие мочерации не означает, что можно безнаказанно постить что угодно.
  </p>
  
  <p>
  Есть три основных категории контента, которые здесь, <i>мягко говоря</i>, не приветствуются:
  </p>

  <p>
  <ol style="margin:none;font-weight:bold;list-style-position:inside;">
    <li>Порно, гуро и прочий explicit content</li>
    <li>Спам</li>
    <li>Вайпы</li>
    <li>Флуд</li>
  </ol>
  </p>
  
  <p>
  Почему? В двух словах: чтобы у Дискурса была перспектива.
  Основная&nbsp;статья:&nbsp;<a href="/why-no-porn">Почему&nbsp;запрещено&nbsp;порно?</a>
  </p>

  <p>
  Аватаркофажество и неймфажество разрешено, но не стоит испытывать наше терпение. Посольства разрешены, но без ссылок.
  </p>
  
</content>
<?php
$html = ob_get_contents();
ob_end_clean();

$twig_data = array
(
  "html" => $html,
  "title" => "Правила"
);

echo render($twig_data);
?>