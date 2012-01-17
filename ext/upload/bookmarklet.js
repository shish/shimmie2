/* Imageboard to Shimmie */
// This should work with "most" sites running Danbooru/Gelbooru/Shimmie

if (confirm("OK = Use Current tags.\nCancel = Use new tags.")==true){}else{var tag=prompt("Enter Tags","");var chk=1;};
// Danbooru
if(document.getElementById("post_tags") !== null){
	if (typeof tag !=="ftp://ftp." && chk !==1){var tag=document.getElementById("post_tags").value;}
	var rtg=document.documentElement.innerHTML.match("<li>Rating: (.*)<\/li>")[1];
	var srx="http://" + document.location.hostname + document.location.href.match("\/post\/show\/[0-9]+\/");
	if(tag.search(/\bflash\b/)==-1){
		location.href=ste+document.getElementById("highres").href+"&tags="+tag+"&rating="+rtg[1]+"&source="+srx;
	}else{
		location.href=ste+document.getElementsByName("movie")[0].value+"&tags="+tag+"&rating="+rtg[1]+"&source="+srx;
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
	/*TODO: Figure out regex for shortening file link.
	I.E http://blah.net/_images/1234abcd/everysingletag.png > http://blah.net/_images/1234abcd.png
	.match("WEBSITE.NET\/_images\/[A-Za-z0-9]+", "(\\.[a-z][a-z]+)")*/
	if(tag.search(/\bflash\b/)==-1){
		location.href=ste+document.getElementById("main_image").src+"&tags="+tag+"&source="+srx;
	}else{
		location.href=ste+document.location.hostname+document.getElementsByName("movie")[0].value+"&tags="+tag+"&source="+srx;
	}
}/*
// Gelbooru
else if(document.getElementById("tags") !== null){
	//Gelbooru has an annoying anti-hotlinking thing which doesn't seem to like the bookmarklet...
	//So if someone can figure out how to bypass the hotlinking, please update the code :<
	if (typeof tag !=="ftp://ftp." && chk !==1){var tag=document.getElementById("tags").value;}
	var rtg=document.documentElement.innerHTML.match("<li>Rating: (.*)<\/li>")[1];
	var srx="http://" + document.location.hostname + document.location.href.match("\/index\.php\\?page=post&s=view&id=.*"); //Gelbooru has really ugly urls..
	var gmi=document.getElementById("image").src.match(".*img[0-9]+\.gelbooru\.com\/\/images\/[0-9]+\/[a-z0-9]+\.[a-z0-9]+")[0];
	//Since Gelbooru does not allow flash, no need to search for flash tag.
		location.href=ste+gmi+"&tags="+tag+"&rating="+rtg[1]+"&source="+srx';
}*/
