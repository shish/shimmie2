$(function() {
	if(Cookies.get("ui-tnc-agreed") !== "true") {
		$("BODY").html(""+
			"<div align='center' style='font-size: 2em; position: absolute; z-index: 99999999999999999999999999;'>"+
			"<p>For legal reasons, we need to point out that:"+
			"<p>A) this site contains material not suitable for minors"+
			"<br>B) cookies may be used"+
			"<p><a href='/tnc_agreed'>Click here if you're an adult, and you're ok with that</a>"+
			"</div>"+
		"");
	}
});

function image_hash_ban(id) {
	var reason = prompt("WHY?", "DNP");
	if(reason) {
		$.post(
			"/image_hash_ban/add",
			{
				"image_id": id,
				"reason": reason,
			},
			function() {
				$("#thumb_" + id).parent().parent().hide();
			}
		);
	}
}

var navHidden = false;
function toggleNav() {
	if(navHidden) {
		$('BODY').removeClass('navHidden');
		Cookies.set("ui-shownav", "true");
	}
	else {
		$('BODY').addClass('navHidden');
		Cookies.set("ui-shownav", "false");
	}
	navHidden = !navHidden;
}

$(function() {
	if(Cookies.get("ui-shownav") === "false") {
		toggleNav();
	}
});


var forceDesktop = false;
function toggleDesktop() {
	if(forceDesktop) {
		var viewport = document.querySelector("meta[name=viewport]");
		viewport.setAttribute('content', 'width=512');
		Cookies.set("ui-desktop", "false");
	}
	else {
		var viewport = document.querySelector("meta[name=viewport]");
		viewport.setAttribute('content', 'width=1024, initial-scale=0.4');
		Cookies.set("ui-desktop", "true");
		navHidden = true;
		toggleNav();
	}
	forceDesktop = !forceDesktop;
}

$(function() {
	if(Cookies.get("ui-desktop") === "true") {
		toggleDesktop();
	}
});
