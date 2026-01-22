<?php

declare(strict_types=1);

namespace Shimmie2;

use function MicroHTML\LINK;

final class RSSComments extends Extension
{
    public const KEY = "rss_comments";

    public function onPostListBuilding(PostListBuildingEvent $event): void
    {
        Ctx::$page->add_html_header(LINK([
            'rel' => 'alternate',
            'type' => 'application/rss+xml',
            'title' => Ctx::$config->get(SetupConfig::TITLE) . " - Comments",
            'href' => (string)make_link("rss/comments")
        ]));
    }

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

            $data = "";
            foreach ($comments as $comment) {
                $image_id = $comment['image_id'];
                $comment_id = $comment['comment_id'];
                $link = make_link("post/view/$image_id")->asAbsolute();
                $owner = html_escape($comment['user_name']);
                $posted = date(DATE_RSS, \Safe\strtotime($comment['posted']));
                $comment = html_escape($comment['comment']);
                $content = html_escape("$owner: $comment");

                $data .= "
					<item>
						<title>$owner comments on $image_id</title>
						<link>$link</link>
						<guid isPermaLink=\"false\">$comment_id</guid>
						<pubDate>$posted</pubDate>
						<description>$content</description>
					</item>
				";
            }

            $title = Ctx::$config->get(SetupConfig::TITLE);
            $base_href = Url::base()->asAbsolute();
            $version = SysConfig::getVersion();
            $xml = <<<EOD
<?xml version="1.0" encoding="utf-8" ?>
<rss version="2.0">
	<channel>
		<title>$title</title>
		<description>The latest comments on the image board</description>
		<link>$base_href</link>
		<generator>$version</generator>
		<copyright>(c) 2007 Shish</copyright>
		$data
	</channel>
</rss>
EOD;
            Ctx::$page->set_data(MimeType::RSS, $xml);
        }
    }

    public function onPageSubNavBuilding(PageSubNavBuildingEvent $event): void
    {
        if ($event->parent === "comment") {
            $event->add_nav_link(make_link('rss/comments'), "Feed", "rss_feed");
        }
    }
}
