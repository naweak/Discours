<?php
if (!is_mod())
{
  die("Restricted");
}

$pdo = pdo();

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

echo "Unique IPs today: ".$row[0];
?>

<hr>
<?php
$i = 0;
$date = date('Y-m-d H:i:s', strtotime("-$i hour"));
$beginning_of_this_hour_str = substr($date, 0, -5)."00:00";
$beginning_of_this_hour = strtotime($beginning_of_this_hour_str);
while ($i < 24)
{
	$end = strtotime("-$i hour", $beginning_of_this_hour);
	$begin = strtotime("-".($i+1)." hour", $beginning_of_this_hour);
	
	echo date('Y-m-d H:i:s', $begin);
	echo " - ";
	echo date('Y-m-d H:i:s', $end);
	echo ": ";
	
	// count number of posts here
	$posts = Post::find("$begin < creation_time AND creation_time < $end");
	
	echo count($posts);
	
	echo "<br>";
	if (date('H:i:s', $begin) == "00:00:00")
	{
		echo "-----------------------<br>";
	}
	
	$i++;
}
?>
<hr>
<?php
echo benchmark();
?>