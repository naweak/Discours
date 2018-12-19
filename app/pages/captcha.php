<?php
$no_captcha = false;

session_start();

$tag = $_GET["tag"];
if (!validate_captcha_tag($tag))
{
  die("Invalid captcha tag");
}

if ($no_captcha)
{
  $captcha_code = "";
  
  $image = imagecreatefrompng(PUBLIC_DIR."/no-captcha.png");
  header("Content-Type: image/png");
  imagepng($image);
  imagedestroy($image);
}

else
{
  require_once ROOT_DIR."/public/securimage/securimage.php";
  $img = new Securimage();

  $img->image_width = 225;
  $img->image_height = 90;

  $captcha_code = mt_rand(10000, 99999);
  $img->code = $captcha_code;

  $img->font_ratio = 0.65;
  $img->perturbation = 0.5;
  $img->text_transparency_percentage = 0;
  $img->text_color = new Securimage_Color("#808080");

  $img->ttf_file = Securimage::getPath() . "/A_rus.ttf";

  $img->noise_level = 10;
  $img->num_lines = 0;

  $img->no_exit = true;
  
  $img->show();
}

$_SESSION["captcha_$tag"] = $captcha_code;
?>