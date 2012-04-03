$(function() {
	var blocked_tags = ($.cookie("ui-blocked-tags") || $.cookie("blocked-tags") || "").split(" ");
	var themecheck = $(".thumb[data-tags~='tagme']").parent().attr('class');
	var needs_refresh = false;
	for(i=0; i<blocked_tags.length; i++) {
		var tag = blocked_tags[i];
		if(tag) {
			$(".thumb[data-tags~='"+tag+"']").hide();
			if(themecheck == "thumbblock") {
				$(".thumb[data-tags~='tagme']").parent().height(0); //required for lite theme
			}
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
	var blocked_tags = prompt("Enter tags to ignore", $.cookie("ui-blocked-tags") || "My_Little_Pony");
	if(blocked_tags) {
		$.cookie("ui-blocked-tags", blocked_tags.toLowerCase(), {path: '/', expires: 365});
		location.reload(true);
	}
}
