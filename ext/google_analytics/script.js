var _gaq = _gaq || [];

document.addEventListener("DOMContentLoaded", () => {
    const meta = document.querySelector('meta[name="google-analytics-id"]');
    if (!meta || !meta.content) return;

    _gaq.push(["_setAccount", meta.content]);
    _gaq.push(["_trackPageview"]);

    const ga = document.createElement("script");
    ga.type = "text/javascript";
    ga.async = true;
    ga.src =
        ("https:" === document.location.protocol
            ? "https://ssl"
            : "http://www") + ".google-analytics.com/ga.js";
    document.head.appendChild(ga);
});
