<?php

declare(strict_types=1);

namespace Shimmie2;

require_once "config.php";

class TagCategories extends Extension
{
    /** @var TagCategoriesTheme */
    protected Themelet $theme;

    public function onInitExt(InitExtEvent $event): void
    {
        global $config;

        // whether we split out separate categories on post view by default
        //  note: only takes effect if /post/view shows the image's exact tags
        $config->set_default_bool(TagCategoriesConfig::SPLIT_ON_VIEW, true);
    }

    public function onDatabaseUpgrade(DatabaseUpgradeEvent $event): void
    {
        global $database;

        if ($this->get_version(TagCategoriesConfig::VERSION) < 1) {
            // primary extension database, holds all our stuff!
            $database->create_table(
                'image_tag_categories',
                'category VARCHAR(60) PRIMARY KEY,
				display_singular VARCHAR(60),
				display_multiple VARCHAR(60),
				color VARCHAR(7)'
            );

            $this->set_version(TagCategoriesConfig::VERSION, 1);

            log_info("tag_categories", "extension installed");
        }

        // if empty, add our default values
        $number_of_db_rows = $database->execute('SELECT COUNT(*) FROM image_tag_categories;')->fetchColumn();

        if ($number_of_db_rows == 0) {
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

    public function onPageSubNavBuilding(PageSubNavBuildingEvent $event): void
    {
        if ($event->parent == "tags") {
            $event->add_nav_link("tag_categories", new Link('tags/categories'), "Tag Categories", NavLink::is_active(["tag_categories"]));
        }
    }

    public function onPageRequest(PageRequestEvent $event): void
    {
        global $database, $page, $user;

        if ($event->page_matches("tags/categories", method: "GET")) {
            $this->theme->show_tag_categories($page, $database->get_all('SELECT * FROM image_tag_categories'));
        }
        if ($event->page_matches("tags/categories", method: "POST", permission: Permissions::EDIT_TAG_CATEGORIES)) {
            $this->page_update();
            $page->set_mode(PageMode::REDIRECT);
            $page->set_redirect(make_link("tags/categories"));
        }
    }

    public function onSearchTermParse(SearchTermParseEvent $event): void
    {
        if (is_null($event->term)) {
            return;
        }

        $matches = [];
        if (preg_match("/^(.+)tags([:]?<|[:]?>|[:]?<=|[:]?>=|[:|=])([0-9]+)$/i", $event->term, $matches)) {
            global $database;
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
					    WHERE LOWER(t.tag) LIKE LOWER('$type:%')) $cmp $count
					")
                );
            }
        }
    }

    public function onHelpPageBuilding(HelpPageBuildingEvent $event): void
    {
        if ($event->key === HelpPages::SEARCH) {
            $block = new Block();
            $block->header = "Tag Categories";
            $block->body = $this->theme->get_help_html();
            $event->add_block($block);
        }
    }

    /**
     * @return array<string, array{category: string, display_singular: string, display_multiple: string, color: string}>
     */
    public function getKeyedDict(): array
    {
        global $database;
        $tc_dict = $database->get_all('SELECT * FROM image_tag_categories');
        $tc_keyed_dict = [];

        foreach ($tc_dict as $row) {
            $tc_keyed_dict[(string)$row['category']] = $row;
        }

        return $tc_keyed_dict;
    }

    /**
     * @param array<string, array{color: string}> $tag_category_dict
     */
    public function getTagHtml(string $h_tag, array $tag_category_dict, string $extra_text = ''): string
    {
        $h_tag_no_underscores = str_replace("_", " ", $h_tag);

        // we found a tag, see if it's valid!
        $h_tag_split = explode(':', $h_tag, 2);
        if ((count($h_tag_split) > 1) and array_key_exists($h_tag_split[0], $tag_category_dict)) {
            $category = $h_tag_split[0];
            $h_tag = $h_tag_split[1];
            $tag_category_css = ' tag_category_'.$category;
            $tag_category_style = 'style="color:'.html_escape($tag_category_dict[$category]['color']).';" ';
            $h_tag_no_underscores = str_replace("_", " ", $h_tag);

            $h_tag_no_underscores = '<span class="'.$tag_category_css.'" '.$tag_category_style.'>'.$h_tag_no_underscores.$extra_text.'</span>';
        } else {
            $h_tag_no_underscores .= $extra_text;
        }

        return $h_tag_no_underscores;
    }

    public function page_update(): void
    {
        global $user, $database;

        if (!isset($_POST['tc_status']) and
           !isset($_POST['tc_category']) and
           !isset($_POST['tc_display_singular']) and
           !isset($_POST['tc_display_multiple']) and
           !isset($_POST['tc_color'])) {
            return;
        }

        if ($_POST['tc_status'] == 'edit') {
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
        } elseif ($_POST['tc_status'] == 'new') {
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
        } elseif ($_POST['tc_status'] == 'delete') {
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
