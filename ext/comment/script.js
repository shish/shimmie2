Comment.replyTo = function(imageId, commentId, userId) {
    var box = document.getElementById("comment_on_" + imageId);
    box.focus();
    box.value += `[url=site://post/view/${imageId}#c${commentId}]@${userId}[/url]: `;
    shm_blink(document.getElementById("c" + commentId));
}

Comment.edit = function(element) {
    const comment_id = element.dataset.comment_id;
    const post_id = element.dataset.post_id;
    const content = element.dataset.content;
    const exists = document.getElementById(`editing_${comment_id}`)
    if (exists){
        exists.remove();
        return;
    }
    const parent = document.getElementById(`c${comment_id}`);
	const postBox = document.getElementById(`comment_add_${post_id}`);
    if (!postBox || !parent) return;
    const editBox = postBox.cloneNode(true);
    editBox.id = `editing_${comment_id}`;
    const form = editBox.querySelector("form");
    if (form) {
        const textarea = form.querySelector("textarea");
        if (textarea) {
            textarea.removeAttribute("id");
            textarea.value = content;
        }
        const submit = form.querySelector("input[type=submit]");
        if (submit){
            submit.value = "Edit Comment";
        }
        form.action = form.action.replace(/add$/, "edit")
        const input = document.createElement("input");
        input.type = "hidden";
        input.name = "comment_id";
        input.value = comment_id;
        form.appendChild(input);
        parent.appendChild(editBox);
    }
}
