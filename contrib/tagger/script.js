// Tagger - Advanced Tagging
// Author: Artanis (Erik Youngren <artanis.00@gmail.com>)
// Do not remove this notice.
// All other code copyright by their authors, see comments for details.


function taggerResetPos() {
	// In case the drag point goes off screen.
	tagger = byId("tagger_window");
	
	// reset default position (bottom right.)
	tagger.style.top="";
	tagger.style.left="";
	tagger.style.right="25px";
	tagger.style.bottom="25px";
	
	// get location in (left,top) terms
	pos = findPos(tagger);
	
	// set top left and clear bottom right.
	tagger.style.top = pos[1]+"px";
	tagger.style.left = pos[0]+"px";
	tagger.style.right="";
	tagger.style.bottom="";
}

function tagExists(tag) {
	var tags = byId("tags");
	tags_list = tags.value;
	tags_array = tags_list.split(" ");
	
	tags_list = "";
	for (x in tags_array) {
		if(tags_array[x] == tag) {
			return true;
		}
	}
	return false;
}

function toggleTag(tag,rTagme) {

	
	if (!tagExists(tag)) {
		addTag(tag);
		if(rTagme && tag != "tagme") {
			remTag("tagme");
		}
	} else {
		remTag(tag);
	}
	obj = byId("tagger_custTag");
	if(obj.value) {
		obj.select();
	}
}

function addTagById(id) {
	tag = byId(id);
	toggleTag(tag.value);
}

function addTag (tag) {
	var tags = byId("tags");
	var	tag_link = byId("tagger_tag_"+tag);
	
	var delim = " ";
	if(tags.value == "") {
		delim="";
	}
	tags.value = tags.value + delim + tag;
	if(tag_link) {
		tag_link.style.fontWeight = "bold";
	}
}

function remTag (tag) {
	var tags = byId("tags");
	var	tag_link = byId("tagger_tag_"+tag);
	
	_tags = tags.value.split(" ");

	tags.value = "";		
	for (i in _tags) {
		_tag = _tags[i];
		if(_tag != tag) {
			addTag(_tag);
		}
	}
	if(tag_link) {
		tag_link.style.fontWeight = "";
	}
}

function setTagIndicators() {
	taggerResetPos();
	
	tags = byId("tags");
	tags = tags.value.split(" ");
	
	for (x in tags) {
		tag = tags[x];
		obj = byId("tagger_tag_"+tag);
		if(obj) {
			obj.style.fontWeight="bold";
		}
	}
}

function tagger_filter(id) {
	var filter = byId(id);
	var e;
	
	search = filter.value;
	// set up single letter filters for first-letter matching only.
	if (search.length == 1)
		search = " "+search;
	
	tag_links = getElementsByTagNames('div',byId('tagger_body'));
	
	for (x in tag_links) {
		tag_id = tag_links[x].id;
		// remove tagger_tag from id, prepend space for first-letter matching.
		tag = " "+tag_id.replace(/tagger_tag_/,"");
		e = byId(tag_id);
		if (!tag.match(search)) {
			e.style.display = 'none';
		} else {
			e.style.display = '';
		}
	}
}

// Quirksmode.org //
// http://www.quirksmode.org/dom/getElementsByTagNames.html //
function getElementsByTagNames(list,obj) {
	if (!obj) var obj = document;
	var tagNames = list.split(',');
	var resultArray = new Array();
	for (var i=0;i<tagNames.length;i++) {
		var tags = obj.getElementsByTagName(tagNames[i]);
		for (var j=0;j<tags.length;j++) {
			resultArray.push(tags[j]);
		}
	}
	var testNode = resultArray[0];
	if (!testNode) return [];
	if (testNode.sourceIndex) {
		resultArray.sort(function (a,b) {
				return a.sourceIndex - b.sourceIndex;
		});
	}
	else if (testNode.compareDocumentPosition) {
		resultArray.sort(function (a,b) {
				return 3 - (a.compareDocumentPosition(b) & 6);
		});
	}
	return resultArray;
}
// http://www.quirksmode.org/js/findpos.html //
function findPos(obj) {
	var curleft = curtop = 0;
	if (obj.offsetParent) {
		curleft = obj.offsetLeft
		curtop = obj.offsetTop
		while (obj = obj.offsetParent) {
			curleft += obj.offsetLeft
			curtop += obj.offsetTop
		}
	}
	return [curleft,curtop];
}
// End //

// Drag Code //
//*****************************************************************************
// Do not remove this notice.
//
// Copyright 2001 by Mike Hall.
// See http://www.brainjar.com for terms of use.
//*****************************************************************************

// Determine browser and version.

function Browser() {

  var ua, s, i;

  this.isIE    = false;
  this.isNS    = false;
  this.version = null;

  ua = navigator.userAgent;

  s = "MSIE";
  if ((i = ua.indexOf(s)) >= 0) {
    this.isIE = true;
    this.version = parseFloat(ua.substr(i + s.length));
    return;
  }

  s = "Netscape6/";
  if ((i = ua.indexOf(s)) >= 0) {
    this.isNS = true;
    this.version = parseFloat(ua.substr(i + s.length));
    return;
  }

  // Treat any other "Gecko" browser as NS 6.1.

  s = "Gecko";
  if ((i = ua.indexOf(s)) >= 0) {
    this.isNS = true;
    this.version = 6.1;
    return;
  }
}

var browser = new Browser();

// Global object to hold drag information.

var dragObj = new Object();
dragObj.zIndex = 0;

function dragStart(event, id) {
  var el;
  var x, y;

  // If an element id was given, find it. Otherwise use the element being
  // clicked on.

  if (id)
    dragObj.elNode = document.getElementById(id);
  else {
    if (browser.isIE)
      dragObj.elNode = window.event.srcElement;
    if (browser.isNS)
      dragObj.elNode = event.target;

    // If this is a text node, use its parent element.

    if (dragObj.elNode.nodeType == 3)
      dragObj.elNode = dragObj.elNode.parentNode;
  }

  // Get cursor position with respect to the page.

  if (browser.isIE) {
    x = window.event.clientX + document.documentElement.scrollLeft
      + document.body.scrollLeft;
    y = window.event.clientY + document.documentElement.scrollTop
      + document.body.scrollTop;
  }
  if (browser.isNS) {
    x = event.clientX + window.scrollX;
    y = event.clientY + window.scrollY;
  }

  // Save starting positions of cursor and element.

  dragObj.cursorStartX = x;
  dragObj.cursorStartY = y;
  dragObj.elStartLeft  = parseInt(dragObj.elNode.style.left, 10);
  dragObj.elStartTop   = parseInt(dragObj.elNode.style.top,  10);

  if (isNaN(dragObj.elStartLeft)) dragObj.elStartLeft = 0;
  if (isNaN(dragObj.elStartTop))  dragObj.elStartTop  = 0;

  // Update element's z-index.

  dragObj.elNode.style.zIndex = ++dragObj.zIndex;

  // Capture mousemove and mouseup events on the page.

  if (browser.isIE) {
    document.attachEvent("onmousemove", dragGo);
    document.attachEvent("onmouseup",   dragStop);
    window.event.cancelBubble = true;
    window.event.returnValue = false;
  }
  if (browser.isNS) {
    document.addEventListener("mousemove", dragGo,   true);
    document.addEventListener("mouseup",   dragStop, true);
    event.preventDefault();
  }
}

function dragGo(event) {

  var x, y;

  // Get cursor position with respect to the page.

  if (browser.isIE) {
    x = window.event.clientX + document.documentElement.scrollLeft
      + document.body.scrollLeft;
    y = window.event.clientY + document.documentElement.scrollTop
      + document.body.scrollTop;
  }
  if (browser.isNS) {
    x = event.clientX + window.scrollX;
    y = event.clientY + window.scrollY;
  }

  // Move drag element by the same amount the cursor has moved.

  dragObj.elNode.style.left = (dragObj.elStartLeft + x - dragObj.cursorStartX) + "px";
  dragObj.elNode.style.top  = (dragObj.elStartTop  + y - dragObj.cursorStartY) + "px";

  if (browser.isIE) {
    window.event.cancelBubble = true;
    window.event.returnValue = false;
  }
  if (browser.isNS)
    event.preventDefault();
}

function dragStop(event) {

  // Stop capturing mousemove and mouseup events.

  if (browser.isIE) {
    document.detachEvent("onmousemove", dragGo);
    document.detachEvent("onmouseup",   dragStop);
  }
  if (browser.isNS) {
    document.removeEventListener("mousemove", dragGo,   true);
    document.removeEventListener("mouseup",   dragStop, true);
  }
}
// End //
