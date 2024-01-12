const observer = new PerformanceObserver((list) => {
    list.getEntries().forEach((entry) => {
        shm_log("timing", {"v": 2, ...entry.toJSON()});
    });
});
observer.observe({ entryTypes: ["navigation"] });
