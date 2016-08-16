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

	$(".shm-clink").each(function(idx, elm) {
		var target_id = $(elm).data("clink-sel");
		if(target_id && $(target_id).length > 0) {
			// if the target comment is already on this page, don't bother
			// switching pages
			$(elm).attr("href", target_id);
			// highlight it when clicked
			$(elm).click(function(e) {
				// This needs jQuery UI
				$(target_id).highlight();
			});
			// vanilla target name should already be in the URL tag, but this
			// will include the anon ID as displayed on screen
			$(elm).html("@"+$(target_id+" .username").html());
		}
	});

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

	$(".shm-unlocker").each(function(idx, elm) {
		var tid = $(elm).data("unlock-sel");
		var tob = $(tid);
		$(elm).click(function(e) {
			$(elm).attr("disabled", true);
			tob.attr("disabled", false);
		});
	});

	if(document.location.hash.length > 3) {
		var query = document.location.hash.substring(1);

		$('#prevlink').attr('href', function(i, attr) {
			return attr + '?' + query;
		});
		$('#nextlink').attr('href', function(i, attr) {
			return attr + '?' + query;
		});
	}

	/*
	 * If an image list has a data-query attribute, append
	 * that query string to all thumb links inside the list.
	 * This allows us to cache the same thumb for all query
	 * strings, adding the query in the browser.
	 */
	$(".shm-image-list").each(function(idx, elm) {
		var query = $(this).data("query");
		if(query) {
			$(this).find(".shm-thumb-link").each(function(idx2, elm2) {
				$(this).attr("href", $(this).attr("href") + query);
			});
		}
	});
});


/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *\
*                              LibShish-JS                                  *
\* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

function addEvent(obj, event, func, capture){
	if (obj.addEventListener){
		obj.addEventListener(event, func, capture);
	} else if (obj.attachEvent){
		obj.attachEvent("on"+event, func);
	}
}


function byId(id) {
	return document.getElementById(id);
}


// used once in ext/setup/main
function getHTTPObject() { 
	if (window.XMLHttpRequest){
		return new XMLHttpRequest();
	}
	else if(window.ActiveXObject){
		return new ActiveXObject("Microsoft.XMLHTTP");
	}
}


function replyTo(imageId, commentId, userId) {
	var box = $("#comment_on_"+imageId);
	var text = "[url=site://post/view/"+imageId+"#c"+commentId+"]@"+userId+"[/url]: ";

	box.focus();
	box.val(box.val() + text);
	$("#c"+commentId).highlight();
}
