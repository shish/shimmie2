function shm_cookie_set(name, value) {
	Cookies.set(name, value, {expires: 365, samesite: "lax", path: "/"});
}
function shm_cookie_get(name) {
	return Cookies.get(name);
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
