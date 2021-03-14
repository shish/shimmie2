<?php declare(strict_types=1);

/*
 * OwnerSetEvent:
 *   $image_id
 *   $source
 *
 */
class OwnerSetEvent extends Event
{
    public Image $image;
    public User $owner;

    public function __construct(Image $image, User $owner)
    {
        parent::__construct();
        $this->image = $image;
        $this->owner = $owner;
    }
}


class SourceSetEvent extends Event
{
    public Image $image;
    public ?string $source;

    public function __construct(Image $image, string $source=null)
    {
        parent::__construct();
        $this->image = $image;
        $this->source = $source;
    }
}


class TagSetException extends SCoreException
{
    public ?string $redirect;

    public function __construct(string $msg, ?string $redirect = null)
    {
        parent::__construct($msg, null);
        $this->redirect = $redirect;
    }
}

class TagSetEvent extends Event
{
    public Image $image;
    public array $tags;
    public array $metatags;

    /**
     * #param string[] $tags
     */
    public function __construct(Image $image, array $tags)
    {
        parent::__construct();
        $this->image    = $image;

        $this->tags     = [];
        $this->metatags = [];

        foreach ($tags as $tag) {
            if ((!str_contains($tag, ':')) && (!str_contains($tag, '='))) {
                //Tag doesn't contain : or =, meaning it can't possibly be a metatag.
                //This should help speed wise, as it avoids running every single tag through a bunch of preg_match instead.
                array_push($this->tags, $tag);
                continue;
            }

            $ttpe = new TagTermCheckEvent($tag);
            send_event($ttpe);

            //seperate tags from metatags
            if (!$ttpe->metatag) {
                array_push($this->tags, $tag);
            } else {
                array_push($this->metatags, $tag);
            }
        }
    }
}

class LockSetEvent extends Event
{
    public Image $image;
    public bool $locked;

    public function __construct(Image $image, bool $locked)
    {
        parent::__construct();
        $this->image = $image;
        $this->locked = $locked;
    }
}

/**
 * Check whether or not a tag is a meta-tag
 */
class TagTermCheckEvent extends Event
{
    public string $term;
    public bool $metatag = false;

    public function __construct(string $term)
    {
        parent::__construct();
        $this->term  = $term;
    }
}

/**
 * If a tag is a meta-tag, parse it
 */
class TagTermParseEvent extends Event
{
    public string $term;
    public int $image_id;

    public function __construct(string $term, int $image_id)
    {
        parent::__construct();
        $this->term = $term;
        $this->image_id = $image_id;
    }
}

class TagEdit extends Extension
{
    /** @var TagEditTheme */
    protected ?Themelet $theme;

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
        global $page, $user;
        if ($user->can(Permissions::EDIT_IMAGE_OWNER) && isset($_POST['tag_edit__owner'])) {
            $owner = User::by_name($_POST['tag_edit__owner']);
            if ($owner instanceof User) {
                send_event(new OwnerSetEvent($event->image, $owner));
            } else {
                throw new NullUserException("Error: No user with that name was found.");
            }
        }
        if ($user->can(Permissions::EDIT_IMAGE_TAG) && isset($_POST['tag_edit__tags'])) {
            try {
                send_event(new TagSetEvent($event->image, Tag::explode($_POST['tag_edit__tags'])));
            } catch (TagSetException $e) {
                if ($e->redirect) {
                    $page->flash("{$e->getMessage()}, please see {$e->redirect}");
                } else {
                    $page->flash($e->getMessage());
                }
            }
        }
        if ($user->can(Permissions::EDIT_IMAGE_SOURCE) && isset($_POST['tag_edit__source'])) {
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
        foreach ($event->metatags as $tag) {
            send_event(new TagTermParseEvent($tag, $event->image->id));
        }
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

    public function onTagTermCheck(TagTermCheckEvent $event)
    {
        if (preg_match("/^source[=|:](.*)$/i", $event->term)) {
            $event->metatag = true;
        }
    }

    public function onTagTermParse(TagTermParseEvent $event)
    {
        if (preg_match("/^source[=|:](.*)$/i", $event->term, $matches)) {
            $source = ($matches[1] !== "none" ? $matches[1] : null);
            send_event(new SourceSetEvent(Image::by_id($event->image_id), $source));
        }
    }

    public function onParseLinkTemplate(ParseLinkTemplateEvent $event)
    {
        $tags = $event->image->get_tag_list();
        $tags = str_replace("/", "", $tags);
        $tags = preg_replace("/^\.+/", "", $tags);
        $event->replace('$tags', $tags);
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
