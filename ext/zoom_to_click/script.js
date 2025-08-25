document.addEventListener("DOMContentLoaded", () => {
    let img = document.querySelector("img#main_image");
    if (!img) return;

    let image_width = parseInt(img.dataset.width, 10);
    let image_height = parseInt(img.dataset.height, 10);
    let parent = img.parentElement;
    parent.style.boxSizing = "border-box";

    // if the image is smaller than the parent's internal area, don't enable zoom
    if (
        image_width <= parent.clientWidth &&
        image_height <= parent.clientHeight
    )
        return;

    let zoomed = false;
    img.style.cursor = "zoom-in";

    function zoom(point = {}) {
        // Lock parent to existing size
        parent.style.width = parent.offsetWidth + "px";
        parent.style.height = parent.offsetHeight + "px";

        let width = img.offsetWidth;
        let height = img.offsetHeight;

        parent.classList.add("zoom-container");
        parent.scrollLeft =
            (image_width * point.x) / width - parent.offsetWidth / 2;
        parent.scrollTop =
            (image_height * point.y) / height - parent.offsetHeight / 2;

        img.style.cursor = "zoom-out";
    }

    function unzoom() {
        parent.classList.remove("zoom-container");
        parent.style.width = "";
        parent.style.height = "";
        img.style.cursor = "zoom-in";
    }

    img.addEventListener("click", function (e) {
        if (zoomed) unzoom();
        else zoom({ x: e.offsetX, y: e.offsetY });
        zoomed = !zoomed;
    });
});
