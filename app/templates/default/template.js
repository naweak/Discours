window.percent_gradient_color = "#efefef";
window.topic_userfile_html = '<i class="fa fa-picture-o" aria-hidden="true"></i> Картинка</label>';
window.error_timeout = 3000;
window.new_topic_textarea_selector = "#text_new_topic";
window.reply_form_selector = "div.contenteditable_textarea";

bind_event_handlers();

$(document).ready(function ()
{ 
    on_resize();
		link_preview_tree_init();
	
		var hash = get_hash();
		if (hash)
		{
			console.log ("Highlighting reply: "+hash);
			scroll_to_reply(hash);
			highlight_reply(hash);
		}
  
    if (topic_id !== 0)
    {
      setInterval(function ()
      {
        // if not submitting any form (checking this to avoid highlighting my own replies)
        if (!$("*").hasClass("is-loading"))
        {
          load_new_replies(topic_id, function (args) // load new replies
          {
            console.log("Loaded new replies");
            if (args.appended_replies > 0)
            {
              if (document.hidden)
              {
                console.log("Window has no focus");
                pageTitleNotification.on("Новый пост");
              }
              else
              {
                console.log("Window has focus");
                setTimeout(function()
                {
                  remove_highlight_from_new_replies();
                }, 5000);
              }
            }
          }, true);
        }
      }, 5000);
    }
});

function get_form_data (element)
{
	var form_data = {};
	var fields = $(element).serializeArray();
	$(fields).each(function(i, field)
	{
		form_data[field.name] = field.value;
	});
	return form_data;
}

function ajax_form (args)
{
	function on_submit()
	{
		var form = this;
		if (!FormData)
		{
			alert("Обнови браузер!");
			return false;
		}

		if (typeof on_submit.args.before !== "undefined")
		{
			on_submit.args.before(form);
		}

		var form_data = new FormData($(this).get(0));
		if (typeof window.submit !== "undefined")
		{
			form_data[window.submit] = true;
			delete window.submit;
		}
    // form.data.get() doesn't work in Safari
    if (form_data.get("userfile"))
    {
      if (form_data.get("userfile").size === 0) // Prevent CloudFlare from returning "400 Bad Request"
      {
        form_data.delete("userfile");
      }
    }
    
    function get_contenteditable_text (element)
    {
      return element.innerText;
    }
    
    if ($(form).find(".contenteditable_textarea").length)
    {
      //var text = $(form).find(".contenteditable_textarea").first().getPreText();
      var contenteditable = $(form).find(".contenteditable_textarea").get(0);
      var text = get_contenteditable_text(contenteditable);
      form_data.append("text", text);
    }
    
    if (typeof get_challenge_answer === "function")
    {
      var challenge_answer = get_challenge_answer();
      form_data.append("challenge_answer", challenge_answer);
    }
    form_data.append("ajax", true);
		console.log("Form data for submission:");
		console.log(form_data);

    /*var object = {};
    form_data.forEach(function(value, key){
        object[key] = value;
    });
    var json = JSON.stringify(object);
    alert(json);*/
    
		on_submit.args.success.form = form;
		if (typeof args.error !== "undefined")
		{
			on_submit.args.error.form = form;
		}
		$.ajax
		({
			url: this.action,
			type: "POST",

			xhr: function()
			{
				var xhr = new window.XMLHttpRequest();

				xhr.upload.addEventListener("progress", function(evt)
				{
					if (evt.lengthComputable)
					{
						var percentComplete = evt.loaded / evt.total;
						percentComplete = parseInt(percentComplete * 100);
						//console.log(percentComplete);
						if (typeof args.percent !== "undefined")
						{
							on_submit.args.percent.form = form;
							args.percent(percentComplete);
						}

						if (percentComplete === 100)
						{

						}

					}
				}, false);

				return xhr;
			},

			data: form_data,
			contentType:false,
			cache:false,
			processData:false,

			success: on_submit.args.success,
			error:   on_submit.args.error
		});
		return false;
	}
	on_submit.args = args;
	//$(args.selector).submit(on_submit);
	$(document).on("submit", args.selector, on_submit);
}

function time_link_click (hash)
{
	if (topic_id)
	{
    $("reply").remove_highlight();
    if (get_hash() != hash)
    {
		  highlight_reply(hash);
    }
    else // click on a highlighted reply
    {
      setTimeout(function(){remove_hash_from_url();}, 100); // doesn't work instantly
    }
	}
}

function bind_event_handlers ()
{
	console.log("Binding event handlers");

	$(document).on("keydown", window.new_topic_textarea_selector, function(e)
	{
		if (e.ctrlKey && e.keyCode == 13) // Ctrl-Enter pressed
		{
			console.log("ctrl+enter");
			var submit = $(e.target).parent().find(":submit");
			$(submit).submit();
			console.log(submit);
      e.preventDefault();
      e.stopPropagation();
		}
	});

	$(document).on("keydown", window.reply_form_selector, function(e)
	{
		if (e.ctrlKey && e.keyCode == 13) // Ctrl-Enter pressed
		{
			var controls = $(e.target).next();
			var submit = $(controls).find(":submit");

			$(submit).submit();

			console.log(controls);
		}
	});

	// Textarea autoresize
	$(document).on("focus", window.reply_form_selector, function()
	{
		console.log("Reply form focus");
		$(this).next().css("display", "block");
	});
  
  $(document).on("click tap", ".captcha_image", function()
	{
    d = new Date();
    //$(this).attr("src", $(this).attr("src")+"&"+d.getTime());
    $(this).css("background-image", "url('/captcha?tag="+$(this).attr("captcha_tag")+"&rand="+d.getTime()+"')");
	});
	
	$(document).on("click tap", "a.more", function()
	{
		var text_formatted = $(this).prevAll(".text_formatted").html();
		$(this).parent().append(text_formatted);
		$(this).prev(".text_preview").remove();
		$(this).remove();
	});
  
  $(document).on("focus", window.new_topic_textarea_selector, function ()
  {
    $(".new_topic_form .captcha_div").css("display", "block");
  });
  
  document.addEventListener("visibilitychange",
  function ()
  {
    console.log("Visibility changed");
    if (!document.hidden)
    {
      console.log("Turning off title notification");
      pageTitleNotification.off();
      setTimeout(function()
      {
        remove_highlight_from_new_replies();
      }, 5000);
    }
  }
  , false);

  function image_insert_notification ()
  {
    new Noty
    ({
      text: "Изображение вставлено",
      layout: "topRight",
      type: "success",
      timeout: 1000,
    }).show();
  }

  function init_drag_and_drop ()
  {     
    function highlight_drag_and_drop (state)
    {
      var background = "#98FF98";

      if (state === false)
      {
        background = "";
      }

      $("textarea_container").
        css("background", background);

      $(".new_topic_form").parent().
        css("background", background);

      //$("body").css("background", body_background);
    }

    // Drag Enter:
    $(document).on("dragenter", "body", function(e)
    {
      highlight_drag_and_drop(true);
    });

    // Drag Leave:
    $(document).on("dragleave", "body", function(e)
    {
      if (!e.originalEvent.clientX && !e.originalEvent.clientY)
      {
        // outside body / window
        highlight_drag_and_drop(false);
      }
    });

    // Drag over:
    $(document).on("dragover", "body", function(e)
    {
      // prevent default to allow drop
      e.preventDefault();
    });

    // Drop:
    $(document).on("drop", "body", function(e)
    {
      // Console always show "files" as empty due to a bug:
      // https://stackoverflow.com/questions/11573710/event-datatransfer-files-is-empty-when-ondrop-is-fired/38598624#comment50187250_11573873

      var files = e.originalEvent.dataTransfer.files;
      var file = files[0];
      var form;

      if
      (
        $(e.target).closest(".new_topic_form").length ||
        $(e.target).closest(".reply_form").length
      )
      {
        if (typeof file !== "undefined")
        {
          if // new topic form
          (
            $(e.target).closest(".new_topic_form").length
          )
          {
            form = $(e.target).closest(".new_topic_form");
            if ($(form).find("[type='file']").length)
            {
              $(form).find("[type='file']").get(0).files = files;
              image_insert_notification();
            }
          }

          if // reply form
          (
            $(e.target).closest(".reply_form").length
          )
          {
            form = $(e.target).closest(".reply_form");
            $(form).find(".controls").css("display", "block");
            if ($(form).find("[type='file']").length)
            {
              $(form).find("[type='file']").get(0).files = files;
              image_insert_notification();
            }
          }
        }

        else
        {
          alert ("Можно загружать только файлы.");
        }
      }

      highlight_drag_and_drop(false);
      e.preventDefault();
    });

    console.log("Drag-and-drop initiated");
  }

  init_drag_and_drop();

  function init_paste ()
  {
    $(document).on("paste", "body", function(e)
    {
      if
      (
        typeof e.originalEvent.clipboardData.files !== "undefined" && 
        e.originalEvent.clipboardData.files.length
      )
      {
        if ($(e.target).closest("form").length)
        {
          var form = $(e.target).closest("form");
          if ($(form).find("[type='file']").length)
          {
            $(form).find("[type='file']").get(0).files = e.originalEvent.clipboardData.files;
            image_insert_notification();
          }
          e.preventDefault();
          e.stopPropagation();
        }
      }
    });

    console.log("Paste initiated"); 
  }

  init_paste();
  
  $("img.embedded").css("cursor", "pointer");
  $("img.embedded").click(function ()
  {
      var win = window.open(this.src, "_blank");
  });

  var input_file_label_html = $(".inputfile").next().html();

  if(/iPhone|iPad|iPod/i.test(navigator.userAgent))
  {
    append_style(".reply_to_topic {margin-left: 2px !important;}");
    append_style(".attach_button {margin-left: 3px !important;}");
    append_style(".contenteditable_textarea {margin-left: 2px !important;}");
  }

  $(document).on("change", ".inputfile", function(e) // image attach button
  {
    var label	 = $(this).next().get(0); // returns DOM element
    var fileName = '';
    if( this.files && this.files.length > 1 )
      fileName = ( this.getAttribute( 'data-multiple-caption' ) || '' ).replace( '{count}', this.files.length );
    else
      fileName = e.target.value.split( '\\' ).pop();

    if(fileName)
    {
      // label.innerHTML = fileName;
      if (fileName.indexOf(".") > -1)
      {
        label.innerHTML = "<i class='fa fa-picture-o' aria-hidden='true'></i>" +
          "&nbsp;" +
          fileName.split('.').pop().toUpperCase() +
          "-файл";
      }
      else
      {
        label.innerHTML = fileName;
      }
      label.style.fontWeight = "bold";
    }
    else
    {
      label.innerHTML = input_file_label_html;
      label.style.fontWeight = "normal";
    }
  });

  if (mobile())
  {
    $(document).on("focus", ".contenteditable_textarea", function(e) // content-editable div
    {
      $(".navbar").hide();

      var y = $(window).scrollTop(); // current position
      var element = $(e.target).parent().parent().prev();
      var element_y = $(element).offset().top;
      var element_h = $(element).height();
      var window_h = $(window).height();
      var document_h = $(document).height();

      var scroll_to = element_y;

      if (scroll_to > document_h)
      {
        scroll_to = document_h;
      }

      $(window).scrollTop(element_y);
    });

    $(document).on("blur", ".contenteditable_textarea", function(e) // content-editable div
    {
      $(".navbar").show();
    });
  }

  $(document).on("paste", ".contenteditable_textarea", function(e) // content-editable div
  {
    e.preventDefault();
    var text = '';
    if (e.clipboardData || e.originalEvent.clipboardData) {
      text = (e.originalEvent || e).clipboardData.getData('text/plain');
    } else if (window.clipboardData) {
      text = window.clipboardData.getData('Text');
    }
    if
    (
      typeof e.originalEvent.clipboardData.files !== "undefined" && 
      e.originalEvent.clipboardData.files.length
    )
    {
      text = "";
    }
    if (document.queryCommandSupported('insertText')) {
      document.execCommand('insertText', false, text);
    } else {
      document.execCommand('paste', false, text);
    }
  });

  var scroll_speed = 1;
  $("#up").click(function () // scroll up button
  {
      $("html, body").animate({scrollTop : 0}, scroll_speed);
  });
  $("#down").click(function () // scroll down button
  {
      $("html, body").animate({scrollTop : $(document).height()}, scroll_speed);
  });

  window.onscroll = function()
  {
    if (!topic_id)
    {
      if($("#load_more_topics").is(":visible") && isScrolledIntoView($("#load_more_topics")))
      {
        $("#load_more_topics").hide();
        load_more_topics(function () {$("#load_more_topics").show();});
      }
    }
  }

  if($("content.news").length)
  {
    hashCode = function(s)
    {
      return s.split("").reduce(function(a,b){a=((a<<5)-a)+b.charCodeAt(0);return a&a},0);              
    }
    var news_id = hashCode($("content.news .message .message-body").html());
    console.log("News_id: "+news_id);
    if ($.cookie("news_"+news_id) != "read")
    {
      $("content.news").css("display", "block");
    }
    $("content.news .message .message-header .delete").on("click tap", function()
    {
      if (is_admin)
      {
        alert("Admins cannot hide news.");
        return 0;
      }
      $(this).parent().parent().hide();
      $.cookie("news_"+news_id, "read");
    });
  }

	// New topic form
	ajax_form
	({
		"selector": ".new_topic_form",
		"before" : function before (form)
		{
			$(form).find("input[type='submit']").prop("disabled", true);
			$(form).find("label[for='topic_submit']").addClass("is-loading");
		},
		"success": function success (data)
		{
			var form = success.form;
      
      function clear_new_topic_form ()
      {
        $(".new_topic_form").find("[name='title']").val("");
        $(".new_topic_form").find("[name='name']").val("");

        $(".new_topic_form").find("#text_new_topic").html("");

        $(".new_topic_form").find(".picrandom").val($(".new_topic_form").find(".picrandom option:first").val());
        $(".new_topic_form").find("[name='userfile']").val("");

        $(".new_topic_form").find("[for='topic_userfile']").html(window.topic_userfile_html);
        $(".new_topic_form").find("[for='topic_userfile']").css("font-weight", "normal");
        
        $(".new_topic_form").find(".captcha_image").trigger("tap");
        $(".new_topic_form").find(".captcha_text").val("");
      }
      
			console.log("Server response:");
			console.log(data);
			data = $.parseJSON(data);
			if (typeof data.success !== "undefined" && data.success)
			{
				$("#new_topic_form_error_message").html("");
				$.get("/topic/"+data.post_id, function(data)
				{
					var parser = new DOMParser();
					var html_doc = parser.parseFromString(data, "text/html");
					var post_with_replies = $(html_doc).find("post_with_replies[topic_id]");
					
					console.log(post_with_replies);
					
					$("#topics").prepend(post_with_replies);
					
					// clear form
					clear_new_topic_form();
				});
			}
			else
			{
				/*var error_message_html = "<article class='message is-warning'><div class='message-header'><p>Ошибка</p><button class='delete' aria-label='delete' onclick='$(this).parent().parent().hide();'></button></div><div class='message-body'>"+data.error+"</div></article>";
				$("#new_topic_form_error_message").html("");
				var new_item = $(error_message_html).hide();
				$("#new_topic_form_error_message").append(new_item);
				new_item.slideDown(300);*/
        
        new Noty
        ({
          text: data.error,
          layout: "topRight",
          type: "error",
          timeout: window.error_timeout,
        }).show();
        
        $(".new_topic_form").find(".captcha_image").trigger("tap");
        $(".new_topic_form").find(".captcha_text").val("");
			}
			$(form).find("input[type='submit']").prop("disabled", false);
			$(form).find("label[for='topic_submit']").removeClass("is-loading");
			$(form).find("[name='text']").blur(); // unfocus textaread
		},
		"error": function error (data)
		{
			var form = error.form;
			console.log(form);
			console.log("Error!");
			/* Copied from success function: */
			var error_message_html = "<article class='message is-warning'><div class='message-header'><p>Ошибка</p><button class='delete' aria-label='delete' onclick='$(this).parent().parent().hide();'></button></div><div class='message-body'>Запрос не был отправлен! Попробуйте еще раз.</div></article>";
			$("#new_topic_form_error_message").html("");
			var new_item = $(error_message_html).hide();
			$("#new_topic_form_error_message").append(new_item);
			new_item.slideDown(300);
			/**/
			$(form).find("input[type='submit']").prop("disabled", false);
			$(form).find("label[for='topic_submit']").removeClass("is-loading");
		},
		"percent": function percent (data)
		{
			var form = percent.form;
			console.log(data);
			$(form).find("label[for='topic_submit']").css("background", "linear-gradient(90deg, "+window.percent_gradient_color+" "+data+"%, transparent "+(data+1)+"%)");
			if (data == 100)
			{
				$(form).find("label[for='topic_submit']").css("background", "");
			}
		}
	});

	// Reply form
	ajax_form
	({
		"selector": ".reply_form",
		"before" : function before (form)
		{
			$(form).find("input[type='submit']").prop("disabled", true);
			$(form).find(".submit_button").addClass("is-loading");
		},
		"success": function success (data)
		{
			var form = success.form;
			console.log("Server response:");
			console.log(data);
			data = $.parseJSON(data);
			
			function clean_and_resize (params)
			{ 
				// clear form
				$(form).find("[name='userfile']").val("");
				$(form).find(".attach_button").html(window.topic_userfile_html);
        $(form).find(".attach_button").css("font-weight", "normal");
				/*var textarea = $(form).find("textarea");
				//$(textarea).blur();
				textarea.val("");
				autosize.update(textarea);
				textarea.trigger("paste"); // resize textarea*/
        $(form).find(".contenteditable_textarea").first().text("");
				
				$(form).find("input[type='submit']").prop("disabled", false);
				$(form).find(".submit_button").removeClass("is-loading");
        
        $(form).find(".captcha_image").trigger("tap");
        $(form).find(".captcha_text").val("");
	
				var reply_form = $(form);
				$(reply_form).prev().detach();
				//$(form).parent().append("<div class='hr'></div>", reply_form.detach());
        $(form).parent().append("<hr>", reply_form.detach());
        
        var reply_to = $(form).find("[name='reply_to']").val();
        if (topic_id)
        {
          if (reply_to === 0 || reply_to == "0" || !reply_to) // if replying to OP post
          {
            // scroll to bottom
            console.log("Scroll to bottom");
            $(window).scrollTop($(document).height());
          }
        }
        
        change_reply_to($(form).find("[name='parent_topic']").val(), 0);
        
        if (typeof params.post_id !== "undefined")
        {
          $("#reply_"+params.post_id).get(0).scrollIntoView();
          window.scrollBy(0, -50);
        }
			}
			
			//if (typeof data.reply !== "undefined")
			if (typeof data.success !== "undefined" && data.success)
			{
        $(form).find(".error").html("");
				if ($(form).hasClass("notification_form"))
				{
					$(form).parent().find(".notification_reply_link").first().hide();
					$(form).hide();
					return true;
				}
				var parent_topic = $(form).find("[name='parent_topic']").val();
				console.log("Parent topic: " + parent_topic);
				
        if (mobile()) // scroll to post
        {
          new Noty
          ({
            text: "Ответ отправлен",
            layout: "topRight",
            type: "success",
            timeout: 1000,
          }).show();
          //load_new_replies(parent_topic, function(){return clean_and_resize({post_id: data.post_id});}, false);
          //load_new_replies(parent_topic, clean_and_resize, false);
          var order_in_topic = $(form).find("[name='reply_to']").val();
          var reply_to_element = $("reply[order_in_topic='"+order_in_topic+"']");
          if ($(reply_to_element).length)
          {
            var reply_to_post_id = $(reply_to_element).attr("id").match(/\d+/g);
            load_new_replies(parent_topic, function(){return clean_and_resize({post_id: reply_to_post_id});}, false);
          }
          else
          {
            load_new_replies(parent_topic, function(){return clean_and_resize({post_id: data.post_id});}, false);
          }
        }
        
        else // don't scroll to post
        {
          /*new Noty
          ({
            text: "Ответ отправлен",
            layout: "topRight",
            type: "success",
            timeout: 1000,
          }).show();*/
          load_new_replies(parent_topic, clean_and_resize, false);
        }
			}
			else
			{
				$(form).find("input[type='submit']").prop("disabled", false);
				$(form).find(".submit_button").removeClass("is-loading");
				//alert(data.error);
        
				/*var error_message_html = "<div style='margin-bottom:7px;'>"+data.error+"</div>";
        $(form).find(".error").html("");
				var new_item = $(error_message_html).hide();
				$(form).find(".error").append(new_item);
				new_item.slideDown(300);*/
        
        new Noty
        ({
          text: data.error,
          layout: "topRight",
          type: "error",
          timeout: window.error_timeout,
        }).show();
			}
			/*$(form).find("input[type='submit']").prop("disabled", false);
			$(form).find(".submit_button").removeClass("is-loading");*/
		},
		"error": function error (data)
		{
			var form = error.form;
			console.log(form);
			console.log("Error!");
			alert("Запрос не был отправлен! Попробуйте еще раз.\n\nОшибка: "+JSON.stringify(data));
			$(form).find("input[type='submit']").prop("disabled", false);
			$(form).find(".submit_button").removeClass("is-loading");
		},
		"percent": function percent (data)
		{
			var form = percent.form;
			console.log(data);
			$(form).find(".submit_button").css("background", "linear-gradient(90deg, "+window.percent_gradient_color+" "+data+"%, transparent "+(data+1)+"%)");
			if (data == 100)
			{
				$(form).find(".submit_button").css("background", "");
			}
		}
	});
}

/* Functions: */

function change_reply_to (topic_id, index)
{
  var topic = $("post_with_replies[topic_id='"+topic_id+"']");
	var reply_form = $(topic).find(".reply_form");
  
  $(reply_form).find(".reply_to").html(index);
  $(reply_form).find("[name='reply_to']").val(index);
  
  if (index !== 0)
  {
    $(reply_form).find(".reply_to_topic").css("display", "block");
  }
  
  else
  {
    $(reply_form).find(".reply_to_topic").css("display", "none");
  }
}

function reply_to_topic (topic_id, reply_id, index)
{
	console.log("reply_to_topic triggered!");
	$(".link_preview").remove(); // remove previews tree
  if (typeof reply_id === "undefined") // replying to original post
  {
      $("#text_"+topic_id).focus();
      return true;
  }
	if (!$("#reply_"+reply_id).length) // reply does not exist on page
	{
		document.location = "/topic/"+topic_id+"#"+index;
		return false;
	}
	var topic = $("post_with_replies[topic_id='"+topic_id+"']");
	var reply_form = $(topic).find(".reply_form");
	$(reply_form).prev().detach();
	if (typeof reply_id === "undefined") // replying to topic
	{
		//$(topic).find("reply").last().after("<div class='hr'></div>", reply_form.detach());
    $(topic).find("reply").last().after("<hr>", reply_form.detach());
	}
	else // replying to reply
	{
		//$("#reply_"+reply_id).after("<div class='hr'></div>", reply_form.detach());
    $("#reply_"+reply_id).after("<hr>", reply_form.detach());
	}
  
  change_reply_to (topic_id, index);
	
  var contenteditable = $("#text_"+topic_id);

  $(contenteditable).focus();
}

function isScrolledIntoView(elem)
{
    var docViewTop = $(window).scrollTop();
    var docViewBottom = docViewTop + $(window).height();

    var elemTop = $(elem).offset().top;
    var elemBottom = elemTop + $(elem).height();

    return ((elemBottom <= docViewBottom) && (elemTop >= docViewTop));
}

function link_click (parent_topic, order_in_topic)
{
	var elem = $("[topic_id='"+parent_topic+"']").find("reply[order_in_topic='"+order_in_topic+"']");
	if (elem.length) // element exists on page
	{
		var post = elem.find("text");

		var html = $(post).html();
		html = html.trim();

		function html2text (html)
		{
			var tag = document.createElement('div');
			tag.innerHTML = html;
			return tag.innerText;
		}

		html = html2text (html);

		console.log(html);

		if (isScrolledIntoView(elem) && $(elem).is(":visible"))
		{
			console.log("element visible");
			$(elem).fadeOut(250).fadeIn(250);
		}

		else
		{
			alert(html);
		}
	}
	
	else
	{
		console.log("Element does not exist on page");
		var win = window.open("/topic/"+parent_topic+"#"+order_in_topic, '_blank');
		win.focus();
	}
}

function delete_post (post_id)
{
    document.getElementById(post_id + "_delete_form").submit();
}

function move_post (post_id)
{
    document.getElementById(post_id + "_move_form").submit();
}

function expand_previous (element)
{
	$(element).prev().css("max-height", "9999px");
	$(element).remove();
}

function show_omitted (post_id)
{
	$("#show_omitted_" + post_id).css("display", "none");
	//$("#show_omitted_" + post_id).after("<div class='hr'></div>");
  $("#show_omitted_" + post_id).after("hr>");
  $("#omitted_" + post_id).css("display", "block");
}

function textarea_action (e)
{
	var line_height = parseFloat($(this).css("line-height"));	
	var text = $(this).val();
	var char_data  = String.fromCharCode(e.which);
	
	if (typeof char_data !== "undefined")
	{
		text += char_data;
	}
	
	if (typeof e.originalEvent !== "undefined" && typeof e.originalEvent.clipboardData !== "undefined")
	{
		text += e.originalEvent.clipboardData.getData("text");
	}

	var lines = text.split(/\r\n|\r|\n/);
	var new_height = lines.length*line_height + 5;
	
	if (new_height != $(this).height())
	{
		$(this).css("height", new_height);
	}
}

function textarea_autoresize (selector)
{
	$(selector)
	.bind("keypress", textarea_action)
	.bind("paste", textarea_action);
}

window.dynamic_page_coounter = 1;

function isScrolledIntoView (elem)
{
		var $elem = $(elem);
		var $window = $(window);

		var docViewTop = $window.scrollTop();
		var docViewBottom = docViewTop + $window.height();

		var elemTop = $elem.offset().top;
		var elemBottom = elemTop + $elem.height();

		return ((elemBottom <= docViewBottom) && (elemTop >= docViewTop));
}

function load_more_topics (on_finish)
{
	var onclick_script = $("#load_more_posts").attr("onclick");
	var caption = $("#load_more_posts post").html();
	$("#load_more_posts post").html("Загрузка...");
	$("#load_more_posts").attr("onclick", "");
	$.get(location.protocol+"//"+location.host+location.pathname+"?page="+(window.dynamic_page_coounter+1), function( data )
	{
		var parser = new DOMParser();
		var html_doc = parser.parseFromString(data, "text/html");
		var topics_element = html_doc.getElementById("topics");
		if (topics_element)
		{
			$("#topics").append(topics_element.innerHTML);
		}
		$("#load_more_posts").attr("onclick", onclick_script);
		$("#load_more_posts post").html(caption);
		window.dynamic_page_coounter++;
		if(typeof on_finish !== "undefined")
		{
			on_finish();
		}
	});
}

function load_new_replies (id, on_finish, highlight)
{
	if(typeof highlight === "undefined")
	{
		highlight = false;
	}
	
	$.get("/topic/"+id, function( data )
	{
		var parser = new DOMParser();
		var html_doc = parser.parseFromString(data, "text/html");
		var replies_element = $(html_doc).find("replies");
		if (replies_element.length)
		{
			/*if (highlight)
			{
				$("*").removeClass("highlighted");
			}*/
			var last_reply = $("post_with_replies[topic_id='"+id+"'] replies reply").last();
			var last_reply_order_in_topic = parseInt($(last_reply).attr("order_in_topic"));
			if (!last_reply_order_in_topic) {last_reply_order_in_topic = 0;}
      var appended_replies = 0;
			$(replies_element).find("reply").each(function(index)
			{
				//console.log($(this).attr("id"));
				if (parseInt($(this).attr("order_in_topic")) > last_reply_order_in_topic)
				{
					console.log("Appending reply");
					//$("post_with_replies[topic_id='"+id+"'] replies").append("<div class='hr'></div>");
          $("post_with_replies[topic_id='"+id+"'] replies").append("<hr>");
					$("post_with_replies[topic_id='"+id+"'] replies").append(this);
					if (highlight)
					{
						highlight_reply($(this).attr("order_in_topic"));
					}
          appended_replies++;
				}
			});
			if(typeof on_finish !== "undefined")
			{
				on_finish({appended_replies: appended_replies});
			}
		}
	});
}

function load_new_replies_click (element)
{
	$(element).prop("disabled", "true");
	$(element).addClass("is-loading");
	load_new_replies(topic_id, function ()
	{
		$(element).prop("disabled", "");
		$(element).removeClass("is-loading");
	}, true);
}

function hamburger_click ()
{
	if ($("#mobile_menu").is(":hidden"))
	{
		$("#mobile_menu").show();
		$.scrollLock(true);
	}
	else
	{
		$("#mobile_menu").hide();
		$.scrollLock(false);
	}
}

function scroll_to_reply (order_in_topic)
{
  var reply = $("reply[order_in_topic='"+order_in_topic+"']");
  if (reply.length)
  {
    $('html, body').animate
    ({
      scrollTop: reply.offset().top - $(".navbar").height() - 10
    }, 1);
  }
}

function highlight_reply (order_in_topic)
{
	//$("reply[order_in_topic='"+order_in_topic+"']").addClass("highlighted");
  $("reply[order_in_topic='"+order_in_topic+"']").highlight();
}

function link_preview_tree_init ()
{
	function assing_random_identifiers () // assigns a "rand" attribute to each post or preview
	{
		$("post, reply, .link_preview").each(function(index)
		{
			if (!$(this).attr("rand"))
			{
				$(this).attr("rand", Math.random());
			}
		});
	}
	
	assing_random_identifiers();
	
	// Trigger click event on a >> link
	$("body").on("click", "a.preview", function(e) // doesn't work with $(document)
	{
		console.log("Preview link clicked");
		if (mobile())
		{
			$(this).trigger("mouseenter");
		}
		else
		{
			document.location = $(this).attr("href");
		}
		return false;
	});
	
	// Trigger tap event on any element
	if (mobile())
	{
		$(document).on("tap", "*", function(e)
		{
			/*var target = e.toElement || e.relatedTarget || e.target;
			if ($(target).is("a.preview")) // tap on a.preview
			{
				$(this).trigger("mouseenter");
			}
			else if ($(target).is("a.answer_link")) // tap on a reply link
			{
				//$(this).trigger("click");
			}
			else if (!$(target).parents(".link_preview").length) // tap outside of previews tree
			{
				console.log("Remove all previews!");
				$(".link_preview").remove();
			}*/
		});
	}
	
	// Mouse enters a >> link
	$(document).on("mouseenter", "a.preview", function(e)
	{
		//var preview_link = e.toElement || e.relatedTarget || e.target; // preview link
    // new order for Firefox
    var preview_link = e.target || e.toElement || e.relatedTarget; // preview link
		var my_rand = $(this).parent().attr("rand") || $(this).parent().parent().attr("rand");
		if (!my_rand)
		{
			console.error("my_rand undefined");
			console.log("This element:");
			console.log(this);
		}
		console.log("My_rand: "+my_rand);
		if($("[parent_rand='"+my_rand+"']").length) // if a child preview exists
		{
			var child_rand = my_rand;
			while (true) // remove all it and remove all of its child previews
			{
				var child = $("[parent_rand='"+child_rand+"']");
				if (!child.length)
				{
					break;
				}
				child_rand = $(child).attr("rand");
				$(child).remove();
			}
			return false;
		}
		
		if // mouse entered a root link
		(
			!$(this).parent().hasClass("link_preview") &&
			!$(this).parent().parent().hasClass("link_preview")
		)
		{
			console.log("Remove all previews");
			$(".link_preview").remove();
		}
		
		var post_footnotes_width = [$(".post_footnote.left").width(), $(".post_footnote.right").width()];
		console.log("post_footnotes_width:");
		console.log(post_footnotes_width);
		var reply_padding = parseInt($("reply").css("padding"));
		console.log("reply_padding: " + reply_padding);
		var link_preview_min_width = (reply_padding*2) + post_footnotes_width[0] + post_footnotes_width[1] + 175;
		console.log("link_preview_min_width: " + link_preview_min_width);
		
		var topic_id = $(this).attr("topic_id");
		var order_in_topic = $(this).attr("order_in_topic");
		var preview_link_position = $(preview_link).offset();

		var x = preview_link_position.left;
		var y = preview_link_position.top + $(preview_link).height();
		
		var element; // declared here because it will be changet in AJAX get function
		var html = $("post_with_replies[topic_id='"+topic_id+"'] reply[order_in_topic='"+order_in_topic+"']").html();
		if (typeof html !== "undefined") // if reply found on page, use its HTML
		{
			html = "<div class='link_preview' style='min-width:"+link_preview_min_width+"px;'>"+html+"</div>";
		}
		else // if not, load it with AJAX
		{
			html = "<div class='link_preview' style='min-width:"+link_preview_min_width+"px;'>Загрузка...</div>";

			$.get("/topic/"+topic_id, function(data)
			{
				var parser = new DOMParser();
				var html_doc = parser.parseFromString(data, "text/html");
				
				var html = $(html_doc).find("post_with_replies[topic_id='"+topic_id+"'] reply[order_in_topic='"+order_in_topic+"']").html();
				if (typeof html === "undefined") // error
				{
					html = "<div class='link_preview' style='min-width:"+link_preview_min_width+"px;'><span style='color:red;'>Не удалось получить ответ</span></div>";
				}
				$(element).html(html);
			});
		}
		element = $(html);
		$("body").append(element);
		$(element).css({position: "absolute", "left": x, "top": y});
		$(element).attr("parent_rand", my_rand);
		$(element).children().each(function(index)
		{
			$(this).removeAttr("rand");
		});
		assing_random_identifiers();
	});
	
	// Mouse leaves a >> link or a preview div
	$(document).on("mouseleave", "post, reply, .link_preview, a.preview", function(e)
	{
		if (!$(".link_preview").length)
		{
			return false;
		}
		var target = e.toElement || e.relatedTarget || e.target; // element mouse is entering
		if
		(
			!$(target).hasClass("link_preview") &&
			!$(target).parent().hasClass("link_preview") &&
			!$(target).parent().parent().hasClass("link_preview")
		) // mouse out of previews tree
		{
			console.log("Remove all previews");
			$(".link_preview").remove();
    }
		else // mouse moves inside previews tree
		{
			var target_rand = $(target).attr("rand") || $(target).parent().attr("rand") || $(target).parent().parent().attr("rand");
			if (!target_rand)
			{
				console.error("target_rand undefined");
				console.log("Target:");
				console.log(target);
			}
			$("[parent_rand='"+target_rand+"']").remove();
		}
	});
}

function mobile ()
{
	if(/Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent))
	{
		return true;
	}
	else
	{
		return false;
	}
}

function get_hash ()
{
  return window.location.hash.substr(1);
}

function remove_highlight_from_new_replies ()
{
  $("reply").each(function()
  {
    if ($(this).attr("order_in_topic") != get_hash())
    {
      //$(this).removeClass("highlighted");
      $(this).remove_highlight();
    }
  });
}

function remove_hash_from_url ()
{
    var uri = window.location.toString();
    if (uri.indexOf("#") > 0)
    {
      var clean_uri = uri.substring(0, uri.indexOf("#"));
      window.history.replaceState({}, document.title, clean_uri);
    }
}

function append_style (str)
{
  var style = $("<style>"+str+"</style>");
  $("html > head").append(style);
}

jQuery.fn.highlight = function()
{
    $(this).addClass("highlighted");
    return this; // This is needed so others can keep chaining off of this
};

jQuery.fn.remove_highlight = function()
{
    $(this).removeClass("highlighted");
    return this; // This is needed so others can keep chaining off of this
};

$.fn.getPreText = function ()
{
    var ce = $("<pre />").html(this.html());
    if ($.browser.webkit)
      ce.find("div").replaceWith(function() { return "\n" + this.innerHTML; });
    if ($.browser.msie)
      ce.find("p").replaceWith(function() { return this.innerHTML + "<br>"; });
    if ($.browser.mozilla || $.browser.opera || $.browser.msie)
      ce.find("br").replaceWith("\n");
    return ce.text();
};

/* Page logic: */

function on_resize ()
{
    var form_width = ($(document).width() / 4) - 75;
    var form_height = form_width * (1 / 1.8);
    $("#text").css("width", form_width + "px");
    $("#text").css("height", form_height + "px");
}

$(window).resize(function ()
{
	on_resize();
});

/* END Page logic */

function more_click (element)
{
  $(element).hide();
  $(element).parent().find('.hidden').removeClass('hidden');
}

function quick_load (params)
{
  $.ajax
  ({
  url: "/notifications",
  success: function(data)
  {
    var newDoc = document.open("text/html", "replace");
    newDoc.write(data);
    newDoc.close();
  }
  });
}