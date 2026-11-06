document.addEventListener("DOMContentLoaded", () => {
    document.querySelectorAll("[data-tc-mode]").forEach((btn) => {
        btn.addEventListener("click", function () {
            const block = this.closest(".tagcategoryblock");
            if (block) {
                block.className = "tagcategoryblock " + this.dataset.tcMode;
            }
        });
    });
});
