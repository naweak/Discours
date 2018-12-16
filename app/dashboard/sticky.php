<?php
require_bundle();

if (!is_admin())
{
  die("Restricted");
}

$pdo = pdo();

if (isset($_POST["submit"]))
{
  $post_id = intval($_POST["post_id"]);
  
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
	
  if (!$error)
  {
    if (isset($_POST["pin"]))
    {
      $ord = round(microtime(true) * 1000) * 2;
    }
    
    else
    {
      $ord = round(microtime(true) * 1000);
    }
      
    $post_obj->ord = $ord;
    $post_obj->save();
    cache_delete("forum_".$post_obj->forum_id); // delete page cache
  }
}

ob_start();
?>
<h1>Закрепить пост</h1>

<content style="text-align:center;">
  <div>
    <?php if (isset($message)) {echo str_replace("\n", "<br>", $message);} ?>
  </div>
  
  <div style="color:red;">
    <?php if (isset($error)) {echo str_replace("\n", "<br>", $error);} ?>
  </div>

  <form action="" method="post">
     <div align="center">
       <table>
         <tr>
           <td>
             Номер поста:&nbsp;
           </td>

           <td>
            <input type="text" name="post_id" value="<?php if(isset($_POST["n"])) {echo intval($_POST["n"]);} ?>">
           </td>
         </tr>
         
         <tr>
           <td>Действите:</td>
           <td>
             <select name="action" style="width:100%;">
              <option value="pin">Закрепить</option>
               <option value="unpin">Открепить</option>
            </select>
           </td>
         </tr>
       </table>
       
       <br>
       <input type="submit" name="submit" value="Отправить">
       
     </div>
    
	</form>
</content>
<?php
$html = ob_get_contents();
ob_end_clean();

$twig_data = array
(
  "html" => $html,
	"final_title" => "Закрепить пост"
);

echo render($twig_data);
?>