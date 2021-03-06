<?php
if (!is_admin())
{
  die("Restricted");
}

$pdo = pdo();

ob_start();

echo "Cloudflare country: ";
echo cloudflare_country_code();
echo "<br>";
//echo "Challenge answer: ".get_challenge_answer(get_identity());
//echo "<br>";

//$pdo->query("INSERT INTO forums (forum_id, title) VALUES ('', 'Советы и вопросы')");
//$pdo->query("UPDATE forums SET title = 'Старый дизайн' WHERE forum_id = 14");
?>

<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>

Server time: <?php echo date(DATE_RFC822); ?>

<hr>

<?php
$beginning_of_this_day = strtotime("midnight", time());

$sql = $pdo->query("SELECT COUNT(DISTINCT ip) FROM posts WHERE creation_time >= $beginning_of_this_day");
$row = $sql->fetch();

echo "Unique IPs today: {$row[0]}<br>";

$sql = $pdo->query("SELECT COUNT(post_id) FROM posts WHERE creation_time >= $beginning_of_this_day");
$row = $sql->fetch();

echo "Posts today: {$row[0]}<br>";

$sql = $pdo->query("SELECT COUNT(post_id) FROM posts WHERE parent_topic = 0 AND creation_time >= $beginning_of_this_day");
$row = $sql->fetch();

echo "Topics today: {$row[0]}<br>";

echo "<hr>";

function get_dir_size ($directory)
{
    $size = 0;
    foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory)) as $file)
    {
        $size += $file->getSize();
    }
    return $size;
}

$files_size_mb = round(get_dir_size(UPLOAD_DIR)/1048576, 2);

echo "Files size: $files_size_mb&nbsp;MB<br>";

$query = $pdo->query("SELECT COUNT(user_id) FROM users");
$row = $query->fetch();

echo "Users registered: {$row[0]}<br>";
?>

<hr>

<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.5.0/Chart.min.js"></script>

<input type="button" value="show" onclick="show_chart();">

<div align="center">
<canvas id="chart" style="max-width:80%;"></canvas>
</div>

<script>
var labels = [];
var data = [];  
</script>

<?php
/*$i = 0;
$start_time = strtotime("midnight", strtotime("-90 days"));
$iterartion_time = $start_time;
while (true)
{
  $last_post = Post::findFirst(["creation_time < $iterartion_time", "order" => "post_id DESC"]);
  ?>
  <script>
  labels.push("<?php echo date("d-m-Y", $iterartion_time) ?>");
  data.push(<?php echo $last_post->post_id; ?>);
  </script>
  <?php
  
  $iterartion_time = strtotime("+1 day", $iterartion_time);
  if ($iterartion_time > time())
  {
    break;
  }
  $i++;
}*/
?>

<script>
function show_chart ()
{ 
  new Chart(document.getElementById("chart"), {
    type: 'line',
    data: {
      labels: labels,
      datasets: [{ 
          data: data,
          label: "Last post ID",
          borderColor: "#3e95cd",
          fill: false
        }
      ]
    },
    options: {
      title: {
        display: true,
        text: "Discours posts by day"
      }
    }
  });
}
</script>

<hr>

<?php
echo benchmark();

$html = ob_get_contents();
ob_end_clean();

$twig_data = array
(
  "html" => $html,
  "final_title" => "Статистика"
);

echo render($twig_data);
?>