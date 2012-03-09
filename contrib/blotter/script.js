
$(document).ready(function() {
	$("#blotter2-toggle").click(function() {
		$("#blotter2").slideToggle("slow", function() {
			if($("#blotter2").is(":hidden")) {
				$.cookie("blotter2-hidden", 'true', {path: '/'});
			}
			else {
				$.cookie("blotter2-hidden", 'false', {path: '/'});
			}
		});
	});
	if($.cookie("blotter2-hidden") == 'true') {
		$("#blotter2").hide();
	}
});
