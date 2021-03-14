function transcodeSubmit(e) {
    var mime = document.getElementById('transcode_mime').value;
    if(!mime.includes("lossless=true") && format !== "image/png") {
        var lossless = document.getElementById('image_lossless');
        if(lossless!=null && lossless.value==='1') {
            return confirm('You are about to transcode from a lossless format to a lossy format. Lossless formats compress with no quality loss, but converting to a lossy format always results in quality loss, and it will lose more quality every time it is done again on the same image. Are you sure you want to perform this transcode?');
        } else {
            return confirm('Converting to a lossy format always results in quality loss, and it will lose more quality every time it is done again on the same image. Are you sure you want to perform this transcode?');
        }
    }
}
