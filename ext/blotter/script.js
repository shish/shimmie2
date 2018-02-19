/*jshint bitwise:true, curly:true, forin:false, noarg:true, noempty:true, nonew:true, undef:true, strict:false, browser:true, jquery:true */

$(document).ready(function() {
	$(".shm-blotter2-toggle").click(function() {
		$(".shm-blotter2").slideToggle("slow", function() {
			if($(".shm-blotter2").is(":hidden")) {
				Cookies.set("ui-blotter2-hidden", 'true');
			}
			else {
				Cookies.set("ui-blotter2-hidden", 'false');
			}
		});
	});
	if(Cookies.get("ui-blotter2-hidden") === 'true') {
		$(".shm-blotter2").hide();
	}
});
