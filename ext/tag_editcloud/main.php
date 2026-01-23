<?php

declare(strict_types=1);

namespace Shimmie2;

/** @extends Extension<TagEditCloudTheme> */
final class TagEditCloud extends Extension
{
    public const KEY = "tag_editcloud";

    #[EventListener]
    public function onImageInfoBoxBuilding(ImageInfoBoxBuildingEvent $event): void
    {
        if ($this->can_tag($event->image)) {
            $data = $this->get_cloud_data($event->image);
            if (!is_null($data)) {
                $event->add_part($this->theme->build_tag_map(
                    $data,
                    $event->image->get_tag_array()
                ), 40);
            }
        }
    }

    /**
     * @return array<array{tag: string, scaled: float, count: int}>|null
     */
    private function get_cloud_data(Image $image): array|null
    {
        global $database;

        $sort_method = Ctx::$config->get(TagEditCloudConfig::SORT);
        $tags_min = Ctx::$config->get(TagEditCloudConfig::MIN_USAGE);
        $max_count = Ctx::$config->get(TagEditCloudConfig::MAX_COUNT);
        $ignore_tags = Tag::explode(Ctx::$config->get(TagEditCloudConfig::IGNORE_TAGS));

        switch ($sort_method) {
            case 'r':
                $relevant_tags = array_diff($image->get_tag_array(), $ignore_tags);
                if (count($relevant_tags) === 0) {
                    return null;
                }
                $relevant_tag_ids = implode(',', array_map(fn ($t) => Tag::get_or_create_id($t), $relevant_tags));

                $tag_data = Ctx::$database->get_all(
                    // @phpstan-ignore-next-line
                    "
					SELECT t2.tag AS tag, COUNT(image_id) AS count, FLOOR(LN(LN(COUNT(image_id) - :tag_min1 + 1)+1)*150)/200 AS scaled
					FROM image_tags it1
					JOIN image_tags it2 USING(image_id)
					JOIN tags t1 ON it1.tag_id = t1.id
					JOIN tags t2 ON it2.tag_id = t2.id
					WHERE t1.count >= :tag_min2 AND t1.id IN ($relevant_tag_ids)
					GROUP BY t2.tag
					ORDER BY count DESC
					LIMIT :limit",
                    ["tag_min1" => $tags_min, "tag_min2" => $tags_min, "limit" => $max_count]
                );
                break;
                /** @noinspection PhpMissingBreakStatementInspection */
            case 'c':
                if (TagCategoriesInfo::is_enabled()) {
                    $tag_data = $database->get_all(
                        "
                        SELECT tag, FLOOR(LN(LN(count - :tag_min1 + 1)+1)*150)/200 AS scaled, count
                        FROM tags
                        WHERE count >= :tag_min2
                        ORDER BY SUM(count) OVER (PARTITION BY SUBSTRING_INDEX(tag, ':', 1)) DESC, CASE
                            WHEN tag LIKE '%:%' THEN 1
                            ELSE 2
                        END, tag
                        LIMIT :limit",
                        ["tag_min1" => $tags_min, "tag_min2" => $tags_min, "limit" => $max_count]
                    );
                    break;
                } else {
                    $sort_method = 'a';
                }
                // no break
            case 'a':
            case 'p':
            default:
                $order_by = $sort_method === 'a' ? "tag" : "count DESC";
                $tag_data = $database->get_all(
                    "
					SELECT tag, FLOOR(LN(LN(count - :tag_min1 + 1)+1)*150)/200 AS scaled, count
					FROM tags
					WHERE count >= :tag_min2
					ORDER BY $order_by
					LIMIT :limit",
                    ["tag_min1" => $tags_min, "tag_min2" => $tags_min, "limit" => $max_count]
                );
                break;
        }
        return $tag_data;
    }

    private function can_tag(Image $image): bool
    {
        return (
            Ctx::$user->can(PostTagsPermission::EDIT_IMAGE_TAG)
            && (
                !$image->is_locked()
                || Ctx::$user->can(PostLockPermission::EDIT_IMAGE_LOCK)
            )
        );
    }
}
