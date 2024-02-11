<?php

declare(strict_types=1);

namespace Shimmie2;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\{InputInterface,InputArgument};
use Symfony\Component\Console\Output\OutputInterface;

class BulkActionBlockBuildingEvent extends Event
{
    /**
     * @var array<array{block:string,access_key:?string,confirmation_message:string,action:string,button_text:string,position:int}>
     */
    public array $actions = [];
    /** @var string[] */
    public array $search_terms = [];

    public function add_action(string $action, string $button_text, string $access_key = null, string $confirmation_message = "", string $block = "", int $position = 40): void
    {
        if (!empty($access_key)) {
            assert(strlen($access_key) == 1);
            foreach ($this->actions as $existing) {
                if ($existing["access_key"] == $access_key) {
                    throw new UserError("Access key $access_key is already in use");
                }
            }
        }

        $this->actions[] = [
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
    public string $action;
    public \Generator $items;
    /** @var array<string, mixed> */
    public array $params;
    public bool $redirect = true;

    /**
     * @param array<string, mixed> $params
     */
    public function __construct(string $action, \Generator $items, array $params)
    {
        parent::__construct();
        $this->action = $action;
        $this->items = $items;
        $this->params = $params;
    }
}

class BulkActions extends Extension
{
    /** @var BulkActionsTheme */
    protected Themelet $theme;

    public function onPostListBuilding(PostListBuildingEvent $event): void
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

    public function onBulkActionBlockBuilding(BulkActionBlockBuildingEvent $event): void
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

    public function onCliGen(CliGenEvent $event): void
    {
        $event->app->register('bulk-action')
            ->addArgument('action', InputArgument::REQUIRED)
            ->addArgument('query', InputArgument::REQUIRED)
            ->setDescription('Perform a bulk action on a given query')
            ->setCode(function (InputInterface $input, OutputInterface $output): int {
                $action = $input->getArgument('action');
                $query = $input->getArgument('query');
                $items = $this->yield_search_results($query);
                log_info("bulk_actions", "Performing $action on $query");
                send_event(new BulkActionEvent($action, $items, []));
                return Command::SUCCESS;
            });
    }

    public function onBulkAction(BulkActionEvent $event): void
    {
        global $page, $user;

        switch ($event->action) {
            case "bulk_delete":
                if ($user->can(Permissions::DELETE_IMAGE)) {
                    $i = $this->delete_posts($event->items);
                    $page->flash("Deleted $i[0] items, totaling ".human_filesize($i[1]));
                }
                break;
            case "bulk_tag":
                if (!isset($event->params['bulk_tags'])) {
                    return;
                }
                if ($user->can(Permissions::BULK_EDIT_IMAGE_TAG)) {
                    $tags = $event->params['bulk_tags'];
                    $replace = false;
                    if (isset($event->params['bulk_tags_replace']) &&  $event->params['bulk_tags_replace'] == "true") {
                        $replace = true;
                    }

                    $i = $this->tag_items($event->items, $tags, $replace);
                    $page->flash("Tagged $i items");
                }
                break;
            case "bulk_source":
                if (!isset($event->params['bulk_source'])) {
                    return;
                }
                if ($user->can(Permissions::BULK_EDIT_IMAGE_SOURCE)) {
                    $source = $event->params['bulk_source'];
                    $i = $this->set_source($event->items, $source);
                    $page->flash("Set source for $i items");
                }
                break;
        }
    }

    public function onPageRequest(PageRequestEvent $event): void
    {
        global $page, $user;
        if ($event->page_matches("bulk_action", method: "POST", permission: Permissions::PERFORM_BULK_ACTIONS)) {
            $action = $event->req_POST('bulk_action');
            $items = null;
            if ($event->get_POST('bulk_selected_ids')) {
                $data = json_decode($event->req_POST('bulk_selected_ids'));
                if (!is_array($data) || empty($data)) {
                    throw new InvalidInput("No ids specified in bulk_selected_ids");
                }
                $items = $this->yield_items($data);
            } elseif ($event->get_POST('bulk_query')) {
                $query = $event->req_POST('bulk_query');
                $items = $this->yield_search_results($query);
            } else {
                throw new InvalidInput("No ids selected and no query present, cannot perform bulk operation on entire collection");
            }

            shm_set_timeout(null);
            $bae = send_event(new BulkActionEvent($action, $items, $event->POST));

            if ($bae->redirect) {
                $page->set_mode(PageMode::REDIRECT);
                $page->set_redirect(referer_or(make_link()));
            }
        }
    }

    /**
     * @param int[] $data
     * @return \Generator<Image>
     */
    private function yield_items(array $data): \Generator
    {
        foreach ($data as $id) {
            $image = Image::by_id($id);
            if ($image != null) {
                yield $image;
            }
        }
    }

    /**
     * @return \Generator<Image>
     */
    private function yield_search_results(string $query): \Generator
    {
        $tags = Tag::explode($query);
        return Search::find_images_iterable(0, null, $tags);
    }

    /**
     * @param array{position: int} $a
     * @param array{position: int} $b
     */
    private function sort_blocks(array $a, array $b): int
    {
        return $a["position"] - $b["position"];
    }

    /**
     * @param iterable<Image> $posts
     * @return array{0: int, 1: int}
     */
    private function delete_posts(iterable $posts): array
    {
        global $page;
        $total = 0;
        $size = 0;
        foreach ($posts as $post) {
            try {
                if (Extension::is_enabled(ImageBanInfo::KEY) && isset($_POST['bulk_ban_reason'])) {
                    $reason = $_POST['bulk_ban_reason'];
                    if ($reason) {
                        send_event(new AddImageHashBanEvent($post->hash, $reason));
                    }
                }
                send_event(new ImageDeletionEvent($post));
                $total++;
                $size += $post->filesize;
            } catch (\Exception $e) {
                $page->flash("Error while removing {$post->id}: " . $e->getMessage());
            }
        }
        return [$total, $size];
    }

    /**
     * @param iterable<Image> $items
     */
    private function tag_items(iterable $items, string $tags, bool $replace): int
    {
        $tags = Tag::explode($tags);

        $pos_tag_array = [];
        $neg_tag_array = [];
        foreach ($tags as $new_tag) {
            if (str_starts_with($new_tag, '-')) {
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

    /**
     * @param iterable<Image> $items
     */
    private function set_source(iterable $items, string $source): int
    {
        global $page;
        $total = 0;
        foreach ($items as $image) {
            try {
                send_event(new SourceSetEvent($image, $source));
                $total++;
            } catch (\Exception $e) {
                $page->flash("Error while setting source for {$image->id}: " . $e->getMessage());
            }
        }
        return $total;
    }
}
