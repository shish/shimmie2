var defaultTexts = new Array();

window.onload = function(e) {
	var sections=get_sections();
	for(var i=0;i<sections.length;i++) toggle(sections[i]);

	initGray("search_input", "Search");
	initGray("commentBox", "Comment");
	initGray("tagBox", "tagme");
	
	// if we're going to show with JS, hide with JS first
	pass_confirm = byId("pass_confirm");
	if(pass_confirm) {
		pass_confirm.style.display = "none";
	}
}


function initGray(boxname, text) {
	var box = byId(boxname);
	if(!box) return;

	addEvent(box, "focus", function f() {cleargray(box, text);}, false);
	addEvent(box, "blur",  function f() {setgray(box, text);}, false);

	if(box.value == text) {
		box.style.color = "#999";
		box.style.textAlign = "center";
	}
	else {
		box.style.color = "#000";
		box.style.textAlign = "left";
	}
}

function cleargray(box, text) {
	if(box.value == text) {
		box.value = "";
		box.style.color = "#000";
		box.style.textAlign = "left";
	}
}
function setgray(box, text) {
	if(box.value == "") {
		box.style.textAlign = "center";
		box.style.color = "gray";
		box.value = text;
	}
}

function showUp(elem) {
	e = document.getElementById(elem)
	if(!e) return;
	e.style.display = "";
//	alert(e.type+": "+e.value);
	if(e.value.match(/^http|^ftp/)) {
		e.type = "text";
		alert("Box is web upload");
	}
}

