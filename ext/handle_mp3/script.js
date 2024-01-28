document.addEventListener("DOMContentLoaded", () => {
  let main_image = document.getElementById("main_image");
  if (main_image) {
    main_image.setAttribute("volume", 0.25);
  }

  let base_href = document.body.getAttribute("data-base-href");
  let audio_src = document.getElementById("audio_src");
  if (audio_src) {
    let ilink = audio_src.getAttribute("src");
    window.jsmediatags.read(location.origin + base_href + ilink, {
      onSuccess: function (tag) {
        var artist = tag.tags.artist,
          title = tag.tags.title;

        document.getElementById("audio-title").innerText = title;
        document.getElementById("audio-artist").innerText = artist;
        document
          .getElementById("audio-download")
          .setAttribute(
            "download",
            (artist + " - " + title).substring(0, 250) + ".mp3",
          );
      },
      onError: function (error) {
        console.log(error);
      },
    });
  }
});
