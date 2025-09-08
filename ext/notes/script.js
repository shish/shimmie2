/**
 * @type {
 *     notesContainer: HTMLElement,
 *     noteImage: HTMLElement,
 *     noteBeingEdited: null | number,
 *     dragStart: null | {
 *         x: number,
 *         y: number,
 *         mode: string,
 *     },
 * } Notes
 */
let Notes = {
    notesContainer: null,
    noteImage: null,
    noteBeingEdited: null,
    dragStart: null,
};

document.addEventListener("DOMContentLoaded", () => {
    Notes.noteImage = document.getElementById("main_image");
    if (window.notes && Notes.noteImage) {
        if (Notes.noteImage.complete) {
            Notes.renderNotes();
        } else {
            Notes.noteImage.addEventListener("load", Notes.renderNotes);
        }

        let resizeObserver = new ResizeObserver(Notes.renderNotes);
        resizeObserver.observe(Notes.noteImage);

        Notes.noteImage.parentNode.addEventListener(
            "scroll",
            Notes.renderNotes,
        );
    }
});

Notes.renderNotes = function () {
    let notesContainer = Notes.notesContainer;

    // reset the DOM to empty
    if (notesContainer) {
        notesContainer.remove();
    }

    // check the image we're adding notes on top of
    let br = Notes.noteImage.getBoundingClientRect();
    let scale = br.width / Notes.noteImage.dataset.width;

    // render a container full of notes
    Notes.notesContainer = document.createElement("div");
    notesContainer = Notes.notesContainer;
    notesContainer.className = "notes-container";
    notesContainer.style.top = br.top + "px";
    notesContainer.style.left = br.left + "px";
    notesContainer.style.width = br.width + "px";
    notesContainer.style.height = br.height + "px";

    // render each note
    window.notes.forEach((note) => {
        let noteDiv = document.createElement("div");
        noteDiv.classList.add("note");
        noteDiv.style.left = note.x1 * scale + "px";
        noteDiv.style.top = note.y1 * scale + "px";
        noteDiv.style.width = note.width * scale + "px";
        noteDiv.style.height = note.height * scale + "px";
        let text = document.createElement("div");
        text.innerText = note.note;
        // only add listener if user has edit permissions
        if (window.notes_edit) {
            noteDiv.addEventListener("click", (e) => {
                Notes.noteBeingEdited = note.note_id;
                Notes.renderNotes();
            });
        }
        noteDiv.appendChild(text);
        notesContainer.appendChild(noteDiv);

        // if the current note is being edited, render the editor
        if (note.note_id == Notes.noteBeingEdited) {
            let editor = Notes.renderEditor(noteDiv, note);
            notesContainer.appendChild(editor);
        }
    });

    Notes.noteImage.parentNode.appendChild(notesContainer);
};

/**
 *
 * @param {HTMLElement} noteDiv
 * @param {*} note
 * @returns
 */
Notes.renderEditor = function (noteDiv, note) {
    // check the image we're adding notes on top of
    let br = Notes.noteImage.getBoundingClientRect();
    let scale = br.width / Notes.noteImage.dataset.width;

    // set the note itself into drag & resize mode
    // NOTE: to avoid re-rendering the whole DOM every time the mouse
    // moves, we directly edit the style of the noteDiv, and then when
    // the mouse is released, we update the note object and re-render
    noteDiv.classList.add("editing");
    noteDiv.addEventListener("mousedown", (e) => {
        Notes.dragStart = {
            x: e.pageX,
            y: e.pageY,
            mode: Notes.getArea(
                e.offsetX,
                e.offsetY,
                noteDiv.clientWidth,
                noteDiv.clientHeight,
            ),
        };
        noteDiv.classList.add("dragging");
        Notes.notesContainer.classList.add("dragging");
    });
    noteDiv.addEventListener("mousemove", (e) => {
        let dragStart = Notes.dragStart;
        if (dragStart) {
            if (dragStart.mode == "c") {
                noteDiv.style.left =
                    note.x1 * scale + (e.pageX - dragStart.x) + "px";
                noteDiv.style.top =
                    note.y1 * scale + (e.pageY - dragStart.y) + "px";
            }
            if (dragStart.mode.indexOf("n") >= 0) {
                noteDiv.style.top =
                    note.y1 * scale + (e.pageY - dragStart.y) + "px";
                noteDiv.style.height =
                    note.height * scale - (e.pageY - dragStart.y) + "px";
            }
            if (dragStart.mode.indexOf("s") >= 0) {
                noteDiv.style.height =
                    note.height * scale + (e.pageY - dragStart.y) + "px";
            }
            if (dragStart.mode.indexOf("w") >= 0) {
                noteDiv.style.left =
                    note.x1 * scale + (e.pageX - dragStart.x) + "px";
                noteDiv.style.width =
                    note.width * scale - (e.pageX - dragStart.x) + "px";
            }
            if (dragStart.mode.indexOf("e") >= 0) {
                noteDiv.style.width =
                    note.width * scale + (e.pageX - dragStart.x) + "px";
            }
        } else {
            let area = Notes.getArea(
                e.offsetX,
                e.offsetY,
                noteDiv.clientWidth,
                noteDiv.clientHeight,
            );
            if (area == "c") {
                noteDiv.style.cursor = "move";
            } else {
                noteDiv.style.cursor = area + "-resize";
            }
        }
    });
    function _commit() {
        noteDiv.classList.remove("dragging");
        Notes.notesContainer.classList.remove("dragging");
        Notes.dragStart = null;
        note.x1 = Math.round(noteDiv.offsetLeft / scale);
        note.y1 = Math.round(noteDiv.offsetTop / scale);
        note.width = Math.round(noteDiv.offsetWidth / scale);
        note.height = Math.round(noteDiv.offsetHeight / scale);
        Notes.renderNotes();
    }
    noteDiv.addEventListener("mouseup", _commit);
    noteDiv.addEventListener("mouseleave", _commit);

    // add textarea / save / cancel / delete buttons
    let editor = document.createElement("div");
    editor.classList.add("editor");
    editor.style.left = note.x1 * scale + "px";
    editor.style.top = (note.y1 + note.height) * scale + "px";

    let textarea = document.createElement("textarea");
    textarea.value = note.note;
    textarea.addEventListener("input", () => {
        note.note = textarea.value;
    });
    editor.appendChild(textarea);

    let save = document.createElement("button");
    save.innerText = "Save";
    save.addEventListener("click", () => {
        if (note.note_id == null) {
            fetch(shm_make_link("note/create_note"), {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                },
                body: JSON.stringify(note),
            })
                .then((response) => {
                    if (response.ok) {
                        return response.json();
                    } else {
                        throw new Error("Failed to create note");
                    }
                })
                .then((data) => {
                    note.note_id = data.note_id;
                    Notes.renderNotes();
                })
                .catch((error) => {
                    alert(error);
                });
        } else {
            fetch(shm_make_link("note/update_note"), {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                },
                body: JSON.stringify(note),
            })
                .then((response) => {
                    if (!response.ok) {
                        throw new Error("Failed to update note");
                    }
                })
                .catch((error) => {
                    alert(error);
                });
        }
        Notes.noteBeingEdited = null;
        Notes.renderNotes();
    });
    editor.appendChild(save);

    let cancel = document.createElement("button");
    cancel.innerText = "Cancel";
    cancel.addEventListener("click", () => {
        Notes.noteBeingEdited = null;
        if (note.note_id == null) {
            // delete the un-saved note
            window.notes = window.notes.filter((n) => n.note_id != null);
        }
        Notes.renderNotes();
    });
    editor.appendChild(cancel);

    if (window.notes_admin && note.note_id != null) {
        let deleteNote = document.createElement("button");
        deleteNote.innerText = "Delete";
        deleteNote.addEventListener("click", () => {
            // TODO: delete note from server
            fetch(shm_make_link("note/delete_note"), {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                },
                body: JSON.stringify(note),
            })
                .then((response) => {
                    if (!response.ok) {
                        throw new Error("Failed to delete note");
                    }
                })
                .catch((error) => {
                    alert(error);
                });
            Notes.noteBeingEdited = null;
            window.notes = window.notes.filter(
                (n) => n.note_id != note.note_id,
            );
            Notes.renderNotes();
        });
        editor.appendChild(deleteNote);
    }

    return editor;
};

Notes.addNewNote = function () {
    if (window.notes.filter((note) => note.note_id == null).length > 0) {
        alert("Please save all notes before adding a new one.");
        return;
    }
    window.notes.push({
        x1: Notes.noteImage.dataset.width * 0.1,
        y1: Notes.noteImage.dataset.height * 0.1,
        width: Notes.noteImage.dataset.width * 0.2,
        height: Notes.noteImage.dataset.height * 0.1,
        note: "new note",
        note_id: null,
        image_id: window.notes_image_id,
    });
    Notes.noteBeingEdited = null;
    Notes.renderNotes();
};

/**
 *
 * @param {number} x
 * @param {number} y
 * @param {number} width
 * @param {number} height
 */
Notes.getArea = function (x, y, width, height, border = 16) {
    let area = "";
    if (y < border) area += "n";
    if (y > height - border) area += "s";
    if (x < border) area += "w";
    if (x > width - border) area += "e";
    if (area === "") area = "c";
    return area;
};
