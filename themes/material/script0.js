$(function(){
  $( "#layout-top" ).click(function() {
    leftAddFullSize("top");
  });

  $( "#layout-right" ).click(function() {
    leftAddSideSize("right");
  });

  $( "#layout-bottom" ).click(function() {
    leftAddFullSize("bottom");
  });

  $( "#layout-left" ).click(function() {
    leftAddSideSize("left");
  });

  function leftAddSideSize(layout_type){
    $("#left-block").removeClass (function (index, css) {
        return (css.match (/(^|\s)mdl-cell--\S+/g) || []).join(' ');
    }).addClass("mdl-cell--4-col mdl-cell--8-col-tablet mdl-cell--4-col-phone");

    $(".left-blocks").removeClass (function (index, css) {
        return (css.match (/(^|\s)mdl-cell--\S+/g) || []).join(' ');
    }).addClass("mdl-cell--12-col mdl-cell--4-col-tablet");

    $("#main-block").removeClass (function (index, css) {
        return (css.match (/(^|\s)mdl-cell--\S+/g) || []).join(' ');
    }).addClass("mdl-cell--8-col mdl-cell--8-col-tablet");

    if(layout_type == "right"){
      $("#left-block").appendTo("#main-grid")
    } else {
      $("#left-block").prependTo("#main-grid")
    }
  	Cookies.set("ui-layout-type", layout_type, {path: '/', expires: 365});
    zoom("width");
  }

  function leftAddFullSize(layout_type){
    $("#left-block").removeClass (function (index, css) {
        return (css.match (/(^|\s)mdl-cell--\S+/g) || []).join(' ');
    }).addClass("mdl-cell--12-col");

    $(".left-blocks").removeClass (function (index, css) {
        return (css.match (/(^|\s)mdl-cell--\S+/g) || []).join(' ');
    }).addClass("mdl-cell--4-col");

    $("#main-block").removeClass (function (index, css) {
        return (css.match (/(^|\s)mdl-cell--\S+/g) || []).join(' ');
    }).addClass("mdl-cell--12-col");

    if(layout_type == "bottom"){
      $("#left-block").appendTo("#main-grid")
    } else {
      $("#left-block").prependTo("#main-grid")
    }
  	Cookies.set("ui-layout-type", layout_type, {path: '/', expires: 365});
    zoom("width");
  }
  current_layout = Cookies.get("layout-type");
  if (current_layout != null) {
    if(current_layout =="top" || current_layout == "bottom"){
      leftAddFullSize(current_layout);
    } else {
      leftAddSideSize(current_layout);
    }
  }
  $('#main-block, #left-block').show();
});


function zoom(zoom_type) {
	var img = $('.shm-main-image');
	if(zoom_type == "full") {
		img.css('max-width', img.data('width') + 'px');
		img.css('max-height', img.data('height') + 'px');
	}
	if(zoom_type == "width") {
		img.css('max-width', ($( "#main-block" ).width()) + 'px');
		img.css('max-height', img.data('height') + 'px');
	}
	if(zoom_type == "height") {
		img.css('max-width', img.data('width') + 'px');
		img.css('max-height', (window.innerHeight * 0.95) + 'px');
	}
	if(zoom_type == "both") {
		img.css('max-width', '95%');
		img.css('max-height', (window.innerHeight * 0.95) + 'px');
	}

	$(".shm-zoomer").val(zoom_type);

	Cookies.set("ui-image-zoom", zoom_type, {path: '/', expires: 365});
}
