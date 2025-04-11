<?php

declare(strict_types=1);

namespace Shimmie2;

use function MicroHTML\{A, B, BR, DIV, INPUT, LABEL, LI, P, TABLE, TD, TH, TR, UL, emptyHTML};

use MicroHTML\HTMLElement;

/**
 * @phpstan-type HistoryEntry array{image_id:int,id:int,source:string,date_set:string,user_id:string,user_ip:string,name:string}
 */
class SourceHistoryTheme extends Themelet
{
    /** @var HTMLElement[] */
    private array $messages = [];

    /**
     * @param HistoryEntry[] $history
     */
    public function display_history_page(int $image_id, array $history): void
    {
        Ctx::$page->set_title('Post '. $image_id .' Source History');
        Ctx::$page->set_heading('Source History: '.$image_id);
        $this->display_navigation();
        Ctx::$page->add_block(new Block("Source History", $this->history_list($history, true), "main", 10));
    }

    /**
     * @param HistoryEntry[] $history
     */
    public function display_global_page(array $history, int $page_number): void
    {
        Ctx::$page->set_title("Global Source History");
        $this->display_navigation([
            ($page_number <= 1) ? null : make_link('source_history/all/'.($page_number - 1)),
            make_link(),
            make_link('source_history/all/'.($page_number + 1))
        ]);
        Ctx::$page->add_block(new Block("Source History", $this->history_list($history, false), "main", 10));
    }

    /**
     * Add a section to the admin page.
     */
    public function display_admin_block(string $validation_msg = ''): void
    {
        $html = emptyHTML(
            "Revert source changes by a specific IP address or username, optionally limited to recent changes.",
            empty($validation_msg) ? null : emptyHTML(BR(), B($validation_msg)),
            BR(),
            BR(),
            SHM_SIMPLE_FORM(
                make_link("source_history/bulk_revert"),
                TABLE(
                    ["class" => "form"],
                    TR(
                        TH("Username"),
                        TD(INPUT(["type" => "text", "name" => "revert_name", "size" => "15"]))
                    ),
                    TR(
                        TH("IP Address"),
                        TD(INPUT(["type" => "text", "name" => "revert_ip", "size" => "15"]))
                    ),
                    TR(
                        TH("Since"),
                        TD(INPUT(["type" => "date", "name" => "revert_date", "size" => "15"]))
                    ),
                    TR(
                        TD(["colspan" => 2], SHM_SUBMIT("Revert"))
                    )
                )
            )
        );
        Ctx::$page->add_block(new Block("Mass Source Revert", $html));
    }

    /*
     * Show a standard page for results to be put into
     */
    public function display_revert_ip_results(): void
    {
        Ctx::$page->add_block(new Block("Bulk Revert Results", emptyHTML(...$this->messages)));
    }

    public function add_status(string $title, string $body): void
    {
        $this->messages[] = P(B($title), BR(), $body);
    }

    /**
     * @param HistoryEntry[] $history
     */
    protected function history_list(array $history, bool $select_2nd): HTMLElement
    {
        $history_list = [];
        foreach ($history as $n => $fields) {
            $history_list[] = $this->history_entry($fields, $select_2nd && $n === 1);
        }

        return DIV(
            ["style" => "text-align: left"],
            SHM_FORM(
                action: make_link("source_history/revert"),
                children: [
                    UL(
                        ["style" => "list-style-type:none;"],
                        ...$history_list
                    ),
                    SHM_SUBMIT("Revert To")
                ]
            )
        );
    }

    /**
     * @param HistoryEntry $fields
     */
    protected function history_entry(array $fields, bool $selected): HTMLElement
    {
        $image_id = $fields['image_id'];
        $current_id = $fields['id'];
        $current_source = $fields['source'];
        $name = $fields['name'];
        $date_set = SHM_DATE($fields['date_set']);
        $ip = Ctx::$user->can(IPBanPermission::VIEW_IP) ?
            emptyHTML(" ", SHM_IP($fields['user_ip'], "Sourcing >>$image_id as '$current_source'"))
            : null;
        $setter = A(["href" => make_link("user/" . url_escape($name))], $name);

        return LI(
            INPUT(["type" => "radio", "name" => "revert", "id" => "$current_id", "value" => "$current_id", "checked" => $selected]),
            A(["href" => make_link("post/view/$image_id")], $image_id),
            ": ",
            LABEL(
                ["for" => "$current_id"],
                $current_source,
                " - ",
                $setter,
                $ip,
                " - ",
                $date_set
            )
        );
    }
}
