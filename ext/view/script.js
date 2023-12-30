function joinUrlSegments(base, query) {
    let  separatorChar = "?";
    if(base.includes("?")) {
        separatorChar = "&";
    }
    return base + separatorChar + query;
}

function clearViewMode() {
	document.querySelectorAll('.image_info').forEach((element) => {
		element.classList.remove('infomode-view');
	});
}

document.addEventListener('DOMContentLoaded', () => {
	// find elements with class image_info and set them to view mode
	// (by default, with no js, they are in edit mode - so that no-js
	// users can still edit them)
	document.querySelectorAll('.image_info').forEach((element) => {
		element.classList.add('infomode-view');
	});

	if(document.location.hash.length > 3) {
		var query = document.location.hash.substring(1);

		$('LINK#prevlink').attr('href', function(i, attr) {
			return joinUrlSegments(attr,query);
		});
		$('LINK#nextlink').attr('href', function(i, attr) {
            return joinUrlSegments(attr,query);
		});
		$('A#prevlink').attr('href', function(i, attr) {
            return joinUrlSegments(attr,query);
		});
		$('A#nextlink').attr('href', function(i, attr) {
            return joinUrlSegments(attr,query);
		});
        $('span#image_delete_form form').attr('action', function(i, attr) {
            return joinUrlSegments(attr,query);
        });
	}
});
