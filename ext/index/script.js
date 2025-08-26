document.addEventListener("DOMContentLoaded", () => {
    let blocked_tags = (ui_cookie_get("blocked-tags") || "").split(" ");
    let blocked_css = blocked_tags
        .filter((tag) => tag.length > 0)
        .map((tag) => tag.replace(/\\/g, "\\\\").replace(/"/g, '\\"'))
        .map((tag) => `.shm-thumb[data-tags~="${tag}"]`)
        .join(", ");
    if (blocked_css) {
        let style = document.createElement("style");
        style.innerHTML = blocked_css + " { display: none; }";
        document.head.appendChild(style);
    }

    //Generate a random seed when using order:random
    document
        .querySelectorAll('form > input[placeholder="Search"]')
        .forEach((input) => {
            input.parentNode.addEventListener("submit", function () {
                const tagArr = input.value.split(" ");

                const randomIndex = Math.max(
                    tagArr.indexOf("order:random"),
                    tagArr.indexOf("order=random"),
                );

                if (randomIndex !== -1) {
                    tagArr[randomIndex] =
                        "order:random_" + Math.floor(Math.random() * 9999 + 1);
                    input.value = tagArr.join(" ");
                }
            });
        });

    /*
     * If an image list has a data-query attribute, append
     * that query string to all thumb links inside the list.
     * This allows us to cache the same thumb for all query
     * strings, adding the query in the browser.
     */
    document.querySelectorAll(".shm-image-list").forEach(function (list) {
        var query = list.getAttribute("data-query");
        if (query) {
            list.querySelectorAll(".shm-thumb-link").forEach(function (thumb) {
                const href = thumb.getAttribute("href");
                const join = href.indexOf("?") === -1 ? "?" : "&";
                thumb.setAttribute("href", href + join + query);
            });
        }
    });
});

function select_blocked_tags() {
    var blocked_tags = prompt(
        "Enter tags to ignore",
        ui_cookie_get("blocked-tags") || "AI-generated",
    );
    if (blocked_tags !== null) {
        ui_cookie_set("blocked-tags", blocked_tags.toLowerCase());
        location.reload(true);
    }
}
