/*jshint bitwise:true, curly:true, forin:false, noarg:true, noempty:true, nonew:true, undef:true, strict:false, browser:true, jquery:true */

var peekerOpen = false;
function calculatePeekerSize(imageWidth, imageHeight, maxWidth, maxHeight) {
    let xscale = maxWidth / imageWidth;
    let yscale = maxHeight / imageHeight;

    let scale;
    if(yscale  < xscale) {
        scale = yscale;
    } else {
        scale = xscale;
    }

    return [imageWidth * scale, imageHeight * scale];
}

function postPeekAddPeeker() {
    const mimeRegex = /^image\/.+/g;
    const cursorMargin = 20;
    const windowMargin = 50;

    var peekerElement = document.createElement("DIV");
    peekerElement.style.position = "absolute";
    peekerElement.style.border = "solid 1px black";
    peekerElement.style.boxShadow = "5px 5px 10px black";

    var image_elements = document.querySelectorAll(".shm-image-list a img");
    image_elements.forEach(function(item) {
        var parent = item.parentElement;
        parent.style.position = "relative";

        var mime =parent.dataset["mime"];
        if(mime.match(mimeRegex)) {
            var linkElement = document.createElement("DIV");
            linkElement.innerHTML = "&#x1F50D;";
            linkElement.style.position = "absolute";
            linkElement.style.top = "4px";
            linkElement.style.left = "4px";
            linkElement.style.width = "10px";
            linkElement.style.height = "10px";
            linkElement.style.fontSize = "20px";
            linkElement.style.color = "red";

            var width = parseInt(parent.dataset["width"]);
            var height = parseInt(parent.dataset["height"]);
            var ratio = width/height;


            linkElement.onmouseenter = function(e) {
                let imgElement = document.createElement("IMG");
                imgElement.src = item.src.replace("thumb/", "image/");
                imgElement.style.width = "100%";
                imgElement.style.aheight = "100%";

                peekerElement.innerHTML = "";

                let sizeCandidates = [];

                // Add arrays defining each possible area to render
                // Array is [left, right, top, bottom, [width, height]]

                // Calculate for the right area
                let dimensions = calculatePeekerSize(width, height,  window.innerWidth - e.clientX - windowMargin, window.innerHeight - windowMargin);
                sizeCandidates.push([
                    (e.clientX + cursorMargin - window.scrollX) + "px", // left
                    "", // right
                    "", // top
                    ((window.innerHeight - dimensions[1]) / 2  - window.scrollY) + "px", // bottom
                    dimensions
                ]);

                // Calculate for the bottom area
                dimensions = calculatePeekerSize(width, height,  window.innerWidth - windowMargin, window.innerHeight - e.clientY - windowMargin);
                sizeCandidates.push([
                    ((window.innerWidth - dimensions[0]) / 2  - window.scrollX) + "px", // left
                    "", // right
                    (e.clientY + cursorMargin + window.scrollY) + "px", // top
                    "", // bottom
                    dimensions
                ]);

                // Calculate for the left area
                dimensions = calculatePeekerSize(width, height, e.clientX - windowMargin, window.innerHeight - windowMargin);
                sizeCandidates.push([
                    "", // left
                    (window.innerWidth - e.clientX + cursorMargin - window.scrollX) + "px", // right
                    "", // top
                    ((window.innerHeight - dimensions[1]) / 2 - window.scrollY) + "px", // bottom
                    dimensions
                ]);

                // Calculate for the top area
                dimensions = calculatePeekerSize(width, height,  window.innerWidth - windowMargin, e.clientY - windowMargin);
                sizeCandidates.push([
                    ((window.innerWidth - dimensions[0]) / 2  - window.scrollX) + "px", // left
                    "", // right
                    "", // top
                    (window.innerHeight - e.clientY + cursorMargin - window.scrollY) + "px", // bottom
                    dimensions
                ]);

                let candidate = null;
                let candidateSize = 0;
                for(let i = 0; i < sizeCandidates.length; i++) {
                    let newCandidate = sizeCandidates[i];
                    let newCandidateSize = newCandidate[4][0] * newCandidate[4][1];
                    if(newCandidateSize>candidateSize) {
                        candidateSize = newCandidateSize;
                        candidate = newCandidate;
                    }
                }

                peekerElement.style.left = candidate[0];
                peekerElement.style.right = candidate[1];
                peekerElement.style.top =candidate[2];
                peekerElement.style.bottom = candidate[3];

                peekerElement.style.width = candidate[4][0] + "px";
                peekerElement.style.height =  candidate[4][1]  + "px";


                peekerElement.appendChild(imgElement);

                if(!peekerOpen) {
                    document.body.appendChild(peekerElement);
                }

                peekerOpen = true;
            }
            linkElement.onmouseleave = function (e) {
                if(peekerOpen) {
                    document.body.removeChild(peekerElement);
                    peekerOpen = false;
                }
            }

            parent.appendChild(linkElement);


            //
            // var offsetX = (item.offsetWidth - newWidth)/2;
            // var offsetY = (item.offsetHeight - newHeight)/2;
            //
            // var scaleX = newWidth / frameWidth;
            // var scaleY = newHeight / frameHeight;
            // var scale = scaleX;
            // if(scaleY<scaleX)  {
            //     scale = scaleY;
            // }
            // frameWidth = frameWidth * scale;
            // frameHeight = frameHeight * scale;
            //
            // offsetX = offsetX + ((newWidth - frameWidth)/2);
            // offsetY = offsetY + ((newHeight - frameHeight)/2);
            //
            // console.log("test");
            // var frame = $("<div class='frame" + inWidth + "x" + inHeight + "' style='position:absolute; left:" + offsetX + "px; top:" + offsetY + "px; width:" + frameWidth + "px; height:" + frameHeight + "px;outline:solid 1px " + color + ";color:" + color + ";vertical-align: bottom; '><div style='position:absolute; left:0; bottom:0;'>" + inWidth + ":" + inHeight + "</div></div>");
            // $(parent).append(frame);
        }
    });
}


document.addEventListener('DOMContentLoaded', () => {
    postPeekAddPeeker();
});
