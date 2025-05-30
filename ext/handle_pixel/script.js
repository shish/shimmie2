document.addEventListener("DOMContentLoaded", () => {
    function resize(type) {
        let $img = $("#main_image");
        let image_width = $img.data("width");
        let image_height = $img.data("height");

        $img.removeClass();
        if (type !== "full") {
            $img.addClass(`fit-${type}`);
        }
        if (window.innerWidth * 0.9 < image_width || window.innerHeight * 0.9 < image_height) {
            $img.css("cursor", "zoom-in");
        }
        shm_cookie_set("ui-image-zoom", type);
        $(".shm-zoomer").val(type);
    }

    function zoom(point = {}) {
        let $img = $("#main_image");
        let image_width = $img.data("width");
        let image_height = $img.data("height");

        if (window.innerWidth * 0.9 >= image_width && window.innerHeight * 0.9 >= image_height) {
            $img.css("cursor", '');
            if ($img.hasClass("zoom-point")) {
                $img.removeClass();
                $img.parent().removeClass("zoom-container");
                $img.addClass("fit-both");
            }
            return;
        }
        if ($img.hasClass("zoom-point")) {
            $img.removeClass();
            $img.parent().removeClass("zoom-container");
            $img.addClass("fit-both");
            $img.css("cursor", "zoom-in");
        } else {
            let width = $img.width();
            let height = $img.height();
            $img.removeClass();
            $img.addClass("zoom-point");
            $img.css("cursor", "zoom-out");

            let $parent = $img.parent();
            $parent.addClass("zoom-container");
            $parent.scrollLeft((image_width * point.x / width) - ($parent.width() / 2));
            $parent.scrollTop((image_height * point.y / height) - ($parent.height() / 2));
        }
        $(".shm-zoomer").val("both");
    }

    $(".shm-zoomer").on("change", function (e) {
        resize(this.options[this.selectedIndex].value);
    });

    $("img#main_image").on("click", function (e) {
        zoom({x: e.offsetX, y: e.offsetY})
    });

    if (shm_cookie_get("ui-image-zoom")) {
        resize(shm_cookie_get("ui-image-zoom"));
    } else {
        resize("both");
    }
});
