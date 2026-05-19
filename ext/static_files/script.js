document.addEventListener("DOMContentLoaded", () => {
    // If a page wants to flash a message, but it does a redirect, then the
    // redirect will have ?flash=... appended to it so that the destination
    // can flash on its behalf, but we then want to remove the parameter so
    // that it doesn't stick around (eg if the user hits refresh)
    const url = new URL(window.location.href);
    const params = new URLSearchParams(url.search.slice(1));
    if (params.has("flash")) {
        params.delete("flash");
        window.history.replaceState(
            {},
            "",
            `${window.location.pathname}?${params}${window.location.hash}`,
        );
    }

    /** time-ago dates **/
    function updateTimeAgo() {
        const CUTOFF_MS = 365 * 24 * 60 * 60 * 1000; // 1 year in milliseconds
        const now = new Date();

        document.querySelectorAll("time[datetime]").forEach((timeElement) => {
            const datetime = timeElement.getAttribute("datetime");
            if (!datetime) return;

            const date = new Date(datetime);
            const diff = now - date;

            // If older than 1 year, keep the original text
            if (diff > CUTOFF_MS) {
                return;
            }

            // Calculate relative time
            const seconds = Math.floor(diff / 1000);
            const minutes = Math.floor(seconds / 60);
            const hours = Math.floor(minutes / 60);
            const days = Math.floor(hours / 24);
            const weeks = Math.floor(days / 7);
            const months = Math.floor(days / 30);

            let timeAgoText;
            if (seconds < 45) {
                timeAgoText = "a few seconds ago";
            } else if (seconds < 90) {
                timeAgoText = "a minute ago";
            } else if (minutes < 45) {
                timeAgoText = minutes + " minutes ago";
            } else if (minutes < 90) {
                timeAgoText = "an hour ago";
            } else if (hours < 24) {
                timeAgoText = hours + " hours ago";
            } else if (hours < 48) {
                timeAgoText = "a day ago";
            } else if (days < 7) {
                timeAgoText = days + " days ago";
            } else if (days < 14) {
                timeAgoText = "a week ago";
            } else if (days < 30) {
                timeAgoText = weeks + " weeks ago";
            } else if (days < 60) {
                timeAgoText = "a month ago";
            } else {
                timeAgoText = months + " months ago";
            }

            timeElement.textContent = timeAgoText;
        });
    }
    updateTimeAgo();
    setInterval(updateTimeAgo, 60000);

    /** sidebar toggle **/
    let sidebar_hidden = [];
    try {
        sidebar_hidden = (ui_cookie_get("sidebar-hidden") || "").split("|");
        for (let i = 0; i < sidebar_hidden.length; i++) {
            if (sidebar_hidden[i].length > 0) {
                document
                    .querySelectorAll(sidebar_hidden[i] + " .blockbody")
                    .forEach((e) => {
                        e.style.display = "none";
                    });
            }
        }
    } catch (err) {}
    $(".shm-toggler").each(function (idx, elm) {
        let tid = $(elm).data("toggle-sel");
        let tob = $(tid + " .blockbody");
        $(elm).click(function (e) {
            tob.slideToggle("slow");
            if (sidebar_hidden.indexOf(tid) === -1) {
                sidebar_hidden.push(tid);
            } else {
                for (let i = 0; i < sidebar_hidden.length; i++) {
                    if (sidebar_hidden[i] === tid) {
                        sidebar_hidden.splice(i, 1);
                    }
                }
            }
            ui_cookie_set("sidebar-hidden", sidebar_hidden.join("|"));
        });
    });

    /** unlocker buttons **/
    document.querySelectorAll(".shm-unlocker").forEach(function (elm) {
        let tid = elm.dataset.unlockSel;
        let tob = document.querySelector(tid);
        elm.addEventListener("click", function () {
            elm.disabled = true;
            tob.disabled = false;
        });
    });

    /** click-to-copy */
    document.querySelectorAll(".shm-clicktocopy").forEach(function (elm) {
        elm.addEventListener("click", function () {
            navigator.clipboard.writeText(elm.textContent);
        });
    });

    /** left/right arrow key bindings for next/prev page **/
    document.addEventListener("keyup", function (e) {
        if (e.target.matches("input,textarea")) {
            return;
        }
        if (e.metaKey || e.ctrlKey || e.altKey || e.shiftKey) {
            return;
        }
        const prevLink = document.querySelector("link[rel='previous']");
        const nextLink = document.querySelector("link[rel='next']");
        if (e.key === "ArrowLeft" && prevLink) {
            window.location.href = prevLink.getAttribute("href");
        } else if (e.key === "ArrowRight" && nextLink) {
            window.location.href = nextLink.getAttribute("href");
        }
    });

    /** checkbox range selection **/
    const chkboxes = document.querySelectorAll('input[type="checkbox"]');
    let lastChecked = null;
    chkboxes.forEach((checkbox) => {
        checkbox.addEventListener("click", function (e) {
            if (!lastChecked) {
                lastChecked = this;
                return;
            }

            if (e.shiftKey) {
                const checkboxesArray = Array.from(chkboxes);
                const start = checkboxesArray.indexOf(this);
                const end = checkboxesArray.indexOf(lastChecked);

                checkboxesArray
                    .slice(Math.min(start, end), Math.max(start, end) + 1)
                    .forEach((checkbox) => {
                        checkbox.checked = lastChecked.checked;
                    });
            }

            lastChecked = this;
        });
    });
});
