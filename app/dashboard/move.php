<?php
require_bundle();

$pdo = pdo();

if (!is_mod())
{
  die("Restricted");
}

if (isset($_POST["submit"]))
{
  $post_id = intval($_POST["post_id"]);
	$new_forum_id = intval($_POST["new_forum_id"]);
  
  $post_obj = Post::findFirst
  (
  [
    "post_id = :post_id: AND parent_topic = 0",
    "bind" =>
    [
      "post_id" => $post_id
    ]
  ]
  );
  
  if (!$post_obj)
  {
    $error = "Topic not found!";
  }
	
	$new_forum_obj = Forum::findFirst
  (
  [
    "forum_id = :forum_id:",
    "bind" =>
    [
      "forum_id" => $new_forum_id
    ]
  ]
  );
  
  if (!$new_forum_obj)
  {
    $error = "Forum not found ($new_forum_id)!";
  }
  
  if (!$error)
  {
    $query = $pdo->prepare("UPDATE posts SET forum_id = :forum_id WHERE post_id = :post_id OR parent_topic = :post_id");
    $query->execute(["forum_id" => $new_forum_id, "post_id" => $post_id]);
    
    if ($query->rowCount() and $new_forum_id == 6)
    {
      $message = "Тема перенесена на свалку";
			
			$modlog = new Modlog();
			$modlog->mod_id = $_SESSION["user_id"];
			$modlog->timestamp = time();
			$modlog->post_id = $post_id;
			$modlog->text_sample = "Тема перенесена на свалку";
			$modlog->ip = "";
			$modlog->reason = "";
			$modlog->ban_id = 0;
			$modlog->unlawful = "";
			$modlog->save();
    }
  }
}

ob_start();
?>
<h1>Перенести пост</h1>

<content style="text-align:center;">
  <div>
    <?php if (isset($message)) {echo str_replace("\n", "<br>", $message);} ?>
  </div>
  
  <div style="color:red;">
    <?php if (isset($error)) {echo str_replace("\n", "<br>", $error);} ?>
  </div>

  <form action="" method="post">
     Номер поста:  <input type="text" name="post_id" value="<?php if(isset($_POST["n"])) {echo intval($_POST["n"]);} ?>">
		 <input type="submit" name="submit" value="Отправить">
		 <br>
  	 Перенести в:  <input type="text" name="new_forum_id" value="6">
	</form>
</content>
<?php
$html = ob_get_contents();
ob_end_clean();

$twig_data = array
(
  "html" => $html
);

echo render($twig_data);
?>