$(document).ready(function ()
{
    on_resize();
	
		var hash = window.location.hash.substr(1);
		if (hash)
		{
			console.log ("Highlighting reply: "+hash);
			scroll_to_reply(hash);
			highlight_reply(hash);
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
		form_data.append("ajax", true);
		console.log("Form data for submission:");
		console.log(form_data);
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
		$("*").removeClass("highlighted");
		highlight_reply(hash);
	}
}

function bind_event_handlers ()
{
	console.log("Binding event handlers");

	//$(window.reply_form_selector).focusin(function ()
	/*$(document).on("focus", window.reply_form_selector, function()
	{
		console.log("Reply form focus");
		$(this).next().css("display", "block");
	});*/

	//$(window.new_topic_form_selector).keydown(function (e)
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

	//$(window.reply_form_selector).keydown(function (e)
	$(document).on("keydown", window.reply_form_selector, function(e)
	{
		if (e.ctrlKey && e.keyCode == 13) // Ctrl-Enter pressed
		{
			//console.log("ctrl+enter");

			var controls = $(e.target).next();
			var submit = $(controls).find(":submit");

			$(submit).submit();

			console.log(controls);
		}
	});

	// Textarea autoresize
	//autosize(document.querySelectorAll(reply_form_selector));
	//autosize(document.querySelectorAll(new_topic_form_selector));
	//$(window.reply_form_selector).focus(function()
	$(document).on("focus", window.reply_form_selector, function()
	{
		console.log("Reply form focus");
		$(this).next().css("display", "block");
		
		//autosize(this);

		if ($(window).width() > 700)  // normal design
		{
			autosize(this);
		}

		else // adaptive design
		{
			/*$("html, body").animate
			({
				scrollTop:
						$(this).offset().top - $(window).height() + $(this).height()
			}, 0);*/
			$(this).keypress(function()
			{
				//console.log( "Handler for .keypress() called." );
				autosize(this);
				$(this).keypress(function(){});
			});
		}
	});
	
	// if hidden, it's impossible to select a picture before writing text
	//$(window.reply_form_selector).focusout(function ()
	$(document).on("blur", window.reply_form_selector, function()
	{
			/*if ($(this).val() === "")
			{
				$(this).next().css("display", "none");
			}*/
	});

	//$(window.new_topic_form_selector).focus(function()
	$(document).on("focus", window.new_topic_form_selector, function()
	{
		autosize(this);
		$(this).parent().find("[name='title']").css("display", "block");
	});

	$("text").each(function(index)
	{
		var height = $(this).height();
		var display_height = 200; /* duplicated in CSS file */
		var expand_html = "<a class='expand_text' onclick='expand_previous(this);'>Показать текст полностью</a>";

		//if (height > display_height)
		if (this.scrollHeight > $(this).innerHeight())
		{
			//$(this).css("max-height", display_height);
			$(this).after(expand_html);
		}
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
			if (typeof data.topic !== "undefined")
			{
				$("#new_topic_form_error_message").html("");

				// prepend thread
				rendered = data.html;
				$("#topics").prepend(rendered);

				// clear form
				clear_new_topic_form();
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
			$(form).find("label[for='topic_submit']").css("background", "linear-gradient(90deg, #ffdd57 "+data+"%, transparent "+(data+1)+"%)");
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
				$(form).find(".attach_button").html("Прикрепить картинку");
				var textarea = $(form).find("textarea");
				//$(textarea).blur();
				textarea.val("");
				autosize.update(textarea);

				textarea.trigger("paste"); // resize textarea
				
				$(form).find("input[type='submit']").prop("disabled", false);
				$(form).find(".submit_button").removeClass("is-loading");
	
				var reply_form = $(form);
				$(reply_form).prev().detach();
				$(form).parent().append("<div class='hr'></div>", reply_form.detach());
				
				//$("html, body").animate({scrollTop: $(document).height() }, 1); // scroll to bottom
			}
			
			if (typeof data.reply !== "undefined")
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
			alert("Запрос не был отправлен! Попробуйте еще раз.");
			$(form).find("input[type='submit']").prop("disabled", false);
			$(form).find(".submit_button").removeClass("is-loading");
		},
		"percent": function percent (data)
		{
			var form = percent.form;
			console.log(data);
			$(form).find(".submit_button").css("background", "linear-gradient(90deg, #ffdd57 "+data+"%, transparent "+(data+1)+"%)");
			if (data == 100)
			{
				$(form).find(".submit_button").css("background", "");
			}
		}
	});
}

/* Functions: */

function render (data)
{
	var twig = Twig.twig;
	//var twig = require('twig');
	var template = twig
	({
		data: window.template
	});
	var output = template.render(data);
	return output;
}

function clear_new_topic_form ()
{
	$(".new_topic_form").find("[name='title']").val("");
	$(".new_topic_form").find("[name='name']").val("");
	
	var textarea = $(".new_topic_form").find("[name='text']");
	textarea.val("");
	autosize.update(textarea);
	
	$(".new_topic_form").find(".picrandom").val($(".new_topic_form").find(".picrandom option:first").val());
	$(".new_topic_form").find("[name='userfile']").val("");
	
	$(".new_topic_form").find("[for='topic_userfile']").html("Прикрепить картинку");
	
	// resize textarea
	$(".new_topic_form").find("[name='text']").trigger("paste");
}

function reply_to_topic (topic_id, reply_id, index)
{
	console.log("reply_to_topic triggered!");
	
	var reply_form = $("post_with_replies[topic_id='"+topic_id+"'] .reply_form");
	$(reply_form).prev().detach();
	$("#reply_"+reply_id).after("<div class='hr'></div>", reply_form.detach());
	
	var contenteditable = $("#text_"+topic_id);
	var textarea = document.querySelector("#text_"+topic_id);
	var quote_text = "";

	if (typeof reply_id !== "undefined")
	{
		quote_text = ">>"+index;
		
		//contenteditable.html(quote_text+"\n"+contenteditable.html());
		textarea.value = quote_text+"\n"+textarea.value;
	}
		
	//autosize(textarea);

	if ($(window).width() > 700)  // normal design
	{
		autosize(textarea);
	}

	else // adaptive design
	{
		$(textarea).keypress(function()
		{
			autosize(textarea);
			$(textarea).keypress(function(){});
		});
	}

	var pos = quote_text.length + 1;
	contenteditable.selectRange(pos,pos);

	/* Copied from Autoresize Plugin */
	var ta = contenteditable[0];
	var style = window.getComputedStyle(ta, null);
	if (style.boxSizing === 'content-box')
	{
		heightOffset = -(parseFloat(style.paddingTop)+parseFloat(style.paddingBottom));
	}
	else
	{
		heightOffset = parseFloat(style.borderTopWidth)+parseFloat(style.borderBottomWidth);
	}
	if (isNaN(heightOffset))
	{
		heightOffset = 0;
	}
	var endHeight = ta.scrollHeight+heightOffset;
	contenteditable.css("height", endHeight);
	/* /Copied from Autoresize Plugin */
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
	var elem = $("[topic_id='"+parent_topic+"']").find("[order_in_topic='"+order_in_topic+"']");
	var post = elem.find("text");
	
	var html = $(post).html();
	html = html.trim();
	//html = html.replace(/<(?:.|\n)*?>/gm, '');
	
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
		/*$(elem).animate
		({
			opacity: 0.25,
			left: "+=50",
			height: "toggle"
		}, 500);*/
		$(elem).fadeOut(250).fadeIn(250);
	}
	
	else
	{
		alert(html);
	}
}

function delete_post (post_id)
{
    document.getElementById(post_id + "_delete_form").submit();
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
		if (replies_element)
		{
			if (highlight)
			{
				$("*").removeClass("highlighted");
			}
			var last_reply = $("post_with_replies[topic_id='"+id+"'] replies reply").last();
			var last_reply_order_in_topic = parseInt($(last_reply).attr("order_in_topic"));
			$(replies_element).find("reply").each(function(index)
			{
				console.log($(this).attr("id"));
				/*var append = false;
				if (parseInt($(this).attr("order_in_topic")) > last_reply_order_in_topic)
				{
					//console.log($(this).attr("order_in_topic") + " > " + last_reply_order_in_topic);
					append = true;
				}*/
				//if (append)
				if (parseInt($(this).attr("order_in_topic")) > last_reply_order_in_topic)
				{
					console.log("Appending reply");
					$("post_with_replies[topic_id='"+id+"'] replies").append("<div class='hr'></div>");
					$("post_with_replies[topic_id='"+id+"'] replies").append(this);
					if (highlight)
					{
						highlight_reply($(this).attr("order_in_topic"));
					}
				}
			});
			if(typeof on_finish !== "undefined")
			{
				on_finish();
			}
		}
	});
}

function load_new_replies_click (element)
{
	$(element).prop("disabled", "true");
	load_new_replies(topic_id, function ()
	{
		$(element).prop("disabled", "");
	}, true);
}

function hamburger_click ()
{
	if ($("#mobile_menu").is(":hidden"))
	{
		$("#mobile_menu").show();
	}
	else
	{
		$("#mobile_menu").hide();
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
	$("reply[order_in_topic='"+order_in_topic+"']").addClass("highlighted");
}

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