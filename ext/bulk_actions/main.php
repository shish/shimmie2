<?php
/*
 * Name: Bulk Actions
 * Author: Matthew Barbour
 * License: WTFPL
 * Description: Provides query and selection-based bulk action support
 * Documentation: Provides bulk action section in list view. Allows performing actions against a set of images based on query or manual selection.
 * Based on Mass Tagger by Christian Walde <walde.christian@googlemail.com>, contributions by Shish and Agasa.
 */


class BulkActionBlockBuildingEvent extends Event
{
    /** @var array  */
    public $actions = array();

    public function add_action(String $action, String $confirmation_message = "", String $block = "", int $position = 40)
    {
        if ($block == null)
            $block = "";

        array_push(
            $this->actions,
            array(
                "block" => $block,
                "confirmation_message" => $confirmation_message,
                "action" => $action,
                "position" => $position
            )
        );
    }
}

class BulkActionEvent extends Event
{
    public $action;
    public $items;
    public $page_request;

    function __construct(String $action, PageRequestEvent $pageRequestEvent, array $items)
    {
        $this->action = $action;
        $this->page_request = $pageRequestEvent;
        $this->items = $items;
    }
}

class BulkActions extends Extension
{
    public function onPostListBuilding(PostListBuildingEvent $event)
    {
        global $config, $page, $user;

        $this->theme->display_selector($page, $event, $config, Tag::implode($event->search_terms));
    }

    public function onBulkActionBlockBuilding(BulkActionBlockBuildingEvent $event)
    {
        global $user;

        if ($user->can("delete_image")) {
            $event->add_action("Delete", "Delete selected images?", "", 10);
        }

        if ($user->can("bulk_edit_image_tag")) {
            $event->add_action("Tag", "", $this->theme->render_tag_input(), 10);
        }

        if ($user->can("bulk_edit_image_source")) {
            $event->add_action("Set Source", "", $this->theme->render_source_input(), 10);
        }
    }

    public function onBulkAction(BulkActionEvent $event)
    {
        global $user;

        switch ($event->action) {
            case "Delete":
                if ($user->can("delete_image")) {
                    $this->delete_items($event->items);
                }
                break;
            case "Tag":
                if (!isset($_POST['bulk_tags'])) {
                    return;
                }
                if ($user->can("bulk_edit_image_tag")) {
                    $tags = $_POST['bulk_tags'];
                    $replace = false;
                    if (isset($_POST['bulk_tags_replace']) &&  $_POST['bulk_tags_replace'] == "true") {
                        $replace = true;
                    }

                    $this->tag_items($event->items, $tags, $replace);
                }
                break;
            case "Set Source":
                if (!isset($_POST['bulk_source'])) {
                    return;
                }
                if ($user->can("bulk_edit_image_source")) {
                    $source = $_POST['bulk_source'];
                    $this->set_source($event->items, $source);
                }
                break;
        }
    }

    public function onPageRequest(PageRequestEvent $event)
    {
        global $page, $user;
        if ($event->page_matches("bulk_action") && $user->is_admin()) {
            if (!isset($_POST['bulk_action'])) {
                return;
            }

            $action = $_POST['bulk_action'];

            $items = [];
            if (isset($_POST['bulk_selected_ids']) && $_POST['bulk_selected_ids'] != "") {
                $data = json_decode($_POST['bulk_selected_ids']);
                if (is_array($data)) {
                    foreach ($data as $id) {
                        if (is_numeric($id)) {
                            $item = Image::by_id(int_escape($id));
                            array_push($items, $item);
                        }
                    }
                }
                if (sizeof($items) > 0) {
                    reset($items); // rewind to first element in array.
                    $newEvent = new BulkActionEvent($action, $event, $items);
                    send_event($newEvent);
                }
            } else if (isset($_POST['bulk_query']) && $_POST['bulk_query'] != "") {
                $query = $_POST['bulk_query'];
                if ($query != null && $query != "") {
                    $n = 0;
                    while (true) {
                        $items = Image::find_images($n, 100, Tag::explode($query));
                        if (count($items) == 0) {
                            break;
                        }

                        reset($items); // rewind to first element in array.
                        $newEvent = new BulkActionEvent($action, $event, $items);
                        send_event($newEvent);

                        $n += 100;
                    }
                }
            }



            $page->set_mode("redirect");
            if (!isset($_SERVER['HTTP_REFERER'])) {
                $_SERVER['HTTP_REFERER'] = make_link();
            }
            $page->set_redirect($_SERVER['HTTP_REFERER']);
        }
    }

    private function delete_items(array $items)
    {
        $total = 0;
        foreach ($items as $item) {
            try {
                send_event(new ImageDeletionEvent($item));
                $total++;
            } catch (Exception $e) {
                flash_message("Error while removing $item->id: " . $e->getMessage(), "error");
            }
        }

        flash_message("Deleted $total items");
    }

    private function tag_items(array $items, string $tags, bool $replace)
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
            foreach ($items as $item) {
                send_event(new TagSetEvent($item, $tags));
                $total++;
            }
        } else {
            foreach ($items as $item) {
                $img_tags = [];
                if (!empty($neg_tag_array)) {
                    $img_tags = array_merge($pos_tag_array, $item->get_tag_array());
                    $img_tags = array_diff($img_tags, $neg_tag_array);
                } else {
                    $img_tags = array_merge($tags, $item->get_tag_array());
                }
                send_event(new TagSetEvent($item, $img_tags));
                $total++;
            }
        }

        flash_message("Tagged $total items");
    }

    private function set_source(array $items, String $source)
    {
        $total = 0;
        foreach ($items as $item) {
            try {
                send_event(new SourceSetEvent($item, $source));
                $total++;
            } catch (Exception $e) {
                flash_message("Error while setting source for $item->id: " . $e->getMessage(), "error");
            }
        }

        flash_message("Set source for $total items");
    }
}
