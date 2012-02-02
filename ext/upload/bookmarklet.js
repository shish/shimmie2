/* Imageboard to Shimmie */
// This should work with "most" sites running Danbooru/Gelbooru/Shimmie

var maxsze = (maxsze.match("(?:\.*[0-9])")) * 1024; //This assumes we are only working with MB.
var toobig = "The file you are trying to upload is too big to upload!";
var notsup = "The file you are trying to upload is not supported!";
if (CA === 0 || CA > 2){ //Default
	if (confirm("OK = Use Current tags.\nCancel = Use new tags.")==true){
	}else{
		var tag=prompt("Enter Tags","");
		var chk=1; //This makes sure it doesn't use current tags.
	}
}else if (CA === 1){ //Current Tags
}else if (CA === 2){ //New Tags
	var tag=prompt("Enter Tags","");
	var chk=1;
}

// Danbooru | oreno.imouto | konachan | sankakucomplex
if(document.getElementById("post_tags") !== null){
	if (typeof tag !=="ftp://ftp." && chk !==1){var tag=document.getElementById("post_tags").value;}
	var srx="http://" + document.location.hostname + document.location.href.match("\/post\/show\/[0-9]+\/");
	if(srx.search("oreno\\.imouto") >= 0 || srx.search("konachan\\.com") >= 0){
		var rtg=document.getElementById("stats").innerHTML.match("<li>Rating: (.*) <span")[1];
	}else{
		var rtg=document.getElementById("stats").innerHTML.match("<li>Rating: (.*)<\/li>")[1];
	}

	if(tag.search(/\bflash\b/)===-1){
		var hrs=document.getElementById("highres").href;
		if(srx.search("oreno\\.imouto") >= 0 || srx.search("konachan\\.com") >= 0){ //oreno's theme seems to have moved the filesize
			var filesze = document.getElementById("highres").innerHTML.match("[a-zA-Z0-9]+ \\(+([0-9]+\\.[0-9]+) ([a-zA-Z]+)");
		}else{
			var filesze=document.getElementById("stats").innerHTML.match("[0-9] \\(((?:\.*[0-9])) ([a-zA-Z]+)");
		}
		if(filesze[2] == "MB"){var filesze = filesze[1] * 1024;}else{var filesze = filesze[2].match("[0-9]+");}

		if(supext.search(hrs.match("http\:\/\/.*\\.([a-z0-9]+)")[1]) !== -1){
			if(filesze <= maxsze){
				if(srx.search("oreno\\.imouto") >= 0){
					//this regex tends to be a bit picky with tags -_-;;
					var hrs=hrs.match("(http\:\/\/[a-z0-9]+\.[a-z]+\.[a-z]\/[a-z0-9]+\/[a-z0-9]+)\/[a-z0-9A-Z%_-]+(\.[a-zA-Z0-9]+)");
					var hrs=hrs[1]+hrs[2]; //this should bypass hotlink protection
				}else if(srx.search("konachan\\.com") >= 0){
					//konachan affixs konachan.com to the start of the tags, this requires different regex
					var hrs=hrs.match("(http\:\/\/[a-z0-9]+\.[a-z]+\.[a-z]\/[a-z0-9]+\/[a-z0-9]+)\/[a-z0-9A-Z%_]+\.[a-zA-Z0-9%_-]+(\.[a-z0-9A-Z]+)")
					var hrs=hrs[1]+hrs[2];
				}
				location.href=ste+hrs+"&tags="+tag+"&rating="+rtg+"&source="+srx;
			}else{alert(toobig);}
		}else{alert(notsup);}
	}else{
		if(supext.search("swf") !== -1){
				location.href=ste+document.getElementsByName("movie")[0].value+"&tags="+tag+"&rating="+rtg+"&source="+srx;
		}else{alert(notsup);}
	}
}
/* Shimmie
One problem with shimmie is each theme does not show the same info as other themes (I.E only the danbooru & lite themes show statistics)
Shimmie doesn't seem to have any way to grab tags via id unless you have the ability to edit tags.
Have to go the round about way of checking the title for tags.
This crazy way of checking "should" work with older releases though (Seems to work with 2009~ ver) */
else if(document.getElementsByTagName("title")[0].innerHTML.search("Image [0-9.-]+\: ")==0){
	if (typeof tag !=="ftp://ftp." && chk !==1){var tag=document.getElementsByTagName("title")[0].innerHTML.match("Image [0-9.-]+\: (.*)")[1];}
	//TODO: Make rating show in statistics.
	var srx="http://" + document.location.hostname + document.location.href.match("\/post\/view\/[0-9]+");
	/*TODO: Make file size show on all themes (Only seems to show in lite/Danbooru themes.)*/
	if(tag.search(/\bflash\b/)==-1){
		var img = document.getElementById("main_image").src;
		if(supext.search(img.match(".*\\.([a-z0-9]+)")[1]) !== -1){
			location.href=ste+img+"&tags="+tag+"&source="+srx;
		}else{alert(notsup);}
	}else{
		var mov = document.location.hostname+document.getElementsByName("movie")[0].value;
		if(supext.search("swf") !== -1){
			location.href=ste+mov+"&tags="+tag+"&source="+srx;
		}else{alert(notsup);}
	}
}
// Gelbooru
else if(document.getElementById("tags") !== null){
	if (typeof tag !=="ftp://ftp." && chk !==1){var tag=document.getElementById("tags").value;}
	var rtg=document.getElementById("stats").innerHTML.match("<li>Rating: (.*)<\/li>")[1];
	//Can't seem to grab source due to url containing a &
	//var srx="http://" + document.location.hostname + document.location.href.match("\/index\.php?page=post&amp;s=view\\&amp;id=.*");
	var gmi=document.getElementById("image").src.match(".*img[0-9]+\.gelbooru\.com\/\/images\/[0-9]+\/[a-z0-9]+\.[a-z0-9]+")[0];
	//Since Gelbooru does not allow flash, no need to search for flash tag.
	//Gelbooru doesn't show file size in statistics either...
	if(supext.search(gmi.match("http\:\/\/.*\\.([a-z0-9]+)")[1]) !== -1){
		location.href=ste+gmi+"&tags="+tag+"&rating="+rtg[1];//+"&source="+srx;
	}else{alert(notsup);}
}
