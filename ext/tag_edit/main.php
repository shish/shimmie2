<?php

declare(strict_types=1);

namespace Shimmie2;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\{InputInterface,InputArgument};
use Symfony\Component\Console\Output\OutputInterface;

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

    public function __construct(Image $image, string $source = null)
    {
        parent::__construct();
        $this->image = $image;
        $this->source = trim($source);
    }
}


class TagSetException extends UserErrorException
{
    public ?string $redirect;

    public function __construct(string $msg, ?string $redirect = null)
    {
        parent::__construct($msg);
        $this->redirect = $redirect;
    }
}

class TagSetEvent extends Event
{
    public Image $image;
    /** @var string[] */
    public array $old_tags;
    /** @var string[] */
    public array $new_tags;
    /** @var string[] */
    public array $metatags;

    /**
     * @param string[] $tags
     */
    public function __construct(Image $image, array $tags)
    {
        parent::__construct();
        $this->image    = $image;
        $this->old_tags = $image->get_tag_array();
        $this->new_tags = [];
        $this->metatags = [];

        foreach ($tags as $tag) {
            if ((!str_contains($tag, ':')) && (!str_contains($tag, '='))) {
                //Tag doesn't contain : or =, meaning it can't possibly be a metatag.
                //This should help speed wise, as it avoids running every single tag through a bunch of preg_match instead.
                $this->new_tags[] = $tag;
                continue;
            }

            $ttpe = send_event(new TagTermCheckEvent($tag));

            //seperate tags from metatags
            if (!$ttpe->metatag) {
                $this->new_tags[] = $tag;
            } else {
                $this->metatags[] = $tag;
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
    protected Themelet $theme;

    public function onPageRequest(PageRequestEvent $event): void
    {
        global $user, $page;
        if ($event->page_matches("tag_edit", method: "POST", permission: Permissions::MASS_TAG_EDIT)) {
            if ($event->get_arg(0) == "replace") {
                $search = $event->req_POST('search');
                $replace = $event->req_POST('replace');
                $this->mass_tag_edit($search, $replace, true);
                $page->set_mode(PageMode::REDIRECT);
                $page->set_redirect(make_link("admin"));
            }
            if ($event->get_arg(0) == "mass_source_set") {
                $this->mass_source_edit($event->req_POST('tags'), $event->req_POST('source'));
                $page->set_mode(PageMode::REDIRECT);
                $page->set_redirect(search_link());
            }
        }
    }

    public function onCliGen(CliGenEvent $event): void
    {
        $event->app->register('tag-replace')
            ->addArgument('old_tag', InputArgument::REQUIRED)
            ->addArgument('new_tag', InputArgument::REQUIRED)
            ->setDescription('Mass edit tags')
            ->setCode(function (InputInterface $input, OutputInterface $output): int {
                $old_tag = $input->getArgument('old_tag');
                $new_tag = $input->getArgument('new_tag');
                $output->writeln("Mass editing tags: '$old_tag' -> '$new_tag'");
                $this->mass_tag_edit($old_tag, $new_tag, true);
                return Command::SUCCESS;
            });
    }

    // public function onPostListBuilding(PostListBuildingEvent $event): void
    // {
    //     global $user;
    //     if ($user->can(UserAbilities::BULK_EDIT_IMAGE_SOURCE) && !empty($event->search_terms)) {
    //         $event->add_control($this->theme->mss_html(Tag::implode($event->search_terms)));
    //     }
    // }

    public function onImageAddition(ImageAdditionEvent $event): void
    {
        if(!empty($event->metadata['tags'])) {
            send_event(new TagSetEvent($event->image, $event->metadata['tags']));
        }
        if(!empty($event->metadata['source'])) {
            send_event(new SourceSetEvent($event->image, $event->metadata['source']));
        }
        if (!empty($event->metadata['locked'])) {
            send_event(new LockSetEvent($event->image, $event->metadata['locked']));
        }
    }

    public function onImageInfoSet(ImageInfoSetEvent $event): void
    {
        global $page, $user;
        if ($user->can(Permissions::EDIT_IMAGE_OWNER) && isset($event->params['tag_edit__owner'])) {
            $owner = User::by_name($event->params['tag_edit__owner']);
            if ($owner instanceof User) {
                send_event(new OwnerSetEvent($event->image, $owner));
            } else {
                throw new NullUserException("Error: No user with that name was found.");
            }
        }
        if ($user->can(Permissions::EDIT_IMAGE_TAG) && isset($event->params['tag_edit__tags'])) {
            try {
                send_event(new TagSetEvent($event->image, Tag::explode($event->params['tag_edit__tags'])));
            } catch (TagSetException $e) {
                if ($e->redirect) {
                    $page->flash("{$e->getMessage()}, please see {$e->redirect}");
                } else {
                    $page->flash($e->getMessage());
                }
            }
        }
        if ($user->can(Permissions::EDIT_IMAGE_SOURCE) && isset($event->params['tag_edit__source'])) {
            if (isset($event->params['tag_edit__tags']) ? !preg_match('/source[=|:]/', $event->params["tag_edit__tags"]) : true) {
                send_event(new SourceSetEvent($event->image, $event->params['tag_edit__source']));
            }
        }
        if ($user->can(Permissions::EDIT_IMAGE_LOCK)) {
            $locked = isset($event->params['tag_edit__locked']) && $event->params['tag_edit__locked'] == "on";
            send_event(new LockSetEvent($event->image, $locked));
        }
    }

    public function onOwnerSet(OwnerSetEvent $event): void
    {
        global $user;
        if ($user->can(Permissions::EDIT_IMAGE_OWNER) && (!$event->image->is_locked() || $user->can(Permissions::EDIT_IMAGE_LOCK))) {
            $event->image->set_owner($event->owner);
        }
    }

    public function onTagSet(TagSetEvent $event): void
    {
        global $user;
        if ($user->can(Permissions::EDIT_IMAGE_TAG) && (!$event->image->is_locked() || $user->can(Permissions::EDIT_IMAGE_LOCK))) {
            $event->image->set_tags($event->new_tags);
        }
        foreach ($event->metatags as $tag) {
            send_event(new TagTermParseEvent($tag, $event->image->id));
        }
    }

    public function onSourceSet(SourceSetEvent $event): void
    {
        global $user;
        if ($user->can(Permissions::EDIT_IMAGE_SOURCE) && (!$event->image->is_locked() || $user->can(Permissions::EDIT_IMAGE_LOCK))) {
            $event->image->set_source($event->source);
        }
    }

    public function onLockSet(LockSetEvent $event): void
    {
        global $user;
        if ($user->can(Permissions::EDIT_IMAGE_LOCK)) {
            $event->image->set_locked($event->locked);
        }
    }

    public function onImageDeletion(ImageDeletionEvent $event): void
    {
        $event->image->delete_tags_from_image();
    }

    public function onAdminBuilding(AdminBuildingEvent $event): void
    {
        $this->theme->display_mass_editor();
    }

    public function onPageSubNavBuilding(PageSubNavBuildingEvent $event): void
    {
        if ($event->parent == "tags") {
            $event->add_nav_link("tags_help", new Link('ext_doc/tag_edit'), "Help");
        }
    }

    /**
     * When an alias is added, oldtag becomes inaccessible.
     */
    public function onAddAlias(AddAliasEvent $event): void
    {
        $this->mass_tag_edit($event->oldtag, $event->newtag, false);
    }

    public function onImageInfoBoxBuilding(ImageInfoBoxBuildingEvent $event): void
    {
        $event->add_part($this->theme->get_user_editor_html($event->image), 39);
        $event->add_part($this->theme->get_tag_editor_html($event->image), 40);
        $event->add_part($this->theme->get_source_editor_html($event->image), 41);
        $event->add_part($this->theme->get_lock_editor_html($event->image), 42);
    }

    public function onTagTermCheck(TagTermCheckEvent $event): void
    {
        if (preg_match("/^source[=|:](.*)$/i", $event->term)) {
            $event->metatag = true;
        }
    }

    public function onTagTermParse(TagTermParseEvent $event): void
    {
        if (preg_match("/^source[=|:](.*)$/i", $event->term, $matches)) {
            $source = ($matches[1] !== "none" ? $matches[1] : null);
            send_event(new SourceSetEvent(Image::by_id($event->image_id), $source));
        }
    }

    public function onParseLinkTemplate(ParseLinkTemplateEvent $event): void
    {
        $tags = $event->image->get_tag_list();
        $tags = str_replace("/", "", $tags);
        $tags = ltrim($tags, ".");
        $event->replace('$tags', $tags);
    }

    private function mass_tag_edit(string $search, string $replace, bool $commit): void
    {
        global $database, $tracer_enabled, $_tracer;

        $search_set = Tag::explode(strtolower($search), false);
        $replace_set = Tag::explode(strtolower($replace), false);

        log_info("tag_edit", "Mass editing tags: '$search' -> '$replace'");

        if (count($search_set) == 1 && count($replace_set) == 1) {
            $images = Search::find_images(limit: 10, tags: $replace_set);
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
            if ($tracer_enabled) {
                $_tracer->begin("Batch starting with $last_id");
            }
            // make sure we don't look at the same images twice.
            // search returns high-ids first, so we want to look
            // at images with lower IDs than the previous.
            $search_forward = $search_set;
            $search_forward[] = "order=id_desc"; //Default order can be changed, so make sure we order high > low ID
            if ($last_id >= 0) {
                $search_forward[] = "id<$last_id";
            }

            $images = Search::find_images(limit: 100, tags: $search_forward);
            if (count($images) == 0) {
                break;
            }

            foreach ($images as $image) {
                $before = array_map('strtolower', $image->get_tag_array());
                $after = array_merge(array_diff($before, $search_set), $replace_set);
                send_event(new TagSetEvent($image, $after));
                $last_id = $image->id;
            }
            if ($commit) {
                // Mass tag edit can take longer than the page timeout,
                // so we need to commit periodically to save what little
                // work we've done and avoid starting from scratch.
                $database->commit();
                $database->begin_transaction();
            }
            if ($tracer_enabled) {
                $_tracer->end();
            }
        }
    }

    private function mass_source_edit(string $tags, string $source): void
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

            $images = Search::find_images(limit: 100, tags: $search_forward);
            if (count($images) == 0) {
                break;
            }

            foreach ($images as $image) {
                send_event(new SourceSetEvent($image, $source));
                $last_id = $image->id;
            }
        }
    }
}
