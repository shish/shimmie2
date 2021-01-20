<?php declare(strict_types=1);

require_once "config.php";
require_once "events.php";

class Index extends Extension
{
    /** @var IndexTheme */
    protected $theme;

    public function onInitExt(InitExtEvent $event)
    {
        global $config;
        $config->set_default_int(IndexConfig::IMAGES, 24);
        $config->set_default_bool(IndexConfig::TIPS, true);
        $config->set_default_string(IndexConfig::ORDER, "id DESC");
    }

    public function onPageRequest(PageRequestEvent $event)
    {
        global $cache, $page, $user;
        if ($event->page_matches("post/list")) {
            if (isset($_GET['search'])) {
                // implode(explode()) to resolve aliases and sanitise
                $search = url_escape(Tag::caret(Tag::implode(Tag::explode($_GET['search'], false))));
                if (empty($search)) {
                    $page->set_mode(PageMode::REDIRECT);
                    $page->set_redirect(make_link("post/list/1"));
                } else {
                    $page->set_mode(PageMode::REDIRECT);
                    $page->set_redirect(make_link('post/list/'.$search.'/1'));
                }
                return;
            }

            $search_terms = $event->get_search_terms();
            $page_number = $event->get_page_number();
            $page_size = $event->get_page_size();

            $count_search_terms = count($search_terms);

            try {
                $fast_page_limit = 500;

                $ua = $_SERVER["HTTP_USER_AGENT"] ?? "No UA";
                if (
                    SPEED_HAX
                    && (
                        str_contains($ua, "Googlebot")
                        || str_contains($ua, "YandexBot")
                        || str_contains($ua, "bingbot")
                        || str_contains($ua, "msnbot")
                    )
                    && (
                        $count_search_terms > 1
                        || ($count_search_terms == 1 && $search_terms[0][0] == "-")
                    )
                ) {
                    // bots love searching for weird combinations of tags...
                    $fast_page_limit = 10;
                }

                if (SPEED_HAX && $page_number > $fast_page_limit && !$user->can("big_search")) {
                    $this->theme->display_error(
                        404,
                        "Search limit hit",
                        "Only $fast_page_limit pages of results are searchable - " .
                        "if you want to find older results, use more specific search terms"
                    );
                    return;
                }

                $total_pages = Image::count_pages($search_terms);
                $images = [];

                if (SPEED_HAX && $total_pages > $fast_page_limit && !$user->can("big_search")) {
                    $total_pages = $fast_page_limit;
                }

                if (SPEED_HAX) {
                    if ($count_search_terms === 0 && ($page_number < 10)) {
                        // extra caching for the first few post/list pages
                        $images = $cache->get("post-list:$page_number");
                        if (!$images) {
                            $images = Image::find_images(($page_number-1)*$page_size, $page_size, $search_terms);
                            $cache->set("post-list:$page_number", $images, 60);
                        }
                    }
                }

                if (!$images) {
                    $images = Image::find_images(($page_number-1)*$page_size, $page_size, $search_terms);
                }
            } catch (SearchTermParseException $stpe) {
                // FIXME: display the error somewhere
                $total_pages = 0;
                $images = [];
            }

            $count_images = count($images);

            if ($count_search_terms === 0 && $count_images === 0 && $page_number === 1) {
                $this->theme->display_intro($page);
                send_event(new PostListBuildingEvent($search_terms));
            } elseif ($count_search_terms > 0 && $count_images === 1 && $page_number === 1) {
                $page->set_mode(PageMode::REDIRECT);
                $page->set_redirect(make_link('post/view/'.$images[0]->id));
            } else {
                $plbe = new PostListBuildingEvent($search_terms);
                send_event($plbe);

                $this->theme->set_page($page_number, $total_pages, $search_terms);
                $this->theme->display_page($page, $images);
                if (count($plbe->parts) > 0) {
                    $this->theme->display_admin_block($plbe->parts);
                }
            }
        }
    }

    public function onSetupBuilding(SetupBuildingEvent $event)
    {
        $sb = $event->panel->create_new_block("Index Options");
        $sb->position = 20;

        $sb->add_label("Show ");
        $sb->add_int_option(IndexConfig::IMAGES);
        $sb->add_label(" images on the post list");
    }

    public function onPageNavBuilding(PageNavBuildingEvent $event)
    {
        $event->add_nav_link("posts", new Link('post/list'), "Posts", NavLink::is_active(["post","view"]), 20);
    }

    public function onPageSubNavBuilding(PageSubNavBuildingEvent $event)
    {
        if ($event->parent=="posts") {
            $event->add_nav_link("posts_all", new Link('post/list'), "All");
        }
    }

    public function onHelpPageBuilding(HelpPageBuildingEvent $event)
    {
        if ($event->key===HelpPages::SEARCH) {
            $block = new Block();
            $block->header = "General";
            $block->body = $this->theme->get_help_html();
            $event->add_block($block, 0);
        }
    }

    public function onCommand(CommandEvent $event)
    {
        if ($event->cmd == "help") {
            # TODO: --fields a,b,c
            print "\tsearch <query>\n";
            print "\t\tsearch the database and print results\n\n";
        }
        if ($event->cmd == "search") {
            $query = count($event->args) > 0 ? Tag::explode($event->args[0]) : [];
            $items = Image::find_images(0, 1000, $query);
            foreach ($items as $item) {
                print("{$item->hash}\n");
            }
        }
    }

    public function onSearchTermParse(SearchTermParseEvent $event)
    {
        if (is_null($event->term)) {
            return;
        }

        $matches = [];
        // check for tags first as tag based searches are more common.
        if (preg_match("/^tags([:]?<|[:]?>|[:]?<=|[:]?>=|[:|=])(\d+)$/i", $event->term, $matches)) {
            $cmp = ltrim($matches[1], ":") ?: "=";
            $count = $matches[2];
            $event->add_querylet(
                new Querylet("EXISTS (
				              SELECT 1
				              FROM image_tags it
				              LEFT JOIN tags t ON it.tag_id = t.id
				              WHERE images.id = it.image_id
				              GROUP BY image_id
				              HAVING COUNT(*) $cmp $count
				)")
            );
        } elseif (preg_match("/^ratio([:]?<|[:]?>|[:]?<=|[:]?>=|[:|=])(\d+):(\d+)$/i", $event->term, $matches)) {
            $cmp = preg_replace('/^:/', '=', $matches[1]);
            $args = ["width{$event->id}"=>int_escape($matches[2]), "height{$event->id}"=>int_escape($matches[3])];
            $event->add_querylet(new Querylet("width / :width{$event->id} $cmp height / :height{$event->id}", $args));
        } elseif (preg_match("/^(filesize|id)([:]?<|[:]?>|[:]?<=|[:]?>=|[:|=])(\d+[kmg]?b?)$/i", $event->term, $matches)) {
            $col = $matches[1];
            $cmp = ltrim($matches[2], ":") ?: "=";
            $val = parse_shorthand_int($matches[3]);
            $event->add_querylet(new Querylet("images.$col $cmp :val{$event->id}", ["val{$event->id}"=>$val]));
        } elseif (preg_match("/^(hash|md5)[=|:]([0-9a-fA-F]*)$/i", $event->term, $matches)) {
            $hash = strtolower($matches[2]);
            $event->add_querylet(new Querylet('images.hash = :hash', ["hash" => $hash]));
        } elseif (preg_match("/^(phash)[=|:]([0-9a-fA-F]*)$/i", $event->term, $matches)) {
            $phash = strtolower($matches[2]);
            $event->add_querylet(new Querylet('images.phash = :phash', ["phash" => $phash]));
        } elseif (preg_match("/^(filename|name)[=|:](.+)$/i", $event->term, $matches)) {
            $filename = strtolower($matches[2]);
            $event->add_querylet(new Querylet("lower(images.filename) LIKE :filename{$event->id}", ["filename{$event->id}"=>"%$filename%"]));
        } elseif (preg_match("/^(source)[=|:](.*)$/i", $event->term, $matches)) {
            $source = strtolower($matches[2]);

            if (preg_match("/^(any|none)$/i", $source)) {
                $not = ($source == "any" ? "NOT" : "");
                $event->add_querylet(new Querylet("images.source IS $not NULL"));
            } else {
                $event->add_querylet(new Querylet('images.source LIKE :src', ["src"=>"%$source%"]));
            }
        } elseif (preg_match("/^posted([:]?<|[:]?>|[:]?<=|[:]?>=|[:|=])([0-9-]*)$/i", $event->term, $matches)) {
            // TODO Make this able to search = without needing a time component.
            $cmp = ltrim($matches[1], ":") ?: "=";
            $val = $matches[2];
            $event->add_querylet(new Querylet("images.posted $cmp :posted{$event->id}", ["posted{$event->id}"=>$val]));
        } elseif (preg_match("/^size([:]?<|[:]?>|[:]?<=|[:]?>=|[:|=])(\d+)x(\d+)$/i", $event->term, $matches)) {
            $cmp = ltrim($matches[1], ":") ?: "=";
            $args = ["width{$event->id}"=>int_escape($matches[2]), "height{$event->id}"=>int_escape($matches[3])];
            $event->add_querylet(new Querylet("width $cmp :width{$event->id} AND height $cmp :height{$event->id}", $args));
        } elseif (preg_match("/^width([:]?<|[:]?>|[:]?<=|[:]?>=|[:|=])(\d+)$/i", $event->term, $matches)) {
            $cmp = ltrim($matches[1], ":") ?: "=";
            $event->add_querylet(new Querylet("width $cmp :width{$event->id}", ["width{$event->id}"=>int_escape($matches[2])]));
        } elseif (preg_match("/^height([:]?<|[:]?>|[:]?<=|[:]?>=|[:|=])(\d+)$/i", $event->term, $matches)) {
            $cmp = ltrim($matches[1], ":") ?: "=";
            $event->add_querylet(new Querylet("height $cmp :height{$event->id}", ["height{$event->id}"=>int_escape($matches[2])]));
        } elseif (preg_match("/^length([:]?<|[:]?>|[:]?<=|[:]?>=|[:|=])(.+)$/i", $event->term, $matches)) {
            $value = parse_to_milliseconds($matches[2]);
            $cmp = ltrim($matches[1], ":") ?: "=";
            $event->add_querylet(new Querylet("length $cmp :length{$event->id}", ["length{$event->id}"=>$value]));
        } elseif (preg_match("/^order[=|:](id|width|height|length|filesize|filename)[_]?(desc|asc)?$/i", $event->term, $matches)) {
            $ord = strtolower($matches[1]);
            $default_order_for_column = preg_match("/^(id|filename)$/", $matches[1]) ? "ASC" : "DESC";
            $sort = isset($matches[2]) ? strtoupper($matches[2]) : $default_order_for_column;
            $event->order = "images.$ord $sort";
        } elseif (preg_match("/^order[=|:]random[_]([0-9]{1,4})$/i", $event->term, $matches)) {
            // requires a seed to avoid duplicates
            // since the tag can't be changed during the parseevent, we instead generate the seed during submit using js
            $seed = $matches[1];
            $event->order = "RAND($seed)";
        } elseif (preg_match("/^order[=|:]dailyshuffle$/i", $event->term, $matches)) {
            // will use today's date as seed, thus allowing for a dynamic randomized list without outside intervention.
            // This way the list will change every day, giving a more dynamic feel to the imageboard.
            // recommended to change homepage to "post/list/order:dailyshuffle/1"
            $seed = date("Ymd");
            $event->order = "RAND($seed)";
        }
    }
}
