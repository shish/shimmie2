/**
 * Autosize any textarea in the image-info box to fit their content.
 *
 * @param {HTMLElement} el
 */
function autosize(el) {
    setTimeout(function () {
        if (el.offsetHeight < el.scrollHeight) {
            el.style.height = `calc(${el.scrollHeight}px + 0.5em)`;
            el.style.width = el.offsetWidth + "px";
        }
    }, 0);
}

document.addEventListener("DOMContentLoaded", () => {
    document.querySelectorAll(".image_info textarea").forEach((el) => {
        el.addEventListener("keydown", () => autosize(el));
        autosize(el);
    });
});

/**
 * The image info box is in edit mode by default, so that no-js users can
 * still edit them. If they have JS, this sets it to view mode.
 */
function clearViewMode() {
    document.querySelectorAll(".image_info").forEach((element) => {
        element.classList.remove("infomode-view");
    });
    document.querySelectorAll(".image_info textarea").forEach((el) => {
        autosize(el);
    });
}

document.addEventListener("DOMContentLoaded", () => {
    // find elements with class image_info and set them to view mode
    // (by default, with no js, they are in edit mode - so that no-js
    // users can still edit them)
    document.querySelectorAll(".image_info").forEach((element) => {
        element.classList.add("infomode-view");
    });
});

/**
 * If there's a #foo=bar in .nextlink or .prevlink, translate it
 * to ?foo=bar - this lets us generate post/view pages with a
 * variety of parameters, but the variety is handled on the client
 * side so it can be cached as a single entry on the server side.
 */
function joinUrlSegments(base, query) {
    let separatorChar = "?";
    if (base.includes("?")) {
        separatorChar = "&";
    }
    return base + separatorChar + query;
}

function updateAttr(selector, attr, value) {
    document.querySelectorAll(selector).forEach(function (e) {
        let current = e.getAttribute(attr);
        let newval = joinUrlSegments(current, value);
        e.setAttribute(attr, newval);
    });
}

document.addEventListener("DOMContentLoaded", () => {
    if (document.location.hash.length > 3) {
        var query = document.location.hash.substring(1);

        updateAttr(".prevlink", "href", query);
        updateAttr(".nextlink", "href", query);
        updateAttr("form#image_delete_form", "action", query);
    }
});
