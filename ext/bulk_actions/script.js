var bulk_selector_active = false;
var bulk_selector_initialized = false;
var bulk_selector_valid = false;

function validate_selections(form, confirmationMessage) {
    var queryOnly = false;
    if (bulk_selector_active) {
        var data = get_selected_items();
        if (data.length === 0) {
            return false;
        }
    } else {
        var query = form.querySelector('input[name="bulk_query"]').value;

        if (query === null || query === "") {
            return false;
        } else {
            queryOnly = true;
        }
    }

    if (confirmationMessage != null && confirmationMessage !== "") {
        return confirm(confirmationMessage);
    } else if (queryOnly) {
        var action = form.querySelector('input[name="submit_button"]').value;

        return confirm(
            'Perform bulk action "' +
                action +
                '" on all images matching the current search?',
        );
    }

    return true;
}

function activate_bulk_selector() {
    set_selected_items([]);
    if (!bulk_selector_initialized) {
        document.querySelectorAll(".shm-thumb").forEach(function (block) {
            add_selector_button(block);
        });
    }
    document.getElementById("bulk_selector_controls").style.display = "";
    document.getElementById("bulk_selector_activate").style.display = "none";
    bulk_selector_active = true;
    bulk_selector_initialized = true;
}

function deactivate_bulk_selector() {
    set_selected_items([]);
    document.getElementById("bulk_selector_controls").style.display = "none";
    document.getElementById("bulk_selector_activate").style.display = "";
    var bulkInput = document.querySelector('input[name="bulk_selected_ids"]');
    if (bulkInput) {
        bulkInput.value = "";
    }
    bulk_selector_active = false;
}

function get_selected_items() {
    var input = document.getElementById("bulk_selected_ids");
    if (!input) {
        return [];
    }
    var data = input.value;
    if (data === "" || data == null) {
        data = [];
    } else {
        data = JSON.parse(data);
    }
    return data;
}

function set_selected_items(items) {
    document.querySelectorAll(".shm-thumb").forEach(function (thumb) {
        thumb.classList.remove("bulk_selected");
    });

    items.forEach(function (item) {
        var thumb = document.querySelector(
            '.shm-thumb[data-post-id="' + item + '"]',
        );
        if (thumb) {
            thumb.classList.add("bulk_selected");
        }
    });

    var inputs = document.querySelectorAll('input[name="bulk_selected_ids"]');
    inputs.forEach(function (input) {
        input.value = JSON.stringify(items);
    });
}

function select_item(id) {
    var data = get_selected_items();
    if (!data.includes(id)) data.push(id);
    set_selected_items(data);
}

function deselect_item(id) {
    var data = get_selected_items();
    if (data.includes(id)) data.splice(data.indexOf(id, 1));
    set_selected_items(data);
}

function toggle_selection(id) {
    var data = get_selected_items();
    if (data.includes(id)) {
        data.splice(data.indexOf(id), 1);
        set_selected_items(data);
        return false;
    } else {
        data.push(id);
        set_selected_items(data);
        return true;
    }
}

function select_all() {
    var items = [];
    document.querySelectorAll(".shm-thumb").forEach(function (block) {
        var id = parseInt(block.getAttribute("data-post-id"));
        items.push(id);
    });
    set_selected_items(items);
}

function select_invert() {
    var currentItems = get_selected_items();
    var items = [];
    document.querySelectorAll(".shm-thumb").forEach(function (block) {
        var id = parseInt(block.getAttribute("data-post-id"));
        if (!currentItems.includes(id)) {
            items.push(id);
        }
    });
    set_selected_items(items);
}

function select_none() {
    set_selected_items([]);
}

function select_range(start, end) {
    var data = get_selected_items();
    var selecting = false;
    document.querySelectorAll(".shm-thumb").forEach(function (block) {
        var id = parseInt(block.getAttribute("data-post-id"));
        if (id === start) selecting = true;

        if (selecting) {
            if (!data.includes(id)) data.push(id);
        }

        if (id === end) {
            selecting = false;
        }
    });
    set_selected_items(data);
}

var last_clicked_item;

function add_selector_button(block) {
    var c = function (e) {
        if (!bulk_selector_active) return true;

        e.preventDefault();
        e.stopPropagation();

        var id = parseInt(block.getAttribute("data-post-id"));
        if (e.shiftKey) {
            if (last_clicked_item < id) {
                select_range(id, last_clicked_item);
            } else {
                select_range(last_clicked_item, id);
            }
        } else {
            last_clicked_item = id;
            toggle_selection(id);
        }
        return false;
    };

    var links = block.querySelectorAll("A");
    links.forEach(function (link) {
        link.addEventListener("click", c);
    });
    // Sometimes the thumb *is* the A
    block.addEventListener("click", c);
}

document.addEventListener("DOMContentLoaded", function () {
    // Clear the selection, in case it was autocompleted by the browser.
    var bulkInput = document.getElementById("bulk_selected_ids");
    if (bulkInput) {
        bulkInput.value = "";
    }
});
