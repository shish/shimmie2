document.addEventListener("DOMContentLoaded", () => {
    $(".shm-blotter2-toggle").click(function () {
        $(".shm-blotter2").slideToggle("slow", function () {
            if ($(".shm-blotter2").is(":hidden")) {
                ui_cookie_set("blotter2-hidden", "true");
            } else {
                ui_cookie_set("blotter2-hidden", "false");
            }
        });
    });
    if (ui_cookie_get("blotter2-hidden") === "true") {
        document.querySelector(".shm-blotter2").style.display = "none";
    }
});
