document.addEventListener('DOMContentLoaded', () => {
	if(Cookies.get("ui-tnc-agreed") !== "true" && window.location.href.indexOf("/wiki/") == -1) {
		$("BODY").addClass("censored");
		$("BODY").append("<div class='tnc_bg'></div>");
		$("BODY").append(""+
			"<div class='tnc'>"+
			"<p>Cookies may be used. Please read our <a href='https://rule34.paheal.net/wiki/Privacy%20policy'>privacy policy</a> for more information."+
			"<p>By accepting to enter you agree to our <a href='https://rule34.paheal.net/wiki/rules'>rules</a> and <a href='https://rule34.paheal.net/wiki/Terms%20of%20use'>terms of service</a>."+
			"<p><a onclick='tnc_agree();'>Agree</a> / <a href='https://google.com'>Disagree</a>"+
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
