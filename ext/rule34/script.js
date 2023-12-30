let tnc_div = document.createElement('div');
tnc_div.innerHTML = `
	<div class='tnc_bg'></div>
	<div class='tnc'>
	<p>Cookies may be used. Please read our <a href='https://rule34.paheal.net/wiki/Privacy%20policy'>privacy policy</a> for more information.
	<p>By accepting to enter you agree to our <a href='https://rule34.paheal.net/wiki/rules'>rules</a> and <a href='https://rule34.paheal.net/wiki/Terms%20of%20use'>terms of service</a>.
	<p><a onclick='tnc_agree();'>Agree</a> / <a href='https://google.com'>Disagree</a>
	</div>
`;


document.addEventListener('DOMContentLoaded', () => {
	if(shm_cookie_get("ui-tnc-agreed") !== "true" && window.location.href.indexOf("/wiki/") == -1) {
		document.body.classList.add('censored');
		document.body.appendChild(tnc_div);
	}
});

function tnc_agree() {
	shm_cookie_set("ui-tnc-agreed", "true");
	document.body.classList.remove('censored');
	tnc_div.remove();
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
