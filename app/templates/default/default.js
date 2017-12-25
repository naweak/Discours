$(document).ready(function ()
{
		document.title = "Дискурс";
	
    on_resize();
	
		$.get("/twig", function(data)
    {
    	window.template = data;
    });

    $("img.embedded").css("cursor", "pointer");
    $("img.embedded").click(function () {
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

				if( fileName )
					label.innerHTML = fileName;
				else
					label.innerHTML = labelVal;
			});
		});
	
		var reply_form_selector     = "textarea.reply";
		var new_topic_form_selector = "textarea.new_post";

    $(reply_form_selector).focusin(function ()
    {
      $(this).next().css("display", "block");
    });

		// if hidden, it's impossible to select a picture before writing text
    $(reply_form_selector).focusout(function ()
    {
        /*if ($(this).val() === "")
        {
        	$(this).next().css("display", "none");
        }*/
    });
	
		$(reply_form_selector).keydown(function (e)
		{
			if (e.ctrlKey && e.keyCode == 13) // Ctrl-Enter pressed
			{
				console.log("ctrl+enter");
				
				var controls = $(e.target).next();
				var submit = $(controls).find(":submit");
				
				$(submit).submit();
				
				console.log(controls);
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
	
		$(".new_topic_form").submit(function()
		{
			var picrandom = $(this).find(".picrandom");
			if (picrandom.val())
			{
				var random_sticker_set_name = $(picrandom).val();
				var index_in_sticker_set = Math.floor(Math.random()*sticker_sets[random_sticker_set_name].length);
				var random_sticker = sticker_sets[random_sticker_set_name][index_in_sticker_set];
				
				$("textarea.new_post").val(random_sticker + "\n" + $("textarea.new_post").val());
			}
		});
	
		$(".reply_form").submit(function()
		{
			var text = $(this).find("textarea").val();
			var first_line = text.split('\n')[0];
			var regex = /^:([a-zA-Z0-9]+):$/ig;
			var matches = regex.exec(first_line);
			if (matches && matches[1] && typeof sticker_sets[matches[1]] !== "undefined")
			{
				var random_sticker_set_name = matches[1];
				var index_in_sticker_set = Math.floor(Math.random()*sticker_sets[random_sticker_set_name].length);
				var random_sticker = sticker_sets[random_sticker_set_name][index_in_sticker_set];
				var textarea = $(this).find("textarea");
				
				var value = $(textarea).val().split(/\n+/g);
				value.shift();
				$(textarea).val(value.join("\n"));
				
				textarea.val(random_sticker + "\n" + textarea.val());
			}
		});
	
		function ajax_form (args)
		{
			function on_submit()
			{
				var form = this;
				//var form_data = get_form_data(form);
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
				//form_data["ajax"] = true;
				form_data.append("ajax", true);
				console.log("Form data for submission:");
				console.log(form_data);
				on_submit.args.success.form = form;
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
								console.log(percentComplete);

								if (percentComplete === 100)
								{

								}

							}
						}, false);

						return xhr;
					},
					
					data: form_data,
					contentType:false,
          cache: false,
          processData:false,
					
					success: on_submit.args.success
				});
				return false;
			}
			on_submit.args = args;
			$(args.selector).submit(on_submit);
		}
	
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
					//alert("success");
					var twig_data =
					{
						"block": "topic_with_replies",
						"topic": data.topic
					};

					var rendered = render(twig_data);
					$("#topics").prepend(rendered);
					
					// clear form
					clear_new_topic_form();
				}
				else
				{
					// reset picrandom
					$(".new_topic_form").find(".picrandom").val($(".new_topic_form").find(".picrandom option:first").val());
					
					//alert(data.error);
					
					var error_message_html = "<article class='message is-warning'><div class='message-header'><p>Ошибка</p><button class='delete' aria-label='delete' onclick='$(this).parent().parent().hide();'></button></div><div class='message-body'>"+data.error+"</div></article>";
					
					$("#new_topic_form_error_message").html("");
					var new_item = $(error_message_html).hide();
					$("#new_topic_form_error_message").append(new_item);
					new_item.slideDown(300);
				}
				$(form).find("input[type='submit']").prop("disabled", false);
				$(form).find("label[for='topic_submit']").removeClass("is-loading");
			}
		});
	
		// Reply form
		ajax_form
		({
			"selector": ".reply_form",
			"before" : function before (form)
			{
				$(form).find("input[type='submit']").prop("disabled", true);
			},
			"success": function success (data)
			{
				var form = success.form;
				console.log("Server response:");
				console.log(data);
				data = $.parseJSON(data);
				if (typeof data.reply !== "undefined")
				{
					// append reply
					var twig_data =
					{
						"block": "reply",
						"reply": data.reply
					};
					var rendered = render(twig_data);
					rendered += "<div class='hr'></div>";
					$("post_with_replies."+data.reply.parent_topic).find("replies").append(rendered);
					
					// clear form
					$("post_with_replies."+data.reply.parent_topic).find("[name='userfile']").val("");
					var textarea = $("post_with_replies."+data.reply.parent_topic).find("textarea");
					textarea.val("");
					autosize.update(textarea);
					
					// resize textarea
					$("post_with_replies."+data.reply.parent_topic).find("textarea").trigger("paste");
				}
				else
				{
					alert(data.error);
				}
				$(form).find("input[type='submit']").prop("disabled", false);
			}
		});

		// Textarea autoresize
		//textarea_autoresize(reply_form_selector);
		autosize(document.querySelectorAll(reply_form_selector));
		autosize(document.querySelectorAll(new_topic_form_selector));
	
		$("text").each(function(index)
		{
			var height = $(this).height();
			//var display_height = 310;
			var display_height = 200;
			var expand_html = "<a class='expand_text' onclick='expand_previous(this);'>Показать текст полностью</a>";
			
			if (height > display_height)
			{
				$(this).css("max-height", display_height);
				$(this).after(expand_html);
			}
		});
});

$(window).load(function()
{
    $('.lazyload').each(function()
		{
    	$(this).attr('src', $(this).attr('data-src'));
    });
});

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

function reply_to_topic(topic_id, reply_id, index)
{
	console.log("reply_to_topic triggered!");
	
	var contenteditable = $("#text_"+topic_id);
	var textarea = document.querySelector("#text_"+topic_id);

	if (typeof reply_id !== "undefined")
	{
		var quote_text = ">>"+index;
		
		//contenteditable.html(quote_text+"\n"+contenteditable.html());
		textarea.value = quote_text+"\n"+textarea.value;
		autosize(textarea);

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
	
	/*else
	{
		//autosize.update(contenteditable);
		//contenteditable.focus();
		//contenteditable.trigger("keypress");
	}*/
}

function link_click (parent_topic, order_in_topic)
{
	var post = $("[topic_id='"+parent_topic+"']").find("[order_in_topic='"+order_in_topic+"']").find("text");
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
	
	alert(html);
}

function delete_post(post_id)
{
    document.getElementById(post_id + "_delete_form").submit();
}

function expand_previous (element)
{
	$(element).prev().css("max-height", "");
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