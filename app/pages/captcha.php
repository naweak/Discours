<?php
require_once ROOT_DIR."/public/securimage/securimage.php";

$img = new Securimage();
$img->charset = "abcdefghijklmnpqrstwxz";
$img->no_exit = true;
$img->show();

$captcha_code = $img->get_code();
//write_log("Captcha code: $captcha_code");
$_SESSION["captcha_code"] = $captcha_code;
?>