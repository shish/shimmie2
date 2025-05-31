document.addEventListener("DOMContentLoaded", () => {
    $(".shm-blotter2-toggle").click(function () {
        $(".shm-blotter2").slideToggle("slow", function () {
            if ($(".shm-blotter2").is(":hidden")) {
                shm_cookie_set("ui-blotter2-hidden", "true");
            } else {
                shm_cookie_set("ui-blotter2-hidden", "false");
            }
        });
    });
    if (shm_cookie_get("ui-blotter2-hidden") === "true") {
        document.querySelector(".shm-blotter2").style.display = "none";
    }
});
