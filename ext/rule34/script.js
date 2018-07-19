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
			
		);
	}
}

var navHidden = false;
function toggleNav() {
	if(navHidden) {
		$('NAV').show();
		$('#header').show();
		$('ARTICLE').css('margin-left', '276px');
	}
	else {
		$('NAV').hide();
		$('#header').hide();
		$('ARTICLE').css('margin-left', '0px');
	}
	navHidden = !navHidden;
}
