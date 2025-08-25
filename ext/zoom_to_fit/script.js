document.addEventListener("DOMContentLoaded", () => {
    function resize(type) {
        let $img = $("#main_image");
        let image_width = $img.data("width");
        let image_height = $img.data("height");

        $img.removeClass();
        $img.addClass(`fit-${type}`);
        if (
            window.innerWidth * 0.9 < image_width ||
            window.innerHeight * 0.9 < image_height
        ) {
            $img.css("cursor", "zoom-in");
        }
        shm_cookie_set("ui-image-zoom", type);
        $(".shm-zoomer").val(type);
    }

    $(".shm-zoomer").on("change", function (e) {
        resize(this.options[this.selectedIndex].value);
    });

    resize(shm_cookie_get("ui-image-zoom") ?? "both");
});
