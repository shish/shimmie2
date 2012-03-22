$(function() {
	var blocked_tags = ($.cookie("blocked-tags") || "").split(" ");
	var needs_refresh = false;
	for(i in blocked_tags) {
		var tag = blocked_tags[i];
		if(tag) {
			$(".thumb[data-tags~='"+tag+"']").hide();
			needs_refresh = true;
		}
	}
	// need to trigger a reflow in opera, because opera implements
	// text-align: justify with element margins and doesn't recalculate
	// these margins when part of the line disappears...
	if(needs_refresh) {
		$('#image-list').hide();
		$('#image-list').show();
	}
});

function select_blocked_tags() {
	var blocked_tags = prompt("Enter tags to ignore", $.cookie("blocked-tags") || "My_Little_Pony");
	if(blocked_tags) {
		$.cookie("blocked-tags", blocked_tags.toLowerCase(), {path: '/', expires: 365});
		location.reload(true);
	}
}
