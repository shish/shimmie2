function Comic(root, comicURL) {
    let self = this;

    this.root = document.getElementById(root);
    this.comicPages = [];
    this.comicPage = 0;
    this.comicZip = null;

    this.setComic = function(zip) {
        let self = this;
        self.comicZip = zip;

        // Shimmie-specific; nullify existing back / forward
        document.querySelector("LINK[rel='previous']").remove();
        document.querySelector("LINK[rel='next']").remove();

        zip.forEach(function (relativePath, file){
            self.comicPages.push(relativePath);
        });
        self.comicPages.sort();
        for(let i=0; i<self.comicPages.length; i++) {
            let op = document.createElement("OPTION");
            op.value = i;
            op.innerText = (i+1) + " / " + self.comicPages.length + " - " + self.comicPages[i];
            document.getElementById("comicPageList").appendChild(op);
        }
        self.setPage(0);
    };

    this.setPage = function(n) {
        self.comicPage = n;

        self.comicZip.file(self.comicPages[n]).async("arraybuffer").then(function(arrayBufferView) {
            let blob = new Blob( [ arrayBufferView ], { type: "image/jpeg" } );
            let urlCreator = window.URL || window.webkitURL;
            document.getElementById("comicPage").src = urlCreator.createObjectURL( blob );
        });
        document.getElementById("comicPageList").value = self.comicPage;
    };

    this.prev = function() {
        if(self.comicPage > 0) {
            self.setPage(self.comicPage-1);
            document.getElementById("comicMain").scrollIntoView();
        }
    };

    this.next = function() {
        if(self.comicPage < self.comicPages.length) {
            self.setPage(self.comicPage+1);
            document.getElementById("comicMain").scrollIntoView();
        }
    };

    this.onKeyUp = function(e) {
        let t = e.target;
        if (t.tagName === "INPUT" || t.tagName === "TEXTAREA") { return; }
        if (e.metaKey || e.ctrlKey || e.altKey || e.shiftKey) { return; }
        if (e.keyCode === 37) {self.prev();}
        else if (e.keyCode === 39) {self.next();}
    };

    this.onPageChanged = function(e) {
        self.setPage(parseInt(document.getElementById("comicPageList").value));
    };

    JSZipUtils.getBinaryContent(comicURL, function(err, data) {
        if(err) {throw err;}
        JSZip.loadAsync(data).then(function (zip) {
            self.setComic(zip);
        });
    });

    document.addEventListener("keyup", this.onKeyUp);
    document.getElementById("comicNext").addEventListener("click", this.next);
    document.getElementById("comicPrev").addEventListener("click", this.prev);
    document.getElementById("comicPageList").addEventListener("change", this.onPageChanged);
    document.getElementById("comicPageList").addEventListener("keyup", function(e) {e.stopPropagation();});

    return this;
}
