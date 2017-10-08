      function reply_to_topic (post_id)
      {
        //alert(post_id);
        $("#form_additional_info").html("Ответ в тему с номером: "+post_id);
        $("#parent_topic").val(post_id);
        //$("html, body").animate({ scrollTop: 0 }, "fast"); // scroll to the top of the page
        $("#text").focus();
      }
      
      function delete_post (post_id)
      {
        document.getElementById(post_id+"_delete_form").submit();
      }
      
      function show_omitted (post_id)
      {
        $("#omitted_"+post_id).css("display", "block");
      }