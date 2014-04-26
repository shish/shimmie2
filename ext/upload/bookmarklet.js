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
 * jQuery should always active here, meaning we can use jQuery in this part of the bookmarklet.
 */

if(document.getElementById("post_tag_string") !== null) {
	if (typeof tag !== "ftp://ftp." && chk !==1) {
		var tag = $('#post_tag_string').text().replace(/\n/g, "");
	}
	tag = tag.replace(/\+/g, "%2B");

	var source = "http://" + document.location.hostname + document.location.href.match("\/posts\/[0-9]+");

	var rlist = $('[name="post[rating]"]');
	for( var x=0; x < 3; x++){
		var rating = (rlist[x].checked === true ? rlist[x].value : rating);
	}

	var fileinfo = $('#sidebar > section:eq(3) > ul > :contains("Size") > a');
	var furl = "http://" + document.location.hostname + fileinfo.attr('href');
	var fs = fileinfo.text().split(" ");
	var filesize = (fs[1] === "MB" ? fs[0] * 1024 : fs[0]);

	if(supext.search(furl.match("[a-zA-Z0-9]+$")[0]) !== -1){
		if(filesize <= maxsize){
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
 * konachan | sankakucomplex | gelbooru
 */
else if(document.getElementById('tag-sidebar') !== null) {
	if (typeof tag !== "ftp://ftp." && chk !==1) {
		if(document.location.href.search("sankakucomplex\\.com") >= 0 || document.location.href.search("gelbooru\\.com")){
			var tag = document.getElementById('tag-sidebar').innerText.replace(/ /g, "_").replace(/[\?_]*(.*?)_(\(\?\)_)?[0-9]+\n/g, "$1 ");
		}else{
			var tag = document.getElementById("post_tags").value;
		}
	}
	tag = tag.replace(/\+/g, "%2B");

	var source = "http://" + document.location.hostname + (document.location.href.match("\/post\/show\/[0-9]+") || encodeURIComponent(document.location.href.match(/\/index\.php\?page=post&s=view&id=[0-9]+/)));

	var rating = document.getElementById("stats").innerHTML.match("Rating: ([a-zA-Z]+)")[1];

	if(source.search("sankakucomplex\\.com") >= 0 || source.search("konachan\\.com") >= 0){
		var fileinfo = document.getElementById("highres");
		//NOTE: If highres doesn't exist, post must be flash (only sankakucomplex has flash)
	}else if(source.search("gelbooru\\.com") >= 0){
		var fileinfo = document.getElementById('pfd').parentNode.parentNode.getElementsByTagName('a')[0];
		//gelbooru has no easy way to select the original image link, so we need to double check it is the correct link.
		fileinfo = (fileinfo.getAttribute('href') === "#" ? document.getElementById('pfd').parentNode.parentNode.getElementsByTagName('a')[1] : fileinfo);
	}
	fileinfo = fileinfo || document.getElementsByTagName('embed')[0]; //If fileinfo is null then image is most likely flash.
	var furl = fileinfo.href || fileinfo.src;
	var fs = (fileinfo.innerText.match(/[0-9]+ (KB|MB)/) || ["0 KB"])[0].split(" ");
	var filesize = (fs[1] === "MB" ? fs[0] * 1024 : fs[0]);

	if(supext.search(furl.match("[a-zA-Z0-9]+$")[0]) !== -1){
		if(filesize <= maxsize){
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
			location.href = ste+img+"&tags="+tag+"&source="+source;
		}
		else{
			alert(notsup);
		}
	}
	else{
		var mov = document.location.hostname+document.getElementsByName("movie")[0].value;
		if(supext.search("swf") !== -1) {
			location.href = ste+mov+"&tags="+tag+"&source="+source;
		}
		else{
			alert(notsup);
		}
	}
}