<?php

declare(strict_types=1);

namespace Shimmie2;

use function MicroHTML\{LINK};

final class RSSComments extends Extension
{
    public const KEY = "rss_comments";

    #[EventListener]
    public function onPostListBuilding(PostListBuildingEvent $event): void
    {
        Ctx::$page->add_html_header(LINK([
            'rel' => 'alternate',
            'type' => 'application/rss+xml',
            'title' => Ctx::$config->get(SetupConfig::TITLE) . " - Comments",
            'href' => (string)make_link("rss/comments")
        ]));
    }

    #[EventListener]
    public function onPageRequest(PageRequestEvent $event): void
    {
        global $database;
        if ($event->page_matches("rss/comments")) {
            $comments = $database->get_all("
				SELECT
					users.id as user_id, users.name as user_name,
					comments.comment as comment, comments.id as comment_id,
					comments.image_id as image_id, comments.owner_ip as poster_ip,
					comments.posted as posted
				FROM comments
				LEFT JOIN users ON comments.owner_id=users.id
			  	ORDER BY comments.id DESC
		  		LIMIT 10
			");

            $items = [];
            foreach ($comments as $comment) {
                $image_id = $comment['image_id'];
                $comment_id = $comment['comment_id'];
                $link = make_link("post/view/$image_id")->asAbsolute();
                $owner = $comment['user_name'];
                $posted = date(DATE_RSS, \Safe\strtotime($comment['posted']));
                $comment_text = format_text($comment['comment']);
                $content = "$owner: $comment_text";

                $items[] = ITEM(
                    RSS_TITLE("$owner comments on $image_id"),
                    RSS_LINK($link),
                    RSS_GUID(["isPermaLink" => "false"], (string)$comment_id),
                    RSS_PUBDATE($posted),
                    RSS_DESCRIPTION($content)
                );
            }

            $title = Ctx::$config->get(SetupConfig::TITLE);
            $base_href = Url::base()->asAbsolute();
            $version = SysConfig::getVersion();

            $rss = RSS(
                ["version" => "2.0"],
                CHANNEL(
                    RSS_TITLE($title),
                    RSS_DESCRIPTION("The latest comments on the image board"),
                    RSS_LINK($base_href),
                    RSS_GENERATOR($version),
                    RSS_COPYRIGHT("(c) 2007 Shish"),
                    ...$items
                )
            );

            $xml = "<?xml version=\"1.0\" encoding=\"utf-8\" ?>\n" . $rss;
            Ctx::$page->set_data(MimeType::RSS, $xml);
        }
    }

    #[EventListener]
    public function onPageSubNavBuilding(PageSubNavBuildingEvent $event): void
    {
        if ($event->parent === "comment") {
            $event->add_nav_link(make_link('rss/comments'), "Feed");
        }
    }
}
