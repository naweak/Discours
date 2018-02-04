<?php
header("HTTP/1.0 404 Not Found");
ob_start();
?>
<h2>Раздела /b/ нет</h2>
<content>
  <!--<div align="center">
    <iframe src="//coub.com/embed/12hxbn?muted=false&autostart=false&originalSize=false&startWithHD=false" allowfullscreen="true" frameborder="0" width="480" height="270"></iframe>
  </div>
  <br>-->
  <div align="center">
    Вместо него есть <a href="/">Главная</a>.
  </div>
</content>
<?php
$html = ob_get_contents();
ob_end_clean();
$twig_data = array
(
  "html" => $html,
  "final_title" => "Бред"
);
echo render($twig_data);
exit();