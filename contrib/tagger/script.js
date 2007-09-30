// Tagger - Advanced Tagging
// Author: Artanis (Erik Youngren <artanis.00@gmail.com>)
// Do not remove this notice.
// All other code copyright by their authors, see comments for details.

/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *\
*                              Tagger Management                              *
\* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
var remove_tagme = null;
function taggerInit() {
	taggerResetPos();
	tagger_tagIndicators()
	DragHandler.attach(byId("tagger_titlebar"));
	remove_tagme = byId('tagme');
}

function taggerResetPos() {
	tagger = byId("tagger_window");
	
	tagger.style.top="";
	tagger.style.left="";
	tagger.style.right="25px";
	tagger.style.bottom="25px";
	
	// get location in (left,top) terms
	pos = findPos(tagger);
	
	tagger.style.top = pos[1]+"px";
	tagger.style.left = pos[0]+"px";
	tagger.style.right="";
	tagger.style.bottom="";
}

function tagger_tagIndicators() {
	tags = byId("tags");
	arObjTags = getElementsByTagNames('a',byId('tagger_body'));
	
	for (i in arObjTags) {
		objTag = arObjTags[i];
		if(tagExists(objTag)) {
			objTag.style.fontWeight="bold";
		}
	}
}

/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *\
*                                   Tagging                                   *
\* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

function toggleTag(objTag) {
	if(!tagExists(objTag)) {
		addTag(objTag);
		if (remove_tagme && objTag.getAttribute('tag') != 'tagme') {
			remTag(remove_tagme);
		}
	} else {
		remTag(objTag);
	}
	t = byId("tagger_new-tag");
	if(t.value) { t.select(); }
}

function addTag (objTag) {
	var tags = byId('tags');
	
	delim = tags.value==" "?"":" ";

	tags.value += delim + objTag.getAttribute('tag');
	
	if(objTag.value != 'Add') {
		objTag.style.fontWeight = "bold";
	}
}

function remTag (objTag) {	
	var tags = byId("tags");
	
	aTags = tags.value.split(" ");
	
	tags.value="";
	for(i in aTags) {
		aTag = aTags[i];
		if(aTag != objTag.getAttribute('tag')) {
			if(tags.value=="") {
				tags.value += aTag;
			} else {
				tags.value += " "+aTag;
			}
		}
	}
	if(objTag.value != 'Add') {
		objTag.style.fontWeight = "";
	}
}

function tagExists(objTag) {
	return byId("tags").value.match(reescape(objTag.getAttribute('tag')));
}

/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *\
*                                  Filtering                                  *
\* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

var tagger_filter_focus = false;
function tagger_filter(override) {
	if(tagger_filter_focus || override) {
		var filter = byId('tagger_new-tag');
		var arObjTags = getElementsByTagNames('a',byId('tagger_body'));
		var prepend = filter.value.length<2? " ":"_";
		var search = prepend + reescape(filter.value);
		
		for(i in arObjTags) {
			objTag = arObjTags[i];
			tag = prepend + objTag.getAttribute('tag');

			if(tag.match(search) && taggerFilterMode(objTag)) {
				objTag.style.display='';
			} else {
				objTag.style.display='none';
			}
		}
	}
}
function taggerToggleMode() {
	var obj = byId('tagger_mode');
	
	if(obj.getAttribute('mode')=='all') {
		obj.setAttribute('mode', 'applied');
		obj.innerHTML = 'View All Tags';
	} else {
		obj.setAttribute('mode','all');
		obj.innerHTML = 'View Applied Tags';
	}
	tagger_filter(true);
}
function taggerFilterMode(objTag) {
	var obj = byId('tagger_mode');
	if(obj.getAttribute('mode') == 'all') {
		return true;
	} else {
		return objTag.style.fontWeight=='bold';
	}
}

/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *\
*                                     Misc                                    *
\* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

function pushSet(form_id) {
	var set = getSetButton(form_id);
	if(set) {
		set.click();
	}
}

function getSetButton(form_id) {
	var form_nodes = getElementsByTagNames('input',byId(form_id));
	for (i in form_nodes) {
		node = form_nodes[i];
		if (node.getAttribute('value')=="Set" && node.getAttribute('type')=="submit") {
			return node;
		}
	}
	return false;
}

/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *\
*                                quirksmode.org                               *
\* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

// http://www.quirksmode.org/dom/getElementsByTagNames.html
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

// http://www.quirksmode.org/js/findpos.html
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

// ripped from a forum.
// todo: cite source
function reescape(str){
	var resp="()?:=[]*+{}^$|/,.!\\"
	var found=false
	var ret=""
	for(var i=0;i<str.length;i++) {
		found=false
		for(var j=0;j<resp.length;j++) {
			if(str.charAt(i)==resp.charAt(j)) {
				found=true;break
			}
		}
		if(found) {
			ret+="\\"
		}
		ret+=str.charAt(i)
	}
	return ret
}
