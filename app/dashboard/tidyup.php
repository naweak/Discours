<?php
require_bundle();

$pdo = pdo();

if (!is_mod())
{
  die("Restricted");
}

// DELETE POSTERS' IP ADDRESSES (old posts)
$posts = $pdo->query("SELECT * FROM posts WHERE creation_time < (".(time() - 7*24*60*60).") AND ip != ''");
$i = 1;
foreach ($posts as $post)
{
	echo "$i: ";
	echo smart_time_format($post['creation_time']);
	echo "<br>";
	$i++;
}

// DELETE FILES NOT USED IN ANY POSTS
// requires normal paths in config.php

// UPDATE POSTS WITH CORRUPTED FILES
// requires normal paths in config.php
?>