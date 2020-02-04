/*jshint bitwise:false, curly:true, eqeqeq:true, evil:true, forin:false, noarg:true, noempty:true, nonew:true, undef:false, strict:false, browser:true */

$(document).ready(function() {
	/** Load jQuery extensions **/
	//Code via: http://stackoverflow.com/a/13106698
	$.fn.highlight = function (fadeOut) {
		fadeOut = typeof fadeOut !== 'undefined' ? fadeOut : 5000;
		$(this).each(function () {
			var el = $(this);
			$("<div/>")
				.width(el.outerWidth())
				.height(el.outerHeight())
				.css({
					"position": "absolute",
					"left": el.offset().left,
					"top": el.offset().top,
					"background-color": "#ffff99",
					"opacity": ".7",
					"z-index": "9999999",
					"border-top-left-radius": parseInt(el.css("borderTopLeftRadius"), 10),
					"border-top-right-radius": parseInt(el.css("borderTopRightRadius"), 10),
					"border-bottom-left-radius": parseInt(el.css("borderBottomLeftRadius"), 10),
					"border-bottom-right-radius": parseInt(el.css("borderBottomRightRadius"), 10)
				}).appendTo('body').fadeOut(fadeOut).queue(function () { $(this).remove(); });
		});
	};

	/** Setup jQuery.timeago **/
	$.timeago.settings.cutoff = 365 * 24 * 60 * 60 * 1000; // Display original dates older than 1 year
	$("time").timeago();

	/** Setup tablesorter **/
	$("table.sortable").tablesorter();

	/** Setup sidebar toggle **/
	try {
		var sidebar_hidden = (Cookies.get("ui-sidebar-hidden") || "").split("|");
		for(var i in sidebar_hidden) {
			if(sidebar_hidden.hasOwnProperty(i) && sidebar_hidden[i].length > 0) {
				$(sidebar_hidden[i]+" .blockbody").hide();
			}
		}
	}
	catch(err) {
		var sidebar_hidden = [];
	}
	$(".shm-toggler").each(function(idx, elm) {
		var tid = $(elm).data("toggle-sel");
		var tob = $(tid+" .blockbody");
		$(elm).click(function(e) {
			tob.slideToggle("slow");
			if(sidebar_hidden.indexOf(tid) === -1) {
				sidebar_hidden.push(tid);
			}
			else {
				for (var i in sidebar_hidden) {
					if (sidebar_hidden[i] === tid) {
						sidebar_hidden.splice(i, 1);
					}
				}
			}
			Cookies.set("ui-sidebar-hidden", sidebar_hidden.join("|"), {expires: 365});
		});
	});

	/** setup unlocker buttons **/
	$(".shm-unlocker").each(function(idx, elm) {
		var tid = $(elm).data("unlock-sel");
		var tob = $(tid);
		$(elm).click(function(e) {
			$(elm).attr("disabled", true);
			tob.attr("disabled", false);
		});
	});

	/** setup arrow key bindings **/
    $(document).keyup(function(e) {
        if($(e.target).is('input', 'textarea')){ return; }
        if (e.metaKey || e.ctrlKey || e.altKey || e.shiftKey) { return; }
        if (e.keyCode == 37 && $("[rel='prev']").length) {
            window.location.href = $("[rel='pref']").attr("href");
        }
        else if (e.keyCode == 39 && $("[rel='next']").length) {
            window.location.href = $("[rel='next']").attr("href");
        }
    });
});
