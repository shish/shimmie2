// Disable things that get in the way of smooth admin'ing
document.addEventListener('DOMContentLoaded', () => {
	document.querySelectorAll("input[type='date']").forEach(e => e.setAttribute('type', 'text'));
});

// Navbar controls
var navHidden = false;
function toggleNav() {
	if(navHidden) {
		document.body.classList.remove('navHidden');
		shm_cookie_set("ui-shownav", "true");
	}
	else {
		document.body.classList.add('navHidden');
		shm_cookie_set("ui-shownav", "false");
	}
	navHidden = !navHidden;
}

document.addEventListener('DOMContentLoaded', () => {
	if(shm_cookie_get("ui-shownav") === "false") {
		toggleNav();
	}
});

// Desktop mode toggle
var forceDesktop = false;
function toggleDesktop() {
	if(forceDesktop) {
		let viewport = document.querySelector("meta[name=viewport]");
		viewport.setAttribute('content', 'width=512');
		shm_cookie_set("ui-desktop", "false");
	}
	else {
		let viewport = document.querySelector("meta[name=viewport]");
		viewport.setAttribute('content', 'width=1024, initial-scale=0.4');
		shm_cookie_set("ui-desktop", "true");
		navHidden = true;
		toggleNav();
	}
	forceDesktop = !forceDesktop;
}

document.addEventListener('DOMContentLoaded', () => {
	if(shm_cookie_get("ui-desktop") === "true") {
		toggleDesktop();
	}
});
