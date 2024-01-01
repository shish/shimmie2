/*jshint bitwise:true, curly:true, forin:false, noarg:true, noempty:true, nonew:true, undef:true, strict:false, browser:true, jquery:true */

var bulk_selector_active = false;
var bulk_selector_initialized = false;
var bulk_selector_valid = false;

function validate_selections(form, confirmationMessage) {
    var queryOnly = false;
    if(bulk_selector_active) {
        var data = get_selected_items();
        if(data.length===0) {
            return false;
        }
    } else {
        var query = $(form).find('input[name="bulk_query"]').val();

        if (query === null || query === "") {
            return false;
        } else {
            queryOnly = true;
        }
    }


    if(confirmationMessage!=null&&confirmationMessage!=="") {
        return confirm(confirmationMessage);
    } else if(queryOnly) {
        var action = $(form).find('input[name="submit_button"]').val();

        return confirm("Perform bulk action \"" + action + "\" on all images matching the current search?");
    }

    return true;
}


function activate_bulk_selector () {
    set_selected_items([]);
    if(!bulk_selector_initialized) {
        $(".shm-thumb").each(
            function (index, block) {
                add_selector_button($(block));
            }
        );
    }
    $('#bulk_selector_controls').show();
    $('#bulk_selector_activate').hide();
    bulk_selector_active = true;
    bulk_selector_initialized = true;
}

function deactivate_bulk_selector() {
    set_selected_items([]);
    $('#bulk_selector_controls').hide();
    $('#bulk_selector_activate').show();
    $('input[name="bulk_selected_ids"]').val("");
    bulk_selector_active = false;
}

function get_selected_items() {
    var data = $('#bulk_selected_ids').val();
    if(data===""||data==null) {
        data = [];
    } else {
        data = JSON.parse(data);
    }
    return data;
}

function set_selected_items(items) {
    $(".shm-thumb").removeClass('bulk_selected');

    $(items).each(
        function(index,item) {
            $('.shm-thumb[data-post-id="' + item + '"]').addClass('bulk_selected');
        }
    );

    $('input[name="bulk_selected_ids"]').val(JSON.stringify(items));
}

function select_item(id) {
    var data = get_selected_items();
    if(!data.includes(id))
        data.push(id);
    set_selected_items(data);
}

function deselect_item(id) {
    var data = get_selected_items();
    if(data.includes(id))
        data.splice(data.indexOf(id, 1));
    set_selected_items(data);
}

function toggle_selection( id ) {
    var data = get_selected_items();
    if(data.includes(id)) {
        data.splice(data.indexOf(id),1);
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
    $(".shm-thumb").each(
        function ( index, block ) {
            block = $(block);
            var id = block.data("post-id");
            items.push(id);
        }
    );
    set_selected_items(items);
}

function select_invert() {
    var currentItems = get_selected_items();
    var items = [];
    $(".shm-thumb").each(
        function ( index, block ) {
            block = $(block);
            var id = block.data("post-id");
            if(!currentItems.includes(id)) {
                items.push(id);
            }
        }
    );
    set_selected_items(items);
}

function select_none() {
    set_selected_items([]);
}

function select_range(start, end) {
    var data = get_selected_items();
    var selecting = false;
    $(".shm-thumb").each(
        function ( index, block ) {
            block = $(block);
            var id = block.data("post-id");
            if(id===start)
                selecting = true;

            if(selecting) {
                if(!data.includes(id))
                    data.push(id);
            }

            if(id===end) {
                selecting = false;
            }
        }
    );
    set_selected_items(data);
}

var last_clicked_item;

function add_selector_button($block) {
    var c = function(e) {
        if(!bulk_selector_active)
            return true;

        e.preventDefault();
        e.stopPropagation();

        var id = $block.data("post-id");
        if(e.shiftKey) {
            if(last_clicked_item<id) {
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

    $block.find("A").click(c);
    $block.click(c); // sometimes the thumbs *is* the A
}

document.addEventListener('DOMContentLoaded', () => {
	// Clear the selection, in case it was autocompleted by the browser.
	$('#bulk_selected_ids').val("");
});
