/**
 * Auto Complete v2.1
 * June 11, 2009
 * Corey Hart @ http://www.codenothing.com
 *
 * Auto Complete takes input from the user and runs a check through PHP to find what the user
 * is looking for. This test case runs a limited search on words that begin with the letter 'a'.
 *
 * @css: Optional class for list rollovers, defaults to 'non-404'
 */ 
;(function($){$.fn.autoComplete=function(css){this.each(function(){var $obj=$(this),$input=$("input[type='text']",$obj),settings={opt:-1,inputval:"",css:(css)?css:"non-404",ajax:$("input[name='href']",$obj).val()};$input.keyup(function(e){var key=e.keyCode;if((key>47&&key<91)||key==8){settings.opt=-1;settings.inputval=$input.val();sendRequest(settings.inputval)}else{if(key==37||key==39){settings.opt=-1;$("ul",$obj).html("")}else{if(key==38){if(settings.opt>=0){settings.opt--;var val=$("ul li",$obj).removeClass(settings.css).eq(settings.opt).addClass(settings.css).attr("rel");val=(settings.opt<0)?settings.inputval:val;if(val){$input.val(val)}}}else{if(key==40){if(settings.opt<$("ul li",$obj).length-1){settings.opt++;var val=$("ul li",$obj).removeClass(settings.css).eq(settings.opt).addClass(settings.css).attr("rel");if(val){$input.val(val)}}}}}}}).blur(function(){settings.opt=-1;$("ul",$obj).html("")});var sendRequest=function(val){$.post(settings.ajax,{value:val},function(json){$("ul",$obj).html("");json=eval(json);if(json&&json.length>0){for(i in json){$("ul",$obj).append('<li rel="'+json[i].value+'">'+json[i].display+"</li>")}mouseaction()}})};function mouseaction(){$("ul li",$obj).mouseover(function(){$("ul li",$obj).removeClass(settings.css);$input.val($(this).addClass(settings.css).attr("rel"))}).click(function(){$("ul",$obj).html("")});$("ul",$obj).mouseout(function(){$input.val(settings.inputval)})}})}})(jQuery);
