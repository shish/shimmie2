<?php declare(strict_types=1);

class BulkActionBlockBuildingEvent extends Event
{
    /** @var array  */
    public $actions = [];

    public $search_terms = [];

    public function add_action(String $action, string $button_text, string $access_key = null, String $confirmation_message = "", String $block = "", int $position = 40)
    {
        if ($block == null) {
            $block = "";
        }

        if (!empty($access_key)) {
            assert(strlen($access_key)==1);
            foreach ($this->actions as $existing) {
                if ($existing["access_key"]==$access_key) {
                    throw new SCoreException("Access key $access_key is already in use");
                }
            }
        }

        $this->actions[] =[
                "block" => $block,
                "access_key" => $access_key,
                "confirmation_message" => $confirmation_message,
                "action" => $action,
                "button_text" => $button_text,
                "position" => $position
            ];
    }
}

class BulkActionEvent extends Event
{
    /** @var string  */
    public $action;
    /** @var array  */
    public $items;
    /** @var PageRequestEvent  */
    public $page_request;
    /** @var string  */
    public $download_file;
    public $download_delete = false;

    public function __construct(String $action, Generator $items)
    {
        parent::__construct();
        $this->action = $action;
        $this->items = $items;
    }
}

class BulkActions extends Extension
{
    /** @var BulkActionsTheme */
    protected $theme;

    public function onPostListBuilding(PostListBuildingEvent $event)
    {
        global $page, $user;

        if ($user->is_logged_in()) {
            $babbe = new BulkActionBlockBuildingEvent();
            $babbe->search_terms = $event->search_terms;

            send_event($babbe);

            if (sizeof($babbe->actions) == 0) {
                return;
            }

            usort($babbe->actions, [$this, "sort_blocks"]);

            $this->theme->display_selector($page, $babbe->actions, Tag::implode($event->search_terms));
        }
    }

    public function onBulkActionBlockBuilding(BulkActionBlockBuildingEvent $event)
    {
        global $user;

        if ($user->can(Permissions::DELETE_IMAGE)) {
            $event->add_action("bulk_delete", "(D)elete", "d", "Delete selected images?", $this->theme->render_ban_reason_input(), 10);
        }

        if ($user->can(Permissions::BULK_EDIT_IMAGE_TAG)) {
            $event->add_action(
                "bulk_tag",
                "Tag",
                "t",
                "",
                $this->theme->render_tag_input(),
                10
            );
        }

        if ($user->can(Permissions::BULK_EDIT_IMAGE_SOURCE)) {
            $event->add_action("bulk_source", "Set (S)ource", "s", "", $this->theme->render_source_input(), 10);
        }
    }

    public function onCommand(CommandEvent $event)
    {
        if ($event->cmd == "help") {
            print "\tbulk-action <action> <query>\n";
            print "\t\tperform an action on all query results\n\n";
        }
        if ($event->cmd == "bulk-action") {
            if (count($event->args) < 2) {
                return;
            }
            $action = $event->args[0];
            $query = $event->args[1];
            $items = $this->yield_search_results($query);
            log_info("bulk_actions", "Performing $action on {$event->args[1]}");
            send_event(new BulkActionEvent($event->args[0], $items));
        }
    }

    public function onBulkAction(BulkActionEvent $event)
    {
        global $page, $user;

        switch ($event->action) {
            case "bulk_delete":
                if ($user->can(Permissions::DELETE_IMAGE)) {
                    $i = $this->delete_items($event->items);
                    $page->flash("Deleted $i items");
                }
                break;
            case "bulk_tag":
                if (!isset($_POST['bulk_tags'])) {
                    return;
                }
                if ($user->can(Permissions::BULK_EDIT_IMAGE_TAG)) {
                    $tags = $_POST['bulk_tags'];
                    $replace = false;
                    if (isset($_POST['bulk_tags_replace']) &&  $_POST['bulk_tags_replace'] == "true") {
                        $replace = true;
                    }

                    $i= $this->tag_items($event->items, $tags, $replace);
                    $page->flash("Tagged $i items");
                }
                break;
            case "bulk_source":
                if (!isset($_POST['bulk_source'])) {
                    return;
                }
                if ($user->can(Permissions::BULK_EDIT_IMAGE_SOURCE)) {
                    $source = $_POST['bulk_source'];
                    $i = $this->set_source($event->items, $source);
                    $page->flash("Set source for $i items");
                }
                break;
        }
    }

    public function onPageRequest(PageRequestEvent $event)
    {
        global $page, $user;
        if ($event->page_matches("bulk_action") && $user->can(Permissions::PERFORM_BULK_ACTIONS)) {
            if (!isset($_POST['bulk_action'])) {
                return;
            }

            $action = $_POST['bulk_action'];

            $items = null;
            if (isset($_POST['bulk_selected_ids']) && $_POST['bulk_selected_ids'] != "") {
                $data = json_decode($_POST['bulk_selected_ids']);
                if (is_array($data)&&!empty($data)) {
                    $items = $this->yield_items($data);
                }
            } elseif (isset($_POST['bulk_query']) && $_POST['bulk_query'] != "") {
                $query = $_POST['bulk_query'];
                if ($query != null && $query != "") {
                    $items = $this->yield_search_results($query);
                }
            }

            $newEvent = null;
            if (is_iterable($items)) {
                $newEvent = new BulkActionEvent($action, $event, $items);
                send_event($newEvent);
            }

            if($newEvent!=null && !empty($newEvent->download_file)) {
                $page->set_mode(PageMode::FILE);
                $page->set_filename($newEvent->download_file, "download");
                $page->set_file($newEvent->download_file, $newEvent->download_delete);
            } else {
                $page->set_mode(PageMode::REDIRECT);
                if (!isset($_SERVER['HTTP_REFERER'])) {
                    $_SERVER['HTTP_REFERER'] = make_link();
                }
                $page->set_redirect($_SERVER['HTTP_REFERER']);
            }
        }
    }

    private function yield_items(array $data): Generator
    {
        foreach ($data as $id) {
            if (is_numeric($id)) {
                $image = Image::by_id($id);
                if ($image!=null) {
                    yield $image;
                }
            }
        }
    }

    private function yield_search_results(string $query): Generator
    {
        $tags = Tag::explode($query);
        return Image::find_images_iterable(0, null, $tags);
    }

    private function sort_blocks($a, $b)
    {
        return $a["position"] - $b["position"];
    }

    private function delete_items(iterable $items): int
    {
        global $page;
        $total = 0;
        foreach ($items as $image) {
            try {
                if (class_exists("ImageBan") && isset($_POST['bulk_ban_reason'])) {
                    $reason = $_POST['bulk_ban_reason'];
                    if ($reason) {
                        send_event(new AddImageHashBanEvent($image->hash, $reason));
                    }
                }
                send_event(new ImageDeletionEvent($image));
                $total++;
            } catch (Exception $e) {
                $page->flash("Error while removing {$image->id}: " . $e->getMessage());
            }
        }
        return $total;
    }

    private function tag_items(iterable $items, string $tags, bool $replace): int
    {
        $tags = Tag::explode($tags);

        $pos_tag_array = [];
        $neg_tag_array = [];
        foreach ($tags as $new_tag) {
            if (strpos($new_tag, '-') === 0) {
                $neg_tag_array[] = substr($new_tag, 1);
            } else {
                $pos_tag_array[] = $new_tag;
            }
        }

        $total = 0;
        if ($replace) {
            foreach ($items as $image) {
                send_event(new TagSetEvent($image, $tags));
                $total++;
            }
        } else {
            foreach ($items as $image) {
                $img_tags = array_map("strtolower", $image->get_tag_array());

                if (!empty($neg_tag_array)) {
                    $neg_tag_array = array_map("strtolower", $neg_tag_array);

                    $img_tags = array_merge($pos_tag_array, $img_tags);
                    $img_tags = array_diff($img_tags, $neg_tag_array);
                } else {
                    $img_tags = array_merge($tags, $img_tags);
                }
                send_event(new TagSetEvent($image, $img_tags));
                $total++;
            }
        }

        return $total;
    }

    private function set_source(iterable $items, String $source): int
    {
        global $page;
        $total = 0;
        foreach ($items as $image) {
            try {
                send_event(new SourceSetEvent($image, $source));
                $total++;
            } catch (Exception $e) {
                $page->flash("Error while setting source for {$image->id}: " . $e->getMessage());
            }
        }
        return $total;
    }
}
