<?php

declare(strict_types=1);

namespace Shimmie2;

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

class PostSource extends Extension
{
    /** @var PostSourceTheme */
    protected Themelet $theme;

    public function onPageRequest(PageRequestEvent $event): void
    {
        global $user, $page;
        if ($event->page_matches("tag_edit/mass_source_set", method: "POST", permission: Permissions::MASS_TAG_EDIT)) {
            $this->mass_source_edit($event->req_POST('tags'), $event->req_POST('source'));
            $page->set_mode(PageMode::REDIRECT);
            $page->set_redirect(search_link());
        }
    }

    public function onImageAddition(ImageAdditionEvent $event): void
    {
        if(!empty($event->metadata['source'])) {
            send_event(new SourceSetEvent($event->image, $event->metadata['source']));
        }
    }

    public function onImageInfoSet(ImageInfoSetEvent $event): void
    {
        global $page, $user;
        if ($user->can(Permissions::EDIT_IMAGE_SOURCE) && isset($event->params['source'])) {
            if (isset($event->params['tags']) ? !preg_match('/source[=|:]/', $event->params["tags"]) : true) {
                send_event(new SourceSetEvent($event->image, $event->params['source']));
            }
        }
    }

    public function onSourceSet(SourceSetEvent $event): void
    {
        global $user;
        if ($user->can(Permissions::EDIT_IMAGE_SOURCE)) {
            $event->image->set_source($event->source);
        }
    }

    public function onImageInfoBoxBuilding(ImageInfoBoxBuildingEvent $event): void
    {
        $event->add_part($this->theme->get_source_editor_html($event->image), 41);
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
