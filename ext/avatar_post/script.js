// only run on the right page
if (
    window.location.pathname.startsWith("/set_avatar") || // clean urls
    window.location.search.startsWith("?q=set_avatar")
) {
    // ?q= urls
    let image;
    let zoom_slider;

    let scale_input;
    let x_input;
    let y_input;
    let zooming = false;

    let previous = null;
    const zps = 1000 / 30;
    function zoom(timestamp) {
        if (zooming) {
            if (previous === null) {
                previous = timestamp - 10;
            }
            const delta = (timestamp - previous) / zps;
            const val = zoom_slider.valueAsNumber;

            image.scale += val * delta;
            const scale = Math.round(image.scale);
            image.style.transform = `scale(${scale / 100})`;

            requestAnimationFrame(zoom);
            previous = timestamp;
        }
    }

    function zoom_input(e) {
        if (!zooming) {
            zooming = true;
            requestAnimationFrame(zoom);
        }
    }

    function zoom_change(e) {
        zooming = false;
        previous = null;
        e.target.value = 0;

        const scale = Math.round(image.scale);
        image.style.transform = `scale(${scale / 100})`;
        scale_input.value = Math.round(scale);
    }

    function zoom_init() {
        zoom_slider.addEventListener("change", zoom_change);
        zoom_slider.addEventListener("input", zoom_input);
    }

    let dragging = false;
    let prev_coords = [0, 0];
    function start_image_drag(e) {
        dragging = true;
        image.style.cursor = "grabbing";
        const clientX = e.clientX || e.touches[0].clientX;
        const clientY = e.clientY || e.touches[0].clientY;
        prev_coords = [clientX, clientY];
        e.preventDefault();
    }

    function image_drag(e) {
        if (!dragging) return;

        const clientX = e.clientX || e.touches[0].clientX;
        const clientY = e.clientY || e.touches[0].clientY;

        image.cx += clientX - prev_coords[0];
        image.cy += clientY - prev_coords[1];
        image.style.translate = `${image.cx}% ${image.cy}%`;
        prev_coords = [clientX, clientY];
    }

    function stop_image_drag() {
        if (dragging) {
            dragging = false;
            image.style.cursor = "grab";
            x_input.value = Math.round(image.cx);
            y_input.value = Math.round(image.cy);
        }
    }

    function drag_init() {
        image.addEventListener("mousedown", start_image_drag);
        document.addEventListener("mousemove", image_drag);
        document.addEventListener("mouseup", stop_image_drag);

        // Touch events for mobile
        image.addEventListener("touchstart", start_image_drag);
        document.addEventListener("touchmove", image_drag);
        document.addEventListener("touchend", stop_image_drag);
    }

    document.addEventListener("DOMContentLoaded", () => {
        zoom_slider = document.getElementById("zoom-slider");
        image = document.getElementById("avatar-edit");
        scale_input = document.getElementById("avatar-post-scale");
        x_input = document.getElementById("avatar-post-x");
        y_input = document.getElementById("avatar-post-y");
        if (!(zoom_slider && image && scale_input && x_input && y_input))
            return;
        image.scale = 100;
        image.cx = 0;
        image.cy = 0;
        image.style.cursor = "grab";
        zoom_init();
        drag_init();
    });
}
