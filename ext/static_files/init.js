function shm_cookie_set(name, value) {
	Cookies.set(name, value, {expires: 365, samesite: "lax", path: "/"});
}
function shm_cookie_get(name) {
	return Cookies.get(name);
}
