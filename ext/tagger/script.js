/*jshint bitwise:true, curly:true, forin:false, noarg:true, noempty:true, nonew:true, undef:true, strict:false, browser:true, jquery:true */

/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *\
* Tagger - Advanced Tagging v2                                                *
* Author: Artanis (Erik Youngren <artanis.00@gmail.com>)                      *
* Do not remove this notice.                                                  *
\* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
 
var Tagger = {
	initialize : function (image_id) {
	// object navigation
		this.tag.parent       = this;
		this.position.parent  = this;
	// components
		this.editor.container = byId('tagger_parent');
		this.editor.titlebar  = byId('tagger_titlebar');
		this.editor.toolbar   = byId('tagger_toolbar');
		//this.editor.menu      = byId('tagger_p-menu');
		this.editor.body      = byId('tagger_body');
		this.editor.tags      = byId('tagger_tags');
		this.editor.form      = this.editor.tags.parentNode;
		this.editor.statusbar = byId('tagger_statusbar');
	// initial data
		this.tag.image        = image_id;
		this.tag.query        = config.make_link("tagger/tags");
		this.tag.list         = null;
		this.tag.suggest      = null;
		this.tag.image_tags();
		
	// reveal		
		this.editor.container.style.display = "";
	
	// dragging
		DragHandler.attach(this.editor.titlebar);
	
	// positioning
		this.position.load();
	
	// events
		window.onunload = function () { Tagger.position.save(); };
	},
	
	alert : function (type,text,timeout) {
		var id = "tagger_alert-"+type
		var t_alert = byId(id);
		if (t_alert) {
			if(text === false) {
				// remove
				t_alert.parentNode.removeChild(t_alert);
			} else {
				// update
				t_alert.innerHTML = text;
			}
		} else if (text) {
			// create
			var t_alert = document.createElement("div");
			t_alert.setAttribute("id",id);
			t_alert.appendChild(document.createTextNode(text));
			this.editor.statusbar.appendChild(t_alert);
			if(timeout>1) {
				console.log("Tagger.alert('"+type+"',false,0)");
				setTimeout("Tagger.alert('"+type+"',false,0)",timeout);
			}
		}
	},
	
	editor : {},
	
	tag : {
		submit : function () {
			var l = this.list.childNodes.length;
			var tags = Array();
			for(var i=0; i<l; i++) {
				var s_tag = this.list.childNodes[i].firstChild.data;
				tags.push(s_tag);
			}
			tags = tags.join(" ");
			this.parent.editor.tags.value = tags;
			return true;
		},
		
		search : function(s,ms) {
			clearTimeout(Tagger.tag.timer);
			Tagger.tag.timer = setTimeout(
				"Tagger.tag.ajax('"+Tagger.tag.query+"?s="+s+"',Tagger.tag.receive)",
				ms);
		},
		
		receive : function (xml) {
			if(xml) {
				Tagger.tag.suggest = document.importNode(
					xml.responseXML.getElementsByTagName("list")[0],true);
				Tagger.tag.publish(Tagger.tag.suggest,byId("tagger_p-search"));
			}
			if(Tagger.tag.suggest.getAttribute("max")) {
				var rows = Tagger.tag.suggest.getAttribute("rows");
				var max = Tagger.tag.suggest.getAttribute("max");
				Tagger.alert("maxout","Showing "+rows+" of "+max+" tags",0);
			} else {
				Tagger.alert("maxout",false);
			}
		},
		
		image_tags : function(xml) {
			if (!xml) {
				this.ajax(this.query+"/"+this.image,this.image_tags);
				return true;
			} else {
				Tagger.tag.list = document.importNode(
					xml.responseXML.getElementsByTagName("list")[0],true);
				Tagger.tag.publish(Tagger.tag.list,byId("tagger_p-applied"));
			}
		},
		
		publish : function (list, page) {
			list.setAttribute("xmlns","http://www.w3.org/1999/xhtml");
			
			var l = list.childNodes.length;
			for(var i=0; i<l; i++) {
				var tag = list.childNodes[i];
				tag.onclick = function () {
					Tagger.tag.toggle(this);
					byId("tagger_filter").select();
				};
				tag.setAttribute("title",tag.getAttribute("count")+" uses");
			}
			
			page.innerHTML = "";
			page.appendChild(list);
		},
		
		create : function (tag_name) {
			if(tag_name.length > 0) {
				var tag = document.createElement("tag");
				tag.setAttribute("count","0");
				tag.setAttribute("id","newTag_"+tag_name);
				tag.setAttribute("title","New - 0 uses");
				tag.onclick = function() {
					Tagger.tag.toggle(this);
				};
				tag.appendChild(document.createTextNode(tag_name));
				Tagger.tag.list.appendChild(tag);
			}
		},
		
		toggle : function (tag) {
			if(tag.parentNode == this.list) {
				this.list.removeChild(tag);
			} else {
				this.list.appendChild(tag);
			}
		},
		
		ajax : function (url, callback) {
			var http = (new XMLHttpRequest || new ActiveXObject("Microsoft.XMLHTTP"));
			http.open("GET",url,true);
			http.onreadystatechange = function () {
				if(http.readyState == 4) callback(http);
			};
			http.send(null);
		}
	},
	
	position : {
		set : function (x,y) {
			if (!x || !y) {
				with(this.parent.editor.container.style) {
					top = "25px";
					left = "";
					right = "25px";
					bottom = "";
				}
				var xy = this.get();
				x = xy[0];
				y = xy[1];
			}
			with(this.parent.editor.container.style) {
					top = y+"px";
					left = x+"px";
					right = "";
					bottom = "";
			}
		},
		
		get : function () {
			// http://www.quirksmode.org/js/findpos.html
			var left = 0;
			var top  = 0;
			var obj  = this.parent.editor.container;
			if(obj.offsetParent) {
				left = obj.offsetLeft;
				top  = obj.offsetTop;
				while (obj = obj.offsetParent) {
					left += obj.offsetLeft;
					top  += obj.offsetTop;
				}
			}
			return [left,top];
		},
		
		save : function (x,y) {
			if (!x || !y) {
				var xy = this.get();
				x = xy[0];
				y = xy[1];
			}
			setCookie(config.title+"_tagger-position",x+" "+y,14);
		},
		
		load : function () {
			var p = getCookie(config.title+"_tagger-position");
			if(p) {
				var xy = p.split(" ");
				this.set(xy[0],xy[1]);
			} else {
				this.set();
			}
		}
	}
};
