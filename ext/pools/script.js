document.addEventListener("DOMContentLoaded", () => {
    document
        .getElementById("order_pool")
        .addEventListener("change", function () {
            let val = this.options[this.selectedIndex].value;
            shm_cookie_set("ui-order-pool", val);
            window.location.href = "";
        });
});
