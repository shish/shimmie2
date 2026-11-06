function fileSize(size) {
    var i = Math.floor(Math.log(size) / Math.log(1024));
    return (
        (size / Math.pow(1024, i)).toFixed(2) * 1 +
        ["B", "kB", "MB", "GB", "TB"][i]
    );
}
function updateTracker() {
    var size = 0;
    var upbtn = document.getElementById("uploadbutton");
    var tracker = document.getElementById("upload_size_tracker");
    var lockbtn = false;
    var form = document.getElementById("file_upload");
    var maxSize = parseInt(form.dataset.maxSize, 10) || 0;
    var maxTotalSize = parseInt(form.dataset.maxTotalSize, 10) || 0;

    // check that each individual file is less than the max file size
    document
        .querySelectorAll("#large_upload_form input[type='file']")
        .forEach((input) => {
            var cancelbtn = document.getElementById("cancel" + input.id);
            var toobig = false;
            if (input.files.length) {
                if (cancelbtn) cancelbtn.style.visibility = "visible";
                for (var i = 0; i < input.files.length; i++) {
                    size += input.files[i].size + 1024; // extra buffer for metadata
                    if (maxSize > 0 && input.files[i].size > maxSize) {
                        toobig = true;
                    }
                }
                if (toobig) {
                    lockbtn = true;
                    input.style = "color:red";
                } else {
                    input.style = "color:initial";
                }
            } else {
                if (cancelbtn) cancelbtn.style.visibility = "hidden";
                input.style = "color:initial";
            }
        });

    // check that the total is less than the max total size
    if (size) {
        tracker.innerText = fileSize(size);
        if (maxTotalSize > 0 && size > maxTotalSize) {
            lockbtn = true;
            tracker.style = "color:red";
        } else {
            tracker.style = "color:initial";
        }
    } else {
        tracker.innerText = "0MB";
    }
    upbtn.disabled = lockbtn;
}
document.addEventListener("DOMContentLoaded", () => {
    if (document.getElementById("upload_size_tracker")) {
        document
            .querySelectorAll("#large_upload_form input[type='file']")
            .forEach((el) => {
                el.addEventListener("change", updateTracker);
            });
        updateTracker();
    }

    document.querySelectorAll(".upload-cancel").forEach((el) => {
        el.addEventListener("click", function () {
            const input = document.getElementById(this.dataset.target);
            if (input) {
                input.value = "";
            }
            updateTracker();
        });
    });
});
