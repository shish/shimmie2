
function toggle_tag( button, id ) {
    id += ":";
    var list = $('#mass_tagger_ids');
    var string = list.val();
    
    if( string.indexOf( id ) > -1 ) return remove_mass_tag_id( button, list, id, string );
    
    return add_mass_tag_id( button, list, id, string );
}

function add_mass_tag_id( button, list, id, string ) {
    $(button).attr( 'style', 'display:block;border: 3px solid blue;' );
    string += id;
    list.val( string );
    return false;
}

function remove_mass_tag_id( button, list, id, string ) {
    $(button).attr( 'style', '' );
    string = string.replace( id, '' );
    list.val( string );
    return false;
}

function activate_mass_tagger ( image_link ) {
    
    find_thumb_link_containers().each(
        function ( index, block ) {
            add_mass_tag_button( block, image_link );
        }
    );
    $('#mass_tagger_controls').attr( 'style', 'display:block' );
    $('#mass_tagger_activate').attr( 'style', 'display:none' );
    
    return false;
}

function add_mass_tag_button ( block, image_link ) {
    
    var id = get_image_id( block );
    
    var button = create_mass_tag_button( id, image_link );
    $(block).append( button );
    
    return;
}

function get_image_id ( block ) {
    var link = $(block).children(":first").attr('href');
    var id = link.split('/').pop();
    
    return id;
}

function create_mass_tag_button ( id, image_link ) {
    var img = $('<img />');
    img.attr( "src", image_link+'/ext/mass_tagger/toggle.gif' );
    
    var link = $('<a />');
    link.attr("class",'zoom');
    link.attr("onclick",'return toggle_tag( this, "'+id+'")');
    link.attr("href",'#');
    
    link.append( img );
    
    return link;
}
