function replyTo(imageId, commentId, userId) {
	var box = document.getElementById("comment_on_"+imageId);
	var text = "[url=site://post/view/"+imageId+"#c"+commentId+"]@"+userId+"[/url]: ";

	box.focus();
	box.value += text;
	$("#c"+commentId).highlight();
}
