<?php

declare(strict_types=1);

namespace Shimmie2;

use MicroHTML\HTMLElement;

use function MicroHTML\emptyHTML;
use function MicroHTML\rawHTML;
use function MicroHTML\{A,BR,INPUT,P,SCRIPT,TABLE,THEAD,TBODY,TR,TH,TD,TEXTAREA,SPAN};

class PoolsTheme extends Themelet
{
    /**
     * Adds a block to the panel with information on the pool(s) the image is in.
     * $navIDs = Multidimensional array containing pool id, info & nav IDs.
     */
    public function pool_info(array $navIDs)
    {
        global $page;

        $linksPools = [];
        foreach ($navIDs as $poolID => $poolInfo) {
            $linksPools[] = "<a href='" . make_link("pool/view/" . $poolID) . "'>" . html_escape($poolInfo['info']->title) . "</a>";

            if (!empty($poolInfo['nav'])) {
                $navlinks = "";
                if (!empty($poolInfo['nav']['prev'])) {
                    $navlinks .= '<a href="' . make_link('post/view/' . $poolInfo['nav']['prev']) . '" class="pools_prev_img">Prev</a>';
                }
                if (!empty($poolInfo['nav']['next'])) {
                    $navlinks .= '<a href="' . make_link('post/view/' . $poolInfo['nav']['next']) . '" class="pools_next_img">Next</a>';
                }
                if (!empty($navlinks)) {
                    $navlinks .= "<div style='height: 5px'></div>";
                    $linksPools[] = $navlinks;
                }
            }
        }

        if (count($linksPools) > 0) {
            $page->add_block(new Block("Pools", implode("<br>", $linksPools), "left"));
        }
    }

    public function get_adder_html(Image $image, array $pools): HTMLElement
    {
        return SHM_SIMPLE_FORM(
            "pool/add_post",
            SHM_SELECT("pool_id", $pools),
            INPUT(["type"=>"hidden", "name"=>"image_id", "value"=>$image->id]),
            SHM_SUBMIT("Add Post to Pool")
        );
    }

    /**
     * HERE WE SHOWS THE LIST OF POOLS.
     */
    public function list_pools(Page $page, array $pools, int $pageNumber, int $totalPages)
    {
        // Build up the list of pools.
        $pool_rows = [];
        foreach ($pools as $pool) {
            $pool_link = A(["href"=>make_link("pool/view/" . $pool->id)], $pool->title);
            $user_link = A(["href"=>make_link("user/" . url_escape($pool->user_name))], $pool->user_name);

            $pool_rows[] = TR(
                TD(["class"=>"left"], $pool_link),
                TD($user_link),
                TD($pool->posts),
                TD($pool->public ? "Yes" : "No")
            );
        }

        $table = TABLE(
            ["id"=>"poolsList", "class"=>"zebra"],
            THEAD(TR(TH("Name"), TH("Creator"), TH("Posts"), TH("Public"))),
            TBODY(...$pool_rows)
        );

        $order_arr = ['created' => 'Recently created', 'updated' => 'Last updated', 'name' => 'Name', 'count' => 'Post Count'];
        $order_selected = $page->get_cookie('ui-order-pool');
        $order_sel = SHM_SELECT("order_pool", $order_arr, selected_options: [$order_selected], attrs: ["id"=>"order_pool"]);

        $this->display_top(null, "Pools");
        $page->add_block(new Block("Order By", $order_sel, "left", 15));

        $page->add_block(new Block("Pools", $table, position: 10));

        $this->display_paginator($page, "pool/list", null, $pageNumber, $totalPages);
    }

    /*
     * HERE WE DISPLAY THE NEW POOL COMPOSER
     */
    public function new_pool_composer(Page $page)
    {
        $form = SHM_SIMPLE_FORM("pool/create", TABLE(
            TR(TD("Title:"), TD(INPUT(["type"=>"text", "name"=>"title"]))),
            TR(TD("Public?:"), TD(INPUT(["type"=>"checkbox", "name"=>"public", "value"=>"Y", "checked"=>"checked"]))),
            TR(TD("Description:"), TD(TEXTAREA(["name"=>"description"]))),
            TR(TD(["colspan"=>"2"], SHM_SUBMIT("Create")))
        ));

        $this->display_top(null, "Create Pool");
        $page->add_block(new Block("Create Pool", $form, position: 20));
    }

    private function display_top(?Pool $pool, string $heading, bool $check_all = false)
    {
        global $page, $user;

        $page->set_title($heading);
        $page->set_heading($heading);

        $poolnav = emptyHTML(
            A(["href"=>make_link("pool/list")], "Pool Index"),
            BR(),
            A(["href"=>make_link("pool/new")], "Create Pool"),
            BR(),
            A(["href"=>make_link("pool/updated")], "Pool Changes")
        );

        $page->add_block(new NavBlock());
        $page->add_block(new Block("Pool Navigation", $poolnav, "left", 10));

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
     */
    public function view_pool(Pool $pool, array $images, int $pageNumber, int $totalPages)
    {
        global $page;

        $this->display_top($pool, "Pool: " . html_escape($pool->title));

        $pool_images = emptyHTML();
        foreach ($images as $image) {
            $pool_images->appendChild($this->build_thumb_html($image));
        }

        $page->add_block(new Block("Viewing Posts", $pool_images, "main", 30));
        $this->display_paginator($page, "pool/view/" . $pool->id, null, $pageNumber, $totalPages);
    }


    /**
     * HERE WE DISPLAY THE POOL OPTIONS ON SIDEBAR BUT WE HIDE REMOVE OPTION IF THE USER IS NOT THE OWNER OR ADMIN.
     */
    public function sidebar_options(Page $page, Pool $pool, bool $check_all)
    {
        global $user;

        // This could become a SHM_INPUT function that also accepts 'type' and other attributes.
        $_hidden=function (string $name, $value) {
            return INPUT(["type"=>"hidden", "name"=>$name, "value"=>$value]);
        };

        $_input_id = $_hidden("pool_id", $pool->id);

        $editor = emptyHTML(
            SHM_SIMPLE_FORM(
                "pool/import",
                INPUT(["type"=>"text", "name"=>"pool_tag", "id"=>"edit_pool_tag", "placeholder"=>"Please enter a tag"]),
                $_input_id,
                SHM_SUBMIT("Import", ["name"=>"edit", "id"=>"edit_pool_import_btn"])
            ),
            SHM_SIMPLE_FORM(
                "pool/edit",
                $_hidden("edit_pool", "yes"),
                $_input_id,
                SHM_SUBMIT("Edit Pool", ["name"=>"edit", "id"=>"edit_pool_btn"]),
            ),
            SHM_SIMPLE_FORM(
                "pool/order",
                $_hidden("order_view", "yes"),
                $_input_id,
                SHM_SUBMIT("Order Pool", ["name"=>"edit", "id"=>"edit_pool_order_btn"])
            ),
            SHM_SIMPLE_FORM(
                "pool/reverse",
                $_hidden("reverse_view", "yes"),
                $_input_id,
                SHM_SUBMIT("Reverse Order", ["name"=>"edit", "id"=>"reverse_pool_order_btn"])
            ),
            SHM_SIMPLE_FORM(
                "pool/list/pool_id%3A" . $pool->id . "/1",
                SHM_SUBMIT("Post/List View", ["name"=>"edit", "id"=>"postlist_pool_btn"])
            )
        );

        if ($user->id == $pool->user_id || $user->can(Permissions::POOLS_ADMIN)) {
            $editor->appendChild(
                SCRIPT(
                    ["type"=>"text/javascript"],
                    rawHTML("<!--
                    function confirm_action() {
                        return confirm('Are you sure that you want to delete this pool?');
                    }
                    //-->")
                ),
                SHM_SIMPLE_FORM(
                    "pool/nuke",
                    $_input_id,
                    SHM_SUBMIT("Delete Pool", ["name"=>"delete", "id"=>"delete_pool_btn", "onclick"=>"return confirm_action()"])
                )
            );
        }

        if ($check_all) {
            $editor->appendChild(
                SCRIPT(
                    ["type"=>"text/javascript"],
                    rawHTML("<!--
                    function setAll(value) {
                        $('[name=\"check[]\"]').attr('checked', value);
                    }
                    //-->")
                ),
                INPUT(["type"=>"button", "name"=>"CheckAll", "value"=>"Check All", "onclick"=>"setAll(true)"]),
                INPUT(["type"=>"button", "name"=>"UnCheckAll", "value"=>"Uncheck All", "onclick"=>"setAll(false)"])
            );
        }

        $page->add_block(new Block("Manage Pool", $editor, "left", 15));
    }

    /**
     * HERE WE DISPLAY THE RESULT OF THE SEARCH ON IMPORT.
     */
    public function pool_result(Page $page, array $images, Pool $pool)
    {
        $this->display_top($pool, "Importing Posts", true);

        $import = emptyHTML(
            SCRIPT(
                ["type"=>"text/javascript"],
                rawHTML("
                function confirm_action() {
                    return confirm('Are you sure you want to add selected posts to this pool?');
                }")
            )
        );

        $form = SHM_FORM("pool/add_posts", name: "checks");
        foreach ($images as $image) {
            $form->appendChild(
                SPAN(["class"=>"thumb"], $this->build_thumb_html($image), BR(), INPUT(["type"=>"checkbox", "name"=>"check[]", "value"=>$image->id])),
            );
        }

        $form->appendChild(
            BR(),
            SHM_SUBMIT("Add Selected", ["name"=>"edit", "id"=>"edit_pool_add_btn", "onclick"=>"return confirm_action()"]),
            INPUT(["type"=>"hidden", "name"=>"pool_id", "value"=>$pool->id])
        );

        $import->appendChild($form);

        $page->add_block(new Block("Import", $import, "main", 30));
    }


    /**
     * HERE WE DISPLAY THE POOL ORDERER.
     * WE LIST ALL IMAGES ON POOL WITHOUT PAGINATION AND WITH A TEXT INPUT TO SET A NUMBER AND CHANGE THE ORDER
     */
    public function edit_order(Page $page, Pool $pool, array $images)
    {
        $this->display_top($pool, "Sorting Pool");

        $pool_images = "\n<form action='" . make_link("pool/order") . "' method='POST' name='checks'>";
        $i = 0;
        foreach ($images as $image) {
            $thumb_html = $this->build_thumb_html($image);
            $pool_images .= '<span class="thumb">' . "\n" . $thumb_html . "\n" .
                '<br><input name="imgs[' . $i . '][]" type="number" style="max-width:50px;" value="' . $image->image_order . '" />' .
                '<input name="imgs[' . $i . '][]" type="hidden" value="' . $image->id . '" />' .
                '</span>';
            $i++;
        }

        $pool_images .= "<br>" .
            "<input type='submit' name='edit' id='edit_pool_order' value='Order'/>" .
            "<input type='hidden' name='pool_id' value='" . $pool->id . "'>" .
            "</form>";

        $page->add_block(new Block("Sorting Posts", $pool_images, "main", 30));
    }

    /**
     * HERE WE DISPLAY THE POOL EDITOR.
     *
     * WE LIST ALL IMAGES ON POOL WITHOUT PAGINATION AND WITH
     * A CHECKBOX TO SELECT WHICH IMAGE WE WANT TO REMOVE
     */
    public function edit_pool(Page $page, Pool $pool, array $images)
    {
        /* EDIT POOL DESCRIPTION */
        $desc_html = "
			" . make_form(make_link("pool/edit_description")) . "
					<textarea name='description'>" . $pool->description . "</textarea><br />
					<input type='hidden' name='pool_id' value='" . $pool->id . "'>
					<input type='submit' value='Change Description' />
			</form>
		";

        /* REMOVE POOLS */
        $pool_images = "\n<form action='" . make_link("pool/remove_posts") . "' method='POST' name='checks'>";

        foreach ($images as $image) {
            $thumb_html = $this->build_thumb_html($image);

            $pool_images .= '<span class="thumb">' . "\n" . $thumb_html . "\n" .
                '<br><input name="check[]" type="checkbox" value="' . $image->id . '" />' .
                '</span>';
        }

        $pool_images .= "<br>" .
            "<input type='submit' name='edit' id='edit_pool_remove_sel' value='Remove Selected'/>" .
            "<input type='hidden' name='pool_id' value='" . $pool->id . "'>" .
            "</form>";

        $pool->description = ""; //This is a rough fix to avoid showing the description twice.
        $this->display_top($pool, "Editing Pool", true);
        $page->add_block(new Block("Editing Description", $desc_html, "main", 28));
        $page->add_block(new Block("Editing Posts", $pool_images, "main", 30));
    }

    /**
     * HERE WE DISPLAY THE HISTORY LIST.
     */
    public function show_history(array $histories, int $pageNumber, int $totalPages)
    {
        global $page;
        $html = '
			<table id="poolsList" class="zebra">
				<thead><tr>
					<th>Pool</th>
					<th>Post Count</th>
					<th>Changes</th>
					<th>Updater</th>
					<th>Date</th>
					<th>Action</th>
				</tr></thead><tbody>';

        foreach ($histories as $history) {
            $pool_link = "<a href='" . make_link("pool/view/" . $history['pool_id']) . "'>" . html_escape($history['title']) . "</a>";
            $user_link = "<a href='" . make_link("user/" . url_escape($history['user_name'])) . "'>" . html_escape($history['user_name']) . "</a>";
            $revert_link = "<a href='" . make_link("pool/revert/" . $history['id']) . "'>Revert</a>";

            if ($history['action'] == 1) {
                $prefix = "+";
            } elseif ($history['action'] == 0) {
                $prefix = "-";
            } else {
                throw new \RuntimeException("history['action'] not in {0, 1}");
            }

            $images = trim($history['images']);
            $images = explode(" ", $images);

            $image_link = "";
            foreach ($images as $image) {
                $image_link .= "<a href='" . make_link("post/view/" . $image) . "'>" . $prefix . $image . " </a>";
            }

            $html .= "<tr>" .
                "<td class='left'>" . $pool_link . "</td>" .
                "<td>" . $history['count'] . "</td>" .
                "<td>" . $image_link . "</td>" .
                "<td>" . $user_link . "</td>" .
                "<td>" . $history['date'] . "</td>" .
                "<td>" . $revert_link . "</td>" .
                "</tr>";
        }

        $html .= "</tbody></table>";

        $this->display_top(null, "Recent Changes");
        $page->add_block(new Block("Recent Changes", $html, "main", 10));

        $this->display_paginator($page, "pool/updated", null, $pageNumber, $totalPages);
    }

    public function get_bulk_pool_selector(array $options): HTMLElement
    {
        return SHM_SELECT("bulk_pool_select", $options, required: true, empty_option: true);
    }

    public function get_bulk_pool_input(array $search_terms): HTMLElement
    {
        return INPUT(
            [
                "type"=>"text",
                "name"=>"bulk_pool_new",
                "placeholder"=>"New Pool",
                "required"=>"",
                "value"=>implode(" ", $search_terms)
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
