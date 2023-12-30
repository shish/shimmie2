/*jshint bitwise:true, curly:true, devel:true, forin:false, noarg:true, undef:true, strict:false, browser:true, jquery:true */

/* Imageboard to Shimmie */
// This should work with "most" sites running Danbooru/Gelbooru/Shimmie
// maxsize, supext, CA are set inside the bookmarklet (see theme.php)

var maxsize = (maxsize.match("(?:\.*[0-9])")) * 1024; // This assumes we are only working with MB.
var toobig = "The file you are trying to upload is too big to upload!";
var notsup = "The file you are trying to upload is not supported!";

if(CA === 0 || CA > 2) { // Default
	if (confirm("Keep existing tags?\n(Cancel will prompt for new tags)") === false) {
		var tag = prompt("Enter Tags", "");
		var chk = 1; // This makes sure it doesn't use current tags.
	}
}
else if(CA === 1) { // Current Tags
	// Do nothing
}
else if(CA === 2) { // New Tags
	var tag = prompt("Enter Tags", "");
	var chk = 1;
}



/*
 * Danbooru2
 */

if(document.getElementById("image-container") !== null) {
	var imageContainer = document.querySelector('#image-container');
	if (typeof tag !== "ftp://ftp." && chk !==1) {
		var tag = imageContainer.getAttribute('data-tags');
	}
	tag = tag.replace(/\+/g, "%2B");

	var source = "http://" + document.location.hostname + document.location.href.match("\/posts\/[0-9]+");

	var rating = imageContainer.getAttribute('data-rating');

	var fileinfo = document.querySelector('#sidebar > section:eq(3) > ul > :contains("Size") > a');
	var furl = "http://" + document.location.hostname + fileinfo.getAttribute('href');
	var fs = fileinfo.innerText.split(" ");
	var filesize = (fs[1] === "MB" ? fs[0] * 1024 : fs[0]);

	if(supext.search(furl.match("[a-zA-Z0-9]+$")[0]) !== -1){
		if(filesize <= maxsize){
			history.pushState(history.state, document.title, location.href);
			location.href = ste+furl+"&tags="+tag+"&rating="+rating+"&source="+source;
		}
		else{
			alert(toobig);
		}
	}
	else{
		alert(notsup);
	}
}

/*
 * konachan | sankakucomplex | gelbooru (old versions) | etc.
 */
 else if(document.getElementById('tag-sidebar') !== null) {
	if (typeof tag !== "ftp://ftp." && chk !==1) {
		var tag = document.getElementById('tag-sidebar').innerText.replace(/ /g, "_").replace(/[\?_]*(.*?)_(\(\?\)_)?[0-9]+$/gm, "$1 ");
	}
	tag = tag.replace(/\+/g, "%2B");

	var source = "http://" + document.location.hostname + (document.location.href.match("\/post\/show\/[0-9]+") || encodeURIComponent(document.location.href.match(/\/index\.php\?page=post&s=view&id=[0-9]+/)));

	var rating = document.getElementById("stats").innerHTML.match("Rating: ([a-zA-Z]+)")[1];

	if(document.getElementById('highres') !== null) {
		var fileinfo = document.getElementById("highres");
	}else if(document.getElementById('pfd') !== null){
		// Try to find the "Original image" link in the options sidebar.
		var fileinfo;
		var nodes = document.getElementById('pfd').parentNode.parentNode.getElementsByTagName('a');
		for (var i = 0; i < nodes.length; i++) {
			var href = nodes[i].getAttribute('href');
			if (href === "#" || href === "javascript:;")
				continue;
			fileinfo = nodes[i];
			break;
		}
	}
	fileinfo = fileinfo || document.getElementsByTagName('embed')[0]; //If fileinfo is null then assume that the image is flash.
	var furl = fileinfo.href || fileinfo.src;
	furl = furl.split('?')[0]; // Remove trailing variables, if present.
	var fs = (fileinfo.innerText.match(/[0-9]+ (KB|MB)/) || ["0 KB"])[0].split(" ");
	var filesize = (fs[1] === "MB" ? fs[0] * 1024 : fs[0]);

	if(supext.search(furl.match("[a-zA-Z0-9]+$")[0]) !== -1){
		if(filesize <= maxsize){
			history.pushState(history.state, document.title, location.href);
			location.href = ste+furl+"&tags="+tag+"&rating="+rating+"&source="+source;
		}
		else{
			alert(toobig);
		}
	}
	else{
		alert(notsup);
	}
}

/*
 * gelbooru
 */
 else if(document.getElementById('tag-list') !== null){
	if(typeof tag !== "ftp://ftp." && chk !==1){
		var tags = [];
		$('#tag-list h3:contains("Tags")').nextUntil(":not(li)").each(function(index){
			tags.push($(this).text()
				.replace(/ /g, "_")
				.replace(/[\?_]*(.*?)_(\(\?\)_)?[0-9]+$/gm, "$1"));
		});
		tag = tags.join(" ");
	}
	var source = "http://" + document.location.hostname + (document.location.href.match("\/post\/show\/[0-9]+") || document.location.href.match(/\/index\.php\?page=post&s=view&id=[0-9]+/));
	var rating =
		$('#tag-list h3:contains("Statistics")').nextUntil(":not(li)")
		.filter(':contains("Rating")')
		.text().match("Rating: ([a-zA-Z]+)")[1];
	var furl =
		$('#tag-list h3:contains("Options")').nextUntil(":not(li)")
		.filter(':contains("Original image")').find("a").first()[0].href;
	// File size is not supported because it's not provided.

	if(supext.search(furl.match("[a-zA-Z0-9]+$")[0]) !== -1){
		history.pushState(history.state, document.title, location.href);
        location.href = ste + furl +
			"&tags=" + encodeURIComponent(tag) +
			"&rating=" + encodeURIComponent(rating) +
			"&source=" + encodeURIComponent(source);
	}
	else{
		alert(notsup);
	}
}

/*
 * Shimmie
 *
 * One problem with shimmie is each theme does not show the same info
 * as other themes (I.E only the danbooru & lite themes show statistics)
 * Shimmie doesn't seem to have any way to grab tags via id unless you
 * have the ability to edit tags.
 *
 * Have to go the round about way of checking the title for tags.
 * This crazy way of checking "should" work with older releases though
 * (Seems to work with 2009~ ver)
 */
else if(document.getElementsByTagName("title")[0].innerHTML.search("Image [0-9.-]+\: ") === 0) {
	if(typeof tag !== "ftp://ftp." && chk !==1) {
		var tag = document.getElementsByTagName("title")[0].innerHTML.match("Image [0-9.-]+\: (.*)")[1];
	}

	// TODO: Make rating show in statistics.
	var source = "http://" + document.location.hostname + document.location.href.match("\/post\/view\/[0-9]+");

	// TODO: Make file size show on all themes
	// (Only seems to show in lite/Danbooru themes.)
	if(tag.search(/\bflash\b/) === -1) {
		var img = document.getElementById("main_image").src;
		if(supext.search(img.match(".*\\.([a-z0-9]+)")[1]) !== -1) {
			history.pushState(history.state, document.title, location.href);
			location.href = ste+img+"&tags="+tag+"&source="+source;
		}
		else{
			alert(notsup);
		}
	}
	else{
		var mov = document.location.hostname+document.getElementsByName("movie")[0].value;
		if(supext.search("swf") !== -1) {
			history.pushState(history.state, document.title, location.href);
			location.href = ste+mov+"&tags="+tag+"&source="+source;
		}
		else{
			alert(notsup);
		}
	}
}
