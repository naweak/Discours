$(document).ready(function ()
{
		document.title = "Дискурс";
	
    on_resize();

    $("img.embedded").css("cursor", "pointer");
    $("img.embedded").click(function () {
        var win = window.open(this.src, "_blank");
    });
	
		var reply_form_selector = "textarea.reply";

    $(reply_form_selector).focusin(function ()
    {
      $(this).next().css("display", "block");
    });

    $(reply_form_selector).focusout(function ()
    {
        if ($(this).val() === "")
        {
            $(this).next().css("display", "none");
        }
    });
	
		/*$(".reply_form").submit(function()
		{
			//var text = $(this).find(reply_form_selector).text();
			var text = $(this).find(reply_form_selector).html();
			
			//text = text.replace(/<div>/gi,'\n').replace(/<\/div>/gi,'');
			//text = text.replace(/^\s+|\s+$/g, '');
			//text = text.replace("&gt;", ">");
			//text = text.replace("&nbsp;", "");
			
			$(this).find("[name=text]").val(text);

			//alert (text);
			//return false;
		});*/
	
		$(".new_topic_form").submit(function()
		{
			if (document.getElementById("picrandom").checked)
			{
				var items =
				[
					"https://i.imgur.com/mawE2yH.jpg",
					"https://i.imgur.com/LClCpx1.jpg",
					"https://i.imgur.com/TH3e4wX.jpg",
					"https://i.imgur.com/ei0mg7L.jpg"
				];
				var item = items[Math.floor(Math.random()*items.length)];
				
				$("textarea.new_post").val(item + "\n" + $("textarea.new_post").val());
			}
		});

    //$("textarea").flexible();
		//textarea_init();
		textarea_autoresize(reply_form_selector);
	
		$("text").each(function(index)
		{
			var height = $(this).height();
			
			console.log("Height: "+height);
		});
});

/* Functions: */

function reply_to_topic(topic_id, reply_id, index)
{
    /*$("#form_additional_info").html("Ответ в тему с номером: " + post_id);
    $("#parent_topic").val(post_id);
    //$("html, body").animate({ scrollTop: 0 }, "fast"); // scroll to the top of the page
    $("#text").focus();*/
	
		var contenteditable = $("#text_"+topic_id);
	
		//textarea.text(">>" + reply_id + "\n" + textarea.text());
		//if (typeof reply_id !== "undefined" && contenteditable.text().charAt(0) != '>')
		if (typeof reply_id !== "undefined")
		{
			//textarea.text(">Ответ на пост " + reply_id + "\n" + textarea.text());
			//textarea.text(">Ответ на пост #" + index + "\n" + textarea.text());
			//contenteditable.html("<div>Ответ на пост #"+index+"</div>"+contenteditable.html());
			
			//var quote_text = "&gt;Ответ на пост #"+index;
			var quote_text = ">Ответ на пост #"+index;
			
			//contenteditable.html(quote_text+"<br><br>"+contenteditable.html());
			//contenteditable.html("<div>"+quote_text+"</div><div>&nbsp;</div>"+contenteditable.html());
			contenteditable.html(quote_text+"\n"+contenteditable.html());
			
			/*var el = document.getElementById("text_"+topic_id);
			var range = document.createRange();
			var sel = window.getSelection();
			range.setStart(el.childNodes[1], 0);
			range.collapse(true);
			sel.removeAllRanges();
			sel.addRange(range);*/

			//contenteditable.trigger("updateHeight");
			contenteditable.focus();
			contenteditable.trigger("keypress");

			var pos = quote_text.length + 1;
			contenteditable.selectRange(pos,pos);
		}
	
		else
		{
			contenteditable.focus();
			contenteditable.trigger("keypress");
		}
	
		/*$('html, body').animate
		({
			scrollTop: $(contenteditable).offset().top - $(window).height() + 70 + 'px'
		}, 'slow');*/
	
		//$('body').scrollTop($(contenteditable).offset().top - $(window).height() + 70);
}

function delete_post(post_id)
{
    document.getElementById(post_id + "_delete_form").submit();
}

function show_omitted(post_id)
{
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
		//document.title = $(window).width();
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
* flexibleArea.js v1.3
* A jQuery plugin that dynamically updates textarea's height to fit the content.
* http://flaviusmatis.github.com/flexibleArea.js/
*
* Copyright 2012, Flavius Matis
* Released under the MIT license.
* http://flaviusmatis.github.com/license.html
*/

/*var observe;
if (window.attachEvent)
{
    observe = function (element, event, handler)
		{
        element.attachEvent('on'+event, handler);
    };
}
else
{
    observe = function (element, event, handler) {
        element.addEventListener(event, handler, false);
    };
}
function textarea_init ()
{
    var text = document.getElementById('text');
    function resize ()
		{
        text.style.height = 'auto';
        text.style.height = text.scrollHeight+'px';
    }
    // 0-timeout to get the already changed text
    function delayedResize ()
		{
        window.setTimeout(resize, 0);
    }
    observe(text, 'change',  resize);
    observe(text, 'cut',     delayedResize);
    observe(text, 'paste',   delayedResize);
    observe(text, 'drop',    delayedResize);
    observe(text, 'keydown', delayedResize);

    text.focus();
    text.select();
    resize();
}*/

/////////////

/*(function($){
	var methods = {
		init : function() {

			var styles = [
				'paddingTop',
				'paddingRight',
				'paddingBottom',
				'paddingLeft',
				'fontSize',
				'lineHeight',
				'fontFamily',
				'width',
				'fontWeight',
				'border-top-width',
				'border-right-width',
				'border-bottom-width',
				'border-left-width',
				'-moz-box-sizing',
				'-webkit-box-sizing',
				'box-sizing'
			];

			return this.each(function(){

				if (this.type !== 'textarea')	return false;
					
				var $textarea = $(this).css({'resize': 'none', overflow: 'hidden'});
				
				var	$clone = $('<div></div>').css({
					'position' : 'absolute',
					'display' : 'none',
					'word-wrap' : 'break-word',
					'white-space' : 'pre-wrap',
					'border-style' : 'solid'
				}).appendTo(document.body);

				function copyStyles(){
					for (var i=0; i < styles.length; i++) {
						$clone.css(styles[i],$textarea.css(styles[i]));
					}
				}

				// Apply textarea styles to clone
				copyStyles();

				var hasBoxModel = $textarea.css('box-sizing') == 'border-box' || $textarea.css('-moz-box-sizing') == 'border-box' || $textarea.css('-webkit-box-sizing') == 'border-box';
				var heightCompensation = parseInt($textarea.css('border-top-width')) + parseInt($textarea.css('padding-top')) + parseInt($textarea.css('padding-bottom')) + parseInt($textarea.css('border-bottom-width'));
				var textareaHeight = parseInt($textarea.css('height'), 10);
				var lineHeight = parseInt($textarea.css('line-height'), 10) || parseInt($textarea.css('font-size'), 10);
				var minheight = lineHeight * 2 > textareaHeight ? lineHeight * 2 : textareaHeight;
				var maxheight = parseInt($textarea.css('max-height'), 10) > -1 ? parseInt($textarea.css('max-height'), 10) : Number.MAX_VALUE;

				function updateHeight() {
					var textareaContent = $textarea.val().replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/&/g, '&amp;').replace(/\n/g, '<br/>');
					// Adding an extra white space to make sure the last line is rendered.
					$clone.html(textareaContent + '&nbsp;').css({'width': parseInt($textarea.width(), 10) + 'px'});
					setHeightAndOverflow();
				}

				function setHeightAndOverflow(){
					var cloneHeight = $clone.height();
					var overflow = 'hidden';
					var height = hasBoxModel ? cloneHeight + lineHeight + heightCompensation : cloneHeight + lineHeight;

					// ----------------------------
					minheight = 0;
					
					lines = $('textarea').val().split('\n').length;
					if (lines == 0) {lines = 1;}
					
					height = lines*15.5; // dirty hack
					// ----------------------------
          
					if (height > maxheight) {
						height = maxheight;
						overflow = 'auto';
					} else if (height < minheight) {
						height = minheight;
					}
					if ($textarea.height() !== height) {
						$textarea.css({'overflow': overflow, 'height': height + 'px'});
					}
				}

				// Update textarea size on keyup, change, cut and paste
				$textarea.bind('keyup change cut paste', function(){
					updateHeight();
				});

				// Update textarea on window resize
				$(window).bind('resize', function(){
					if ($clone.width() !== parseInt($textarea.width(), 10)) {
						updateHeight();
					}
				});

				// Update textarea on blur
				$textarea.bind('blur',function(){
					setHeightAndOverflow();
				});

				// Update textarea when needed
				$textarea.bind('updateHeight', function(){
					copyStyles();
					updateHeight();
				});

				// Wait until DOM is ready to fix IE7+ stupid bug
				$(function(){
					updateHeight();
				});
			});
		}
	};

	$.fn.flexible = function(method) {
		// Method calling logic
		if (methods[method]) {
			return methods[method].apply(this, Array.prototype.slice.call(arguments, 1));
		} else if (typeof method === 'object' || ! method) {
			return methods.init.apply(this, arguments);
		} else {
			$.error('Method ' + method + ' does not exist on jQuery.flexible');
		}
	};

})(jQuery);
*/