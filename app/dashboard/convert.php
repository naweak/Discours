<html>
	<head><meta charset="utf-8"></head>
<body>
<?php
// This file is used to convert database from CP-1252 encoding to UTF-8
die("403 Forbidden.");

set_time_limit (600);

require_bundle();

$pdo = pdo();

if (!is_mod())
{
  die("Restricted");
}

$limit = 1000;
$offset = 0 * $limit; // start with 0
	
$rows = Post::find
(
[
	"limit" => $limit,
	"offset" => $offset
]
);

$i = 0;
foreach ($rows as $row)
{
	echo "$i<br>";
	$str = &$row->text; // reference
	$str = mb_convert_encoding($str, "cp1252");
	echo anti_xss($str) . "<br>";

	if ($row->save())
	{
		echo "saved" . "<br>";
	}
	
	$i++;
}

echo "<br>";
echo benchmark();
?>
</body>
</html>