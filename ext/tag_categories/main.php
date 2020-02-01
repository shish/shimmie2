<?php declare(strict_types=1);


require_once "config.php";

class TagCategories extends Extension
{
    public function onInitExt(InitExtEvent $event)
    {
        global $config;

        // whether we split out separate categories on post view by default
        //  note: only takes effect if /post/view shows the image's exact tags
        $config->set_default_bool(TagCategoriesConfig::SPLIT_ON_VIEW, true);
    }

    public function onDatabaseUpgrade(DatabaseUpgradeEvent $event)
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
                ["category"=>"artist", "single"=>"Artist", "multiple"=>"Artists", "color"=>"#BB6666"]
            );
            $database->execute(
                'INSERT INTO image_tag_categories VALUES (:category, :single, :multiple, :color)',
                ["category"=>"series", "single"=>"Series", "multiple"=>"Series", "color"=>"#AA00AA"]
            );
            $database->execute(
                'INSERT INTO image_tag_categories VALUES (:category, :single, :multiple, :color)',
                ["category"=>"character", "single"=>"Character", "multiple"=>"Characters", "color"=>"#66BB66"]
            );
        }
    }

    public function onPageRequest(PageRequestEvent $event)
    {
        global $page, $user;

        if ($event->page_matches("tags/categories")) {
            if ($user->can(Permissions::EDIT_TAG_CATEGORIES)) {
                $this->page_update();
                $this->show_tag_categories($page);
            }
        }
    }

    public function onSearchTermParse(SearchTermParseEvent $event)
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
                    new Querylet("EXISTS (
					    SELECT 1
					    FROM image_tags it
					    LEFT JOIN tags t ON it.tag_id = t.id
					    WHERE images.id = it.image_id
					    GROUP BY image_id
					    HAVING SUM(CASE WHEN LOWER(t.tag) LIKE LOWER('$type:%') THEN 1 ELSE 0 END) $cmp $count
					)")
                );
            }
        }
    }

    public function onHelpPageBuilding(HelpPageBuildingEvent $event)
    {
        if ($event->key===HelpPages::SEARCH) {
            $block = new Block();
            $block->header = "Tag Categories";
            $block->body = $this->theme->get_help_html();
            $event->add_block($block);
        }
    }

    public function getDict()
    {
        global $database;
        return $database->get_all('SELECT * FROM image_tag_categories;');
    }

    public function getKeyedDict($key_with = 'category')
    {
        $tc_dict = $this->getDict();
        $tc_keyed_dict = [];

        foreach ($tc_dict as $row) {
            $key = $row[$key_with];
            $tc_keyed_dict[$key] = $row;
        }

        return $tc_keyed_dict;
    }

    public function page_update()
    {
        global $user, $database;

        if (!$user->can(Permissions::EDIT_TAG_CATEGORIES)) {
            return false;
        }

        if (!isset($_POST['tc_status']) and
           !isset($_POST['tc_category']) and
           !isset($_POST['tc_display_singular']) and
           !isset($_POST['tc_display_multiple']) and
           !isset($_POST['tc_color'])) {
            return false;
        }

        $is_success = null;

        if ($_POST['tc_status'] == 'edit') {
            $is_success = $database->execute(
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
            $is_success = $database->execute(
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
            $is_success = $database->execute(
                'DELETE FROM image_tag_categories
				WHERE category=:category',
                [
                    'category' => $_POST['tc_category']
                ]
            );
        }

        return $is_success;
    }

    public function show_tag_categories($page)
    {
        $this->theme->show_tag_categories($page, $this->getDict());
    }
}
