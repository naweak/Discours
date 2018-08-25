<?php
require_bundle();

if (isset($_POST["submit"]))
{ 
  $username = $_POST["username"];
  $password = $_POST["password"];
  
  try
  {
    /*if (!check_recaptcha())
    {
      throw new Exception("ReCaptcha введена неверно.");
    }*/
    
    $max_attempts_per_day = 10;
    $cache_name = get_client_ip()."_password_attempts";
    
    $user_object = User::findFirst
    (
      [
        "username = :username:",
        "bind" => ["username" => $username]
      ]
    );
    
    if (!$user_object)
    {
      throw new Exception("Пользователь не найден.");
    }
    
    $password_attempts = intval(cache_get($cache_name))+1;
    
    if (intval($password_attempts) > $max_attempts_per_day)
    {
      throw new Exception("Слишком много неправильных попыток. Доступ заблокирован на 24 часа.");
    }
    
    if (!password_verify($password, $user_object->password_hash))
    {
      cache_set($cache_name, intval(cache_get($cache_name)) + 1, 24*60*60);
      throw new Exception("Неправильный пароль. Попыток: $password_attempts/$max_attempts_per_day.");
    }
    
    $_SESSION["user_id"] = $user_object->user_id;
    $_SESSION["username"] = $user_object->username;
    
    //$success_message = "Добро пожаловать, $username!";
  }
  
  catch (Exception $e)
  {
    $error_message = $e->getMessage();
  }
}

if (isset($_POST["logout"]))
{
  session_destroy();
}

ob_start();
?>
<style type="text/css">
content
{
  padding: 0px 15px; /* for mobile version */
}
  
form *[type=text],
form *[type=password]
{
  padding: 5px 5px;
  font-size: 18pt;
  display: block;
  width: 100%;
  margin-top: 4px;
}
  
form *[name=submit],
form *[name=logout]
{
  margin-top: 12px;
}
  
footer
{
  display: none;
}
</style>

<h2>Вход</h2>

<content style="text-align:center;margin-top:1.2em;margin-bottom:1.2em;">
<?php
if (isset($error_message))
{
  ?>
  <div class="notification is-warning">
    <?php echo $error_message; ?>
  </div>
  <?php
}
  
if (isset($success_message))
{
  ?>
  <div class="notification is-success">
    <?php echo $success_message; ?>
  </div>
  <?php
}
?>
</content>

<script>
function my_key_press (e)
{
  if((e && e.keyCode == 13))
  {
    document.forms.form.submit();
  }
}
</script>

<content style="text-align:center;">
    <?php if (!user_id()) { ?>
    <script type="text/javascript">
    var RecaptchaOptions = {theme : 'white'};
    </script>
    <form action="" method="post" name="form">
        <input type="text"     name="username" placeholder="Логин" onkeypress="my_key_press">
        <input type="password" name="password" placeholder="Пароль" onkeypress="my_key_press">
      
        <!--<div align="center">
          <div style="margin-top:10px;" class="g-recaptcha" data-sitekey="<?php echo RECAPTCHA_PUBLIC_KEY; ?>"></div>
        </div>-->
      
        <input class="button is-medium" type="submit" name="submit" value="Войти">
    </form>
    <?php } else { ?>
    <form action="" method="post">
      Ваш ID: <?php echo user_id(); ?><br>
      
      <input class="button is-medium is-danger" type="submit" name="logout" value="Выход">
    </form>
    <?php } ?>
</content>
<?php
$html = ob_get_contents();
ob_end_clean();

$twig_data = array
(
  "html" => $html,
  "final_title" => "Вход"
);

$twig_template = "default";
if (isset(domain_array()["template"]))
{
  $twig_template = domain_array()["template"];
}

$twig_filesystem = TWIG_TEMPLATES_DIR."/$twig_template";
echo render($twig_data, $twig_filesystem, $twig_template);
?>