<?php
ob_start();
?>
<style type="text/css">
p
{
  margin-bottom:1em;
}
</style>

<content style="margin-top:2em;">
  
  <h2>Почему запрещено порно?</h2>

  <p>
  Команда Дискурса планирует выпустить мобильное приложение и загрузить его в AppStore и Google&nbsp;Play.
  Само собой, если на ресурсе будет разрешена порнография и гуро, его оттуда быстро удалят.
  </p>
  
  <p>
  <b>Q: Но ведь разработать такое приложение стоит дорого!</b><br>
  A: Нет, это делается просто при помощи таких инструментов как <a href="https://phonegap.com" target="_blank">PhoneGap</a>.
  </p>
  
  <p>
  <b>Q: Айфонодети не нужны, а на Android можно скачать APK.</b><br>
  A: Нет, нужны для дальнейшего развития проекта. Люди не будут менять телефон просто чтобы удобнее сидеть на Дискурсе.
  </p>
  
</content>
<?php
$html = ob_get_contents();
ob_end_clean();

$twig_data = array
(
  "html" => $html,
  "title" => "Почему запрещено порно?"
);

echo render($twig_data);
?>