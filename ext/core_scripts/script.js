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


/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *\
*                              LibShish-JS                                  *
\* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

function addEvent(obj, event, func, capture){
	if (obj.addEventListener){
		obj.addEventListener(event, func, capture);
	} else if (obj.attachEvent){
		obj.attachEvent("on"+event, func);
	}
}


function byId(id) {
	return document.getElementById(id);
}


function getHTTPObject() { 
	if (window.XMLHttpRequest){
		return new XMLHttpRequest();
	}
	else if(window.ActiveXObject){
		return new ActiveXObject("Microsoft.XMLHTTP");
	}
}

function ajaxRequest(url, callback) {
	var http = getHTTPObject();
	http.open("GET", url, true);
	http.onreadystatechange = function() {
		if(http.readyState == 4) callback(http.responseText);
	}
	http.send(null);
}


/* get, set, and delete cookies */
function getCookie( name ) {
	var start = document.cookie.indexOf( name + "=" );
	var len = start + name.length + 1;
	if ( ( !start ) && ( name != document.cookie.substring( 0, name.length ) ) ) {
		return null;
	}
	if ( start == -1 ) return null;
	var end = document.cookie.indexOf( ";", len );
	if ( end == -1 ) end = document.cookie.length;
	return unescape( document.cookie.substring( len, end ) );
}
	
function setCookie( name, value, expires, path, domain, secure ) {
	var today = new Date();
	today.setTime( today.getTime() );
	if ( expires ) {
		expires = expires * 1000 * 60 * 60 * 24;
	}
	var expires_date = new Date( today.getTime() + (expires) );
	document.cookie = name+"="+escape( value ) +
		( ( expires ) ? ";expires="+expires_date.toGMTString() : "" ) + //expires.toGMTString()
		( ( path ) ? ";path=" + path : "" ) +
		( ( domain ) ? ";domain=" + domain : "" ) +
		( ( secure ) ? ";secure" : "" );
}
	
function deleteCookie( name, path, domain ) {
	if ( getCookie( name ) ) document.cookie = name + "=" +
			( ( path ) ? ";path=" + path : "") +
			( ( domain ) ? ";domain=" + domain : "" ) +
			";expires=Thu, 01-Jan-1970 00:00:01 GMT";
}

