function joinUrlSegments(base, query) {
    let  separatorChar = "?";
    if(base.includes("?")) {
        separatorChar = "&";
    }
    return base + separatorChar + query;
}

document.addEventListener('DOMContentLoaded', () => {
	function updateAttr(selector, attr, value) {
		document.querySelectorAll(selector).forEach(function(e) {
			let current = e.getAttribute(attr);
			let newval = joinUrlSegments(current, query);
			e.setAttribute(attr, newval);
		});
	}

	if(document.location.hash.length > 3) {
		var query = document.location.hash.substring(1);

		updateAttr("LINK#prevlink", "href", query);
		updateAttr("LINK#nextlink", "href", query);
		updateAttr("A#prevlink", "href", query);
		updateAttr("A#nextlink", "href", query);
		updateAttr("span#image_delete_form form", "action", query);
	}
});
