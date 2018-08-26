<?php
require_bundle();

$invite_code = isset($_GET["invite"]) ? $_GET["invite"] : null;

$invite_object = Invite::findFirst
([
  "invite_code = :invite_code:",
  "bind" => ["invite_code" => $invite_code]
]);

$cache_name = get_client_ip()."_used_invite";
if (cache_get($cache_name))
{
  die("Вы уже использовали один инвайт.");
}

if (isset($_POST["submit"]))
{
  $username  = $_POST["username"];
  $password  = $_POST["password"];
  $password2 = $_POST["password2"];
  
  $min_username_length = 3;
  $max_username_length = 15;
  
  $min_password_length = 6;
  $max_password_length = 60;
  
  try
  {
    $username = strtolower($username);
    $password_hash = password_hash($password, PASSWORD_DEFAULT);
    
    if (!$invite_object)
    {
      throw new Exception("Инвайт не найден в базе.");
    }
    
    if (!preg_match("/^[a-z\d_]*$/", $username))
    {
      throw new Exception("В логине могут быть только следующие символы: a-z 0-9 _");
    }
    
    if (mb_strlen($username) < $min_username_length or mb_strlen($username) > $max_username_length)
    {
      throw new Exception("Длина логина: от $min_username_length до $max_username_length символов.");
    }
    
    if (mb_strlen($password) < $min_password_length or mb_strlen($password) > $max_password_length)
    {
      throw new Exception("Длина пароля: от $min_password_length до $max_password_length символов.");
    }
    
    if ($password != $password2)
    {
      throw new Exception("Пароли не совпадают.");
    }
    
    $identical_username_object = User::findFirst
    (
      [
        "username = :username:",
        "bind" => ["username" => $username]
      ]
    );
    
    if ($identical_username_object)
    {
      throw new Exception("Логин уже занят. Придумай другой.");
    }
    
    $user_object = new User();
    $user_object->username = $username;
    $user_object->password_hash = $password_hash;
    $user_object->registration_time = time();
    $user_object->save();
    
    $invite_object->delete();
    
    cache_set($cache_name, true, 24*60*60);
    
    $_SESSION["user_id"] = $user_object->user_id;
    $success_message = "Ты зарегистрирован! Переходи на <a href='/' style='text-decoration:none;'>главную</a> и пости.";
  }
  
  catch (Exception $e)
  {
    $error_message = $e->getMessage();
  }
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
  
form *[name=submit]
{
  margin-top: 12px;
}
  
footer
{
  display: none;
}
</style>

<h2>Регистрация</h2>

<?php if (!$invite_object) { ?>

<content>
  <p>Мы раздаем инвайты всем желающим <b>СОВЕРШЕННО БЕСПЛАТНО</b>.</p>
  <p>Инвайт дает возможность <u>не подтверждать IP</u>, писать <u>из любых стран</u>, с <u>VPN/прокси/тора</u> и с <u>выключенным JS</u>.</p>
  <h2>Как получить?</h2>
  <p>Нужно рассказать нам, как вы любите Дискурс. Инбоксы для ваших сочинений:</p>
  <br>
  Почта: discoursproject 🐶гуглопочта.com<br>
  Telegram: zefirov
</content>

<?php } else { ?>
<content style="text-align:center;">
  Добро пожаловать и все такое. Раз уж ты получил инвайт, скорее регистрируйся.
</content>

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

<content class="form" style="text-align:center;">
    <form action="" method="post">
        <input type="text" name="username" placeholder="Логин">
        <input type="password" name="password" placeholder="Пароль">
        <input type="password" name="password2" placeholder="Пароль еще раз">

        <input class="button is-medium" type="submit" name="submit" value="Внесите меня">
    </form>
</content>
<?php } ?>

<?php
$html = ob_get_contents();
ob_end_clean();

$twig_data = array
(
  "html" => $html,
  "final_title" => "Регистрация"
);

echo render($twig_data);
?>