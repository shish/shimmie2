<?php

declare(strict_types=1);

namespace Shimmie2;

// Tagger AJAX back-end
final class TaggerXML extends Extension
{
    public const KEY = "tagger_xml";

    #[EventListener(priority: 10)]
    public function onPageRequest(PageRequestEvent $event): void
    {
        if ($event->page_matches("tagger/tags")) {
            $tags = null;
            if ($event->GET->get('s')) { // tagger/tags[/...]?s=$string
                // return matching tags in XML form
                $tags = $this->match_tag_list($event->GET->get('s'));
            } elseif ($event->page_matches("tagger/tags/{image_id}")) {
                // return arg[1] AS image_id's tag list in XML form
                $tags = $this->image_tag_list($event->get_iarg('image_id'));
            }

            $xml = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n".
            "<tags>".
                $tags.
            "</tags>";

            $page = Ctx::$page;
            $page->set_data(MimeType::XML, $xml);
        }
    }

    private function match_tag_list(string $s): string
    {
        $max_rows = Ctx::$config->get(TaggerXMLConfig::TAG_MAX);
        $limit_rows = Ctx::$config->get(TaggerXMLConfig::LIMIT);

        $p = strlen($s) === 1 ? " " : "\_";
        $values = [
            'p' => $p,
            'sq' => "%".$p.$s."%"
        ];

        // Match
        $match = "(:p || tag) LIKE :sq";
        // Exclude
        //		$exclude = $event->get_arg('exclude')? "AND NOT IN ".$this->image_tags($event->get_arg('exclude')) : null;

        // Hidden Tags
        $hidden = Ctx::$config->get(TaggerXMLConfig::SHOW_HIDDEN)
            ? null
            : "AND substring(tag,1,1) != '.'";

        $q_where = "WHERE {$match} {$hidden} AND count > 0";

        // FROM based on return count
        $count = $this->count($q_where, $values);
        if ($count > $max_rows) {
            $q_from = "FROM (SELECT * FROM `tags` {$q_where} ".
                "ORDER BY count DESC LIMIT {$limit_rows} OFFSET 0) AS `c_tags`";
            $q_where = null;
            $count = ["max" => $count];
        } else {
            $q_from = "FROM `tags`";
            $count = [];
        }

        // @phpstan-ignore-next-line
        $tags = Ctx::$database->execute("SELECT * {$q_from} {$q_where} ORDER BY tag", $values);

        return $this->list_to_xml($tags, "search", $s, $count);
    }

    private function image_tag_list(int $image_id): string
    {
        $tags = Ctx::$database->execute("
			SELECT tags.*
			FROM image_tags JOIN tags ON image_tags.tag_id = tags.id
			WHERE image_id=:image_id ORDER BY tag", ['image_id' => $image_id]);
        return $this->list_to_xml($tags, "image", (string)$image_id);
    }

    /**
     * @param array<string, mixed> $misc
     */
    private function list_to_xml(\FFSPHP\PDOStatement $tags, string $type, string $query, ?array $misc = []): string
    {
        $props = [
            "id" => $type,
            "query" => $query,
            // @phpstan-ignore-next-line
            "rows" => $tags->_numOfRows
        ];
        if (!is_null($misc)) {
            foreach ($misc as $attr => $val) {
                $props[$attr] = $val;
            }
        }

        $list = new \MicroHTML\HTMLElement("list", [$props]);
        foreach ($tags as $tag) {
            $list->appendChild(new \MicroHTML\HTMLElement("tag", [["id" => $tag["id"], "count" => $tag["count"]], $tag["tag"]]));
        }

        return (string)($list);
    }

    /**
     * @param array<string, string> $values
     */
    private function count(string $query, array $values): int
    {
        // @phpstan-ignore-next-line
        return Ctx::$database->get_one("SELECT COUNT(*) FROM `tags` $query", $values);
    }
}
