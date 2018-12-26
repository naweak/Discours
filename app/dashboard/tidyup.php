<?php
require_bundle();

$pdo = pdo();

if (!is_admin())
{
  die("Restricted");
}

function delete_forum ($forum_id)
{
  $posts = Post::find
  (
    [
      "forum_id = :forum_id:",
      "bind" =>
      [
        "forum_id" => $forum_id
      ],
      "order" => "creation_time DESC",
    ]
  );
  
  foreach ($posts as $post)
  {
    $post->forum_id = 1;
    $post->save();
    echo "Post ".$post->post_id." saved<br>";
  }
  
  $forum_obj = Forum::findFirst
  (
    [
      "forum_id = :forum_id:",
      "bind" =>
      [
        "forum_id" => $forum_id
      ]
    ]
  );
  
  echo "Forum title: ".$forum_obj->title."<br>";
  
  $forum_obj->delete();
  echo "Forum deleted";
}

function delete_wipe ()
{
  $pdo = pdo();
  
  $posts = Post::find
  (
    [
      "order" => "creation_time DESC",
      "limit" => 1000
    ]
  );
  
  foreach ($posts as $post)
  {
    echo $post->post_id . "<br>";
    $text = $post->text;
    $search = "FortSAGE";

    //if (mb_strpos($text, $search))
    if ($post->thumb_w == 111 and $post->thumb_h == 150)
    {
      echo "<span style='color:red;font-weight:bold;'>WIPE!</span><br>";
      $post->delete_files();
      $post->deleted_by = user_id();
      $post->save();
      
      $pdo->query("DELETE FROM notifications WHERE topic_id = '".$post->post_id."' OR post_id = '".$post->post_id."'");
      
      $expires = strtotime("+10 years");
			$query = $pdo->prepare("INSERT INTO bans (ip, expires) VALUES (:ip, :expires)");
			$query->execute(["ip" => $post->ip, "expires" => $expires]);
      
      cache_delete("forum_".$post->forum_id); // delete page cache
    }
  }
}

//delete_wipe();

/*$topic = Post::findFirst
(
  [
    "post_id = :topic_id: AND parent_topic = 0",
    "bind" => ["topic_id" => 25638]
  ]
);
$topic->delete_files();*/

// DELETE POSTERS' IP ADDRESSES (old posts)
/*$posts = $pdo->query("SELECT * FROM posts WHERE creation_time < (".(time() - 7*24*60*60).") AND ip != ''");
$i = 1;
foreach ($posts as $post)
{
	echo "$i: ";
	echo smart_time_format($post['creation_time']);
	echo "<br>";
	$i++;
}*/

// UPDATE POSTS WITH CORRUPTED FILES
function update_posts_with_corrupted_files ()
{
  $posts = Post::find
  (
  [
    "",

    "bind" =>
    [
    ]
  ]
  );

  $i = 0;
  foreach ($posts as $post)
  {
    //echo "$i: ";
    echo $post->post_id.": ";

    $file_basename = basename($post->file_url);
    echo "$file_basename ";
    if (!file_exists(ROOT_DIR."/public/files/".$file_basename))
    {
      echo "<b>FILE DOES NOT EXIST!</b>";
      echo "&nbsp;<b>UPDATING DB...</b>";
      $post->delete_files();
    }

    echo "<br>";
    $i++;
  }
}

// DELETE FILES NOT USED IN ANY POSTS
?>