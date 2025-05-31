document.addEventListener("DOMContentLoaded", () => {
    document
        .getElementById("order_pool")
        .addEventListener("change", function () {
            var val = this.options[this.selectedIndex].value;
            shm_cookie_set("shm_ui-order-pool", val); //FIXME: This won't play nice if COOKIE_PREFIX is not "shm_".
            window.location.href = "";
        });
});
