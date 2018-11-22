<?php
require_bundle();

if (!is_admin())
{
  die("Restricted");
}

function rus2translit ($string)
{
    $converter = array
    (
        'а' => 'a',   'б' => 'b',   'в' => 'v',
        'г' => 'g',   'д' => 'd',   'е' => 'e',
        'ё' => 'e',   'ж' => 'zh',  'з' => 'z',
        'и' => 'i',   'й' => 'y',   'к' => 'k',
        'л' => 'l',   'м' => 'm',   'н' => 'n',
        'о' => 'o',   'п' => 'p',   'р' => 'r',
        'с' => 's',   'т' => 't',   'у' => 'u',
        'ф' => 'f',   'х' => 'h',   'ц' => 'c',
        'ч' => 'ch',  'ш' => 'sh',  'щ' => 'sch',
        'ь' => '\'',  'ы' => 'y',   'ъ' => '\'',
        'э' => 'e',   'ю' => 'yu',  'я' => 'ya',
        
        'А' => 'A',   'Б' => 'B',   'В' => 'V',
        'Г' => 'G',   'Д' => 'D',   'Е' => 'E',
        'Ё' => 'E',   'Ж' => 'Zh',  'З' => 'Z',
        'И' => 'I',   'Й' => 'Y',   'К' => 'K',
        'Л' => 'L',   'М' => 'M',   'Н' => 'N',
        'О' => 'O',   'П' => 'P',   'Р' => 'R',
        'С' => 'S',   'Т' => 'T',   'У' => 'U',
        'Ф' => 'F',   'Х' => 'H',   'Ц' => 'C',
        'Ч' => 'Ch',  'Ш' => 'Sh',  'Щ' => 'Sch',
        'Ь' => '\'',  'Ы' => 'Y',   'Ъ' => '\'',
        'Э' => 'E',   'Ю' => 'Yu',  'Я' => 'Ya',
    );
    return strtr ($string, $converter);
}

function str2url ($str)
{
    // переводим в транслит
    $str = rus2translit($str);
    // в нижний регистр
    $str = strtolower($str);
    // заменям все ненужное нам на "-"
    $str = preg_replace('~[^-a-z0-9_]+~u', '-', $str);
    // удаляем начальные и конечные '-'
    $str = trim($str, "-");
    return $str;
}

$topic_object = Post::findFirst
(
[
  "post_id = :post_id: AND parent_topic = 0",
  "bind" => ["post_id" => intval($_GET["id"])]
]
);

if (!$topic_object)
{
  die("Topic not found!");
}

$url = "https://".MAIN_HOST."/p/".$topic_object->post_id;

$text = $topic_object->text;

echo "Text: $text<br>";
echo str2url($text);

$slug = "srochnaya-novost-clickbait";

$url .= "-".$slug;

ob_start();
?>

<h2>Пиар</h2>

<content style="text-align:center;">
URL: <?php echo $url; ?>
</content>

<?php
$html = ob_get_contents();
ob_end_clean();

$twig_data = array
(
  "html" => $html,
  "final_title" => "Пиар темы"
);

/*$twig_template = "default";
if (isset(domain_array()["template"]))
{
  $twig_template = domain_array()["template"];
}

$twig_filesystem = TWIG_TEMPLATES_DIR."/$twig_template";
echo render($twig_data, $twig_filesystem, $twig_template);*/

echo render($twig_data);
?>