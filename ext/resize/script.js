document.addEventListener('DOMContentLoaded', () => {
	var original_width = $("#original_width").val();
	var original_height = $("#original_height").val();

	$("#resize_width").change(function() {
		if($("#resize_aspect").prop("checked")) {
			$("#resize_height").val(
				Math.round($("#resize_width").val() / original_width * original_height)
			);
		}
	});
	$("#resize_height").change(function() {
		if($("#resize_aspect").prop("checked")) {
			$("#resize_width").val(
				Math.round($("#resize_height").val() / original_height * original_width)
			);
		}
	});
});
