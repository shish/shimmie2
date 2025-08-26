document.addEventListener("DOMContentLoaded", () => {
    let zoomer = document.getElementById("shm-zoomer");
    let img = document.getElementById("main_image");
    if (!zoomer || !img) return;

    /**
     * @param {"full"|"width"|"both"} type
     */
    function resize(type) {
        img.classList.remove("fit-full", "fit-width", "fit-both");
        img.classList.add(`fit-${type}`);
        ui_cookie_set("image-zoom", type);
        zoomer.value = type;
    }

    zoomer.addEventListener("change", function () {
        resize(this.options[this.selectedIndex].value);
    });

    resize(ui_cookie_get("image-zoom") ?? "both");
});
