document.addEventListener("DOMContentLoaded", () => {
    const order_pool = document.getElementById("order_pool");
    if (order_pool) {
        order_pool.addEventListener("change", function () {
            let val = this.options[this.selectedIndex].value;
            shm_cookie_set("ui-order-pool", val);
            window.location.href = "";
        });
    }
});
