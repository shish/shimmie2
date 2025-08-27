document.addEventListener("DOMContentLoaded", () => {
    $(".shm-relationships-parent-toggle").click(function () {
        $(".shm-relationships-parent-thumbs").slideToggle("fast", function () {
            if ($(".shm-relationships-parent-thumbs").is(":hidden")) {
                $(".shm-relationships-parent-toggle").text("show »");
                ui_cookie_set("relationships-parent-hidden", "true");
            } else {
                $(".shm-relationships-parent-toggle").text("« hide");
                ui_cookie_set("relationships-parent-hidden", "false");
            }
        });
    });
    if (ui_cookie_get("relationships-parent-hidden") === "true") {
        $(".shm-relationships-parent-thumbs").hide();
        $(".shm-relationships-parent-toggle").text("show »");
    }

    $(".shm-relationships-child-toggle").click(function () {
        $(".shm-relationships-child-thumbs").slideToggle("fast", function () {
            if ($(".shm-relationships-child-thumbs").is(":hidden")) {
                $(".shm-relationships-child-toggle").text("show »");
                ui_cookie_set("relationships-child-hidden", "true");
            } else {
                $(".shm-relationships-child-toggle").text("« hide");
                ui_cookie_set("relationships-child-hidden", "false");
            }
        });
    });
    if (ui_cookie_get("relationships-child-hidden") === "true") {
        $(".shm-relationships-child-thumbs").hide();
        $(".shm-relationships-child-toggle").text("show »");
    }
});
