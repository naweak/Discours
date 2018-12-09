<?php
require_once ROOT_DIR."/public/securimage/securimage.php";

$img = new Securimage();

$img->image_width = 225;
$img->image_height = 90;

$img->charset = "abcdefghijklmnpqrstwxz";

$codes = ["мятка",
         "ракам",
         "ракодил",
         "игорь",
         "тонет",
         "пепси",
         "зефир",
         "олдфаг",
         "аниме",
         "колчок",
         "нульчан",
         "тиреч",
         "сосач",
         "рачки",
         "вакаба",
         ];

$captcha_code = $codes[array_rand($codes, 1)];
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

$tag = $_GET["tag"];
if (!validate_captcha_tag($tag))
{
  die("Invalid captcha tag");
}

$_SESSION["captcha_$tag"] = $captcha_code;

$img->show();
?>