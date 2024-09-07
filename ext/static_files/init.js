function shm_cookie_set(name, value) {
	Cookies.set(name, value, {expires: 365, samesite: "lax", path: "/"});
}
function shm_cookie_get(name) {
	return Cookies.get(name);
}
function shm_make_link(page, query) {
    let base = (document.body.getAttribute("data-base-link") ?? "");
    let joiner = base.indexOf("?") === -1 ? "?" : "&";
    let url = base + page;
    if(query) url += joiner + new URLSearchParams(query).toString();
    return url;
}

function shm_log(section, ...message) {
    window.dispatchEvent(new CustomEvent("shm_log", {detail: {section, message}}));
}
window.addEventListener("shm_log", function (e) {
    console.log(e.detail.section, ...e.detail.message);
});
window.addEventListener("error", function (e) {
    shm_log("Window error:", e.error);
});
