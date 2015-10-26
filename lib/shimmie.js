/*jshint bitwise:false, curly:true, eqeqeq:true, evil:true, forin:false, noarg:true, noempty:true, nonew:true, undef:false, strict:false, browser:true */

// Adding jQuery ui stuff
$(document).ready(function() {
	var dayMS = 1000 * 60 * 60 * 24;
	jQuery.timeago.settings.cutoff = 365 * dayMS;
	$("time").timeago();

	//TODO: Possibly move to using TextExtJS for autocomplete? - http://textextjs.com/
	//      Also use autocomplete in tag box?
	$('.autocomplete_tags').autocomplete(base_href + '/api/internal/tag_list/complete', {
		//extraParams: {limit: 10},
		queryParamName: 's',
		minChars: 1,
		delay: 0,
		useCache: true,
		maxCacheLength: 10,
		matchInside: false,
		selectFirst: false,
		selectOnly: false,
		preventDefaultReturn: 1,
		preventDefaultTab: 1,
		useDelimiter: true,
		delimiterChar : " ",
		delimiterKeyCode : 48
	});

	$("TABLE.sortable").tablesorter();

	$(".shm-clink").each(function(idx, elm) {
		var target_id = $(elm).data("clink-sel");
		if(target_id && $(target_id).length > 0) {
			// if the target comment is already on this page, don't bother
			// switching pages
			$(elm).attr("href", target_id);
			// highlight it when clicked
			$(elm).click(function(e) {
				// This needs jQuery UI
				$(target_id).effect('highlight', {}, 5000);
			});
			// vanilla target name should already be in the URL tag, but this
			// will include the anon ID as displayed on screen
			$(elm).html("@"+$(target_id+" .username").html());
		}
	});

	try {
		var sidebar_hidden = ($.cookie("ui-sidebar-hidden") || "").split("|");
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
			$.cookie("ui-sidebar-hidden", sidebar_hidden.join("|"), {path: '/', expires: 365});
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
		query = document.location.hash.substring(1);
		a = document.getElementById("prevlink");
		a.href = a.href + '?' + query;
		a = document.getElementById("nextlink");
		a.href = a.href + '?' + query;
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


/* get, set, and delete cookies */
function getCookie( name ) {
	var start = document.cookie.indexOf( name + "=" );
	var len = start + name.length + 1;
	if ( ( !start ) && ( name !== document.cookie.substring( 0, name.length ) ) ) {
		return null;
	}
	if ( start === -1 ) { return null; }
	var end = document.cookie.indexOf( ";", len );
	if ( end === -1 ) { end = document.cookie.length; }
	return unescape( document.cookie.substring( len, end ) );
}
	
function setCookie( name, value, expires, path, domain, secure ) {
	var today = new Date();
	today.setTime( today.getTime() );
	if ( expires ) {
		expires = expires * 1000 * 60 * 60 * 24;
	}
	var expires_date = new Date( today.getTime() + (expires) );
	document.cookie = name+"="+escape( value ) +
		( ( expires ) ? ";expires="+expires_date.toGMTString() : "" ) + //expires.toGMTString()
		( ( path ) ? ";path=" + path : "" ) +
		( ( domain ) ? ";domain=" + domain : "" ) +
		( ( secure ) ? ";secure" : "" );
}
	
function deleteCookie( name, path, domain ) {
	if ( getCookie( name ) ) { document.cookie = name + "=" +
			( ( path ) ? ";path=" + path : "") +
			( ( domain ) ? ";domain=" + domain : "" ) +
			";expires=Thu, 01-Jan-1970 00:00:01 GMT";
	}
}

function replyTo(imageId, commentId, userId) {
	var box = $("#comment_on_"+imageId);
	var text = "[url=site://post/view/"+imageId+"#c"+commentId+"]@"+userId+"[/url]: ";

	box.focus();
	box.val(box.val() + text);
	$("#c"+commentId).effect("highlight", {}, 5000);
}
