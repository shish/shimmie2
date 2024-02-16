<?php

declare(strict_types=1);

namespace Shimmie2;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\{InputInterface,InputArgument};
use Symfony\Component\Console\Output\OutputInterface;

require_once "config.php";
require_once "events.php";

class Index extends Extension
{
    /** @var IndexTheme */
    protected Themelet $theme;

    public function onInitExt(InitExtEvent $event): void
    {
        global $config;
        $config->set_default_int(IndexConfig::IMAGES, 24);
        $config->set_default_bool(IndexConfig::TIPS, true);
        $config->set_default_string(IndexConfig::ORDER, "id DESC");
    }

    public function onPageRequest(PageRequestEvent $event): void
    {
        global $cache, $config, $page, $user;
        if (
            $event->page_matches("post/list", paged: true)
            || $event->page_matches("post/list/{search}", paged: true)
        ) {
            if ($event->get_GET('search')) {
                $page->set_mode(PageMode::REDIRECT);
                $page->set_redirect(search_link(Tag::explode($event->get_GET('search'), false)));
                return;
            }

            $search_terms = Tag::explode($event->get_arg('search', ""), false);
            $count_search_terms = count($search_terms);
            $page_number = $event->get_iarg('page_num', 1);
            $page_size = $config->get_int(IndexConfig::IMAGES);

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
                throw new PermissionDenied(
                    "Only $fast_page_limit pages of results are searchable - " .
                    "if you want to find older results, use more specific search terms"
                );
            }

            $total_pages = (int)ceil(Search::count_images($search_terms) / $config->get_int(IndexConfig::IMAGES));
            if (SPEED_HAX && $total_pages > $fast_page_limit && !$user->can("big_search")) {
                $total_pages = $fast_page_limit;
            }

            $images = null;
            if (SPEED_HAX) {
                if ($count_search_terms === 0 && ($page_number < 10)) {
                    // extra caching for the first few post/list pages
                    $images = cache_get_or_set(
                        "post-list:$page_number",
                        fn () => Search::find_images(($page_number - 1) * $page_size, $page_size, $search_terms),
                        60
                    );
                }
            }
            if (is_null($images)) {
                $images = Search::find_images(($page_number - 1) * $page_size, $page_size, $search_terms);
            }

            $count_images = count($images);

            if ($count_search_terms === 0 && $count_images === 0 && $page_number === 1) {
                $this->theme->display_intro($page);
                send_event(new PostListBuildingEvent($search_terms));
            } elseif ($count_search_terms > 0 && $count_images === 1 && $page_number === 1) {
                $page->set_mode(PageMode::REDIRECT);
                $page->set_redirect(make_link('post/view/'.$images[0]->id));
            } else {
                $plbe = send_event(new PostListBuildingEvent($search_terms));

                $this->theme->set_page($page_number, $total_pages, $search_terms);
                $this->theme->display_page($page, $images);
                if (count($plbe->parts) > 0) {
                    $this->theme->display_admin_block($plbe->parts);
                }
            }
        }
    }

    public function onSetupBuilding(SetupBuildingEvent $event): void
    {
        $sb = $event->panel->create_new_block("Index Options");
        $sb->position = 20;

        $sb->add_label("Show ");
        $sb->add_int_option(IndexConfig::IMAGES);
        $sb->add_label(" images on the post list");
    }

    public function onPageNavBuilding(PageNavBuildingEvent $event): void
    {
        $event->add_nav_link("posts", new Link('post/list'), "Posts", NavLink::is_active(["post","view"]), 20);
    }

    public function onPageSubNavBuilding(PageSubNavBuildingEvent $event): void
    {
        if ($event->parent == "posts") {
            $event->add_nav_link("posts_all", new Link('post/list'), "All");
        }
    }

    public function onHelpPageBuilding(HelpPageBuildingEvent $event): void
    {
        if ($event->key === HelpPages::SEARCH) {
            $event->add_block(new Block("General", $this->theme->get_help_html()), 0);
        }
    }

    public function onCliGen(CliGenEvent $event): void
    {
        $event->app->register('search')
            ->addArgument('query', InputArgument::REQUIRED)
            ->setDescription('Search the database and print results')
            ->setCode(function (InputInterface $input, OutputInterface $output): int {
                $query = Tag::explode($input->getArgument('query'));
                $items = Search::find_images(limit: 1000, tags: $query);
                foreach ($items as $item) {
                    $output->writeln($item->hash);
                }
                return Command::SUCCESS;
            });
    }

    public function onSearchTermParse(SearchTermParseEvent $event): void
    {
        global $database;

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
            $args = ["width{$event->id}" => int_escape($matches[2]), "height{$event->id}" => int_escape($matches[3])];
            $event->add_querylet(new Querylet("width / :width{$event->id} $cmp height / :height{$event->id}", $args));
        } elseif (preg_match("/^filesize([:]?<|[:]?>|[:]?<=|[:]?>=|[:|=])(\d+[kmg]?b?)$/i", $event->term, $matches)) {
            $cmp = ltrim($matches[1], ":") ?: "=";
            $val = parse_shorthand_int($matches[2]);
            $event->add_querylet(new Querylet("images.filesize $cmp :val{$event->id}", ["val{$event->id}" => $val]));
        } elseif (preg_match("/^id=([\d,]+)$/i", $event->term, $matches)) {
            $val = array_map(fn ($x) => int_escape($x), explode(",", $matches[1]));
            $set = implode(",", $val);
            $event->add_querylet(new Querylet("images.id IN ($set)"));
        } elseif (preg_match("/^id([:]?<|[:]?>|[:]?<=|[:]?>=|[:|=])(\d+)$/i", $event->term, $matches)) {
            $cmp = ltrim($matches[1], ":") ?: "=";
            $val = int_escape($matches[2]);
            $event->add_querylet(new Querylet("images.id $cmp :val{$event->id}", ["val{$event->id}" => $val]));
        } elseif (preg_match("/^(hash|md5)[=|:]([0-9a-fA-F]*)$/i", $event->term, $matches)) {
            $hash = strtolower($matches[2]);
            $event->add_querylet(new Querylet('images.hash = :hash', ["hash" => $hash]));
        } elseif (preg_match("/^(phash)[=|:]([0-9a-fA-F]*)$/i", $event->term, $matches)) {
            $phash = strtolower($matches[2]);
            $event->add_querylet(new Querylet('images.phash = :phash', ["phash" => $phash]));
        } elseif (preg_match("/^(filename|name)[=|:](.+)$/i", $event->term, $matches)) {
            $filename = strtolower($matches[2]);
            $event->add_querylet(new Querylet("lower(images.filename) LIKE :filename{$event->id}", ["filename{$event->id}" => "%$filename%"]));
        } elseif (preg_match("/^(source)[=|:](.*)$/i", $event->term, $matches)) {
            $source = strtolower($matches[2]);

            if (preg_match("/^(any|none)$/i", $source)) {
                $not = ($source == "any" ? "NOT" : "");
                $event->add_querylet(new Querylet("images.source IS $not NULL"));
            } else {
                $event->add_querylet(new Querylet('images.source LIKE :src', ["src" => "%$source%"]));
            }
        } elseif (preg_match("/^posted([:]?<|[:]?>|[:]?<=|[:]?>=|[:|=])([0-9-]*)$/i", $event->term, $matches)) {
            // TODO Make this able to search = without needing a time component.
            $cmp = ltrim($matches[1], ":") ?: "=";
            $val = $matches[2];
            $event->add_querylet(new Querylet("images.posted $cmp :posted{$event->id}", ["posted{$event->id}" => $val]));
        } elseif (preg_match("/^size([:]?<|[:]?>|[:]?<=|[:]?>=|[:|=])(\d+)x(\d+)$/i", $event->term, $matches)) {
            $cmp = ltrim($matches[1], ":") ?: "=";
            $args = ["width{$event->id}" => int_escape($matches[2]), "height{$event->id}" => int_escape($matches[3])];
            $event->add_querylet(new Querylet("width $cmp :width{$event->id} AND height $cmp :height{$event->id}", $args));
        } elseif (preg_match("/^width([:]?<|[:]?>|[:]?<=|[:]?>=|[:|=])(\d+)$/i", $event->term, $matches)) {
            $cmp = ltrim($matches[1], ":") ?: "=";
            $event->add_querylet(new Querylet("width $cmp :width{$event->id}", ["width{$event->id}" => int_escape($matches[2])]));
        } elseif (preg_match("/^height([:]?<|[:]?>|[:]?<=|[:]?>=|[:|=])(\d+)$/i", $event->term, $matches)) {
            $cmp = ltrim($matches[1], ":") ?: "=";
            $event->add_querylet(new Querylet("height $cmp :height{$event->id}", ["height{$event->id}" => int_escape($matches[2])]));
        } elseif (preg_match("/^length([:]?<|[:]?>|[:]?<=|[:]?>=|[:|=])(.+)$/i", $event->term, $matches)) {
            $value = parse_to_milliseconds($matches[2]);
            $cmp = ltrim($matches[1], ":") ?: "=";
            $event->add_querylet(new Querylet("length $cmp :length{$event->id}", ["length{$event->id}" => $value]));
        } elseif (preg_match("/^order[=|:](id|width|height|length|filesize|filename)[_]?(desc|asc)?$/i", $event->term, $matches)) {
            $ord = strtolower($matches[1]);
            $default_order_for_column = preg_match("/^(id|filename)$/", $matches[1]) ? "ASC" : "DESC";
            $sort = isset($matches[2]) ? strtoupper($matches[2]) : $default_order_for_column;
            $event->order = "images.$ord $sort";
        } elseif (preg_match("/^order[=|:]random[_]([0-9]{1,4})$/i", $event->term, $matches)) {
            // requires a seed to avoid duplicates
            // since the tag can't be changed during the parseevent, we instead generate the seed during submit using js
            $seed = (int)$matches[1];
            $event->order = $database->seeded_random($seed, "images.id");
        } elseif (preg_match("/^order[=|:]dailyshuffle$/i", $event->term, $matches)) {
            // will use today's date as seed, thus allowing for a dynamic randomized list without outside intervention.
            // This way the list will change every day, giving a more dynamic feel to the imageboard.
            // recommended to change homepage to "post/list/order:dailyshuffle/1"
            $seed = (int)date("Ymd");
            $event->order = $database->seeded_random($seed, "images.id");
        }

        // If we've reached this far, and nobody else has done anything with this term, then treat it as a tag
        if ($event->order === null && $event->img_conditions == [] && $event->tag_conditions == []) {
            $event->add_tag_condition(new TagCondition($event->term, $event->positive));
        }
    }

    public function get_priority(): int
    {
        // we want to turn a search term into a TagCondition only if nobody did anything else with that term
        return 95;
    }
}
