Array.prototype.editcloud_contains = function (ele) {
    for (var i = 0; i < this.length; i++) {
        if (this[i] == ele) {
            return true;
        }
    }
    return false;
};
Array.prototype.editcloud_remove = function (ele) {
    var arr = new Array();
    var count = 0;
    for (var i = 0; i < this.length; i++) {
        if (this[i] != ele) {
            arr[count] = this[i];
            count++;
        }
    }
    return arr;
};

var hide_text = null;
function tageditcloud_toggle_extra(hide) {
	if (hide_text == null) {
		hide_text = hide.innerHTML;
	}

	var el = document.getElementById('tagcloud_extra');
	el.style.display = (el.style.display != 'none' ? 'none' : '' );
	hide.innerHTML = (el.style.display != 'none' ? 'show fewer tags' : hide_text );
}

function tageditcloud_toggle_tag(ele) {
    var thisTag = ele.innerHTML;
    var taglist = document.getElementById('tag_editor');
    var tags = taglist.value.split(' ');
    
    // If tag is already listed, remove it
    if (tags.editcloud_contains(thisTag)) {
        tags = tags.editcloud_remove(thisTag);
        ele.className = 'tag-unselected';
        
    // Otherwise add it
    } else {
        tags.splice(0, 0, thisTag);
        ele.className = 'tag-selected';
    }
     
    taglist.value = tags.join(' ');
    
    document.getElementById('tags').focus();
}
