<?php

use Phalcon\Mvc\Controller;
use Phalcon\Http\Request;

class NotificationController extends Controller
{
  
  public function viewAction ()
  {
    $request = new Request();
		$notification_id = intval($request->get("id"));
    //echo "Notification ID: $notification_id<br>";
    
    require_session();
    $notification_object = Notification::findFirst
    (
      [
        "notification_id = :notification_id: AND
        is_read = 0 AND
        (
          recipient_session_id = :recipient_session_id:
          OR 
          recipient_user_id = :recipient_user_id:
        )",
        "bind" =>
        [
          "notification_id" => $notification_id,
          "recipient_session_id" => session_id(),
          "recipient_user_id" => user_id()
        ]
      ]
    );
    if (!$notification_object)
    {
      die("Notification not found!");
    }

    $post_object = Post::findFirst($notification_object->post_id);
    if (!$post_object)
    {
      die("Post not found!");
    }
    
    // Mark this notification as read
    $notification_object->is_read = 1;
    $notification_object->save();
    
    // Mark notifications from the same topic as read
    $similar_notifications = Notification::find
    (
      [
        "topic_id = :topic_id: AND
        is_read = 0 AND
        (
          recipient_session_id = :recipient_session_id:
          OR
          recipient_user_id    = :recipient_user_id:
        )",
        "bind" =>
        [
          "topic_id" => $notification_object->topic_id,
          "recipient_session_id" => $notification_object->recipient_session_id,
          "recipient_user_id"    => $notification_object->recipient_user_id
        ]
      ]
    );
    foreach ($similar_notifications as $similar_notification)
    {
      $similar_notification->is_read = 1;
      $similar_notification->save();
    }
    
    //echo "Similar notifications marked as read: ".count($similar_notifications)."<br>";
    //echo "Text: " . strip_tags($post_object->text) . "<br>";
    //echo benchmark()."<br>";
    
    //$redirect_location = "/topic/".$notification_object->topic_id;
    $redirect_location = "/".$notification_object->topic_id;
    if ($notification_object->topic_id != $post_object->post_id)
    {
      $redirect_location .= "#".$post_object->order_in_topic;
    }
    
    if ($GLOBALS["domain"] == "dristach.cf")
    {
      $forum_obj = Forum::findFirst
      (
      [
        "forum_id = :forum_id:",
        "bind" =>
        [
          "forum_id" => $post_object->forum_id
        ]
      ]
      );
      
      $redirect_location = "/".$forum_obj->slug."/res/".$notification_object->topic_id.".html";
      
      if ($notification_object->topic_id != $post_object->post_id)
      {
        $redirect_location .= "#".$post_object->post_id;
      }
    }
    
    header("Location: $redirect_location");
    //echo "<a href='".$redirect_location."' target='_blank'>here</a>";
  }

}
