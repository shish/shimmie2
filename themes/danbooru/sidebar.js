/*
 * This script shamelessly stolen from wakachan.org d(^_^)b
 */

var cookie_name="shimmie_sidebar";
var default_sections=[];

function toggle(id) 
{
	var e=document.getElementById(id);
	if(!e) return;
	if(e.style.display)
	{
		remove_section(id);
		e.style.display="";

		var e2 = document.getElementById(id+"-toggle");
		if(e2) {
			e2.style.color = "#000";
		}
	}
	else
	{
		add_section(id);
		e.style.display="none"; 
		
		var e2 = document.getElementById(id+"-toggle");
		if(e2) {
			e2.style.color = "#AAA";
		}
	}
}

function add_section(id)
{
	var sections=get_sections();
	for(var i=0;i<sections.length;i++) if(sections[i]==id) return;
	sections.push(id);
	set_sections(sections);	
}

function remove_section(id)
{
	var sections=get_sections();
	var new_sections=new Array();
	for(var i=0;i<sections.length;i++) if(sections[i]!=id) new_sections.push(sections[i]);
	set_sections(new_sections);	
}

function get_sections()
{
	var cookie=get_cookie(cookie_name);
	if(cookie) return cookie.split(/,/);
	else return default_sections;
}

function set_sections(sections) { set_cookie(cookie_name,sections.join(","),365); }

function get_cookie(name)
{
	with(document.cookie)
	{
		var index=indexOf(name+"=");
		if(index==-1) return '';
		index=indexOf("=",index)+1;
		var endstr=indexOf(";",index);
		if(endstr==-1) endstr=length;
		return unescape(substring(index,endstr));
	}
};

function set_cookie(name,value,days)
{
	if(days)
	{
		var date=new Date();
		date.setTime(date.getTime()+(days*24*60*60*1000));
		var expires="; expires="+date.toGMTString();
	}
	else expires="";
	document.cookie=name+"="+value+expires+"; path=/";
}

