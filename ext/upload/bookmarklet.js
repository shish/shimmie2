/* Imageboard to Shimmie */
// This should work with "most" sites running Danbooru/Gelbooru/Shimmie

var maxsze = (maxsze.match("(?:\.*[0-9])")) * 1024; //This assumes we are only working with MB.
var toobig = "The file you are trying to upload is too big to upload!";
var notsup = "The file you are trying to upload is not supported!";

if (confirm("OK = Use Current tags.\nCancel = Use new tags.")==true){}else{var tag=prompt("Enter Tags","");var chk=1;};

// Danbooru
if(document.getElementById("post_tags") !== null){
	if (typeof tag !=="ftp://ftp." && chk !==1){var tag=document.getElementById("post_tags").value;}
	var rtg=document.getElementById("stats").innerHTML.match("<li>Rating: (.*)<\/li>")[1];
	var srx="http://" + document.location.hostname + document.location.href.match("\/post\/show\/[0-9]+\/");
	var filesze=document.getElementById("stats").innerHTML.match("[0-9] \\(((?:\.*[0-9])) ([a-zA-Z]+)");
	if(filesze[2] == "MB"){var filesze = filesze[1] * 1024;}else{var filesze = filesze[2].match("[0-9]+");}
	if(tag.search(/\bflash\b/)==-1){
		if(supext.search(document.getElementById("highres").href.match("http\:\/\/.*\\.([a-z0-9]+)")[1]) !== -1){
			if(filesze <= maxsze){
				location.href=ste+document.getElementById("highres").href+"&tags="+tag+"&rating="+rtg[1]+"&source="+srx;
			}else{alert(toobig);}
		}else{alert(notsup);}
	}else{
		if(supext.search(document.getElementById("highres").href.match("http\:\/\/.*\\.([a-z0-9]+)")[1]) !== -1){
			if(filesze <= maxsze){
				location.href=ste+document.getElementsByName("movie")[0].value+"&tags="+tag+"&rating="+rtg[1]+"&source="+srx;
			}else{alert(toobig);}
		}else{alert(notsup);}
	}
}
/* Shimmie
Shimmie doesn't seem to have any way to grab tags via id unless you have the ability to edit tags.
Have to go the round about way of checking the title for tags.
This crazy way of checking "should" work with older releases though (Seems to work with 2009~ ver) */
else if(document.getElementsByTagName("title")[0].innerHTML.search("Image [0-9.-]+\: ")==0){
	if (typeof tag !=="ftp://ftp." && chk !==1){var tag=document.getElementsByTagName("title")[0].innerHTML.match("Image [0-9.-]+\: (.*)")[1];}
	//TODO: Make rating show in statistics.
	var srx="http://" + document.location.hostname + document.location.href.match("\/post\/view\/[0-9]+");
	/*TODO: Figure out regex for shortening file link. I.E http://blah.net/_images/1234abcd/everysingletag.png > http://blah.net/_images/1234abcd.png*/
	/*TODO: Make file size show on all themes (Only seems to show in lite/Danbooru themes.)*/
	if(tag.search(/\bflash\b/)==-1){
		var img = document.getElementById("main_image").src;
		if(supext.search(img.match(".*\\.([a-z0-9]+)")[1]) !== -1){
			location.href=ste+img+"&tags="+tag+"&source="+srx;
		}else{alert(notsup);}
	}else{
		var mov = document.location.hostname+document.getElementsByName("movie")[0].value;
		if(supext.search(mov.match(".*\\.([a-z0-9]+)")[1]) !== -1){
			location.href=ste+mov+"&tags="+tag+"&source="+srx;
		}else{alert(notsup);}
	}
}
// Gelbooru
else if(document.getElementById("tags") !== null){
	//Gelbooru has an annoying anti-hotlinking thing which doesn't seem to like the bookmarklet.
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
