$(function() {
	$("#zoomer").change(function(e) {
		zoom(this.options[this.selectedIndex].value);
		$.cookie("ui-image-zoom", this.options[this.selectedIndex].value, {path: '/', expires: 365});
	});

	if($.cookie("ui-image-zoom")) {
		$("#zoomer").val($.cookie("ui-image-zoom"));
		zoom($.cookie("ui-image-zoom"));
	}
});

function zoom(zoom) {
	var img = $('#main_image');
	if(zoom == "full") {
		img.css('max-width', img.data('width') + 'px');
		img.css('max-height', img.data('height') + 'px');
	}
	if(zoom == "width") {
		img.css('max-width', '90%');
		img.css('max-height', img.data('height') + 'px');
	}
	if(zoom == "height") {
		img.css('max-width', img.data('width') + 'px');
		img.css('max-height', (window.innerHeight * 0.9) + 'px');
	}
	if(zoom == "both") {
		img.css('max-width', '90%');
		img.css('max-height', (window.innerHeight * 0.9) + 'px');
	}
}
