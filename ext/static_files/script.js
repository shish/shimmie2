document.addEventListener('DOMContentLoaded', () => {
	/** Load jQuery extensions **/
	//Code via: https://stackoverflow.com/a/13106698
	$.fn.highlight = function (fadeOut) {
		fadeOut = typeof fadeOut !== 'undefined' ? fadeOut : 5000;
		$(this).each(function () {
			let el = $(this);
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

	/** Setup sidebar toggle **/
	let sidebar_hidden = [];
	try {
		sidebar_hidden = (shm_cookie_get("ui-sidebar-hidden") || "").split("|");
        for (let i=0; i<sidebar_hidden.length; i++) {
			if(sidebar_hidden[i].length > 0) {
				$(sidebar_hidden[i]+" .blockbody").hide();
			}
		}
	}
	catch(err) {}
	$(".shm-toggler").each(function(idx, elm) {
		let tid = $(elm).data("toggle-sel");
		let tob = $(tid+" .blockbody");
		$(elm).click(function(e) {
			tob.slideToggle("slow");
			if(sidebar_hidden.indexOf(tid) === -1) {
				sidebar_hidden.push(tid);
			}
			else {
			    for (let i=0; i<sidebar_hidden.length; i++) {
					if (sidebar_hidden[i] === tid) {
						sidebar_hidden.splice(i, 1);
					}
				}
			}
			shm_cookie_set("ui-sidebar-hidden", sidebar_hidden.join("|"));
		});
	});

	/** setup unlocker buttons **/
	$(".shm-unlocker").each(function(idx, elm) {
		let tid = $(elm).data("unlock-sel");
		let tob = $(tid);
		$(elm).click(function(e) {
			$(elm).attr("disabled", true);
			tob.attr("disabled", false);
		});
	});

	/** setup copyable things */
	$(".shm-clicktocopy").each(function(idx, elm) {
		$(elm).click(function(e) {
			navigator.clipboard.writeText($(elm).text());
		});
	});

	/** setup arrow key bindings **/
    document.addEventListener("keyup", function(e) {
        if ($(e.target).is('input,textarea')) { return; }
        if (e.metaKey || e.ctrlKey || e.altKey || e.shiftKey) { return; }
        if (e.keyCode === 37 && $("[rel='previous']").length) {
            window.location.href = $("[rel='previous']").attr("href");
        }
        else if (e.keyCode === 39 && $("[rel='next']").length) {
            window.location.href = $("[rel='next']").attr("href");
        }
    });
});
