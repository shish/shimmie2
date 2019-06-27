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
    public $actions = [];

    public $search_terms = [];

    public function add_action(String $action, string $button_text, string $access_key = null, String $confirmation_message = "", String $block = "", int $position = 40)
    {
        if ($block == null) {
            $block = "";
        }

        if(!empty($access_key)) {
            assert(strlen($access_key)==1);
            foreach ($this->actions as $existing) {
                if($existing["access_key"]==$access_key) {
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

    public function __construct(String $action, PageRequestEvent $pageRequestEvent, array $items)
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

        if ($user->can("delete_image")) {
            $event->add_action("bulk_delete", "(D)elete", "d", "Delete selected images?", "", 10);
        }

        if ($user->can("bulk_edit_image_tag")) {

            $event->add_action(
                "bulk_tag",
                "Tag",
                "t",
                "",
                $this->theme->render_tag_input(),
                10);
        }

        if ($user->can("bulk_edit_image_source")) {
            $event->add_action("bulk_source", "Set (S)ource", "s","", $this->theme->render_source_input(), 10);
        }
    }

    public function onBulkAction(BulkActionEvent $event)
    {
        global $user;

        switch ($event->action) {
            case "bulk_delete":
                if ($user->can("delete_image")) {
                    $i = $this->delete_items($event->items);
                    flash_message("Deleted $i items");
                }
                break;
            case "bulk_tag":
                if (!isset($_POST['bulk_tags'])) {
                    return;
                }
                if ($user->can("bulk_edit_image_tag")) {
                    $tags = $_POST['bulk_tags'];
                    $replace = false;
                    if (isset($_POST['bulk_tags_replace']) &&  $_POST['bulk_tags_replace'] == "true") {
                        $replace = true;
                    }

                    $i= $this->tag_items($event->items, $tags, $replace);
                    flash_message("Tagged $i items");
                }
                break;
            case "bulk_source":
                if (!isset($_POST['bulk_source'])) {
                    return;
                }
                if ($user->can("bulk_edit_image_source")) {
                    $source = $_POST['bulk_source'];
                    $i = $this->set_source($event->items, $source);
                    flash_message("Set source for $i items");
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
                            array_push($items, int_escape($id));
                        }
                    }
                }
            } elseif (isset($_POST['bulk_query']) && $_POST['bulk_query'] != "") {
                $query = $_POST['bulk_query'];
                if ($query != null && $query != "") {
                    $n = 0;
                    $tags = Tag::explode($query);
                    while (true) {
                        $results = Image::find_image_ids($n, 100, $tags);
                        if (count($results) == 0) {
                            break;
                        }

                        reset($results); // rewind to first element in array.
                        $items = array_merge($items, $results);
                        $n += count($results);
                    }
                }
            }

            if (sizeof($items) > 0) {
                reset($items); // rewind to first element in array.
                $newEvent = new BulkActionEvent($action, $event, $items);
                send_event($newEvent);
            }


            $page->set_mode(PageMode::REDIRECT);
            if (!isset($_SERVER['HTTP_REFERER'])) {
                $_SERVER['HTTP_REFERER'] = make_link();
            }
            $page->set_redirect($_SERVER['HTTP_REFERER']);
        }
    }

    private function sort_blocks($a, $b)
    {
        return $a["position"] - $b["position"];
    }
    
    private function delete_items(array $items): int
    {
        $total = 0;
        foreach ($items as $id) {
            try {
                $image = Image::by_id($id);
                if ($image==null) {
                    continue;
                }

                send_event(new ImageDeletionEvent($image));
                $total++;
            } catch (Exception $e) {
                flash_message("Error while removing $id: " . $e->getMessage(), "error");
            }
        }
        return $total;
    }

    private function tag_items(array $items, string $tags, bool $replace): int
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
            foreach ($items as $id) {
                $image = Image::by_id($id);
                if ($image==null) {
                    continue;
                }

                send_event(new TagSetEvent($image, $tags));
                $total++;
            }
        } else {
            foreach ($items as $id) {
                $image = Image::by_id($id);
                if ($image==null) {
                    continue;
                }

                $img_tags = [];
                if (!empty($neg_tag_array)) {
                    $img_tags = array_merge($pos_tag_array, $image->get_tag_array());
                    $img_tags = array_diff($img_tags, $neg_tag_array);
                } else {
                    $img_tags = array_merge($tags, $image->get_tag_array());
                }
                send_event(new TagSetEvent($image, $img_tags));
                $total++;
            }
        }

        return $total;
    }

    private function set_source(array $items, String $source): int
    {
        $total = 0;
        foreach ($items as $id) {
            try {
                $image = Image::by_id($id);
                if ($image==null) {
                    continue;
                }

                send_event(new SourceSetEvent($image, $source));
                $total++;
            } catch (Exception $e) {
                flash_message("Error while setting source for $id: " . $e->getMessage(), "error");
            }
        }

        return $total;
    }
}
