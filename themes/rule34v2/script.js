
var navHidden = false;
function toggleNav() {
	if(navHidden) {
		$('BODY').removeClass('navHidden');
		Cookies.set("ui-shownav", "true");
	}
	else {
		$('BODY').addClass('navHidden');
		Cookies.set("ui-shownav", "false");
	}
	navHidden = !navHidden;
}

document.addEventListener('DOMContentLoaded', () => {
	if(Cookies.get("ui-shownav") === "false") {
		toggleNav();
	}
});


var forceDesktop = false;
function toggleDesktop() {
	if(forceDesktop) {
		let viewport = document.querySelector("meta[name=viewport]");
		viewport.setAttribute('content', 'width=512');
		Cookies.set("ui-desktop", "false");
	}
	else {
		let viewport = document.querySelector("meta[name=viewport]");
		viewport.setAttribute('content', 'width=1024, initial-scale=0.4');
		Cookies.set("ui-desktop", "true");
		navHidden = true;
		toggleNav();
	}
	forceDesktop = !forceDesktop;
}

document.addEventListener('DOMContentLoaded', () => {
	if(Cookies.get("ui-desktop") === "true") {
		toggleDesktop();
	}
});
