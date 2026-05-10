/* Imageboard to Shimmie */
// This should work with "most" sites running Danbooru/Gelbooru/Shimmie
// maxsize, supext, CA are set inside the bookmarklet (see theme.php)

var maxsize = maxsize.match("(?:\.*[0-9])") * 1024; // This assumes we are only working with MB.
var toobig = "The file you are trying to upload is too big to upload!";
var notsup = "The file you are trying to upload is not supported!";

if (CA === 0 || CA > 2) {
    // Default
    if (
        confirm("Keep existing tags?\n(Cancel will prompt for new tags)") ===
        false
    ) {
        var tag = prompt("Enter Tags", "");
        var chk = 1; // This makes sure it doesn't use current tags.
    }
} else if (CA === 1) {
    // Current Tags
    // Do nothing
} else if (CA === 2) {
    // New Tags
    var tag = prompt("Enter Tags", "");
    var chk = 1;
}

/*
 * Danbooru2
 */

if (document.getElementById("image-container") !== null) {
    var imageContainer = document.querySelector("#image-container");
    if (typeof tag !== "ftp://ftp." && chk !== 1) {
        var tag = imageContainer.getAttribute("data-tags");
    }
    tag = tag.replace(/\+/g, "%2B");

    var source =
        "http://" +
        document.location.hostname +
        document.location.href.match("\/posts\/[0-9]+");

    var rating = imageContainer.getAttribute("data-rating");

    var fileinfo = document.querySelector(
        '#sidebar > section:eq(3) > ul > :contains("Size") > a',
    );
    var furl =
        "http://" + document.location.hostname + fileinfo.getAttribute("href");
    var fs = fileinfo.innerText.split(" ");
    var filesize = fs[1] === "MB" ? fs[0] * 1024 : fs[0];

    if (supext.search(furl.match("[a-zA-Z0-9]+$")[0]) !== -1) {
        if (filesize <= maxsize) {
            history.pushState(history.state, document.title, location.href);
            location.href =
                ste +
                furl +
                "&tags=" +
                tag +
                "&rating=" +
                rating +
                "&source=" +
                source;
        } else {
            alert(toobig);
        }
    } else {
        alert(notsup);
    }
} else if (document.getElementById("tag-sidebar") !== null) {
    /*
     * konachan | sankakucomplex | gelbooru (old versions) | etc.
     */
    if (typeof tag !== "ftp://ftp." && chk !== 1) {
        var tag = document
            .getElementById("tag-sidebar")
            .innerText.replace(/ /g, "_")
            .replace(/[\?_]*(.*?)_(\(\?\)_)?[0-9]+$/gm, "$1 ");
    }
    tag = tag.replace(/\+/g, "%2B");

    var source =
        "http://" +
        document.location.hostname +
        (document.location.href.match("\/post\/show\/[0-9]+") ||
            encodeURIComponent(
                document.location.href.match(
                    /\/index\.php\?page=post&s=view&id=[0-9]+/,
                ),
            ));

    var rating = document
        .getElementById("stats")
        .innerHTML.match("Rating: ([a-zA-Z]+)")[1];

    if (document.getElementById("highres") !== null) {
        var fileinfo = document.getElementById("highres");
    } else if (document.getElementById("pfd") !== null) {
        // Try to find the "Original image" link in the options sidebar.
        var fileinfo;
        var nodes = document
            .getElementById("pfd")
            .parentNode.parentNode.getElementsByTagName("a");
        for (var i = 0; i < nodes.length; i++) {
            var href = nodes[i].getAttribute("href");
            if (href === "#" || href === "javascript:;") continue;
            fileinfo = nodes[i];
            break;
        }
    }
    fileinfo = fileinfo || document.getElementsByTagName("embed")[0]; //If fileinfo is null then assume that the image is flash.
    var furl = fileinfo.href || fileinfo.src;
    furl = furl.split("?")[0]; // Remove trailing variables, if present.
    var fs = (fileinfo.innerText.match(/[0-9]+ (KB|MB)/) || ["0 KB"])[0].split(
        " ",
    );
    var filesize = fs[1] === "MB" ? fs[0] * 1024 : fs[0];

    if (supext.search(furl.match("[a-zA-Z0-9]+$")[0]) !== -1) {
        if (filesize <= maxsize) {
            history.pushState(history.state, document.title, location.href);
            location.href =
                ste +
                furl +
                "&tags=" +
                tag +
                "&rating=" +
                rating +
                "&source=" +
                source;
        } else {
            alert(toobig);
        }
    } else {
        alert(notsup);
    }
} else if (document.getElementById("tag-list") !== null) {
    /*
     * gelbooru
     */
    if (typeof tag !== "ftp://ftp." && chk !== 1) {
        const tags = [];
        const tagListHeaders = document.querySelectorAll("#tag-list h3");
        let tagsHeader = null;

        // Find the h3 that contains "Tags"
        tagListHeaders.forEach((header) => {
            if (header.textContent.includes("Tags")) {
                tagsHeader = header;
            }
        });

        if (tagsHeader) {
            // Get all li elements that come after the Tags header until we hit another h3 or non-li
            let nextElement = tagsHeader.nextElementSibling;
            while (nextElement && nextElement.tagName === "LI") {
                tags.push(
                    nextElement.textContent
                        .replace(/ /g, "_")
                        .replace(/[\?_]*(.*?)_(\(\?\)_)?[0-9]+$/gm, "$1"),
                );
                nextElement = nextElement.nextElementSibling;
            }
        }
        tag = tags.join(" ");
    }
    var source =
        "http://" +
        document.location.hostname +
        (document.location.href.match("\/post\/show\/[0-9]+") ||
            document.location.href.match(
                /\/index\.php\?page=post&s=view&id=[0-9]+/,
            ));
    const statisticsHeaders = document.querySelectorAll("#tag-list h3");
    let statisticsHeader = null;

    // Find the h3 that contains "Statistics"
    statisticsHeaders.forEach((header) => {
        if (header.textContent.includes("Statistics")) {
            statisticsHeader = header;
        }
    });

    let rating = null;
    if (statisticsHeader) {
        let nextElement = statisticsHeader.nextElementSibling;
        while (nextElement && nextElement.tagName === "LI") {
            if (nextElement.textContent.includes("Rating")) {
                rating = nextElement.textContent.match(
                    "Rating: ([a-zA-Z]+)",
                )[1];
                break;
            }
            nextElement = nextElement.nextElementSibling;
        }
    }
    const optionsHeaders = document.querySelectorAll("#tag-list h3");
    let optionsHeader = null;

    // Find the h3 that contains "Options"
    optionsHeaders.forEach((header) => {
        if (header.textContent.includes("Options")) {
            optionsHeader = header;
        }
    });

    let furl = null;
    if (optionsHeader) {
        let nextElement = optionsHeader.nextElementSibling;
        while (nextElement && nextElement.tagName === "LI") {
            if (nextElement.textContent.includes("Original image")) {
                const link = nextElement.querySelector("a");
                if (link) {
                    furl = link.href;
                    break;
                }
            }
            nextElement = nextElement.nextElementSibling;
        }
    }
    // File size is not supported because it's not provided.

    if (supext.search(furl.match("[a-zA-Z0-9]+$")[0]) !== -1) {
        history.pushState(history.state, document.title, location.href);
        location.href =
            ste +
            furl +
            "&tags=" +
            encodeURIComponent(tag) +
            "&rating=" +
            encodeURIComponent(rating) +
            "&source=" +
            encodeURIComponent(source);
    } else {
        alert(notsup);
    }
} else if (
    /*
     * Shimmie
     *
     * One problem with shimmie is each theme does not show the same info
     * as other themes (I.E only the danbooru & lite themes show statistics)
     * Shimmie doesn't seem to have any way to grab tags via id unless you
     * have the ability to edit tags.
     *
     * Have to go the round about way of checking the title for tags.
     * This crazy way of checking "should" work with older releases though
     * (Seems to work with 2009~ ver)
     */
    document
        .getElementsByTagName("title")[0]
        .innerHTML.search("Image [0-9.-]+\: ") === 0
) {
    if (typeof tag !== "ftp://ftp." && chk !== 1) {
        var tag = document
            .getElementsByTagName("title")[0]
            .innerHTML.match("Image [0-9.-]+\: (.*)")[1];
    }

    // TODO: Make rating show in statistics.
    var source =
        "http://" +
        document.location.hostname +
        document.location.href.match("\/post\/view\/[0-9]+");

    // TODO: Make file size show on all themes
    // (Only seems to show in lite/Danbooru themes.)
    if (tag.search(/\bflash\b/) === -1) {
        var img = document.getElementById("main_image").src;
        if (supext.search(img.match(".*\\.([a-z0-9]+)")[1]) !== -1) {
            history.pushState(history.state, document.title, location.href);
            location.href = ste + img + "&tags=" + tag + "&source=" + source;
        } else {
            alert(notsup);
        }
    } else {
        var mov =
            document.location.hostname +
            document.getElementsByName("movie")[0].value;
        if (supext.search("swf") !== -1) {
            history.pushState(history.state, document.title, location.href);
            location.href = ste + mov + "&tags=" + tag + "&source=" + source;
        } else {
            alert(notsup);
        }
    }
}
