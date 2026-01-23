<?php

declare(strict_types=1);

namespace Shimmie2;

final class SourceSetEvent extends Event
{
    public function __construct(
        public Image $image,
        public string $source
    ) {
        parent::__construct();
        $this->source = trim($source);
    }
}

/** @extends Extension<PostSourceTheme> */
final class PostSource extends Extension
{
    public const KEY = "post_source";

    #[EventListener]
    public function onPageRequest(PageRequestEvent $event): void
    {
        if ($event->page_matches("tag_edit/mass_source_set", method: "POST", permission: PostTagsPermission::MASS_TAG_EDIT)) {
            $this->mass_source_edit($event->POST->req('tags'), $event->POST->req('source'));
            Ctx::$page->set_redirect(search_link());
        }
    }

    #[EventListener]
    public function onImageInfoSet(ImageInfoSetEvent $event): void
    {
        $source = $event->get_param('source');
        if (is_null($source) && Ctx::$config->get(UploadConfig::TLSOURCE)) {
            $source = $event->get_param('url');
        }
        if (Ctx::$user->can(PostSourcePermission::EDIT_IMAGE_SOURCE) && !is_null($source)) {
            if ($event->params['tags'] ? !\Safe\preg_match('/source[=:]/', $event->params->req("tags")) : true) {
                send_event(new CheckStringContentEvent($source, type: StringType::URL));
                send_event(new SourceSetEvent($event->image, $source));
            }
        }
    }

    #[EventListener]
    public function onSourceSet(SourceSetEvent $event): void
    {
        if (Ctx::$user->can(PostSourcePermission::EDIT_IMAGE_SOURCE)) {
            $event->image->set_source($event->source);
        }
    }

    #[EventListener]
    public function onImageInfoBoxBuilding(ImageInfoBoxBuildingEvent $event): void
    {
        $event->add_part($this->theme->get_source_editor_html($event->image), 41);
    }

    #[EventListener]
    public function onSearchTermParse(SearchTermParseEvent $event): void
    {
        if ($matches = $event->matches("/^(source)[=:](.*)$/i")) {
            $source = strtolower($matches[2]);
            $source = \Safe\preg_replace('/^https?:/', '', $source);

            if (\Safe\preg_match("/^(any|none)$/i", $source)) {
                $not = ($source === "any" ? "NOT" : "");
                $event->add_querylet(new Querylet("images.source IS $not NULL"));
            } else {
                $event->add_querylet(new Querylet('SCORE_ILIKE(images.source, :src)', ["src" => "%$source%"]));
            }
        }
    }

    #[EventListener]
    public function onTagTermCheck(TagTermCheckEvent $event): void
    {
        if ($event->matches("/^source[=:](.*)$/i")) {
            $event->metatag = true;
        }
    }

    #[EventListener]
    public function onTagTermParse(TagTermParseEvent $event): void
    {
        if ($matches = $event->matches("/^source[=:](.*)$/i")) {
            $source = ($matches[1] !== "none" ? $matches[1] : "");
            send_event(new CheckStringContentEvent($source, type: StringType::URL));
            send_event(new SourceSetEvent(Image::by_id_ex($event->image_id), $source));
        }
    }

    #[EventListener]
    public function onUploadHeaderBuilding(UploadHeaderBuildingEvent $event): void
    {
        $event->add_part("Source", 11);
    }

    #[EventListener]
    public function onUploadCommonBuilding(UploadCommonBuildingEvent $event): void
    {
        $event->add_part($this->theme->get_upload_common_html(), 11);
    }

    #[EventListener]
    public function onUploadSpecificBuilding(UploadSpecificBuildingEvent $event): void
    {
        $event->add_part($this->theme->get_upload_specific_html($event->suffix), 11);
    }

    private function mass_source_edit(string $terms, string $source): void
    {
        $terms = SearchTerm::explode($terms);

        $last_id = -1;
        while (true) {
            // make sure we don't look at the same images twice.
            // search returns high-ids first, so we want to look
            // at images with lower IDs than the previous.
            $search_forward = $terms;
            if ($last_id >= 0) {
                $search_forward[] = "id<$last_id";
            }

            $images = Search::find_images(limit: 100, terms: $search_forward);
            if (count($images) === 0) {
                break;
            }

            foreach ($images as $image) {
                send_event(new SourceSetEvent($image, $source));
                $last_id = $image->id;
            }
        }
    }
}
