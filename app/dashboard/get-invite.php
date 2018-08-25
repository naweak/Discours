<?php
require_bundle();

$pdo = pdo();

if (!is_admin())
{
  die("Restricted");
}

$invite_code = substr(str_shuffle(MD5(microtime())), 0, 15);

$invite = new Invite();
$invite->invite_code = $invite_code;
$invite->save();

echo "https://discou.rs/register?invite=$invite_code";
?>