<?php
ob_start();
?>
<content class="page">

  <h2>Контакты</h2>
  <!-- вставить текст -->  
</content>
<?php
$html = ob_get_contents();
ob_end_clean();

$twig_data = array
(
  "html" => $html,
  "final_title" => "Контакты"
);

echo render($twig_data);
?>