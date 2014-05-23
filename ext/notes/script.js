/*jshint bitwise:true, curly:true, forin:false, noarg:true, noempty:true, nonew:true, undef:true, strict:false, browser:true, jquery:true */

$(function() {
	if(window.notes) {
		$('#main_image').load(function(){
			$('#main_image').imgNotes({notes: window.notes});
		});
	}

	$('#cancelnote').click(function(){
		$('#main_image').imgAreaSelect({ hide: true });
		$('#noteform').hide();
	});

	$('#EditCancelNote').click(function() {
		$('#main_image').imgAreaSelect({ hide: true });
		$('#noteEditForm').hide();
	});

	$('#addnote').click(function(){
		$('#noteEditForm').hide();
		$('#main_image').imgAreaSelect({ onSelectChange: showaddnote, x1: 120, y1: 90, x2: 280, y2: 210 });
		return false;
	});

	$('.note').click(function() {
		$('#noteform').hide();
		var imgOffset = $('#main_image').offset();

		var x1 = parseInt(this.style.left) - imgOffset.left;
		var y1 = parseInt(this.style.top) - imgOffset.top;
		var width = parseInt(this.style.width);
		var height = parseInt(this.style.height);
		var text = $(this).next('.notep').text().replace(/([^>]?)\\n{2}/g, '$1\\n');
		var id = $(this).next('.notep').next('.noteID').text();

		$('#main_image').imgAreaSelect({ onSelectChange: showeditnote, x1: x1, y1: y1, x2: x1 + width, y2: y1 + height });
		setEditNoteData(x1, y1, width, height, text, id);
	});
});
		
function showaddnote (img, area) {
	var imgOffset = $(img).offset();
	var form_left  = parseInt(imgOffset.left) + parseInt(area.x1);
	var form_top   = parseInt(imgOffset.top) + parseInt(area.y1) + parseInt(area.height)+5;

	$('#noteform').css({ left: form_left + 'px', top: form_top + 'px'});
	$('#noteform').show();
	$('#noteform').css('z-index', 10000);
	$('#NoteX1').val(area.x1);
	$('#NoteY1').val(area.y1);
	$('#NoteHeight').val(area.height);
	$('#NoteWidth').val(area.width);
}

function showeditnote (img, area) {
	var imgOffset = $(img).offset();
	var form_left  = parseInt(imgOffset.left) + area.x1;
	var form_top   = parseInt(imgOffset.top) + area.y2;

	$('#noteEditForm').css({ left: form_left + 'px', top: form_top + 'px'});
	$('#noteEditForm').show();
	$('#noteEditForm').css('z-index', 10000);
	$('#EditNoteX1').val(area.x1);
	$('#EditNoteY1').val(area.y1);
	$('#EditNoteHeight').val(area.height);
	$('#EditNoteWidth').val(area.width);
}

function setEditNoteData(x1, y1, width, height, text, id) {
	$('#EditNoteX1').val(x1);
	$('#EditNoteY1').val(y1);
	$('#EditNoteHeight').val(height);
	$('#EditNoteWidth').val(width);
	$('#EditNoteNote').text(text);
	$('#EditNoteID').val(id);
	$('#DeleteNoteNoteID').val(id);
}
