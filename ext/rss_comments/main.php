<?php

declare(strict_types=1);

namespace Shimmie2;

class RSSComments extends Extension
{
    public function onPostListBuilding(PostListBuildingEvent $event): void
    {
        global $config, $page;
        $title = $config->get_string(SetupConfig::TITLE);

        $page->add_html_header("<link rel=\"alternate\" type=\"application/rss+xml\" ".
            "title=\"$title - Comments\" href=\"".make_link("rss/comments")."\" />");
    }

    public function onPageRequest(PageRequestEvent $event): void
    {
        global $config, $database, $page;
        if ($event->page_matches("rss/comments")) {
            $page->set_mode(PageMode::DATA);
            $page->set_mime(MimeType::RSS);

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
                $link = make_http(make_link("post/view/$image_id"));
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

            $title = $config->get_string(SetupConfig::TITLE);
            $base_href = make_http(get_base_href());
            $version = VERSION;
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
            $page->set_data($xml);
        }
    }

    public function onPageSubNavBuilding(PageSubNavBuildingEvent $event): void
    {
        if ($event->parent == "comment") {
            $event->add_nav_link("comment_rss", new Link('rss/comments'), "Feed");
        }
    }
}
