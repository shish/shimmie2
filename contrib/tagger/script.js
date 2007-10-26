/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *\
* Tagger - Advanced Tagging v2                                                *
* Author: Artanis (Erik Youngren <artanis.00@gmail.com>)                      *
* Do not remove this notice.                                                  *
\* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

/* Tagger Window Object
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
function Tagger() {
// components
	this.t_parent         = null;
	this.t_title          = null;
	this.t_toolbar        = null;
	this.t_menu           = null;
	this.t_body           = null;
	this.t_tags           = null;
	this.t_form           = null;
	this.t_status         = null;
// data
	this.searchTags       = null;
	this.appliedTags      = null;
// methods
	this.initialize       = initialize;
	this.submit           = submit;
	this.getPosition      = function () { return findPos(this.t_parent); };
	this.setPosition      = setPosition;
	this.setSavedPosition = setSavedPosition;
	this.getSavedPosition = getSavedPosition;
	this.tagSearch        = tagSearch;
	this.searchRequest    = searchRequest;
	this.searchReceive    = searchReceive;
	this.tagListReceive   = tagListReceive;
	this.tagPublish       = tagPublish;
	this.prepTags         = prepTags;
	this.createTag        = createTag;
	this.buildPages       = buildPages;
	this.tagsToString     = tagsToString;
	this.toggleTag        = toggleTag;
	this.setAlert         = setAlert;

	
// definitions
	function initialize () {
	// components
		this.t_parent  = byId("tagger_parent");
		this.t_title   = byId("tagger_titlebar");
		this.t_toolbar = byId("tagger_toolbar");
		this.t_menu    = byId("tagger_p-menu");
		this.t_body    = byId("tagger_body");
		this.t_tags    = byId("tagger_tags");
		this.t_form    = this.t_tags.parentNode;
		this.t_status  = byId("tagger_statusbar");
	//pages
		//this.buildPages();
	// initial data
		ajaxXML(query+"/"+image_id,tagListReceive);
	// reveal
		this.t_parent.style.display = "";
	// dragging
		DragHandler.attach(this.t_title);
	// set position
		var pos = ( Tagger.getSavedPosition() || Tagger.getPosition() );
		setPosition(pos[0],pos[1]);
	// events
		window.onunload = function () {Tagger.setSavedPosition(); };
		
	}
	function submit() {
		this.t_tags.value = Tagger.tagsToString(Tagger.appliedTags);
	}
	function setPosition(x,y) {
		if(!x || !y) {
			with(Tagger.t_parent.style) {
				top = "25px";
				left = "";
				right = "25px";
				bottom = "";
			}
			var pos = Tagger.getPosition();
			x = pos[0];
			y = pos[1];
		}
		with(Tagger.t_parent.style) {
			top = y+"px";
			left = x+"px";
			right="";
			bottom="";
		}
	}
	function setSavedPosition(x,y) {
		if (!x || !y) {
			var p = Tagger.getPosition();
			x = p[0];
			y = p[1];
		}
		setCookie("shimmie_tagger-position",x+" "+y,14);
	}
	function getSavedPosition() {
		var p = getCookie("shimmie_tagger-position");
		if(p) {
			return p.split(" ");
		} else {
			return false;
		}
	}
	function tagSearch(s,ms) {
		clearTimeout(tagger_filter_timer);
		tagger_filter_timer = setTimeout("Tagger.searchRequest('"+s+"')",ms);
	}
	function searchRequest(s) {
		var s_query = !s? query+"?s" : query+"?s="+sqlescape(s);

		if(!this.searchTags) {
			ajaxXML(s_query,searchReceive);
			return true;
		} else {
			var prv_s = this.searchTags.getAttribute('query');
		
			if(s==prv_s) {
				return false;
			}else if(!s || s.length <= 2 || s.length<prv_s.length ||
				this.searchTags.getAttribute("max"))
			{
				ajaxXML(s_query,searchReceive);
				return true;
				
			} else if(s.length > 2 && s.match(reescape(prv_s))) {
				var len = this.searchTags.childNodes.length;
				
				for (var i=len-1; i>=0; i--) {
					var tag = this.searchTags.childNodes[i];
					var t_name = tag.firstChild.data;
					
					if(!t_name.match(reescape(s))) {
						this.searchTags.removeChild(tag);
						// TODO: Fix so back searches are not needlessly re-queried.
						//tag.setAttribute("style","display:none;");
					} else {
						//tag.setAttribute("style","");
					}
				}
				
				if (len != this.searchTags.childNodes.length) {
					this.searchTags.setAttribute('query',s);
				}
			}
		}
		return false;
	}
	function searchReceive(xml) {
		Tagger.searchTags = document.importNode(xml.getElementsByTagName("list")[0],true);
		tagPublish(Tagger.searchTags,byId("tagger_p-search"));
		
		if(Tagger.searchTags.getAttribute("max")) {
			Tagger.setAlert("maxout","Showing "+Tagger.searchTags.getAttribute("rows")+" of "+Tagger.searchTags.getAttribute("max")+" tags");
		} else {
			Tagger.setAlert("maxout",false);
		}
	}
	
	function tagListReceive(xml) {
		Tagger.appliedTags = document.importNode(xml.getElementsByTagName("list")[0],true);
		tagPublish(Tagger.appliedTags,byId("tagger_p-applied"));
	}
	function tagPublish(tag_list,page) {
		page.innerHTML = "";
		Tagger.prepTags(tag_list);
		page.appendChild(tag_list);
	}
	function prepTags(tag_list) {
		var len = tag_list.childNodes.length;
		
		for(var i=0; i<len;i++) {
			var tag = tag_list.childNodes[i];
			tag.onclick = function() { toggleTag(this); byId("tagger_filter").select(); };
			//tag.style.display="block";
			tag.setAttribute("title",tag.getAttribute("count")+" uses");
		}
	}
	function createTag(tag_name) {
		if (tag_name.length>0) {
			var tag = document.createElement("tag");
			tag.setAttribute("count","0");
			tag.setAttribute("id","newTag_"+tag_name);
			tag.setAttribute("count",1);
			tag.setAttribute("title","New");
			tag.onclick = function() { toggleTag(this); };
			tag.appendChild(document.createTextNode(tag_name));
			Tagger.appliedTags.appendChild(tag);
		}
	}
	function buildPages () {
		var pages = getElementsByTagNames("div",byId("tagger_body"));
		var len = pages.length;
		for(var i=0; i<len; i++) {
			this.t_menu.innerHTML += "<li onclick='Tagger.togglePages("+
				"\""+pages[i].getAttribute("id")+"\")'>"+
				pages[i].getAttribute('name')+"</li>";
		}
	}
	function tagsToString(tags) {
		var len = tags.childNodes.length;
		var str = "";
		for (var i=0; i<len; i++) {
			str += tags.childNodes[i].firstChild.data+" ";
		}
		return str;
	}
	function toggleTag(tag) {
		if(tag.parentNode == Tagger.appliedTags) {
			Tagger.appliedTags.removeChild(tag);
		} else {
			Tagger.appliedTags.appendChild(tag);
		}
	}
	function setAlert(type,arg) {
		var alert = byId("tagger_alert_"+type);
		if (alert) {
			if (arg==false) {
				//remove existing
				alert.parentNode.removeChild(alert);
				return;
			}
			//update prior
			alert.innerHTML = arg;
		} else if (arg!=false) {
			//create
			var status = document.createElement("div");
			status.setAttribute("id","tagger_alert_"+type);
			status.innerHTML = arg;
			Tagger.t_status.appendChild(status);
		}
	}
}

/* AJAX
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
function ajaxXML(url, callback) {
	//var http = getHTTPObject();
	var http = (new XMLHttpRequest() || new ActiveXObject("Microsoft.XMLHTTP"));
	http.open("GET", url, true);
	http.onreadystatechange = function() {
		if(http.readyState == 4) callback(http.responseXML);
	}
	http.send(null);
}

/* Miscellaneous Code
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

// Quirksmode
// http://www.quirksmode.org/dom/getElementsByTagNames.htmlgetElementbyId
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

// Modified from above
function sqlescape(str){
	var resp="#%&_"
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
