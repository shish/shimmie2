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
        shm_cookie_set("ui-image-zoom", type);
        zoomer.value = type;
    }

    zoomer.addEventListener("change", function () {
        resize(this.options[this.selectedIndex].value);
    });

    resize(shm_cookie_get("ui-image-zoom") ?? "both");
});
