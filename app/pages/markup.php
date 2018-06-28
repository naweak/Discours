<?php
ob_start();
?>
<style type="text/css">
.row
{
  display: flex;
  padding: 0px 3px;
  margin: 0px 0px 6px 0px;
  border: 1px solid #f0f0f0;
  border-width: 1px 1px 1px 1px;
}
  
.row:last-of-type
{
  margin: 0px !important;
}
  
.left
{
  flex-grow: 2;
}
  
.right
{
  text-align: right;
}
</style>

<h2>Разметка</h2>
<content>
  <post_with_replies>
    <post>
      
      <div class="row">
        <div class="left">
          <b>Жирный текст</b>
        </div>
        <div class="right">
          **Жирный текст**<br>
          [b]Жирный текст[/b]<br>
          &lt;b&gt;Жирный текст&lt;/b&gt;
        </div>
      </div>
      
      <div class="row">
        <div class="left">
          <i>Курсивный текст</i>
        </div>
        <div class="right">
          *Курсивный текст*<br>
          [i]Курсивный текст[/i]<br>
          &lt;i&gt;Курсивный текст&lt;/i&gt;
        </div>
      </div>
      
      <div class="row">
        <div class="left">
          <u>Подчеркнутный текст</u>
        </div>
        <div class="right">
          [u]Подчеркнутный текст[/u]<br>
          &lt;u&gt;Подчеркнутный текст&lt;/u&gt;
        </div>
      </div>
      
      <div class="row">
        <div class="left">
          <s>Зачеркнутный текст</s>
        </div>
        <div class="right">
          [s]Зачеркнутный текст[/s]<br>
          &lt;s&gt;Зачеркнутный текст&lt;/s&gt;<br>
          ^Зачеркнутный текст^
        </div>
      </div>
      
      <div class="row">
        <div class="left">
          <span class="spoiler">Спойлер</span>
        </div>
        <div class="right">
          %%Спойлер%%<br>
          [spoiler]Спойлер[/spoiler]
        </div>
      </div>
     
    </post>
  </post_with_replies>
</content>
<?php
$html = ob_get_contents();
ob_end_clean();
$twig_data = array
(
  "html" => $html,
  "final_title" => "Разметка"
);
echo render($twig_data);
exit();