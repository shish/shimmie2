document.addEventListener('DOMContentLoaded', () => {
	function zoom(zoom_type, save_cookie) {
		save_cookie = save_cookie === undefined ? true : save_cookie;

		var img = $('.shm-click-to-scale');

		/* get dimensions for the image when zoomed out to fit the screen */
		zoom_height = Math.min(window.innerHeight * 0.8, img.data('height'));
		// check max width of parent element when the image isn't extending it
		img.css('display', 'none');
		zoom_width = Math.min(img.parent().width(), img.data('width'));
		img.css('display', '');
		// keep the image in ratio
		if(zoom_width / zoom_height > img.data('width') / img.data('height')) {
			zoom_width = img.data('width') * (zoom_height / img.data('height'))
		} else {
			zoom_height = img.data('height') * (zoom_width / img.data('width'))
		}

		if(zoom_type === "full") {
			img.attr('width', img.data('width'));
			img.attr('height', img.data('height'));
		}
		if(zoom_type === "width") {
			img.attr('width', zoom_width);
			img.attr('height', img.data('height'));
		}
		if(zoom_type === "height") {
			img.attr('width', img.data('width'));
			img.attr('height', zoom_height);
		}
		if(zoom_type === "both") {
			img.attr('width', zoom_width);
			img.attr('height', zoom_height);
		}

		const zoom_height_diff = Math.round(zoom_height - img.data('height'));
		const zoom_width_diff = Math.round(zoom_width - img.data('width'));

		if (zoom_height_diff == 0 && zoom_width_diff == 0) {
			img.css('cursor', '');
		} else if (zoom_type == "full") {
			img.css('cursor', 'zoom-out');
		} else {
			img.css('cursor', 'zoom-in');
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
