<?php

declare(strict_types=1);

namespace Shimmie2;

use function MicroHTML\{A, DIV, INPUT, LINK, META, P, TABLE, TD, TR, emptyHTML, joinHTML};
use function MicroHTML\BR;

use MicroHTML\HTMLElement;

class ViewPostTheme extends Themelet
{
    public function display_meta_headers(Image $image): void
    {
        $page = Ctx::$page;
        $h_metatags = str_replace(" ", ", ", $image->get_tag_list());
        $page->add_html_header(META(["name" => "keywords", "content" => $h_metatags]));
        $page->add_html_header(META(["property" => "og:title", "content" => $h_metatags]));
        $page->add_html_header(META(["property" => "og:type", "content" => "article"]));
        $page->add_html_header(META(["property" => "og:image", "content" => $image->get_image_link()->asAbsolute()]));
        $page->add_html_header(META(["property" => "og:url", "content" => make_link("post/view/{$image->id}")->asAbsolute()]));
        $page->add_html_header(META(["property" => "og:image:width", "content" => $image->width]));
        $page->add_html_header(META(["property" => "og:image:height", "content" => $image->height]));
        $page->add_html_header(META(["property" => "twitter:title", "content" => $h_metatags]));
        $page->add_html_header(META(["property" => "twitter:card", "content" => "summary_large_image"]));
        $page->add_html_header(META(["property" => "twitter:image:src", "content" => $image->get_image_link()->asAbsolute()]));
    }

    /**
     * Build a page showing $image and some info about it
     *
     * @param HTMLElement[] $editor_parts
     */
    public function display_page(Image $image, array $editor_parts): void
    {
        $page = Ctx::$page;
        $page->set_title("Post {$image->id}: ".$image->get_tag_list());
        $page->set_heading($image->get_tag_list());
        $page->add_block(new Block("Post {$image->id}", $this->build_navigation($image), "left", 0, "Navigationleft"));
        $page->add_block(new Block(null, $this->build_info($image, $editor_parts), "main", 20, "ImageInfo"));
        //$page->add_block(new Block(null, $this->build_pin($image), "main", 11));

        if (!$this->is_ordered_search()) {
            $query = $this->get_query();
            $page->add_html_header(LINK(["id" => "nextlink", "rel" => "next", "href" => make_link("post/next/{$image->id}", $query)]));
            $page->add_html_header(LINK(["id" => "prevlink", "rel" => "previous", "href" => make_link("post/prev/{$image->id}", $query)]));
        }
    }

    /**
     * @param HTMLElement[] $parts
     */
    public function display_admin_block(array $parts): void
    {
        if (count($parts) > 0) {
            Ctx::$page->add_block(new Block("Post Controls", DIV(["class" => "post_controls"], joinHTML("", $parts)), "left", 50));
        }
    }

    protected function get_query(): ?QueryArray
    {
        if (isset($_GET['search'])) {
            $query = new QueryArray(["search" => $_GET['search']]);
        } else {
            $query = null;
        }
        return $query;
    }

    /**
     * prev/next only work for default-ordering searches - if the user
     * has specified a custom order, we can't show prev/next.
     */
    protected function is_ordered_search(): bool
    {
        if (isset($_GET['search'])) {
            $tags = Tag::explode($_GET['search']);
            foreach ($tags as $tag) {
                if (\Safe\preg_match("/^order[=:]/", $tag) === 1) {
                    return true;
                }
            }
        }
        return false;
    }

    protected function build_pin(Image $image): HTMLElement
    {
        if ($this->is_ordered_search()) {
            return A(["href" => make_link()], "Index");
        } else {
            $query = $this->get_query();
            return joinHTML(" | ", [
                A(["href" => make_link("post/prev/{$image->id}", $query), "id" => "prevlink"], "Prev"),
                A(["href" => make_link()], "Index"),
                A(["href" => make_link("post/next/{$image->id}", $query), "id" => "nextlink"], "Next"),
            ]);
        }
    }

    protected function build_navigation(Image $image): HTMLElement
    {
        $pin = $this->build_pin($image);

        $search = SHM_FORM(
            action: search_link(),
            method: 'GET',
            children: [
                INPUT([
                    "name" => 'search',
                    "type" => 'text',
                    "class" => 'autocomplete_tags',
                ]),
                INPUT([
                    "type" => 'submit',
                    "value" => 'Find',
                    "style" => 'display: none;'
                ]),
            ]
        );

        return emptyHTML($pin, P(), $search);
    }

    /**
     * @param HTMLElement[] $editor_parts
     */
    protected function build_info(Image $image, array $editor_parts): HTMLElement
    {
        if (count($editor_parts) === 0) {
            return emptyHTML($image->is_locked() ? "[Post Locked]" : "");
        }

        if (
            (!$image->is_locked() || Ctx::$user->can(PostLockPermission::EDIT_IMAGE_LOCK)) &&
            Ctx::$user->can(PostTagsPermission::EDIT_IMAGE_TAG)
        ) {
            $editor_parts[] = TR(TD(
                ["colspan" => 4],
                INPUT(["class" => "view", "type" => "button", "value" => "Edit", "onclick" => "clearViewMode()"]),
                INPUT(["class" => "edit", "type" => "submit", "value" => "Set"])
            ));
        }

        // SHM_POST_INFO returns a TR, let's sneakily append
        // a TD with the avatar in it
        /** @var BuildAvatarEvent $bae */
        $bae = send_event(new BuildAvatarEvent($image->get_owner()));
        if ($bae->html) {
            array_values($editor_parts)[0]->appendChild(
                TD(
                    ["class" => "image-info-avatar-box", "width" => Ctx::$config->get(SetupConfig::AVATAR_SIZE) . "px", "rowspan" => count($editor_parts) - 2],
                    $bae->html
                )
            );
        }

        return SHM_SIMPLE_FORM(
            make_link("post/set"),
            INPUT(["type" => "hidden", "name" => "image_id", "value" => $image->id]),
            TABLE(
                [
                    "class" => "image_info form",
                ],
                ...$editor_parts,
            ),
        );
    }

    protected function build_stats(Image $image): HTMLElement
    {
        $owner = $image->get_owner()->name;
        $ip = Ctx::$user->can(IPBanPermission::VIEW_IP) ? " ({$image->owner_ip})" : "";

        $parts = [
            "ID: {$image->id}",
            emptyHTML("Uploader: ", A(["href" => make_link("user/$owner")], $owner . $ip)),
            emptyHTML("Date: ", SHM_DATE($image->posted)),
            "Size: ".to_shorthand_int($image->filesize)." ({$image->width}x{$image->height})",
            "Type: {$image->get_mime()}",
        ];
        if ($image->video_codec !== null) {
            $parts[] = "Video Codec: {$image->video_codec->name}";
        }
        if ($image->length !== null) {
            $parts[] = "Length: " . format_milliseconds($image->length);
        }
        if ($image->source !== null) {
            $parts[] = emptyHTML("Source: ", A(["href" => $image->source], "link"));
        }
        if (RatingsInfo::is_enabled()) {
            $rating = $image['rating'] ?? "?";
            $h_rating = Ratings::rating_to_human($rating);
            $parts[] = emptyHTML("Rating: ", A(["href" => search_link(["rating=$rating"])], $h_rating));
        }

        return joinHTML(BR(), $parts);
    }
}
