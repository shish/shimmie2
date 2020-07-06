document.addEventListener('DOMContentLoaded', () => {
	if(Cookies.get("ui-tnc-agreed") !== "true") {
		$("BODY").addClass("censored");
		$("BODY").append("<div class='tnc_bg'></div>");
		$("BODY").append(""+
			"<div class='tnc'>"+
			"<p>For legal reasons, we need to point out that:"+
			"<p>A) this site contains material not suitable for minors"+
			"<br>B) cookies may be used"+
			"<p><a onclick='tnc_agree();'>Click here if you're an adult, and you're ok with that</a>"+
			"</div>"+
		"");
	}
});

function tnc_agree() {
	Cookies.set("ui-tnc-agreed", "true", {path: '/', expires: 365});
	$("BODY").removeClass("censored");
	$(".tnc_bg").hide();
	$(".tnc").hide();
}

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

document.addEventListener('DOMContentLoaded', () => {
	if(Cookies.get("ui-shownav") === "false") {
		toggleNav();
	}
});


var forceDesktop = false;
function toggleDesktop() {
	if(forceDesktop) {
		let viewport = document.querySelector("meta[name=viewport]");
		viewport.setAttribute('content', 'width=512');
		Cookies.set("ui-desktop", "false");
	}
	else {
		let viewport = document.querySelector("meta[name=viewport]");
		viewport.setAttribute('content', 'width=1024, initial-scale=0.4');
		Cookies.set("ui-desktop", "true");
		navHidden = true;
		toggleNav();
	}
	forceDesktop = !forceDesktop;
}

document.addEventListener('DOMContentLoaded', () => {
	if(Cookies.get("ui-desktop") === "true") {
		toggleDesktop();
	}
});
