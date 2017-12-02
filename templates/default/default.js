var sticker_sets =
{
	"yoba":
	[
		"https://i.imgur.com/mawE2yH.jpg",
		"https://i.imgur.com/LClCpx1.jpg",
		"https://i.imgur.com/TH3e4wX.jpg",
		"https://i.imgur.com/ei0mg7L.jpg",
	],

	"criminal_raccoon":
	[
		"https://i.imgur.com/USy1FRD.jpg",
		"https://i.imgur.com/bvZbYFm.jpg",
		"https://i.imgur.com/CrmRfz2.jpg",
	],
	
	"shizik":
	[
		"https://i.imgur.com/6ud2js5.png",
		"https://i.imgur.com/Fzuv9RA.png",
		"https://i.imgur.com/nJ49DUX.png",
		"https://i.imgur.com/Vgifgpw.png",
		"https://i.imgur.com/rT1ne7l.png",
	],
	
	"cat":
	[
		"https://i.imgur.com/Bv7pvJP.png",
		"https://i.imgur.com/cXmLp8s.png",
		"https://i.imgur.com/8IjWzcm.png",
		"https://i.imgur.com/B7XxsfA.png",
		"https://i.imgur.com/mBIXv3i.png",
	],
	
	"pepe":
	[
		"https://i.imgur.com/YylBAkR.png",
		"https://i.imgur.com/wAEJMFB.png",
		"https://i.imgur.com/I8XMdRj.png",
		"https://i.imgur.com/Ox2iOf6.png",
		"https://i.imgur.com/2wFHrwO.png",
		"https://i.imgur.com/vmx3Lv5.png",
		"https://i.imgur.com/HcpU0pH.png",
		"https://i.imgur.com/l2XpWAZ.png",
		"https://i.imgur.com/5KW90qE.png",
		"https://i.imgur.com/0YD4bwt.png",
	],
};

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
	
		var reply_form_selector = "textarea.reply";

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
					method: "POST",
					
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
	
		/*ajax_form
		({
			"selector": ".voting_form",
			"success": function success (data)
			{
				console.log("Server response:");
				console.log(data);
				data = $.parseJSON(data);
				if (typeof data.result !== "undefined")
				{
					$(success.form).find("span").html(data.result);
					
					if (data.result > 0)
					{
						$(success.form).find("span").css("color", "green");
					}
					
					if (data.result == 0)
					{
						$(success.form).find("span").css("color", "inherit");
					}
					
					if (data.result < 0)
					{
						$(success.form).find("span").css("color", "red");
					}
				}
			}
		});
												 
		ajax_form
		({
			"selector": ".like_form",
			"success": function success (data)
			{
				console.log("Server response:");
				console.log(data);
				data = $.parseJSON(data);
				if (typeof data.result !== "undefined")
				{
					$(success.form).find("span").html(data.result+"&nbsp;");
					$(success.form).find("a").css("font-weight", "bold");
				}
			}
		});*/
	
		ajax_form
		({
			"selector": ".new_topic_form",
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
				if (typeof data.topic !== "undefined")
				{
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
					
					alert(data.error);
				}
				$(form).find("input[type='submit']").prop("disabled", false);
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
		//$(reply_form_selector).trigger('autosize');
	
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

/* Functions: */

function render(data)
{
	var twig = Twig.twig;
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
	$(".new_topic_form").find("[name='text']").val("");
	$(".new_topic_form").find(".picrandom").val($(".new_topic_form").find(".picrandom option:first").val());
	$(".new_topic_form").find("[name='userfile']").val("");
	
	// resize textarea
	$(".new_topic_form").find("[name='text']").trigger("paste");
}

function reply_to_topic(topic_id, reply_id, index)
{
	console.log("reply_to_topic triggered");
	
	var contenteditable = $("#text_"+topic_id);

	if (typeof reply_id !== "undefined")
	{
		var quote_text = ">Ответ на пост #"+index;
			
		contenteditable.html(quote_text+"\n"+contenteditable.html());

		var pos = quote_text.length + 1;
		contenteditable.selectRange(pos,pos);
		
		/* Copied from Autoresize Plugin */
		var ta = contenteditable[0];
		const style = window.getComputedStyle(ta, null);
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

/*!
	Autosize 4.0.0
	license: MIT
	http://www.jacklmoore.com/autosize
*/

!function(e,t){if("function"==typeof define&&define.amd)define(["exports","module"],t);else if("undefined"!=typeof exports&&"undefined"!=typeof module)t(exports,module);else{var n={exports:{}};t(n.exports,n),e.autosize=n.exports}}(this,function(e,t){"use strict";function n(e){function t(){var t=window.getComputedStyle(e,null);"vertical"===t.resize?e.style.resize="none":"both"===t.resize&&(e.style.resize="horizontal"),s="content-box"===t.boxSizing?-(parseFloat(t.paddingTop)+parseFloat(t.paddingBottom)):parseFloat(t.borderTopWidth)+parseFloat(t.borderBottomWidth),isNaN(s)&&(s=0),l()}function n(t){var n=e.style.width;e.style.width="0px",e.offsetWidth,e.style.width=n,e.style.overflowY=t}function o(e){for(var t=[];e&&e.parentNode&&e.parentNode instanceof Element;)e.parentNode.scrollTop&&t.push({node:e.parentNode,scrollTop:e.parentNode.scrollTop}),e=e.parentNode;return t}function r(){var t=e.style.height,n=o(e),r=document.documentElement&&document.documentElement.scrollTop;e.style.height="";var i=e.scrollHeight+s;return 0===e.scrollHeight?void(e.style.height=t):(e.style.height=i+"px",u=e.clientWidth,n.forEach(function(e){e.node.scrollTop=e.scrollTop}),void(r&&(document.documentElement.scrollTop=r)))}function l(){r();var t=Math.round(parseFloat(e.style.height)),o=window.getComputedStyle(e,null),i="content-box"===o.boxSizing?Math.round(parseFloat(o.height)):e.offsetHeight;if(i!==t?"hidden"===o.overflowY&&(n("scroll"),r(),i="content-box"===o.boxSizing?Math.round(parseFloat(window.getComputedStyle(e,null).height)):e.offsetHeight):"hidden"!==o.overflowY&&(n("hidden"),r(),i="content-box"===o.boxSizing?Math.round(parseFloat(window.getComputedStyle(e,null).height)):e.offsetHeight),a!==i){a=i;var l=d("autosize:resized");try{e.dispatchEvent(l)}catch(e){}}}if(e&&e.nodeName&&"TEXTAREA"===e.nodeName&&!i.has(e)){var s=null,u=e.clientWidth,a=null,c=function(){e.clientWidth!==u&&l()},p=function(t){window.removeEventListener("resize",c,!1),e.removeEventListener("input",l,!1),e.removeEventListener("keyup",l,!1),e.removeEventListener("autosize:destroy",p,!1),e.removeEventListener("autosize:update",l,!1),Object.keys(t).forEach(function(n){e.style[n]=t[n]}),i.delete(e)}.bind(e,{height:e.style.height,resize:e.style.resize,overflowY:e.style.overflowY,overflowX:e.style.overflowX,wordWrap:e.style.wordWrap});e.addEventListener("autosize:destroy",p,!1),"onpropertychange"in e&&"oninput"in e&&e.addEventListener("keyup",l,!1),window.addEventListener("resize",c,!1),e.addEventListener("input",l,!1),e.addEventListener("autosize:update",l,!1),e.style.overflowX="hidden",e.style.wordWrap="break-word",i.set(e,{destroy:p,update:l}),t()}}function o(e){var t=i.get(e);t&&t.destroy()}function r(e){var t=i.get(e);t&&t.update()}var i="function"==typeof Map?new Map:function(){var e=[],t=[];return{has:function(t){return e.indexOf(t)>-1},get:function(n){return t[e.indexOf(n)]},set:function(n,o){e.indexOf(n)===-1&&(e.push(n),t.push(o))},delete:function(n){var o=e.indexOf(n);o>-1&&(e.splice(o,1),t.splice(o,1))}}}(),d=function(e){return new Event(e,{bubbles:!0})};try{new Event("test")}catch(e){d=function(e){var t=document.createEvent("Event");return t.initEvent(e,!0,!1),t}}var l=null;"undefined"==typeof window||"function"!=typeof window.getComputedStyle?(l=function(e){return e},l.destroy=function(e){return e},l.update=function(e){return e}):(l=function(e,t){return e&&Array.prototype.forEach.call(e.length?e:[e],function(e){return n(e,t)}),e},l.destroy=function(e){return e&&Array.prototype.forEach.call(e.length?e:[e],o),e},l.update=function(e){return e&&Array.prototype.forEach.call(e.length?e:[e],r),e}),t.exports=l});