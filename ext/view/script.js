function joinUrlSegments(base, query) {
    let  separatorChar = "?";
    if(base.includes("?")) {
        separatorChar = "&";
    }
    return base + separatorChar + query;
}

document.addEventListener('DOMContentLoaded', () => {
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
