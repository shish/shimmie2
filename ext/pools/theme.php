<?php

declare(strict_types=1);

namespace Shimmie2;

use MicroHTML\HTMLElement;

use function MicroHTML\emptyHTML;
use function MicroHTML\rawHTML;
use function MicroHTML\{A,BR,DIV,INPUT,P,SCRIPT,SPAN,TABLE,TBODY,TD,TEXTAREA,TH,THEAD,TR};

/**
 * @phpstan-type PoolHistory array{id:int,pool_id:int,title:string,user_name:string,action:string,images:string,count:int,date:string}
 */
class PoolsTheme extends Themelet
{
    /**
     * Adds a block to the panel with information on the pool(s) the image is in.
     * $navIDs = Multidimensional array containing pool id, info & nav IDs.
     *
     * @param array<int,array{info:Pool,nav:array{prev:?int,next:?int}|null}> $navIDs
     */
    public function pool_info(array $navIDs): void
    {
        global $page;

        //TODO: Use a 3 column table?
        $linksPools = emptyHTML();
        foreach ($navIDs as $poolID => $poolInfo) {
            $div = DIV(SHM_A("pool/view/" . $poolID, $poolInfo["info"]->title));

            if(!empty($poolInfo["nav"])) {
                if (!empty($poolInfo["nav"]["prev"])) {
                    $div->appendChild(SHM_A("post/view/" . $poolInfo["nav"]["prev"], "Prev", class: "pools_prev_img"));
                }
                if (!empty($poolInfo["nav"]["next"])) {
                    $div->appendChild(SHM_A("post/view/" . $poolInfo["nav"]["next"], "Next", class: "pools_next_img"));
                }
            }

            $linksPools->appendChild($div);
        }

        if (!empty($navIDs)) {
            $page->add_block(new Block("Pools", $linksPools, "left"));
        }
    }

    /**
     * HERE WE SHOWS THE LIST OF POOLS.
     *
     * @param Pool[] $pools
     */
    public function list_pools(Page $page, array $pools, string $search, int $pageNumber, int $totalPages): void
    {
        // Build up the list of pools.
        $pool_rows = [];
        foreach ($pools as $pool) {
            $pool_link = SHM_A("pool/view/" . $pool->id, $pool->title);
            $user_link = SHM_A("user/" . url_escape($pool->user_name), $pool->user_name);

            $pool_rows[] = TR(
                TD(["class" => "left"], $pool_link),
                TD($user_link),
                TD($pool->posts),
                TD($pool->public ? "Yes" : "No")
            );
        }

        $table = TABLE(
            ["id" => "poolsList", "class" => "zebra"],
            THEAD(TR(TH("Name"), TH("Creator"), TH("Posts"), TH("Public"))),
            TBODY(...$pool_rows)
        );

        $order_arr = ['created' => 'Recently created', 'updated' => 'Last updated', 'name' => 'Name', 'count' => 'Post Count'];
        $order_selected = $page->get_cookie('ui-order-pool');
        $order_sel = SHM_SELECT("order_pool", $order_arr, selected_options: [$order_selected], attrs: ["id" => "order_pool"]);

        $this->display_top(null, "Pools");
        $page->add_block(new Block("Order By", $order_sel, "left", 15));

        $page->add_block(new Block("Pools", $table, position: 10));

        if ($search != "" and !str_starts_with($search, '/')) {
            $search = '/'.$search;
        }
        $this->display_paginator($page, "pool/list".$search, null, $pageNumber, $totalPages);
    }

    /*
     * HERE WE DISPLAY THE NEW POOL COMPOSER
     */
    public function new_pool_composer(Page $page): void
    {
        $form = SHM_SIMPLE_FORM("pool/create", TABLE(
            TR(TD("Title:"), TD(INPUT(["type" => "text", "name" => "title"]))),
            TR(TD("Public?:"), TD(INPUT(["type" => "checkbox", "name" => "public", "value" => "Y", "checked" => "checked"]))),
            TR(TD("Description:"), TD(TEXTAREA(["name" => "description"]))),
            TR(TD(["colspan" => "2"], SHM_SUBMIT("Create")))
        ));

        $this->display_top(null, "Create Pool");
        $page->add_block(new Block("Create Pool", $form, position: 20));
    }

    private function display_top(?Pool $pool, string $heading, bool $check_all = false): void
    {
        global $page, $user;

        $page->set_title($heading);
        $page->set_heading($heading);

        $poolnav = emptyHTML(
            SHM_A("pool/list", "Pool Index"),
            BR(),
            SHM_A("pool/new", "Create Pool"),
            BR(),
            SHM_A("pool/updated", "Pool Changes")
        );

        $search = "<form action='".make_link('pool/list')."' method='POST'>
				<input name='search' type='text'  style='width:75%'>
				<input type='submit' value='Go' style='width:20%'>
			</form>";

        $page->add_block(new NavBlock());
        $page->add_block(new Block("Pool Navigation", $poolnav, "left", 10));
        $page->add_block(new Block("Search", $search, "left", 10));

        if (!is_null($pool)) {
            if ($pool->public || $user->can(Permissions::POOLS_ADMIN)) {// IF THE POOL IS PUBLIC OR IS ADMIN SHOW EDIT PANEL
                if (!$user->is_anonymous()) {// IF THE USER IS REGISTERED AND LOGGED IN SHOW EDIT PANEL
                    $this->sidebar_options($page, $pool, $check_all);
                }
            }
            $tfe = send_event(new TextFormattingEvent($pool->description));
            $page->add_block(new Block(html_escape($pool->title), $tfe->formatted, "main", 10));
        }
    }

    /**
     * HERE WE DISPLAY THE POOL WITH TITLE DESCRIPTION AND IMAGES WITH PAGINATION.
     *
     * @param Image[] $images
     */
    public function view_pool(Pool $pool, array $images, int $pageNumber, int $totalPages): void
    {
        global $page;

        $this->display_top($pool, "Pool: " . html_escape($pool->title));

        $image_list = DIV(["class" => "shm-image-list"]);
        foreach ($images as $image) {
            $image_list->appendChild($this->build_thumb_html($image));
        }

        $page->add_block(new Block("Viewing Posts", $image_list, "main", 30));
        $this->display_paginator($page, "pool/view/" . $pool->id, null, $pageNumber, $totalPages);
    }


    /**
     * HERE WE DISPLAY THE POOL OPTIONS ON SIDEBAR BUT WE HIDE REMOVE OPTION IF THE USER IS NOT THE OWNER OR ADMIN.
     */
    public function sidebar_options(Page $page, Pool $pool, bool $check_all): void
    {
        global $user;

        $editor = emptyHTML(
            SHM_SIMPLE_FORM(
                "pool/import/{$pool->id}",
                INPUT(["type" => "text", "name" => "pool_tag", "id" => "edit_pool_tag", "placeholder" => "Please enter a tag", "class" => "autocomplete_tags"]),
                SHM_SUBMIT("Import", ["name" => "edit", "id" => "edit_pool_import_btn"])
            ),
            SHM_SIMPLE_FORM(
                "pool/edit/{$pool->id}",
                SHM_SUBMIT("Edit Pool", ["name" => "edit", "id" => "edit_pool_btn"]),
            ),
            SHM_SIMPLE_FORM(
                "pool/order/{$pool->id}",
                SHM_SUBMIT("Order Pool", ["name" => "edit", "id" => "edit_pool_order_btn"])
            ),
            SHM_SIMPLE_FORM(
                "pool/reverse/{$pool->id}",
                SHM_SUBMIT("Reverse Order", ["name" => "edit", "id" => "reverse_pool_order_btn"])
            ),
            SHM_SIMPLE_FORM(
                "post/list/pool_id=" . $pool->id . "/1",
                SHM_SUBMIT("Post/List View", ["name" => "edit", "id" => $pool->id])
            )
        );

        if ($user->id == $pool->user_id || $user->can(Permissions::POOLS_ADMIN)) {
            $editor->appendChild(
                SCRIPT(
                    ["type" => "text/javascript"],
                    rawHTML("<!--
                    function confirm_action() {
                        return confirm('Are you sure that you want to delete this pool?');
                    }
                    //-->")
                ),
                SHM_SIMPLE_FORM(
                    "pool/nuke/{$pool->id}",
                    SHM_SUBMIT("Delete Pool", ["name" => "delete", "id" => "delete_pool_btn", "onclick" => "return confirm_action()"])
                )
            );
        }

        if ($check_all) {
            $editor->appendChild(
                SCRIPT(
                    ["type" => "text/javascript"],
                    rawHTML("<!--
                    function setAll(value) {
                        $('[name=\"check[]\"]').attr('checked', value);
                    }
                    //-->")
                ),
                INPUT(["type" => "button", "name" => "CheckAll", "value" => "Check All", "onclick" => "setAll(true)"]),
                INPUT(["type" => "button", "name" => "UnCheckAll", "value" => "Uncheck All", "onclick" => "setAll(false)"])
            );
        }

        $page->add_block(new Block("Manage Pool", $editor, "left", 15));
    }

    /**
     * HERE WE DISPLAY THE RESULT OF THE SEARCH ON IMPORT.
     *
     * @param Image[] $images
     */
    public function pool_result(Page $page, array $images, Pool $pool): void
    {
        $this->display_top($pool, "Importing Posts", true);

        $import = emptyHTML(
            SCRIPT(
                ["type" => "text/javascript"],
                rawHTML("
                function confirm_action() {
                    return confirm('Are you sure you want to add selected posts to this pool?');
                }")
            )
        );

        $form = SHM_FORM("pool/add_posts/{$pool->id}", name: "checks");
        $image_list = DIV(["class" => "shm-image-list"]);
        foreach ($images as $image) {
            $image_list->appendChild(
                SPAN(["class" => "thumb"], $this->build_thumb_html($image), BR(), INPUT(["type" => "checkbox", "name" => "check[]", "value" => $image->id])),
            );
        }
        $form->appendChild($image_list);

        $form->appendChild(
            BR(),
            SHM_SUBMIT("Add Selected", ["name" => "edit", "id" => "edit_pool_add_btn", "onclick" => "return confirm_action()"]),
        );

        $import->appendChild($form);

        $page->add_block(new Block("Import", $import, "main", 30));
    }


    /**
     * HERE WE DISPLAY THE POOL ORDERER.
     * WE LIST ALL IMAGES ON POOL WITHOUT PAGINATION AND WITH A TEXT INPUT TO SET A NUMBER AND CHANGE THE ORDER
     *
     * @param Image[] $images
     */
    public function edit_order(Page $page, Pool $pool, array $images): void
    {
        $this->display_top($pool, "Sorting Pool");

        $form = SHM_FORM("pool/save_order/{$pool->id}", name: "checks");
        $image_list = DIV(["class" => "shm-image-list"]);
        foreach ($images as $i => $image) {
            $image_list->appendChild(SPAN(
                ["class" => "thumb"],
                $this->build_thumb_html($image),
                INPUT(["type" => "number", "name" => "order_{$image->id}", "value" => $image['image_order'], "style" => "max-width: 50px;"]),
            ));
        }
        $form->appendChild($image_list);

        $form->appendChild(
            SHM_SUBMIT("Order", ["name" => "edit", "id" => "edit_pool_order"])
        );

        $page->add_block(new Block("Sorting Posts", $form, position: 30));
    }

    /**
     * HERE WE DISPLAY THE POOL EDITOR.
     *
     * WE LIST ALL IMAGES ON POOL WITHOUT PAGINATION AND WITH
     * A CHECKBOX TO SELECT WHICH IMAGE WE WANT TO REMOVE
     *
     * @param Image[] $images
     */
    public function edit_pool(Page $page, Pool $pool, array $images): void
    {
        $desc_form = SHM_SIMPLE_FORM(
            "pool/edit_description/{$pool->id}",
            TEXTAREA(["name" => "description"], $pool->description),
            BR(),
            SHM_SUBMIT("Change Description")
        );

        $images_form = SHM_FORM("pool/remove_posts/{$pool->id}", name: "checks");
        $image_list = DIV(["class" => "shm-image-list"]);
        foreach ($images as $image) {
            $image_list->appendChild(SPAN(
                ["class" => "thumb"],
                $this->build_thumb_html($image),
                INPUT(["type" => "checkbox", "name" => "check[]", "value" => $image->id])
            ));
        }
        $images_form->appendChild($image_list);

        $images_form->appendChild(
            BR(),
            SHM_SUBMIT("Remove Selected", ["name" => "edit", "id" => "edit_pool_remove_sel"])
        );

        $pool->description = ""; //This is a rough fix to avoid showing the description twice.
        $this->display_top($pool, "Editing Pool", true);
        $page->add_block(new Block("Editing Description", $desc_form, position: 28));
        $page->add_block(new Block("Editing Posts", $images_form, position: 30));
    }

    /**
     * HERE WE DISPLAY THE HISTORY LIST.
     *
     * @param PoolHistory[] $histories
     */
    public function show_history(array $histories, int $pageNumber, int $totalPages): void
    {
        global $page;

        $table = TABLE(
            ["id" => "poolsList", "class" => "zebra"],
            THEAD(TR(TH("Pool"), TH("Post Count"), TH("Changes"), TH("Updater"), TH("Date"), TH("Action")))
        );

        $body = [];
        foreach ($histories as $history) {
            $pool_link = SHM_A("pool/view/" . $history["pool_id"], $history["title"]);
            $user_link = SHM_A("user/" . url_escape($history["user_name"]), $history["user_name"]);
            $revert_link = SHM_A(("pool/revert/" . $history["id"]), "Revert");

            if ($history['action'] == 1) {
                $prefix = "+";
            } elseif ($history['action'] == 0) {
                $prefix = "-";
            } else {
                throw new \RuntimeException("history['action'] not in {0, 1}");
            }

            $images = trim($history["images"]);
            $images = explode(" ", $images);

            $image_links = emptyHTML();
            foreach ($images as $image) {
                $image_links->appendChild(" ", SHM_A("post/view/" . $image, $prefix . $image));
            }

            $body[] = TR(
                TD(["class" => "left"], $pool_link),
                TD($history["count"]),
                TD($image_links),
                TD($user_link),
                TD($history["date"]),
                TD($revert_link)
            );
        }

        $table->appendChild(TBODY(...$body));

        $this->display_top(null, "Recent Changes");
        $page->add_block(new Block("Recent Changes", $table, position: 10));

        $this->display_paginator($page, "pool/updated", null, $pageNumber, $totalPages);
    }

    /**
     * @param array<int,string> $options
     */
    public function get_bulk_pool_selector(array $options): HTMLElement
    {
        return SHM_SELECT("bulk_pool_select", $options, required: true, empty_option: true);
    }

    /**
     * @param string[] $search_terms
     */
    public function get_bulk_pool_input(array $search_terms): HTMLElement
    {
        return INPUT(
            [
                "type" => "text",
                "name" => "bulk_pool_new",
                "placeholder" => "New Pool",
                "required" => "",
                "value" => Tag::implode($search_terms)
            ]
        );
    }

    public function get_help_html(): HTMLElement
    {
        return emptyHTML(
            P("Search for posts that are in a pool."),
            SHM_COMMAND_EXAMPLE(
                "pool=1",
                "Returns posts in pool #1."
            ),
            SHM_COMMAND_EXAMPLE(
                "pool=any",
                "Returns posts in any pool."
            ),
            SHM_COMMAND_EXAMPLE(
                "pool=none",
                "Returns posts not in any pool."
            ),
            SHM_COMMAND_EXAMPLE(
                "pool_by_name=swimming",
                "Returns posts in the \"swimming\" pool."
            ),
            SHM_COMMAND_EXAMPLE(
                "pool_by_name=swimming_pool",
                "Returns posts in the \"swimming pool\" pool. Note that the underscore becomes a space."
            )
        );
    }
}
