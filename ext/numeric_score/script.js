document.addEventListener("DOMContentLoaded", () => {
    document.querySelectorAll(".numeric-score-votes-button").forEach((button) => {
        button.addEventListener("click", () => {
            fetch(button.dataset.votesUrl)
                .then((r) => r.text())
                .then((html) => {
                    document.getElementById("votes-content").innerHTML = html;
                });
        });
    });
});
