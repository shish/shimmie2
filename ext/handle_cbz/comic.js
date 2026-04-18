function Comic(rootElement) {
    let self = this;

    this.root = rootElement;
    this.comicPages = [];
    this.comicPage = 0;
    this.comicZip = null;

    let comicURL = rootElement.dataset.comicFile;
    if (!comicURL) {
        throw new Error(
            "Comic root element must have data-comic-file attribute",
        );
    }

    this.setComic = function (zip) {
        let self = this;
        self.comicZip = zip;

        // Shimmie-specific; nullify existing back / forward
        let prevLink = document.querySelector("LINK[rel='previous']");
        let nextLink = document.querySelector("LINK[rel='next']");
        if (prevLink) prevLink.remove();
        if (nextLink) nextLink.remove();

        zip.forEach(function (relativePath, file) {
            self.comicPages.push(relativePath);
        });
        self.comicPages.sort();

        let pageList = self.root.querySelector("#comicPageList");
        for (let i = 0; i < self.comicPages.length; i++) {
            let op = document.createElement("OPTION");
            op.value = i;
            op.innerText =
                i +
                1 +
                " / " +
                self.comicPages.length +
                " - " +
                self.comicPages[i];
            pageList.appendChild(op);
        }
        self.setPage(0);
    };

    this.setPage = function (n) {
        self.comicPage = n;

        self.comicZip
            .file(self.comicPages[n])
            .async("arraybuffer")
            .then(function (arrayBufferView) {
                let blob = new Blob([arrayBufferView], { type: "image/jpeg" });
                let urlCreator = window.URL || window.webkitURL;
                self.root.querySelector("#comicPage").src =
                    urlCreator.createObjectURL(blob);
            });
        self.root.querySelector("#comicPageList").value = self.comicPage;
    };

    this.prev = function () {
        if (self.comicPage > 0) {
            self.setPage(self.comicPage - 1);
            self.root.scrollIntoView();
        }
    };

    this.next = function () {
        if (self.comicPage < self.comicPages.length) {
            self.setPage(self.comicPage + 1);
            self.root.scrollIntoView();
        }
    };

    this.onKeyUp = function (e) {
        let t = e.target;
        if (t.tagName === "INPUT" || t.tagName === "TEXTAREA") {
            return;
        }
        if (e.metaKey || e.ctrlKey || e.altKey || e.shiftKey) {
            return;
        }
        if (e.keyCode === 37) {
            self.prev();
        } else if (e.keyCode === 39) {
            self.next();
        }
    };

    this.onPageChanged = function (e) {
        self.setPage(parseInt(self.root.querySelector("#comicPageList").value));
    };

    JSZipUtils.getBinaryContent(comicURL, function (err, data) {
        if (err) {
            throw err;
        }
        JSZip.loadAsync(data).then(function (zip) {
            self.setComic(zip);
        });
    });

    document.addEventListener("keyup", this.onKeyUp);
    self.root.querySelector("#comicNext").addEventListener("click", this.next);
    self.root.querySelector("#comicPrev").addEventListener("click", this.prev);
    self.root
        .querySelector("#comicPageList")
        .addEventListener("change", this.onPageChanged);
    self.root
        .querySelector("#comicPageList")
        .addEventListener("keyup", function (e) {
            e.stopPropagation();
        });

    return this;
}

// Auto-initialize all comics on page load
document.addEventListener("DOMContentLoaded", function () {
    document.querySelectorAll("[data-comic-file]").forEach(function (element) {
        new Comic(element);
    });
});
