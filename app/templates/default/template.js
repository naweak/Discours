var topic_userfile_html = '<i class="fa fa-picture-o" aria-hidden="true"></i> Картинка</label>';

$(document).ready(function ()
{
    window.percent_gradient_color = "#efefef";
  
    /*window.onerror = function (msg, url, linenumber)
    {
      alert('Error message: '+msg+'\nURL: '+url+'\nLine Number: '+linenumber);
      return true;
    }*/
  
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
        load_new_replies(topic_id, function (args)
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
      }, 5000);
    }

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
  
    /*document.onpaste = function(event){
      var items = (event.clipboardData || event.originalEvent.clipboardData).items;
      console.log(JSON.stringify(items)); // will give you the mime types
      for (var index in items) {
        var item = items[index];
        if (item.kind === 'file') {
          var blob = item.getAsFile();
          var reader = new FileReader();
          reader.onload = function(event){
            console.log(event.target.result)}; // data url!
          reader.readAsDataURL(blob);
        }
      }
    }*/
  
    if(/iPhone|iPad|iPod/i.test(navigator.userAgent))
    {
      append_style(".reply_to_topic {margin-left: 2px !important;}");
      append_style(".attach_button {margin-left: 3px !important;}");
    }

    $("img.embedded").css("cursor", "pointer");
    $("img.embedded").click(function ()
		{
        var win = window.open(this.src, "_blank");
    });
	
		var inputs = document.querySelectorAll( '.inputfile' );
		Array.prototype.forEach.call( inputs, function( input )
		{
			var label	 = input.nextElementSibling,
				labelVal = label.innerHTML;

			input.addEventListener( 'change', function( e )
			{
				var fileName = '';
				if( this.files && this.files.length > 1 )
					fileName = ( this.getAttribute( 'data-multiple-caption' ) || '' ).replace( '{count}', this.files.length );
				else
					fileName = e.target.value.split( '\\' ).pop();

				if(fileName)
				{
					/*label.innerHTML = fileName;*/
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
					label.innerHTML = labelVal;
					label.style.fontWeight = "normal";
				}
			});
		});
	
		var scroll_speed = 1;
		$("#up").click(function ()
		{
        $("html, body").animate({scrollTop : 0}, scroll_speed);
    });
		$("#down").click(function ()
		{
        $("html, body").animate({scrollTop : $(document).height()}, scroll_speed);
    });
	
		bind_event_handlers();
	
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
});

window.reply_form_selector     = "textarea.reply";
window.new_topic_form_selector = "textarea.new_post";

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

		var form_data = new FormData($(this)[0]);
		if (typeof window.submit !== "undefined")
		{
			form_data[window.submit] = true;
			delete window.submit;
		}
    // form.data.get() doesn't work in Safari
    /*if (form_data.get("userfile").size === 0) // Prevent CloudFlare from returning "400 Bad Request"
    {
      form_data.delete("userfile");
    }*/
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

	$(document).on("keydown", window.new_topic_form_selector, function(e)
	{
		if (e.ctrlKey && e.keyCode == 13) // Ctrl-Enter pressed
		{
			console.log("ctrl+enter");

			var submit = $(e.target).parent().find(":submit");

			$(submit).submit();

			console.log(submit);
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

		if ($(window).width() > 700)  // normal design
		{
			autosize(this);
		}

		else // adaptive design
		{
			$(this).keypress(function()
			{
				autosize(this);
				$(this).keypress(function(){});
			});
		}
	});
  
  /*
  // Intended use: fix the input form to the bottom of the screen
  
  $(document).on("keypress, input", window.reply_form_selector, function(event)
	{
    // fix
  });
  
  $(document).on("blur", window.reply_form_selector, function()
	{
    // unfix
  });*/

	// if hidden, it's impossible to select a picture before writing text
	$(document).on("blur", window.reply_form_selector, function()
	{
			/*if ($(this).val() === "")
			{
				$(this).next().css("display", "none");
			}*/
	});

	$(document).on("focus", window.new_topic_form_selector, function()
	{
		autosize(this);
	});
	
	$(document).on("click tap", "a.more", function()
	{
		var text_formatted = $(this).prevAll(".text_formatted").html();
		$(this).parent().append(text_formatted);
		$(this).prev(".text_preview").remove();
		$(this).remove();
	});

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
				var error_message_html = "<article class='message is-warning'><div class='message-header'><p>Ошибка</p><button class='delete' aria-label='delete' onclick='$(this).parent().parent().hide();'></button></div><div class='message-body'>"+data.error+"</div></article>";
				$("#new_topic_form_error_message").html("");
				var new_item = $(error_message_html).hide();
				$("#new_topic_form_error_message").append(new_item);
				new_item.slideDown(300);
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
			
			function clean_and_resize ()
			{
				// clear form
				$(form).find("[name='userfile']").val("");
				$(form).find(".attach_button").html(topic_userfile_html);
        $(form).find(".attach_button").css("font-weight", "normal");
				var textarea = $(form).find("textarea");
				//$(textarea).blur();
				textarea.val("");
				autosize.update(textarea);

				textarea.trigger("paste"); // resize textarea
        
        change_reply_to($(form).find("[name='parent_topic']").val(), 0);
				
				$(form).find("input[type='submit']").prop("disabled", false);
				$(form).find(".submit_button").removeClass("is-loading");
	
				var reply_form = $(form);
				$(reply_form).prev().detach();
				$(form).parent().append("<div class='hr'></div>", reply_form.detach());
			}
			
			//if (typeof data.reply !== "undefined")
			if (typeof data.success !== "undefined" && data.success)
			{
				if ($(form).hasClass("notification_form"))
				{
					$(form).parent().find(".notification_reply_link").first().hide();
					$(form).hide();
					return true;
				}
				var parent_topic = $(form).find("[name='parent_topic']").val();
				console.log("Parent topic: " + parent_topic);
				
				load_new_replies(parent_topic, clean_and_resize, false);
			}
			else
			{
				$(form).find("input[type='submit']").prop("disabled", false);
				$(form).find(".submit_button").removeClass("is-loading");
				alert(data.error);
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

function clear_new_topic_form ()
{
	$(".new_topic_form").find("[name='title']").val("");
	$(".new_topic_form").find("[name='name']").val("");
	
	var textarea = $(".new_topic_form").find("[name='text']");
	textarea.val("");
	autosize.update(textarea);
	
	$(".new_topic_form").find(".picrandom").val($(".new_topic_form").find(".picrandom option:first").val());
	$(".new_topic_form").find("[name='userfile']").val("");
	
	$(".new_topic_form").find("[for='topic_userfile']").html(topic_userfile_html);
  $(".new_topic_form").find("[for='topic_userfile']").css("font-weight", "normal");
	
	// resize textarea
	$(".new_topic_form").find("[name='text']").trigger("paste");
}

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
		$(topic).find("reply").last().after("<div class='hr'></div>", reply_form.detach());
	}
	else // replying to reply
	{
		$("#reply_"+reply_id).after("<div class='hr'></div>", reply_form.detach());
	}
  
  change_reply_to (topic_id, index);
	
  var contenteditable = $("#text_"+topic_id);
	var textarea = document.querySelector("#text_"+topic_id);
	var quote_text = "";
  
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
	$("#show_omitted_" + post_id).after("<div class='hr'></div>");
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
					$("post_with_replies[topic_id='"+id+"'] replies").append("<div class='hr'></div>");
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
	$('html, body').animate
	({
		scrollTop: $("reply[order_in_topic='"+order_in_topic+"']").offset().top - $(".navbar").height() - 10
	}, 1);
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
			console.log("Remove all previews!");
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
			console.log("Remove all previews!");
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

/* Plugins: */

$.fn.selectRange = function(start, end)
{
    return this.each(function() {
        if (this.setSelectionRange) {
            this.focus();
            this.setSelectionRange(start, end);
        } else if (this.createTextRange) {
            var range = this.createTextRange();
            range.collapse(true);
            range.moveEnd('character', end);
            range.moveStart('character', start);
            range.select();
        }
    });
};

$.scrollLock = (function scrollLockClosure()
{
    'use strict';

    var $html      = $( 'html' ),
        // State: unlocked by default
        locked     = false,
        // State: scroll to revert to
        prevScroll = {
            scrollLeft : $( window ).scrollLeft(),
            scrollTop  : $( window ).scrollTop()
        },
        // State: styles to revert to
        prevStyles = {},
        lockStyles = {
            'overflow-y' : 'scroll',
            'position'   : 'fixed',
            'width'      : '100%'
        };

    // Instantiate cache in case someone tries to unlock before locking
    saveStyles();

    // Save context's inline styles in cache
    function saveStyles() {
        var styleAttr = $html.attr( 'style' ),
            styleStrs = [],
            styleHash = {};

        if( !styleAttr ){
            return;
        }

        styleStrs = styleAttr.split( /;\s/ );

        $.each( styleStrs, function serializeStyleProp( styleString ){
            if( !styleString ) {
                return;
            }

            var keyValue = styleString.split( /\s:\s/ );

            if( keyValue.length < 2 ) {
                return;
            }

            styleHash[ keyValue[ 0 ] ] = keyValue[ 1 ];
        } );

        $.extend( prevStyles, styleHash );
    }

    function lock() {
        var appliedLock = {};

        // Duplicate execution will break DOM statefulness
        if( locked ) {
            return;
        }

        // Save scroll state...
        prevScroll = {
            scrollLeft : $( window ).scrollLeft(),
            scrollTop  : $( window ).scrollTop()
        };

        // ...and styles
        saveStyles();

        // Compose our applied CSS
        $.extend( appliedLock, lockStyles, {
            // And apply scroll state as styles
            'left' : - prevScroll.scrollLeft + 'px',
            'top'  : - prevScroll.scrollTop  + 'px'
        } );

        // Then lock styles...
        $html.css( appliedLock );

        // ...and scroll state
        $( window )
            .scrollLeft( 0 )
            .scrollTop( 0 );

        locked = true;
    }

    function unlock() {
        // Duplicate execution will break DOM statefulness
        if( !locked ) {
            return;
        }

        // Revert styles
        $html.attr( 'style', $( '<x>' ).css( prevStyles ).attr( 'style' ) || '' );

        // Revert scroll values
        $( window )
            .scrollLeft( prevScroll.scrollLeft )
            .scrollTop(  prevScroll.scrollTop );

        locked = false;
    }

    return function scrollLock( on ) {
        // If an argument is passed, lock or unlock depending on truthiness
        if( arguments.length ) {
            if( on ) {
                lock();
            }
            else {
                unlock();
            }
        }
        // Otherwise, toggle
        else {
            if( locked ){
                unlock();
            }
            else {
                lock();
            }
        }
    };
}());

(function($, specialEventName) {
  'use strict';

  /**
   * Native event names for creating custom one.
   *
   * @type {Object}
   */
  var nativeEvent = Object.create(null);
  /**
   * Get current time.
   *
   * @return {Number}
   */
  var getTime = function() {
    return new Date().getTime();
  };

  nativeEvent.original = 'click';

  if ('ontouchstart' in document) {
    nativeEvent.start = 'touchstart';
    nativeEvent.end = 'touchend';
  } else {
    nativeEvent.start = 'mousedown';
    nativeEvent.end = 'mouseup';
  }

  $.event.special[specialEventName] = {
    setup: function(data, namespaces, eventHandle) {
      var $element = $(this);
      var eventData = {};

      $element
        // Remove all handlers that were set for an original event.
        .off(nativeEvent.original)
        // Prevent default actions.
        .on(nativeEvent.original, false)
        // Split original event by two different and collect an information
        // on every phase.
        .on(nativeEvent.start + ' ' + nativeEvent.end, function(event) {
          // Handle the event system of touchscreen devices.
          eventData.event = event.originalEvent.changedTouches ? event.originalEvent.changedTouches[0] : event;
        })
        .on(nativeEvent.start, function(event) {
          // Stop execution if an event is simulated.
          if (event.which && event.which !== 1) {
            return;
          }

          eventData.target = event.target;
          eventData.pageX = eventData.event.pageX;
          eventData.pageY = eventData.event.pageY;
          eventData.time = getTime();
        })
        .on(nativeEvent.end, function(event) {
          // Compare properties from two phases.
          if (
            // The target should be the same.
            eventData.target === event.target &&
            // Time between first and last phases should be less than 750 ms.
            getTime() - eventData.time < 750 &&
            // Coordinates, when event ends, should be the same as they were
            // on start.
            (
              eventData.pageX === eventData.event.pageX &&
              eventData.pageY === eventData.event.pageY
            )
          ) {
            event.type = specialEventName;
            event.pageX = eventData.event.pageX;
            event.pageY = eventData.event.pageY;

            eventHandle.call(this, event);

            // If an event wasn't prevented then execute original actions.
            if (!event.isDefaultPrevented()) {
              $element
                // Remove prevention of default actions.
                .off(nativeEvent.original)
                // Bring the action.
                .trigger(nativeEvent.original);
            }
          }
        });
    },

    remove: function() {
      $(this).off(nativeEvent.start + ' ' + nativeEvent.end);
    }
  };

  $.fn[specialEventName] = function(fn) {
    return this[fn ? 'on' : 'trigger'](specialEventName, fn);
  };
})(jQuery, 'tap');

(function(window, document){

  window.pageTitleNotification = (function () {

      var config = {
          currentTitle: null,
          interval: null
      };

      var on = function (notificationText, intervalSpeed) {
          if (!config.interval) {
              config.currentTitle = document.title;
              config.interval = window.setInterval(function() {
                  document.title = (config.currentTitle === document.title)
                      ? notificationText
                      : config.currentTitle;
              }, (intervalSpeed) ? intervalSpeed : 1000);
          }
      };

      var off = function () {
          window.clearInterval(config.interval);
          config.interval = null;
          if (config.currentTitle !== null)
          {
            document.title = config.currentTitle;
          }
      };

      return {
          on: on,
          off: off
      };

  })();

}(window, document));