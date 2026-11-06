document.addEventListener("DOMContentLoaded", () => {
    const order_pool = document.getElementById("order_pool");
    if (order_pool) {
        order_pool.addEventListener("change", function () {
            let val = this.options[this.selectedIndex].value;
            shm_cookie_set("ui-order-pool", val);
            window.location.href = "";
        });
    }

    function set_all_checks(checked) {
        document
            .querySelectorAll('[name="check[]"]')
            .forEach((el) => (el.checked = checked));
    }
    document.querySelectorAll(".pool-check-all").forEach((btn) => {
        btn.addEventListener("click", () => set_all_checks(true));
    });
    document.querySelectorAll(".pool-uncheck-all").forEach((btn) => {
        btn.addEventListener("click", () => set_all_checks(false));
    });
});
