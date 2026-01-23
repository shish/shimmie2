<?php

declare(strict_types=1);

namespace Shimmie2;

use MicroHTML\HTMLElement;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\{InputArgument, InputInterface};
use Symfony\Component\Console\Output\OutputInterface;

final readonly class BulkAction
{
    public function __construct(
        public string $action,
        public string $button_text,
        public ?string $access_key = null,
        public string $confirmation_message = "",
        public ?HTMLElement $block = null,
        public int $position = 40,
        public ?string $permission = null,
    ) {
    }
}
final class BulkActionBlockBuildingEvent extends Event
{
    /**
     * @var array<BulkAction>
     */
    public array $actions = [];
    /** @var search-term-array */
    public array $search_terms = [];

    public function add_action(
        string $action,
        string $button_text,
        ?string $access_key = null,
        string $confirmation_message = "",
        ?HTMLElement $block = null,
        int $position = 40,
        ?string $permission = null,
    ): void {
        if (!empty($access_key)) {
            assert(strlen($access_key) === 1);
            foreach ($this->actions as $existing) {
                if ($existing->access_key === $access_key) {
                    throw new UserError("Access key $access_key is already in use");
                }
            }
        }

        $this->actions[] = new BulkAction(
            $action,
            $button_text,
            $access_key,
            $confirmation_message,
            $block,
            $position,
            $permission,
        );
    }
}

final class BulkActionEvent extends Event
{
    public bool $redirect = true;

    public function __construct(
        public string $action,
        public \Generator $items,
        public QueryArray $params
    ) {
        parent::__construct();
    }

    public function log_action(string $message): void
    {
        Log::debug(BulkActions::KEY, $message);
        Ctx::$page->flash($message);
    }
}

/** @extends Extension<BulkActionsTheme> */
final class BulkActions extends Extension
{
    public const KEY = "bulk_actions";

    #[EventListener]
    public function onPostListBuilding(PostListBuildingEvent $event): void
    {
        $babbe = new BulkActionBlockBuildingEvent();
        $babbe->search_terms = $event->search_terms;

        send_event($babbe);

        $actions = $babbe->actions;
        if (count($actions) === 0) {
            return;
        }

        $actions = array_filter($actions, fn ($a) => $a->permission === null || Ctx::$user->can($a->permission));
        usort($actions, $this->sort_blocks(...));
        $this->theme->display_selector($actions, SearchTerm::implode($event->search_terms));
    }

    #[EventListener]
    public function onCliGen(CliGenEvent $event): void
    {
        foreach (send_event(new BulkActionBlockBuildingEvent())->actions as $action) {
            $event->app->register("bulk:" . $action->action)
                ->addArgument('query', InputArgument::REQUIRED)
                ->setDescription($action->button_text)
                ->setCode(function (InputInterface $input, OutputInterface $output) use ($action): int {
                    $query = $input->getArgument('query');
                    $items = $this->yield_search_results($query);
                    Log::info("bulk_actions", "Performing {$action->action} on $query");
                    send_event(new BulkActionEvent($action->action, $items, new QueryArray([])));
                    return Command::SUCCESS;
                });

        }
    }

    #[EventListener]
    public function onBulkActionBlockBuilding(BulkActionBlockBuildingEvent $event): void
    {
        $event->add_action("delete", "(D)elete", "d", "Delete selected images?", $this->theme->render_ban_reason_input(), 10, permission: ImagePermission::DELETE_IMAGE);
        $event->add_action("tag", "Tag", "t", "", $this->theme->render_tag_input(), 10, permission: BulkActionsPermission::BULK_EDIT_IMAGE_TAG);
        $event->add_action("source", "Set (S)ource", "s", "", $this->theme->render_source_input(), 10, permission: BulkActionsPermission::BULK_EDIT_IMAGE_SOURCE);
    }

    #[EventListener]
    public function onBulkAction(BulkActionEvent $event): void
    {
        switch ($event->action) {
            case "delete":
                if (Ctx::$user->can(ImagePermission::DELETE_IMAGE)) {
                    $i = $this->delete_posts($event->items);
                    $event->log_action("Deleted $i[0] items, totaling ".human_filesize($i[1]));
                }
                break;
            case "tag":
                if (!isset($event->params['bulk_tags'])) {
                    return;
                }
                if (Ctx::$user->can(BulkActionsPermission::BULK_EDIT_IMAGE_TAG)) {
                    $tags = $event->params['bulk_tags'];
                    $replace = false;
                    if (isset($event->params['bulk_tags_replace']) &&  $event->params['bulk_tags_replace'] === "true") {
                        $replace = true;
                    }

                    $i = $this->tag_items($event->items, $tags, $replace);
                    $event->log_action("Tagged $i items");
                }
                break;
            case "source":
                if (!isset($event->params['bulk_source'])) {
                    return;
                }
                if (Ctx::$user->can(BulkActionsPermission::BULK_EDIT_IMAGE_SOURCE)) {
                    $source = $event->params['bulk_source'];
                    $i = $this->set_source($event->items, $source);
                    $event->log_action("Set source for $i items");
                }
                break;
        }
    }

    #[EventListener]
    public function onPageRequest(PageRequestEvent $event): void
    {
        if ($event->page_matches("bulk_action", method: "POST", permission: BulkActionsPermission::PERFORM_BULK_ACTIONS)) {
            $action = $event->POST->req('bulk_action');
            $items = null;
            if ($event->POST->get('bulk_selected_ids')) {
                $data = json_decode($event->POST->req('bulk_selected_ids'));
                if (!is_array($data) || count($data) === 0) {
                    throw new InvalidInput("No ids specified in bulk_selected_ids");
                }
                $items = $this->yield_items($data);
            } elseif ($event->POST->get('bulk_query')) {
                $query = $event->POST->req('bulk_query');
                $items = $this->yield_search_results($query);
            } else {
                throw new InvalidInput("No ids selected and no query present, cannot perform bulk operation on entire collection");
            }

            Ctx::$event_bus->set_timeout(null);
            $bae = send_event(new BulkActionEvent($action, $items, $event->POST));

            if ($bae->redirect) {
                Ctx::$page->set_redirect(Url::referer_or());
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
            if ($image !== null) {
                yield $image;
            }
        }
    }

    /**
     * @return \Generator<Image>
     */
    private function yield_search_results(string $query): \Generator
    {
        $terms = SearchTerm::explode($query);
        return Search::find_images_iterable(0, null, $terms);
    }

    /**
     * @param BulkAction $a
     * @param BulkAction $b
     */
    private function sort_blocks(BulkAction $a, BulkAction $b): int
    {
        return $a->position - $b->position;
    }

    /**
     * @param iterable<Image> $posts
     * @return array{0: int, 1: int}
     */
    private function delete_posts(iterable $posts): array
    {
        $total = 0;
        $size = 0;
        foreach ($posts as $post) {
            try {
                if (ImageBanInfo::is_enabled() && isset($_POST['bulk_ban_reason'])) {
                    $reason = $_POST['bulk_ban_reason'];
                    if ($reason) {
                        send_event(new AddImageHashBanEvent($post->hash, $reason));
                    }
                }
                send_event(new ImageDeletionEvent($post));
                $total++;
                $size += $post->filesize;
            } catch (\Exception $e) {
                Ctx::$page->flash("Error while removing {$post->id}: " . $e->getMessage());
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
                $new_tag = substr($new_tag, 1);
                assert($new_tag !== '');
                $neg_tag_array[] = $new_tag;
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
                $img_tags = array_map(strtolower(...), $image->get_tag_array());

                if (!empty($neg_tag_array)) {
                    $neg_tag_array = array_map(strtolower(...), $neg_tag_array);
                    $img_tags = array_merge($pos_tag_array, $img_tags);
                    $img_tags = array_diff($img_tags, $neg_tag_array);
                } else {
                    $img_tags = array_merge($tags, $img_tags);
                }
                $img_tags = array_filter($img_tags, fn ($tag) => !empty($tag));
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
        $total = 0;
        foreach ($items as $image) {
            try {
                send_event(new SourceSetEvent($image, $source));
                $total++;
            } catch (\Exception $e) {
                Ctx::$page->flash("Error while setting source for {$image->id}: " . $e->getMessage());
            }
        }
        return $total;
    }
}
