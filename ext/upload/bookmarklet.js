/* Imageboard to Shimmie */
// This should work with "most" sites running Danbooru/Gelbooru/Shimmie
// TODO: Make this use jQuery! (if we can be sure that jquery is loaded)
// maxsize, supext, CA are set inside the bookmarklet (see theme.php)

var maxsize = (maxsize.match("(?:\.*[0-9])")) * 1024; // This assumes we are only working with MB.
var toobig = "The file you are trying to upload is too big to upload!";
var notsup = "The file you are trying to upload is not supported!";

if(CA === 0 || CA > 2) { // Default
	if(confirm("Keep existing tags?\n(Cancel will prompt for new tags)")) {
		// Do nothing
	}
	else {
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
 * Danbooru (oreno.imouto | konachan | sankakucomplex)
 */
if(document.getElementById("post_tags") !== null) {
	if (typeof tag !== "ftp://ftp." && chk !==1) {
		var tag = document.getElementById("post_tags").value;
	}
	tag = tag.replace(/\+/g, "%2B"); // This should stop + not showing in tags :x

	var source = "http://" + document.location.hostname + document.location.href.match("\/post\/show\/[0-9]+");
	if(source.search("oreno\\.imouto") >= 0 || source.search("konachan\\.com") >= 0) {
		var rating = document.getElementById("stats").innerHTML.match("<li>Rating: (.*) <span")[1];
	}
	else {
		var rating = document.getElementById("stats").innerHTML.match("<li>Rating: (.*)<\/li>")[1];
	}

	if(tag.search(/\bflash\b/)===-1){
		var highres_url = document.getElementById("highres").href;
		if(source.search("oreno\\.imouto") >= 0 || source.search("konachan\\.com") >= 0){ // oreno's theme seems to have moved the filesize
			var filesize = document.getElementById("highres").innerHTML.match("[a-zA-Z0-9]+ \\(+([0-9]+\\.[0-9]+) ([a-zA-Z]+)");
		}else{
			var filesize = document.getElementById("stats").innerHTML.match("[0-9] \\(((?:\.*[0-9])) ([a-zA-Z]+)");
		}
		if(filesize[2] == "MB") {
			var filesize = filesize[1] * 1024;
		}
		else {
			var filesize = filesize[2].match("[0-9]+");
		}

		if(supext.search(highres_url.match("http\:\/\/.*\\.([a-z0-9]+)")[1]) !== -1) {
			if(filesize <= maxsize) {
				if(source.search("oreno\\.imouto") >= 0) {
					// this regex tends to be a bit picky with tags -_-;;
					var highres_url = highres_url.match("(http\:\/\/[a-z0-9]+\.[a-z]+\.[a-z]\/[a-z0-9]+\/[a-z0-9]+)\/[a-z0-9A-Z%_-]+(\.[a-zA-Z0-9]+)");
					var highres_url = highres_url[1]+highres_url[2]; // this should bypass hotlink protection
				}
				else if(source.search("konachan\\.com") >= 0) {
					// konachan affixs konachan.com to the start of the tags, this requires different regex
					var highres_url = highres_url.match("(http\:\/\/[a-z0-9]+\.[a-z]+\.[a-z]\/[a-z0-9]+\/[a-z0-9]+)\/[a-z0-9A-Z%_]+\.[a-zA-Z0-9%_-]+(\.[a-z0-9A-Z]+)")
					var highres_url = highres_url[1]+highres_url[2];
				}
				location.href = ste+highres_url+"&tags="+tag+"&rating="+rating+"&source="+source;
			}
			else{
				alert(toobig);
			}
		}
		else{
			alert(notsup);
		}
	}
	else {
		if(supext.search("swf") !== -1) {
			location.href = ste+document.getElementsByName("movie")[0].value+"&tags="+tag+"&rating="+rating+"&source="+source;
		}
		else{
			alert(notsup);
		}
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
else if(document.getElementsByTagName("title")[0].innerHTML.search("Image [0-9.-]+\: ") == 0) {
	if(typeof tag !=="ftp://ftp." && chk !==1) {
		var tag = document.getElementsByTagName("title")[0].innerHTML.match("Image [0-9.-]+\: (.*)")[1];
	}

	// TODO: Make rating show in statistics.
	var source = "http://" + document.location.hostname + document.location.href.match("\/post\/view\/[0-9]+");

	// TODO: Make file size show on all themes
	// (Only seems to show in lite/Danbooru themes.)
	if(tag.search(/\bflash\b/) == -1) {
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

/*
 * Gelbooru
 */
else if(document.getElementById("tags") !== null) {
	if (typeof tag !=="ftp://ftp." && chk !==1) {
		var tag = document.getElementById("tags").value;
	}

	var rating = document.getElementById("stats").innerHTML.match("<li>Rating: (.*)<\/li>")[1];

	// Can't seem to grab source due to url containing a &
	// var source="http://" + document.location.hostname + document.location.href.match("\/index\.php?page=post&amp;s=view\\&amp;id=.*");
	
	// Updated Nov. 24, 2013 by jgen.
	var gmi;	
	try {
		gmi = document.getElementById("image").src.match(".*img[0-9]*\.gelbooru\.com[\/]+images[\/]+[0-9]+[\/]+[a-z0-9]+\.[a-z0-9]+")[0];
		
		// Since Gelbooru does not allow flash, no need to search for flash tag.
		// Gelbooru doesn't show file size in statistics either...
		if(supext.search(gmi.match("http\:\/\/.*\\.([a-z0-9]+)")[1]) !== -1){
			location.href = ste+gmi+"&tags="+tag+"&rating="+rating;//+"&source="+source;
		}
		else{
			alert(notsup);
		}
	}
	catch (err)
	{
		alert("Unable to locate the image on the page!\n(Gelbooru may have changed the structure of their page, please file a bug.)");
	}
}
