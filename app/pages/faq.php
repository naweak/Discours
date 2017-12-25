<?php
ob_start();
?>
<content>

  <h2>Как прикреплять изображения к постам?</h2>
  Об этом рассказано в <a href="/topic/4050">специальной теме</a>.<br>
  <br>
  Коротко: скопировать изображение в буфер обмена и отправить боту <a href="https://t.me/UploadToImgurBot" target="_blank">@UploadToImgurBot</a> в Telegram.
  Далее вставить полученную ссылку в начале поста отдельной строкой.<br>
  <br>
  Отвечая в тему, вместо ссылки можно вставить код стикер-пака, окруженный двоеточиями, например :yoba: или :pepe:
  
  <h2>Как можно связаться с администрацией Дискурса?</h2>
  
  См. раздел <a href="/contact">«контакты»</a>.
  
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