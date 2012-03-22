$(function() {
	var blocked_tags = ($.cookie("blocked-tags") || "").split(" ");
	for(i in blocked_tags) {
		var tag = blocked_tags[i];
		if(tag) $(".thumb[data-tags~='"+tag+"']").hide();
	}
	// FIXME: need to trigger a reflow in opera, because opera implements
	// text-align: justify with element margins and doesn't recalculate
	// these margins when part of the line disappears...
});

function select_blocked_tags() {
	var blocked_tags = prompt("Enter tags to ignore", $.cookie("blocked-tags") || "My_Little_Pony");
	if(blocked_tags) {
		$.cookie("blocked-tags", blocked_tags.toLowerCase(), {path: '/'});
		location.reload(true);
	}
}
