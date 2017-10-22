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
};

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
	
		$(".new_topic_form").submit(function()
		{
			if ($("#picrandom").val())
			{
				var random_sticker_set_name = $("#picrandom").val();
				var index_in_sticker_set = Math.floor(Math.random()*sticker_sets[random_sticker_set_name].length);
				var random_sticker = sticker_sets[random_sticker_set_name][index_in_sticker_set];
				
				$("textarea.new_post").val(random_sticker + "\n" + $("textarea.new_post").val());
			}
		});

		textarea_autoresize(reply_form_selector);
	
		$("text").each(function(index)
		{
			var height = $(this).height();
			var display_height = 310;
			var expand_html = "<div class='expand_text' onclick='expand_previous(this);'>Развернуть текст поста</div>";
			
			if (height > display_height)
			{
				$(this).css("max-height", display_height);
				$(this).after(expand_html);
			}
		});
});

/* Functions: */

function reply_to_topic(topic_id, reply_id, index)
{
		var contenteditable = $("#text_"+topic_id);

		if (typeof reply_id !== "undefined")
		{
			var quote_text = ">Ответ на пост #"+index;
			
			contenteditable.html(quote_text+"\n"+contenteditable.html());
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