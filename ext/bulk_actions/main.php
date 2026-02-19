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
     * @return \Generator<Post>
     */
    private function yield_items(array $data): \Generator
    {
        foreach ($data as $id) {
            $image = Post::by_id($id);
            if ($image !== null) {
                yield $image;
            }
        }
    }

    /**
     * @return \Generator<Post>
     */
    private function yield_search_results(string $query): \Generator
    {
        $terms = SearchTerm::explode($query);
        return Search::find_posts_iterable(0, null, $terms);
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
     * @param iterable<Post> $posts
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
                send_event(new PostDeletionEvent($post));
                $total++;
                $size += $post->filesize;
            } catch (\Exception $e) {
                Ctx::$page->flash("Error while removing {$post->id}: " . $e->getMessage());
            }
        }
        return [$total, $size];
    }


}
