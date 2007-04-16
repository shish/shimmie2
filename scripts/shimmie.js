var defaultTexts = new Array();

window.onload = function(e) {
	var sections=get_sections();
	for(var i=0;i<sections.length;i++) toggle(sections[i]);

//	initAjax("searchBox", "search_completions");
//	initAjax("tagBox", "upload_completions");
	initGray("search_input", "Search");
	initGray("commentBox", "Comment");
	initGray("tagBox", "tagme");
	
	// if we're going to show with JS, hide with JS first
	pass_confirm = byId("pass_confirm");
	if(pass_confirm) {
		pass_confirm.style.display = "none";
	}
}


function endWord(sentance) {
	words = sentance.split(" ");
	return words[words.length-1];
}

var resultCache = new Array();
resultCache[""] = new Array();

function complete(boxname, text) {
	box = byId(boxname);
	words = box.value.split(" ");
	box.value = "";
	for(n=0; n<words.length-1; n++) {
		box.value += words[n]+" ";
	}
	box.value += text+" ";
	box.focus();
	return false;
}

function fillCompletionZone(boxname, areaname, results) {
	byId(areaname).innerHTML = "";
	for(i=0; i<results.length; i++) {
		byId(areaname).innerHTML += "<br><a href=\"#\" onclick=\"complete('"+boxname+"', '"+results[i]+"');\">"+results[i]+"</a>";
	}
}

function initAjax(boxname, areaname) {
	var box = byId(boxname);
	if(!box) return;

	addEvent(
		box,
		"keyup", 
		function f() {
			starter = endWord(box.value);
				
			if(resultCache[starter]) {
				fillCompletionZone(boxname, areaname, resultCache[starter]);
			} 
			else { 
				ajaxRequest( 
					"ajax.php?start="+starter, 
					function g(text) { 
						resultCache[starter] = text.split("\n");
						fillCompletionZone(boxname, areaname, resultCache[starter]);
					} 
				); 
			} 
		},
		false
	);
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


function check_int(box, min, max) {
	check(box, (box.value >= min && box.value <= max));
}

function check(box, bool) {
	if(bool) {
		box.style.background = "#AFA";
	}
	else {
		box.style.background = "#FAA";
	}
}

