
// addEvent(window, "load", function() {
//	initAjax("searchBox", "search_completions");
//	initAjax("tagBox", "upload_completions");
// });

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


//completion_cache = new array();

input = byId("search_input");
output = byId("search_completions");

function get_cached_completions(start) {
//	if(completion_cache[start]) {
//		return completion_cache[start];
//	}
//	else {
		return null;
//	}
}
function get_completions(start) {
	cached = get_cached_completions(start);
	if(cached) {
		output.innerHTML = cached;
	}
	else {
		ajaxRequest(autocomplete_url+"/"+start, function(data) {set_completions(start, data);});
	}
}
function set_completions(start, data) {
//	completion_cache[start] = data;
	output.innerHTML = data;
}

if(input) {
	input.onkeyup = function() {
		if(input.value.length < 3) {
			output.innerHTML = "";
		}
		else {
			get_completions(input.value);
		}
	};
}
