document.addEventListener("DOMContentLoaded", () => {
    function escapeForGLB(text) {
        if (!text) return null;
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    const lightbox = GLightbox({
        //selector: "[data-glightbox]",
        touchNavigation: false,
        // keyboardNavigation: false,
        draggable: false,
        openEffect: "none",
        closeEffect: "none",
    });
    const media = document.getElementById("shm_post_media");
    if (media) {
        var type = null;
        if (media.dataset.mime.startsWith("image/")) {
            type = "image";
        }
        if (type !== null) {
            media.addEventListener("click", (event) => {
                lightbox.setElements([
                    {
                        href: event.target.src,
                        title: escapeForGLB(media.dataset.title),
                        description: escapeForGLB(media.dataset.description),
                        type: type,
                    },
                ]);
                lightbox.open();
            });
        }
    }
});
