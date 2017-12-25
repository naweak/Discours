<?php
ob_start();
?>
<content>

  <h2>Контакты</h2>
  
  <a href="https://discou.rs/topic/68">Тема для обратной связи</a><br>
  <a href="https://t.me/discoursanonymous" target="_blank">Конференция в Telegram</a><br>
  <a href="https://t.me/zefirov" target="_blank">ЛС в Telegram</a>
  
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