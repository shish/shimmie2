<?php

declare(strict_types=1);

namespace Shimmie2;

use MicroHTML\HTMLElement;

use function MicroHTML\SPAN;

/** @extends Extension<TagCategoriesTheme> */
final class TagCategories extends Extension
{
    public const KEY = "tag_categories";

    #[EventListener]
    public function onDatabaseUpgrade(DatabaseUpgradeEvent $event): void
    {
        global $database;

        if ($this->get_version() < 1) {
            // primary extension database, holds all our stuff!
            $database->create_table(
                'image_tag_categories',
                'category VARCHAR(60) PRIMARY KEY,
				display_singular VARCHAR(60),
				display_multiple VARCHAR(60),
				color VARCHAR(7)'
            );

            $this->set_version(1);
        }

        // if empty, add our default values
        $number_of_db_rows = $database->get_one('SELECT COUNT(*) FROM image_tag_categories');

        if ($number_of_db_rows === 0) {
            $database->execute(
                'INSERT INTO image_tag_categories VALUES (:category, :single, :multiple, :color)',
                ["category" => "artist", "single" => "Artist", "multiple" => "Artists", "color" => "#BB6666"]
            );
            $database->execute(
                'INSERT INTO image_tag_categories VALUES (:category, :single, :multiple, :color)',
                ["category" => "series", "single" => "Series", "multiple" => "Series", "color" => "#AA00AA"]
            );
            $database->execute(
                'INSERT INTO image_tag_categories VALUES (:category, :single, :multiple, :color)',
                ["category" => "character", "single" => "Character", "multiple" => "Characters", "color" => "#66BB66"]
            );
        }
    }

    #[EventListener]
    public function onPageSubNavBuilding(PageSubNavBuildingEvent $event): void
    {
        if ($event->parent === "tags") {
            $event->add_nav_link(make_link('tags/categories'), "Tag Categories", ["tag_categories"]);
        }
    }

    #[EventListener]
    public function onPageRequest(PageRequestEvent $event): void
    {
        if ($event->page_matches("tags/categories", method: "GET")) {
            /** @var array<array{category: string, display_singular: string, display_multiple: string, color: string}> $tcs */
            $tcs = Ctx::$database->get_all('SELECT * FROM image_tag_categories');
            $this->theme->show_tag_categories($tcs);
        }
        if ($event->page_matches("tags/categories", method: "POST", permission: TagCategoriesPermission::EDIT_TAG_CATEGORIES)) {
            $this->page_update();
            Ctx::$page->set_redirect(make_link("tags/categories"));
        }
    }

    #[EventListener]
    public function onSearchTermParse(SearchTermParseEvent $event): void
    {
        global $database;

        if ($matches = $event->matches("/^(.+)_?tags(:|<=|<|=|>|>=)([0-9]+)$/i")) {
            $type = strtolower($matches[1]);
            $cmp = ltrim($matches[2], ":") ?: "=";
            $count = $matches[3];

            $types = $database->get_col(
                'SELECT LOWER(category) FROM image_tag_categories'
            );
            if (in_array($type, $types)) {
                $event->add_querylet(
                    new Querylet("(
					    SELECT count(distinct t.id)
					    FROM tags t
					    INNER JOIN image_tags it ON it.tag_id = t.id AND images.id = it.image_id
					    WHERE SCORE_ILIKE(t.tag, :cat{$event->id}) $cmp :count{$event->id}
					", ["cat{$event->id}" => '$type:%', "count{$event->id}" => $count])
                );
            }
        }
    }

    #[EventListener]
    public function onHelpPageBuilding(HelpPageBuildingEvent $event): void
    {
        if ($event->key === HelpPages::SEARCH) {
            $event->add_section("Tag Categories", $this->theme->get_help_html());
        }
    }

    /**
     * @return array<string, array{category: string, display_singular: string, display_multiple: string, color: string}>
     */
    public static function getKeyedDict(): array
    {
        global $database;
        static $tc_keyed_dict = null;

        if (is_null($tc_keyed_dict)) {
            $tc_keyed_dict = [];
            $tc_dict = $database->get_all('SELECT * FROM image_tag_categories');

            foreach ($tc_dict as $row) {
                $tc_keyed_dict[(string)$row['category']] = $row;
            }
        }

        return $tc_keyed_dict;
    }

    public static function get_tag_category(string $tag): ?string
    {
        $tag_category_dict = static::getKeyedDict();
        $tag_split = explode(':', $tag, 2);
        if (count($tag_split) > 1 && array_key_exists($tag_split[0], $tag_category_dict)) {
            return $tag_split[0];
        }
        return null;
    }

    public static function get_tag_body(string $tag): string
    {
        $tag_category_dict = static::getKeyedDict();
        $tag_split = explode(':', $tag, 2);
        if (count($tag_split) > 1 && array_key_exists($tag_split[0], $tag_category_dict)) {
            return $tag_split[1];
        }
        return $tag;
    }

    public static function getTagHtml(string $h_tag, string $extra_text = ''): HTMLElement
    {
        $h_tag_no_underscores = str_replace("_", " ", $h_tag);

        $tag_category_dict = static::getKeyedDict();

        // we found a tag, see if it's valid!
        $h_tag_split = explode(':', $h_tag, 2);
        if ((count($h_tag_split) > 1) and array_key_exists($h_tag_split[0], $tag_category_dict)) {
            $category = $h_tag_split[0];
            $h_tag = $h_tag_split[1];
            $tag_category_css = 'tag_category_'.$category;
            $h_tag_no_underscores = str_replace("_", " ", $h_tag);

            return SPAN(
                [
                    "class" => $tag_category_css,
                    "style" => "color:".$tag_category_dict[$category]['color'].";"
                ],
                $h_tag_no_underscores.$extra_text
            );
        } else {
            return SPAN($h_tag_no_underscores.$extra_text);
        }
    }

    public function page_update(): void
    {
        global $database;

        if (!isset($_POST['tc_status']) and
           !isset($_POST['tc_category']) and
           !isset($_POST['tc_display_singular']) and
           !isset($_POST['tc_display_multiple']) and
           !isset($_POST['tc_color'])) {
            return;
        }

        if ($_POST['tc_status'] === 'edit') {
            $database->execute(
                'UPDATE image_tag_categories
				SET display_singular=:display_singular,
					display_multiple=:display_multiple,
					color=:color
				WHERE category=:category',
                [
                    'category' => $_POST['tc_category'],
                    'display_singular' => $_POST['tc_display_singular'],
                    'display_multiple' => $_POST['tc_display_multiple'],
                    'color' => $_POST['tc_color'],
                ]
            );
        } elseif ($_POST['tc_status'] === 'new') {
            $database->execute(
                'INSERT INTO image_tag_categories
				VALUES (:category, :display_singular, :display_multiple, :color)',
                [
                    'category' => $_POST['tc_category'],
                    'display_singular' => $_POST['tc_display_singular'],
                    'display_multiple' => $_POST['tc_display_multiple'],
                    'color' => $_POST['tc_color'],
                ]
            );
        } elseif ($_POST['tc_status'] === 'delete') {
            $database->execute(
                'DELETE FROM image_tag_categories
				WHERE category=:category',
                [
                    'category' => $_POST['tc_category']
                ]
            );
        }
    }
}
