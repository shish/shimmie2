document.addEventListener("DOMContentLoaded", () => {
    const lightbox = GLightbox({
        selector: "[data-glightbox]",
        touchNavigation: false,
        // keyboardNavigation: false,
        draggable: false,
        openEffect: "none",
        closeEffect: "none",
    });
    const media = document.getElementById("shm_post_media");
    if (media) {
        media.addEventListener("click", (event) => {
            lightbox.setElements([
                {
                    href: event.target.src,
                    title: media.dataset.title || null,
                    description: media.dataset.description || null,
                    type: "image",
                },
            ]);
            lightbox.open();
        });
    }
});
