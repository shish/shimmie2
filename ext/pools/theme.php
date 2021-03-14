<?php declare(strict_types=1);

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

    public function get_adder_html(Image $image, array $pools): string
    {
        $h = "";
        foreach ($pools as $pool) {
            $h .= "<option value='" . $pool->id . "'>" . html_escape($pool->title) . "</option>";
        }
        return "\n" . make_form(make_link("pool/add_post")) . "
				<select name='pool_id'>
					$h
				</select>
				<input type='hidden' name='image_id' value='{$image->id}'>
				<input type='submit' value='Add Post to Pool'>
			</form>
		";
    }

    /**
     * HERE WE SHOWS THE LIST OF POOLS.
     */
    public function list_pools(Page $page, array $pools, int $pageNumber, int $totalPages)
    {
        $html = '
				<table id="poolsList" class="zebra">
					<thead><tr>
						<th>Name</th>
						<th>Creator</th>
						<th>Posts</th>
						<th>Public</th>
					</tr></thead><tbody>';

        // Build up the list of pools.
        foreach ($pools as $pool) {
            $pool_link = '<a href="' . make_link("pool/view/" . $pool->id) . '">' . html_escape($pool->title) . "</a>";
            $user_link = '<a href="' . make_link("user/" . url_escape($pool->user_name)) . '">' . html_escape($pool->user_name) . "</a>";
            $public = ($pool->public ? "Yes" : "No");

            $html .= "<tr>" .
                "<td class='left'>" . $pool_link . "</td>" .
                "<td>" . $user_link . "</td>" .
                "<td>" . $pool->posts . "</td>" .
                "<td>" . $public . "</td>" .
                "</tr>";
        }

        $html .= "</tbody></table>";

        $order_html = '<select id="order_pool">';
        $order_selected = $page->get_cookie('ui-order-pool');
        $order_arr = ['created' => 'Recently created', 'updated' => 'Last updated', 'name' => 'Name', 'count' => 'Post Count'];
        foreach ($order_arr as $value => $text) {
            $selected = ($value == $order_selected ? "selected" : "");
            $order_html .= "<option value=\"{$value}\" {$selected}>{$text}</option>\n";
        }
        $order_html .= '</select>';

        $this->display_top(null, "Pools");
        $page->add_block(new Block("Order By", $order_html, "left", 15));

        $page->add_block(new Block("Pools", $html, "main", 10));


        $this->display_paginator($page, "pool/list", null, $pageNumber, $totalPages);
    }

    /*
     * HERE WE DISPLAY THE NEW POOL COMPOSER
     */
    public function new_pool_composer(Page $page)
    {
        $create_html = "
			" . make_form(make_link("pool/create")) . "
				<table>
					<tr><td>Title:</td><td><input type='text' name='title'></td></tr>
					<tr><td>Public?</td><td><input name='public' type='checkbox' value='Y' checked='checked'/></td></tr>
					<tr><td>Description:</td><td><textarea name='description'></textarea></td></tr>
					<tr><td colspan='2'><input type='submit' value='Create' /></td></tr>
				</table>
			</form>
		";

        $this->display_top(null, "Create Pool");
        $page->add_block(new Block("Create Pool", $create_html, "main", 20));
    }

    private function display_top(?Pool $pool, string $heading, bool $check_all = false)
    {
        global $page, $user;

        $page->set_title($heading);
        $page->set_heading($heading);

        $poolnav_html = '
			<a href="' . make_link("pool/list") . '">Pool Index</a>
			<br><a href="' . make_link("pool/new") . '">Create Pool</a>
			<br><a href="' . make_link("pool/updated") . '">Pool Changes</a>
		';

        $page->add_block(new NavBlock());
        $page->add_block(new Block("Pool Navigation", $poolnav_html, "left", 10));

        if (!is_null($pool)) {
            if ($pool->public || $user->can(Permissions::POOLS_ADMIN)) {// IF THE POOL IS PUBLIC OR IS ADMIN SHOW EDIT PANEL
                if (!$user->is_anonymous()) {// IF THE USER IS REGISTERED AND LOGGED IN SHOW EDIT PANEL
                    $this->sidebar_options($page, $pool, $check_all);
                }
            }
            $tfe = new TextFormattingEvent($pool->description);
            send_event($tfe);
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

        $pool_images = '';
        foreach ($images as $image) {
            $thumb_html = $this->build_thumb_html($image);
            $pool_images .= "\n" . $thumb_html . "\n";
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

        $editor = "\n" . make_form(make_link('pool/import')) . '
				<input type="text" name="pool_tag" id="edit_pool_tag" placeholder="Please enter a tag"/>
				<input type="submit" name="edit" id="edit_pool_import_btn" value="Import"/>
				<input type="hidden" name="pool_id" value="' . $pool->id . '">
			</form>

			' . make_form(make_link('pool/edit')) . '
				<input type="submit" name="edit" id="edit_pool_btn" value="Edit Pool"/>
				<input type="hidden" name="edit_pool" value="yes">
				<input type="hidden" name="pool_id" value="' . $pool->id . '">
			</form>

			' . make_form(make_link('pool/order')) . '
				<input type="submit" name="edit" id="edit_pool_order_btn" value="Order Pool"/>
				<input type="hidden" name="order_view" value="yes">
				<input type="hidden" name="pool_id" value="' . $pool->id . '">
			</form>
			';

        if ($user->id == $pool->user_id || $user->can(Permissions::POOLS_ADMIN)) {
            $editor .= "
				<script type='text/javascript'>
				<!--
				function confirm_action() {
					return confirm('Are you sure that you want to delete this pool?');
				}
				//-->
				</script>

				" . make_form(make_link("pool/nuke")) . "
					<input type='submit' name='delete' id='delete_pool_btn' value='Delete Pool' onclick='return confirm_action()' />
					<input type='hidden' name='pool_id' value='" . $pool->id . "'>
				</form>
				";
        }

        if ($check_all) {
            $editor .= "
				<script type='text/javascript'>
				<!--
				function setAll(value) {
					$('[name=\"check[]\"]').attr('checked', value);
				}
				//-->
				</script>
				<br><input type='button' name='CheckAll' value='Check All' onClick='setAll(true)'>
				<input type='button' name='UnCheckAll' value='Uncheck All' onClick='setAll(false)'>
			";
        }
        $page->add_block(new Block("Manage Pool", $editor, "left", 15));
    }

    /**
     * HERE WE DISPLAY THE RESULT OF THE SEARCH ON IMPORT.
     */
    public function pool_result(Page $page, array $images, Pool $pool)
    {
        $this->display_top($pool, "Importing Posts", true);
        $pool_images = "
			<script type='text/javascript'>
			function confirm_action() {
				return confirm('Are you sure you want to add selected posts to this pool?');
			}
			</script>
		";

        $pool_images .= "<form action='" . make_link("pool/add_posts") . "' method='POST' name='checks'>";

        foreach ($images as $image) {
            $thumb_html = $this->build_thumb_html($image);

            $pool_images .= '<span class="thumb">' . $thumb_html . '<br>' .
                '<input name="check[]" type="checkbox" value="' . $image->id . '" />' .
                '</span>';
        }

        $pool_images .= "<br>" .
            "<input type='submit' name='edit' id='edit_pool_add_btn' value='Add Selected' onclick='return confirm_action()'/>" .
            "<input type='hidden' name='pool_id' value='" . $pool->id . "'>" .
            "</form>";

        $page->add_block(new Block("Import", $pool_images, "main", 30));
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
                throw new RuntimeException("history['action'] not in {0, 1}");
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

    public function get_bulk_pool_selector(array $pools): string
    {
        $output = "<select name='bulk_pool_select' required='required'><option></option>";
        foreach ($pools as $pool) {
            $output .= "<option value='" . $pool->id . "' >" . $pool->title . "</option>";
        }
        return $output . "</select>";
    }

    public function get_bulk_pool_input(array $search_terms): string
    {
        return "<input type='text' name='bulk_pool_new' placeholder='New pool' required='required' value='".(implode(" ", $search_terms))."' />";
    }

    public function get_help_html(): string
    {
        return '<p>Search for posts that are in a pool.</p>
        <div class="command_example">
        <pre>pool=1</pre>
        <p>Returns posts in pool #1.</p>
        </div>
        <div class="command_example">
        <pre>pool=any</pre>
        <p>Returns posts in any pool.</p>
        </div>
        <div class="command_example">
        <pre>pool=none</pre>
        <p>Returns posts not in any pool.</p>
        </div>
        <div class="command_example">
        <pre>pool_by_name=swimming</pre>
        <p>Returns posts in the "swimming" pool.</p>
        </div>
        <div class="command_example">
        <pre>pool_by_name=swimming_pool</pre>
        <p>Returns posts in the "swimming pool" pool. Note that the underscore becomes a space</p>
        </div>
        ';
    }
}
