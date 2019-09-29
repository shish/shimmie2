<?php
/*
 * Name: Tag Editor
 * Author: Shish
 * Description: Allow images to have tags assigned to them
 * Documentation:
 *  Here is a list of the tagging metatags available out of the box;
 *  Shimmie extensions may provide other metatags:
 *  <ul>
 *    <li>source=(*, none) eg -- using this metatag will ignore anything set in the "Source" box
 *      <ul>
 *        <li>source=http://example.com -- set source to http://example.com
 *        <li>source=none -- set source to NULL
 *      </ul>
 *  </ul>
 *  <p>Metatags can be followed by ":" rather than "=" if you prefer.
 *  <br />I.E: "source:http://example.com", "source=http://example.com" etc.
 *  <p>Some tagging metatags provided by extensions:
 *  <ul>
 *    <li>Numeric Score
 *      <ul>
 *        <li>vote=(up, down, remove) -- vote, or remove your vote on an image
 *      </ul>
 *    <li>Pools
 *      <ul>
 *        <li>pool=(PoolID, PoolTitle, lastcreated) -- add post to pool (if exists)
 *        <li>pool=(PoolID, PoolTitle, lastcreated):(PoolOrder) -- add post to pool (if exists) with set pool order
 *        <ul>
 *          <li>pool=50 -- add post to pool with ID of 50
 *          <li>pool=10:25 -- add post to pool with ID of 10 and with order 25
 *          <li>pool=This_is_a_Pool -- add post to pool with a title of "This is a Pool"
 *          <li>pool=lastcreated -- add post to the last pool the user created
 *        </ul>
 *      </ul>
 *    <li>Post Relationships
 *      <ul>
 *        <li>parent=(parentID, none) -- set parent ID of current image
 *        <li>child=(childID) -- set parent ID of child image to current image ID
 *      </ul>
 *  </ul>
 */

/*
 * OwnerSetEvent:
 *   $image_id
 *   $source
 *
 */
class OwnerSetEvent extends Event
{
    /** @var Image  */
    public $image;
    /** @var User  */
    public $owner;

    public function __construct(Image $image, User $owner)
    {
        $this->image = $image;
        $this->owner = $owner;
    }
}


class SourceSetEvent extends Event
{
    /** @var Image */
    public $image;
    /** @var string */
    public $source;

    public function __construct(Image $image, string $source=null)
    {
        $this->image = $image;
        $this->source = $source;
    }
}


class TagSetEvent extends Event
{
    /** @var Image */
    public $image;
    public $tags;
    public $metatags;

    /**
     * #param string[] $tags
     */
    public function __construct(Image $image, array $tags)
    {
        $this->image    = $image;

        $this->tags     = [];
        $this->metatags = [];

        foreach ($tags as $tag) {
            if ((strpos($tag, ':') === false) && (strpos($tag, '=') === false)) {
                //Tag doesn't contain : or =, meaning it can't possibly be a metatag.
                //This should help speed wise, as it avoids running every single tag through a bunch of preg_match instead.
                array_push($this->tags, $tag);
                continue;
            }

            $ttpe = new TagTermParseEvent($tag, $this->image->id, false); //Only check for metatags, don't parse. Parsing is done after set_tags.
            send_event($ttpe);

            //seperate tags from metatags
            if (!$ttpe->is_metatag()) {
                array_push($this->tags, $tag);
            } else {
                array_push($this->metatags, $tag);
            }
        }
    }
}

class LockSetEvent extends Event
{
    /** @var Image */
    public $image;
    /** @var bool */
    public $locked;

    public function __construct(Image $image, bool $locked)
    {
        $this->image = $image;
        $this->locked = $locked;
    }
}

/*
 * TagTermParseEvent:
 * Signal that a tag term needs parsing
 */
class TagTermParseEvent extends Event
{
    public $term = null; //tag
    public $id   = null; //image_id
    /** @var bool */
    public $metatag = false;
    /** @var bool */
    public $parse  = true; //marks the tag to be parsed, and not just checked if valid metatag

    public function __construct(string $term, int $id, bool $parse)
    {
        $this->term  = $term;
        $this->id    = $id;
        $this->parse = $parse;
    }

    public function is_metatag(): bool
    {
        return $this->metatag;
    }
}

class TagEdit extends Extension
{
    public function onPageRequest(PageRequestEvent $event)
    {
        global $user, $page;
        if ($event->page_matches("tag_edit")) {
            if ($event->get_arg(0) == "replace") {
                if ($user->can(Permissions::MASS_TAG_EDIT) && isset($_POST['search']) && isset($_POST['replace'])) {
                    $search = $_POST['search'];
                    $replace = $_POST['replace'];
                    $this->mass_tag_edit($search, $replace);
                    $page->set_mode(PageMode::REDIRECT);
                    $page->set_redirect(make_link("admin"));
                }
            }
            if ($event->get_arg(0) == "mass_source_set") {
                if ($user->can(Permissions::MASS_TAG_EDIT) && isset($_POST['tags']) && isset($_POST['source'])) {
                    $this->mass_source_edit($_POST['tags'], $_POST['source']);
                    $page->set_mode(PageMode::REDIRECT);
                    $page->set_redirect(make_link("post/list"));
                }
            }
        }
    }

    // public function onPostListBuilding(PostListBuildingEvent $event)
    // {
    //     global $user;
    //     if ($user->can(UserAbilities::BULK_EDIT_IMAGE_SOURCE) && !empty($event->search_terms)) {
    //         $event->add_control($this->theme->mss_html(Tag::implode($event->search_terms)));
    //     }
    // }

    public function onImageInfoSet(ImageInfoSetEvent $event)
    {
        global $user;
        if ($user->can(Permissions::EDIT_IMAGE_OWNER) && isset($_POST['tag_edit__owner'])) {
            $owner = User::by_name($_POST['tag_edit__owner']);
            if ($owner instanceof User) {
                send_event(new OwnerSetEvent($event->image, $owner));
            } else {
                throw new NullUserException("Error: No user with that name was found.");
            }
        }
        if ($this->can_tag($event->image) && isset($_POST['tag_edit__tags'])) {
            send_event(new TagSetEvent($event->image, Tag::explode($_POST['tag_edit__tags'])));
        }
        if ($this->can_source($event->image) && isset($_POST['tag_edit__source'])) {
            if (isset($_POST['tag_edit__tags']) ? !preg_match('/source[=|:]/', $_POST["tag_edit__tags"]) : true) {
                send_event(new SourceSetEvent($event->image, $_POST['tag_edit__source']));
            }
        }
        if ($user->can(Permissions::EDIT_IMAGE_LOCK)) {
            $locked = isset($_POST['tag_edit__locked']) && $_POST['tag_edit__locked']=="on";
            send_event(new LockSetEvent($event->image, $locked));
        }
    }

    public function onOwnerSet(OwnerSetEvent $event)
    {
        global $user;
        if ($user->can(Permissions::EDIT_IMAGE_OWNER) && (!$event->image->is_locked() || $user->can(Permissions::EDIT_IMAGE_LOCK))) {
            $event->image->set_owner($event->owner);
        }
    }

    public function onTagSet(TagSetEvent $event)
    {
        global $user;
        if ($user->can(Permissions::EDIT_IMAGE_TAG) && (!$event->image->is_locked() || $user->can(Permissions::EDIT_IMAGE_LOCK))) {
            $event->image->set_tags($event->tags);
        }
        $event->image->parse_metatags($event->metatags, $event->image->id);
    }

    public function onSourceSet(SourceSetEvent $event)
    {
        global $user;
        if ($user->can(Permissions::EDIT_IMAGE_SOURCE) && (!$event->image->is_locked() || $user->can(Permissions::EDIT_IMAGE_LOCK))) {
            $event->image->set_source($event->source);
        }
    }

    public function onLockSet(LockSetEvent $event)
    {
        global $user;
        if ($user->can(Permissions::EDIT_IMAGE_LOCK)) {
            $event->image->set_locked($event->locked);
        }
    }

    public function onImageDeletion(ImageDeletionEvent $event)
    {
        $event->image->delete_tags_from_image();
    }

    public function onAdminBuilding(AdminBuildingEvent $event)
    {
        $this->theme->display_mass_editor();
    }


    public function onPageSubNavBuilding(PageSubNavBuildingEvent $event)
    {
        if ($event->parent=="tags") {
            $event->add_nav_link("tags_help", new Link('ext_doc/tag_edit'), "Help");
        }
    }


    /**
     * When an alias is added, oldtag becomes inaccessible.
     */
    public function onAddAlias(AddAliasEvent $event)
    {
        $this->mass_tag_edit($event->oldtag, $event->newtag);
    }

    public function onImageInfoBoxBuilding(ImageInfoBoxBuildingEvent $event)
    {
        $event->add_part($this->theme->get_user_editor_html($event->image), 39);
        $event->add_part($this->theme->get_tag_editor_html($event->image), 40);
        $event->add_part($this->theme->get_source_editor_html($event->image), 41);
        $event->add_part($this->theme->get_lock_editor_html($event->image), 42);
    }

    public function onTagTermParse(TagTermParseEvent $event)
    {
        $matches = [];

        if (preg_match("/^source[=|:](.*)$/i", $event->term, $matches) && $event->parse) {
            $source = ($matches[1] !== "none" ? $matches[1] : null);
            send_event(new SourceSetEvent(Image::by_id($event->id), $source));
        }

        if (!empty($matches)) {
            $event->metatag = true;
        }
    }

    private function can_tag(Image $image): bool
    {
        global $user;
        return ($user->can(Permissions::EDIT_IMAGE_TAG) || !$image->is_locked());
    }

    private function can_source(Image $image): bool
    {
        global $user;
        return ($user->can(Permissions::EDIT_IMAGE_SOURCE) || !$image->is_locked());
    }

    private function mass_tag_edit(string $search, string $replace)
    {
        global $database;

        $search_set = Tag::explode(strtolower($search), false);
        $replace_set = Tag::explode(strtolower($replace), false);

        log_info("tag_edit", "Mass editing tags: '$search' -> '$replace'");

        if (count($search_set) == 1 && count($replace_set) == 1) {
            $images = Image::find_images(0, 10, $replace_set);
            if (count($images) == 0) {
                log_info("tag_edit", "No images found with target tag, doing in-place rename");
                $database->execute(
                    "DELETE FROM tags WHERE tag=:replace",
                    ["replace" => $replace_set[0]]
                );
                $database->execute(
                    "UPDATE tags SET tag=:replace WHERE tag=:search",
                    ["replace" => $replace_set[0], "search" => $search_set[0]]
                );
                return;
            }
        }

        $last_id = -1;
        while (true) {
            // make sure we don't look at the same images twice.
            // search returns high-ids first, so we want to look
            // at images with lower IDs than the previous.
            $search_forward = $search_set;
            $search_forward[] = "order=id_desc"; //Default order can be changed, so make sure we order high > low ID
            if ($last_id >= 0) {
                $search_forward[] = "id<$last_id";
            }

            $images = Image::find_images(0, 100, $search_forward);
            if (count($images) == 0) {
                break;
            }

            foreach ($images as $image) {
                // remove the search'ed tags
                $before = array_map('strtolower', $image->get_tag_array());
                $after = [];
                foreach ($before as $tag) {
                    if (!in_array($tag, $search_set)) {
                        $after[] = $tag;
                    }
                }

                // add the replace'd tags
                foreach ($replace_set as $tag) {
                    $after[] = $tag;
                }

                // replace'd tag may already exist in tag set, so remove dupes to avoid integrity constraint violations.
                $after = array_unique($after);

                $image->set_tags($after);

                $last_id = $image->id;
            }
        }
    }

    private function mass_source_edit(string $tags, string $source)
    {
        $tags = Tag::explode($tags);

        $last_id = -1;
        while (true) {
            // make sure we don't look at the same images twice.
            // search returns high-ids first, so we want to look
            // at images with lower IDs than the previous.
            $search_forward = $tags;
            if ($last_id >= 0) {
                $search_forward[] = "id<$last_id";
            }

            $images = Image::find_images(0, 100, $search_forward);
            if (count($images) == 0) {
                break;
            }

            foreach ($images as $image) {
                $image->set_source($source);
                $last_id = $image->id;
            }
        }
    }
}
