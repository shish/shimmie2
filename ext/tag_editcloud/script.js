/*jshint bitwise:true, curly:true, eqeqeq: false, forin:false, noarg:true, noempty:true, nonew:true, undef:true, strict:false, browser:true, jquery:true */

Array.prototype.editcloud_contains = function (ele) {
    for (var i = 0; i < this.length; i++) {
        if (this[i] === ele) {
            return true;
        }
    }
    return false;
};
Array.prototype.editcloud_remove = function (ele) {
    var arr = [];
    var count = 0;
    for (var i = 0; i < this.length; i++) {
        if (this[i] !== ele) {
            arr[count] = this[i];
            count++;
        }
    }
    return arr;
};

var hide_text = null;
function tageditcloud_toggle_extra(hide) {
	if (hide_text === null) {
		hide_text = hide.innerHTML;
	}

	var el = document.getElementById('tagcloud_extra');
	el.style.display = (el.style.display !== 'none' ? 'none' : '' );
	hide.innerHTML = (el.style.display !== 'none' ? 'show fewer tags' : hide_text );
}

function tageditcloud_toggle_tag(ele,fullTag) {
    var taglist = document.getElementById('tag_editor');
    var tags = taglist.value.split(' ');

    if (tags.editcloud_contains(fullTag)) {
        tags = tags.editcloud_remove(fullTag);
        ele.className = 'tag-unselected';
    } else {
        tags.splice(0, 0, fullTag);
        ele.className = 'tag-selected';
    }

    taglist.value = tags.join(' ');
    document.getElementById('tag_editor').focus();
}
