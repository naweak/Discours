<?php
echo "<!-- SCRIPT START ".benchmark()." -->";
ob_start();
?>
<style type="text/css">
</style>

<content style="margin-top:2em;">
  
  <h2 style="text-align:center;">Поиск</h2>
  
  <div>
    <script>
    (function() {
      var cx = '005052046382897385703:lsvpjnekfjc';
      var gcse = document.createElement('script');
      gcse.type = 'text/javascript';
      gcse.async = true;
      gcse.src = 'https://cse.google.com/cse.js?cx=' + cx;
      var s = document.getElementsByTagName('script')[0];
      s.parentNode.insertBefore(gcse, s);
    })();
  </script>
  <gcse:search></gcse:search>
  </div>
  
</content>
<?php
$html = ob_get_contents();
ob_end_clean();

$twig_data = array
(
  "html" => $html,
  "final_title" => "Поиск"
);

echo render($twig_data);
?>