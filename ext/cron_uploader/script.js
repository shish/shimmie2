function copyInputToClipboard(inputId) {
    // Referenced from https://www.w3schools.com/howto/howto_js_copy_clipboard.asp
    let source = document.getElementById(inputId);
    source.select();
    source.setSelectionRange(0, 99999); /*For mobile devices*/
    document.execCommand("copy");
}
