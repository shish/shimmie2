$(function() {
	function zoom(zoom_type) {
		var img = $('.shm-main-image');
		
		if(zoom_type == "full") {
			img.css('max-width', img.data('width') + 'px');
			img.css('max-height', img.data('height') + 'px');
		}
		if(zoom_type == "width") {
			img.css('max-width', '95%');
			img.css('max-height', img.data('height') + 'px');
		}
		if(zoom_type == "height") {
			img.css('max-width', img.data('width') + 'px');
			img.css('max-height', (window.innerHeight * 0.95) + 'px');
		}
		if(zoom_type == "both") {
			img.css('max-width', '95%');
			img.css('max-height', (window.innerHeight * 0.95) + 'px');
		}
		
		$(".shm-zoomer").val(zoom_type);
		
		$.cookie("ui-image-zoom", zoom_type, {path: '/', expires: 365});
	}

	$(".shm-zoomer").change(function(e) {
		zoom(this.options[this.selectedIndex].value);
	});

	$(".shm-main-image").click(function(e) {
		switch($.cookie("ui-image-zoom")) {
			case "full": zoom("width"); break;
			default: zoom("full"); break;
		}
	});

	if($.cookie("ui-image-zoom")) {
		zoom($.cookie("ui-image-zoom"));
	}
});
