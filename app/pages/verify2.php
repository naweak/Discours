<?php
require_bundle();

$is_verified = is_ip_verified();

if (isset($_POST["submit"]))
{ 
  try
  {
    $captcha_code = strtolower($_SESSION["captcha_code"]);
    $user_code = strtolower($_POST["captcha_code"]);
    if ($captcha_code != $user_code)
    {
      throw new Exception("Капча введена неверно.");
    }

    write_log("Verified (v.2): ".$GLOBALS["client_ip"]);
    cache_set($GLOBALS["client_ip"]."_verified", 1);
    $is_verified = true;
  }
  
  catch (Exception $e)
  {
    $error_message = $e->getMessage();
  }
}

ob_start();
?>
<content>
  <div align="center">
  <?php
  if ($is_verified)
  {
    echo "<h2 style='color:green; margin-bottom:0px;'>Можно постить.</h2>";
  }
  ?>
  
  <?php if (!$is_verified) { ?>
  <h2>Пожалуйста, введите капчу</h2>
  <?php } ?>
  
  <?php
  if (isset($error_message))
  {
    echo "<div style='margin-top:-10px;margin-bottom:15px;color:orange;'>$error_message</div>";
  }
  ?>
  </div>

  <script>
  function recaptcha_callback ()
  {
    document.getElementById("recaptcha_form").submit();
  }
  </script>
  
  <style>
  input.captcha
  {
    width: 215px;
    height: 20px;
    margin-left: -5px;
    text-align: center;
  }
    
  img.captcha
  {
    cursor: pointer;
  }
  </style>
  
  <?php if (!$is_verified) { ?>
  <form action="" method="post" id="recaptcha_form">
  <div align="center">
    <img class="captcha" src="https://discou.rs/captcha" onclick="this.src = this.src;">
    <br>
    <input type="text" class="captcha" name="captcha_code" value="" placeholder="Введите цифры с картинки">
    <br>
    <input type="submit" name="submit" value="Отправить">
    <div style="margin-top:1em;">
      Надоело? Получи <a href="/register" target="_blank">беслпатный инвайт</a>.
    </div>
  </div>
  </form>
  <?php } ?>
</content>
<?php
$html = ob_get_contents();
ob_end_clean();

$twig_data = array
(
  "html" => $html,
  "final_title" => "Подтверждение"
);

$twig_template = "default";
if (isset(domain_array()["template"]))
{
  $twig_template = domain_array()["template"];
}

$twig_filesystem = TWIG_TEMPLATES_DIR."/$twig_template";
echo render($twig_data, $twig_filesystem, $twig_template);
?>