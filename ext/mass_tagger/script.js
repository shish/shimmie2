/*jshint bitwise:true, curly:true, forin:false, noarg:true, noempty:true, nonew:true, undef:true, strict:false, browser:true, jquery:true */

function activate_mass_tagger ( image_link ) {
    $(".shm-thumb").each(
        function ( index, block ) {
            add_mass_tag_button( $(block), image_link );
        }
    );
    $('#mass_tagger_controls').show();
    $('#mass_tagger_activate').hide();
}

function add_mass_tag_button($block, image_link) {
	
    var c = function() { toggle_tag(this, $block.data("post-id")); return false; };

    $block.find("A").click(c);
    $block.click(c); // sometimes the thumbs *is* the A
}

function toggle_tag( button, id ) {
    id += ":";
    var list = $('#mass_tagger_ids');
    var string = list.val();
    
    if( (string.indexOf(id) == 0) || (string.indexOf(":"+id) > -1) ) {
		$(button).removeClass('mass-tagger-selected');
		string = string.replace(id, '');
		list.val(string);
	}
	else {
		$(button).addClass('mass-tagger-selected');
		string += id;
		list.val(string);
	}
}

$(function () {
	// Clear the selection, in case it was autocompleted by the browser.
	$('#mass_tagger_ids').val("");
});
