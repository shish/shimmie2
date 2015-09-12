/*jshint bitwise:false, curly:true, eqeqeq:true, evil:true, forin:false, noarg:true, noempty:true, nonew:true, undef:false, strict:false, browser:true, jquery:true */

$(function() {
	var blocked_tags = ($.cookie("ui-blocked-tags") || "").split(" ");
	var needs_refresh = false;
	for(var i=0; i<blocked_tags.length; i++) {
		var tag = blocked_tags[i];
		if(tag) {
			$(".shm-thumb[data-tags~='"+tag+"']").hide();
			needs_refresh = true;
		}
	}
	// need to trigger a reflow in opera, because opera implements
	// text-align: justify with element margins and doesn't recalculate
	// these margins when part of the line disappears...
	if(needs_refresh) {
		$('.shm-image-list').hide(
			0,
			function() {$('.shm-image-list').show();}
		);
	}

	//Generate a random seed when using order:random
	$('form > input[placeholder="Search"]').parent().submit(function(e){
		var input = $('form > input[placeholder="Search"]');
		var tagArr = input.val().split(" ");

		var rand = (($.inArray("order:random", tagArr) + 1) || ($.inArray("order=random", tagArr) + 1)) - 1;
		if(rand !== -1){
			tagArr[rand] = "order:random_"+Math.floor((Math.random()*9999)+1);
			input.val(tagArr.join(" "));
		}
	});
});

function select_blocked_tags() {
	var blocked_tags = prompt("Enter tags to ignore", $.cookie("ui-blocked-tags") || "My_Little_Pony");
	if(blocked_tags !== null) {
		$.cookie("ui-blocked-tags", blocked_tags.toLowerCase(), {path: '/', expires: 365});
		location.reload(true);
	}
}
