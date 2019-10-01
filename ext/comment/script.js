function replyTo(imageId, commentId, userId) {
	var box = $("#comment_on_"+imageId);
	var text = "[url=site://post/view/"+imageId+"#c"+commentId+"]@"+userId+"[/url]: ";

	box.focus();
	box.val(box.val() + text);
	$("#c"+commentId).highlight();
}
