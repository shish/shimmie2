/*jshint bitwise:false, curly:true, eqeqeq:true, evil:true, forin:false, noarg:true, noempty:true, nonew:true, undef:false, strict:false, browser:true, jquery:true */

document.addEventListener('DOMContentLoaded', () => {
	let blocked_tags = (shm_cookie_get("ui-blocked-tags") || "").split(" ");
	let blocked_css = blocked_tags
		.filter(tag => tag.length > 0)
		.map(tag => tag.replace(/\\/g, "\\\\").replace(/"/g, "\\\""))
		.map(tag => `.shm-thumb[data-tags~="${tag}"]`).join(", ");
	if(blocked_css) {
		let style = document.createElement("style");
		style.innerHTML = blocked_css + " { display: none; }";
		document.head.appendChild(style);
	}

	//Generate a random seed when using order:random
	$('form > input[placeholder="Search"]').parent().submit(function(e){
		var input = $('form > input[placeholder="Search"]');
		var tagArr = input.val().split(" ");

		var rand = (($.inArray("order:random", tagArr) + 1) || ($.inArray("order=random", tagArr) + 1)) - 1;
		if(rand !== -1){
			tagArr[rand] = "order:random_"+Math.floor((Math.random()*9999)+1);
			input.val(tagArr.join(" "));
		}
	});

	/*
	 * If an image list has a data-query attribute, append
	 * that query string to all thumb links inside the list.
	 * This allows us to cache the same thumb for all query
	 * strings, adding the query in the browser.
	 */
	document.querySelectorAll(".shm-image-list").forEach(function(list) {
		var query = list.getAttribute("data-query");
		if(query) {
			list.querySelectorAll(".shm-thumb-link").forEach(function(thumb) {
				thumb.setAttribute("href", thumb.getAttribute("href") + query);
			});
		}
	});
});

function select_blocked_tags() {
	var blocked_tags = prompt("Enter tags to ignore", shm_cookie_get("ui-blocked-tags") || "AI-generated");
	if(blocked_tags !== null) {
		shm_cookie_set("ui-blocked-tags", blocked_tags.toLowerCase());
		location.reload(true);
	}
}
