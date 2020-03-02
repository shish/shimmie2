document.addEventListener('DOMContentLoaded', () => {
	$(".shm-clink").each(function(idx, elm) {
		var target_id = $(elm).data("clink-sel");
		if(target_id && $(target_id).length > 0) {
			// if the target comment is already on this page, don't bother
			// switching pages
			$(elm).attr("href", target_id);
			// highlight it when clicked
			$(elm).click(function(e) {
				// This needs jQuery UI
				$(target_id).highlight();
			});
			// vanilla target name should already be in the URL tag, but this
			// will include the anon ID as displayed on screen
			$(elm).html("@"+$(target_id+" .username").html());
		}
	});
});
