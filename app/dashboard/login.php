<?php
require_bundle();

if (isset($_POST["submit"]))
{
  if ($_POST["password"] == MOD_PASSWORD)
  {
    $_SESSION["user_id"] = 1;
  }
}

if (isset($_POST["logout"]))
{
  session_destroy();
}

if (is_mod())
{
  $message = "You are mod!";
}

ob_start();
?>
<h2>Вход</h2>

<?php
echo session_cache_expire();
echo "<br>";
echo session_id();
echo "<br>";
echo var_dump($_SESSION);
?>

<content style="text-align:center;">
<?php
if (isset($message))
{
  echo $message;
}
?>
</content>

<content style="text-align:center;">
    <form action="" method="post">
        <input type="password" name="password" value=""><br>
        <input type="submit" name="submit" value="Отправить">
        <input type="submit" name="logout" value="Выход">
    </form>
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