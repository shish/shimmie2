document.addEventListener('DOMContentLoaded', () => {
	function zoom(zoom_type, save_cookie) {
		save_cookie = save_cookie === undefined ? true : save_cookie;

		var img = $('.shm-main-image');

		if(zoom_type === "full") {
			img.css('max-width', img.data('width') + 'px');
			img.css('max-height', img.data('height') + 'px');
		}
		if(zoom_type === "width") {
			img.css('max-width', '95%');
			img.css('max-height', img.data('height') + 'px');
		}
		if(zoom_type === "height") {
			img.css('max-width', img.data('width') + 'px');
			img.css('max-height', (window.innerHeight * 0.95) + 'px');
		}
		if(zoom_type === "both") {
			img.css('max-width', '95%');
			img.css('max-height', (window.innerHeight * 0.95) + 'px');
		}

		$(".shm-zoomer").val(zoom_type);

		if (save_cookie) {
			shm_cookie_set("ui-image-zoom", zoom_type);
		}
	}

	$(".shm-zoomer").change(function(e) {
		zoom(this.options[this.selectedIndex].value);
	});
	$(window).resize(function(e) {
		$(".shm-zoomer").each(function (e) {
			zoom(this.options[this.selectedIndex].value, false)
		});
	});

	$("img.shm-main-image").click(function(e) {
		var val = $(".shm-zoomer")[0].value;
		var cookie = shm_cookie_get("ui-image-zoom");

		if (val == "full" && cookie == "full"){
			zoom("both", false);
		}
		else if (val != "full"){
			zoom("full", false);
		}
		else {
			zoom(cookie, false);
		}
	});

	if(shm_cookie_get("ui-image-zoom")) {
		zoom(shm_cookie_get("ui-image-zoom"));
	}
	else {
		zoom("both");
	}
});
