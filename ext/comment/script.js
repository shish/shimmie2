function replyTo(imageId, commentId, userId) {
    var box = document.getElementById("comment_on_" + imageId);
    box.focus();
    box.value += `[url=site://post/view/${imageId}#c${commentId}]@${userId}[/url]: `;
    shm_blink(document.getElementById("c" + commentId));
}
