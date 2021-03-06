<?php
header("HTTP/1.0 404 Not Found");
ob_start();
?>
<script type="text/javascript">
function setCookie(name, value, options)
{
  options = options || {};

  var expires = options.expires;

  if (typeof expires == "number" && expires) {
    var d = new Date();
    d.setTime(d.getTime() + expires * 1000);
    expires = options.expires = d;
  }
  if (expires && expires.toUTCString) {
    options.expires = expires.toUTCString();
  }

  value = encodeURIComponent(value);

  var updatedCookie = name + "=" + value;

  for (var propName in options) {
    updatedCookie += "; " + propName;
    var propValue = options[propName];
    if (propValue !== true) {
      updatedCookie += "=" + propValue;
    }
  }

  document.cookie = updatedCookie;
}
  
function set_theme (theme)
{
  setCookie("night", theme, {expires: 365*24*3600});
}
</script>

<h2>Настройки</h2>
<content>
  <div align="center">
    <input type="button" onclick="set_theme(1);document.location=document.location;" value="Темная тема">
    <input type="button" onclick="set_theme(0);document.location=document.location;" value="Светлая тема">
  </div>
  
</content>
<?php
$html = ob_get_contents();
ob_end_clean();

$twig_data = array
(
  "html" => $html,
  "final_title" => "Настройки"
);

$twig_template = "default";
if (isset(domain_array()["template"]))
{
  $twig_template = domain_array()["template"];
}

$twig_filesystem = TWIG_TEMPLATES_DIR."/$twig_template";
echo render($twig_data, $twig_filesystem, $twig_template);
?>