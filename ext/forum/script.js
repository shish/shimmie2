class Forum{}

Forum.edit = function(element) {
    const post_id = element.dataset.post_id;
    const thread_id = element.dataset.thread_id;
    const content = element.dataset.content;
    const exists = document.getElementById(`editing_${post_id}`)
    if (exists){
        exists.remove();
        return;
    }
    const parent = document.getElementById(`${post_id}`);
	const postBox = document.getElementById('post_composer');
    if (!postBox || !parent) return;
    const editBox = postBox.cloneNode(true);
    editBox.id = `editing_${post_id}`;
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
        const small = form.querySelector("small");
        const table = form.querySelector("table");
        if (textarea && submit && small && table) {
            form.appendChild(textarea);
            form.appendChild(small);
            form.appendChild(submit);
            table.remove();
        }
        form.action = form.action.replace(/answer$/, "edit")
        const input = document.createElement("input");
        input.type = "hidden";
        input.name = "post_id";
        input.value = post_id;
        form.appendChild(input);
        parent.appendChild(editBox);
    }
}