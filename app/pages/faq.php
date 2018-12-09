<?php
ob_start();
?>
<style type="text/css">
p
{
  margin-bottom:1em;
}
</style>

<content class="page">
  <h2 style="text-align:center;">Часто задаваемые вопросы</h2>
  
  <h2>Чем Дискурс отличается от других имиджборд?</h2>
  <p>
    <b>Основная статья: <a href="/why-us">Почему Дискурс?</a></b>
  </p>

  <h2>Что можно и что нельзя постить?</h2>
  <p>
    <b>Основная статья: <a href="/rules">Правила</a></b>
  </p>
  
  <h2>Как можно связаться с администрацией Дискурса?</h2>
  <p>
    <b>Основная статья: <a href="/contact">Контакты</a></b>
  </p>
  
  <h2>У вас есть архив картинок?</h2>
  <div>
    Да, самые красивые арты сохраняются на <a href="/cloud" target="_blank">Google Disk</a>.
  </div>
  
  <h2>Как можно помочь Дискурсу?</h2>
  <div>
    Помимо создания оригинального контента и поддержания активного общения, вы можете дописать
    статьи про Дискурс на <a href="https://wiki.1chan.ca/Discou.rs" target="_blank">Колчевики</a>,
    а также принять активное участие
    в разработке <a href="https://github.com/DiscoursProject/Discours" target="_blank">движка</a>.
  </div>
  
</content>
<?php
$html = ob_get_contents();
ob_end_clean();

$twig_data = array
(
  "html" => $html,
  "final_title" => "FAQ"
);

echo render($twig_data);
?>