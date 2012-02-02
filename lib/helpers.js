function find_thumb_link_containers () {
    
    var post_link = "a[href*='/post/view/']";
    var has_thumb_img = ":has(img[src*='/thumb/'])";

    var list = $( post_link + has_thumb_img ).parent();

    return list;
}
