document.addEventListener("DOMContentLoaded", () => {
    const originalWidth = document.getElementById("original_width");
    const originalHeight = document.getElementById("original_height");
    const resizeWidth = document.getElementById("resize_width");
    const resizeHeight = document.getElementById("resize_height");
    const resizeAspect = document.getElementById("resize_aspect");

    if (
        !originalWidth ||
        !originalHeight ||
        !resizeWidth ||
        !resizeHeight ||
        !resizeAspect
    ) {
        return;
    }

    resizeWidth.addEventListener("change", function () {
        if (resizeAspect.checked) {
            resizeHeight.value = Math.round(
                (resizeWidth.value / originalWidth.value) *
                    originalHeight.value,
            );
        }
    });

    resizeHeight.addEventListener("change", function () {
        if (resizeAspect.checked) {
            resizeWidth.value = Math.round(
                (resizeHeight.value / originalHeight.value) *
                    originalWidth.value,
            );
        }
    });
});
