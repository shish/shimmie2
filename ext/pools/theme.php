<?php

class PoolsTheme extends Themelet {
	/**
	 * Adds a block to the panel with information on the pool(s) the image is in.
	 */
	public function pool_info($linksPools) {
		global $page;
		if(count($linksPools) > 0) {
			$page->add_block(new Block("Pools", implode("<br>", $linksPools), "left"));
		}
	}

	public function get_adder_html(Image $image, /*array*/ $pools) {
		$editor = "";
		$h = "";
		foreach($pools as $pool) {
			$h .= "<option value='".$pool['id']."'>".html_escape($pool['title'])."</option>";
		}
		$editor = "\n".make_form(make_link("pool/add_post"))."
				<select name='pool_id'>
					$h
				</select>
				<input type='hidden' name='image_id' value='{$image->id}'>
				<input type='submit' value='Add Image to Pool'>
			</form>
		";
		return $editor;
	}


	/*
	 * HERE WE SHOWS THE LIST OF POOLS
	 */
	public function list_pools(Page $page, /*array*/ $pools, /*int*/ $pageNumber, /*int*/ $totalPages) {
		global $user;
		$html = '
				<table id="poolsList" class="zebra">
					<thead><tr>
						<th>Name</th>
						<th>Creator</th>
						<th>Posts</th>
						<th>Public</th>
					</tr></thead><tbody>';

		$n = 0;
		
		// Build up the list of pools.
		foreach($pools as $pool) {
			$pool_link = '<a href="'.make_link("pool/view/".$pool['id']).'">'.html_escape($pool['title'])."</a>";
			$user_link = '<a href="'.make_link("user/".url_escape($pool['user_name'])).'">'.html_escape($pool['user_name'])."</a>";
			$public = ($pool['public'] == "Y" ? "Yes" : "No");

			$html .= "<tr>".
				"<td class='left'>".$pool_link."</td>".
				"<td>".$user_link."</td>".
				"<td>".$pool['posts']."</td>".
				"<td>".$public."</td>".
				"</tr>";
		}

		$html .= "</tbody></table>";

		$nav_html = '
			<a href="'.make_link().'">Index</a>
			<br><a href="'.make_link("pool/new").'">Create Pool</a>
			<br><a href="'.make_link("pool/updated").'">Pool Changes</a>
		';

		$blockTitle = "Pools";
		$page->set_title(html_escape($blockTitle));
		$page->set_heading(html_escape($blockTitle));
		$page->add_block(new Block($blockTitle, $html, "main", 10));
		$page->add_block(new Block("Navigation", $nav_html, "left", 10));

		$this->display_paginator($page, "pool/list", null, $pageNumber, $totalPages);
	}


	/*
	 * HERE WE DISPLAY THE NEW POOL COMPOSER
	 */
	public function new_pool_composer(Page $page) {
		$create_html = "
			".make_form(make_link("pool/create"))."
				<table>
					<tr><td>Title:</td><td><input type='text' name='title'></td></tr>
					<tr><td>Public?</td><td><input name='public' type='checkbox' value='Y' checked='checked'/></td></tr>
					<tr><td>Description:</td><td><textarea name='description'></textarea></td></tr>
					<tr><td colspan='2'><input type='submit' value='Create' /></td></tr>
				</table>
			</form>
		";

		$blockTitle = "Create Pool";
		$page->set_title(html_escape($blockTitle));
		$page->set_heading(html_escape($blockTitle));
		$page->add_block(new Block("Create Pool", $create_html, "main", 20));
	}


	private function display_top(/*array*/ $pools, /*string*/ $heading, $check_all=false) {
		global $page, $user;

		$page->set_title($heading);
		$page->set_heading($heading);
		if(count($pools) == 1) {
			$pool = $pools[0];
			if($pool['public'] == "Y" || $user->is_admin()) {// IF THE POOL IS PUBLIC OR IS ADMIN SHOW EDIT PANEL
				if(!$user->is_anonymous()) {// IF THE USER IS REGISTERED AND LOGGED IN SHOW EDIT PANEL
					$this->sidebar_options($page, $pool, $check_all);
				}
			}
			$page->add_block(new Block(html_escape($pool['title']), html_escape($pool['description']), "main", 10));
		}
		else {
			$pool_info = '
						<table id="poolsList" class="zebra">
							<thead><tr>
								<th class="left">Title</th>
								<th class="left">Description</th>
							</tr></thead><tbody>';

			$n = 0;
			foreach($pools as $pool) {
				$pool_info .= "<tr>".
					"<td class='left'>".html_escape($pool['title'])."</td>".
					"<td class='left'>".html_escape($pool['description'])."</td>".
					"</tr>";

				// this will make disasters if more than one pool comes in the parameter
				if($pool['public'] == "Y" || $user->is_admin()) {// IF THE POOL IS PUBLIC OR IS ADMIN SHOW EDIT PANEL
					if(!$user->is_anonymous()) {// IF THE USER IS REGISTERED AND LOGGED IN SHOW EDIT PANEL
						$this->sidebar_options($page, $pool, $check_all);
					}
				}
			}

			$pool_info .= "</tbody></table>";
			$page->add_block(new Block($heading, $pool_info, "main", 10));
		}
	}


	/*
	 * HERE WE DISPLAY THE POOL WITH TITLE DESCRIPTION AND IMAGES WITH PAGINATION
	 */
	public function view_pool(/*array*/ $pools, /*array*/ $images, /*int*/ $pageNumber, /*int*/ $totalPages) {
		global $user, $page;

		$this->display_top($pools, "Pool: ".html_escape($pools[0]['title']));

		$pool_images = '';
		foreach($images as $image) {
			$thumb_html = $this->build_thumb_html($image);
			$pool_images .= "\n".$thumb_html."\n";
		}

		$page->add_block(new Block("Viewing Posts", $pool_images, "main", 30));		
		$this->display_paginator($page, "pool/view/".$pools[0]['id'], null, $pageNumber, $totalPages);
	}


	/*
	 * HERE WE DISPLAY THE POOL OPTIONS ON SIDEBAR BUT WE HIDE REMOVE OPTION IF THE USER IS NOT THE OWNER OR ADMIN
	 */
	public function sidebar_options(Page $page, $pool, /*bool*/ $check_all) {
		global $user;

		$editor = "\n".make_form( make_link('pool/import') ).'
				<input type="text" name="pool_tag" id="edit_pool_tag" value="Please enter a tag" onclick="this.value=\'\';"/>
				<input type="submit" name="edit" id="edit_pool_import_btn" value="Import"/>
				<input type="hidden" name="pool_id" value="'.$pool['id'].'">
			</form>
			
			'.make_form( make_link('pool/edit') ).'
				<input type="submit" name="edit" id="edit_pool_btn" value="Edit Pool"/>
				<input type="hidden" name="edit_pool" value="yes">
				<input type="hidden" name="pool_id" value="'.$pool['id'].'">
			</form>
			
			'.make_form( make_link('pool/order') ).'
				<input type="submit" name="edit" id="edit_pool_order_btn" value="Order Pool"/>
				<input type="hidden" name="order_view" value="yes">
				<input type="hidden" name="pool_id" value="'.$pool['id'].'">
			</form>
			';

		if($user->id == $pool['user_id'] || $user->is_admin()){
			$editor .= "
				<script language='javascript' type='text/javascript'>
				<!--
				function confirm_action() {
					return confirm('Are you sure that you want to delete this pool?');
				}
				//-->
				</script>

				".make_form(make_link("pool/nuke"))."
					<input type='submit' name='delete' id='delete_pool_btn' value='Delete Pool' onclick='return confirm_action()' />
					<input type='hidden' name='pool_id' value='".$pool['id']."'>
				</form>
				";
		}

		if($check_all) {
			$editor .= "
				<script language='javascript' type='text/javascript'>
				<!--
				function setAll(value) {
					var a=new Array();
					a=document.getElementsByName('check[]');
					var p=0;
					for(i=0;i<a.length;i++){
						a[i].checked = value;
					}
				}
				//-->
				</script>
				<br><input type='button' name='CheckAll' value='Check All' onClick='setAll(true)'>
				<input type='button' name='UnCheckAll' value='Uncheck All' onClick='setAll(false)'>
			";
		}
		$page->add_block(new Block("Manage Pool", $editor, "left", 10));
	}


	/*
	 * HERE WE DISPLAY THE RESULT OF THE SEARCH ON IMPORT
	 */
	public function pool_result(Page $page, /*array*/ $images, /*int*/ $pool_id) {
		// TODO: this could / should be done using jQuery
		$pool_images = "
			<script language='javascript' type='text/javascript'>
			<!--
			function setAll(value) {
				var a=new Array();
				a=document.getElementsByName('check[]');
				var p=0;
				for(i=0;i<a.length;i++) {
					a[i].checked = value;
				}
			}

			function confirm_action() {
				return confirm('Are you sure you want to add selected posts to this pool?');
			}
			//-->
			</script>
		";

		$pool_images .= "<form action='".make_link("pool/add_posts")."' method='POST' name='checks'>";

		foreach($images as $image) {
			$thumb_html = $this->build_thumb_html($image);

			$pool_images .= '<span class="thumb">'. $thumb_html .'<br>'.
				'<input name="check[]" type="checkbox" value="'.$image->id.'" />'.
				'</span>';
		}
		$pool_images .= "<br>".
			"<input type='submit' name='edit' id='edit_pool_add_btn' value='Add Selected' onclick='return confirm_action()'/>".
			"<input type='hidden' name='pool_id' value='".$pool_id."'>".
			"</form>";

		$page->add_block(new Block("Import", $pool_images, "main", 10));

		$editor = "
			<input type='button' name='CheckAll' value='Check All' onClick='setAll(true)'>
			<input type='button' name='UnCheckAll' value='Uncheck All' onClick='setAll(false)'>
			";

		$page->add_block(new Block("Manage Pool", $editor, "left", 10));
	}


	/*
	 * HERE WE DISPLAY THE POOL ORDERER
	 * WE LIST ALL IMAGES ON POOL WITHOUT PAGINATION AND WITH A TEXT INPUT TO SET A NUMBER AND CHANGE THE ORDER
	 */
	public function edit_order(Page $page, /*array*/ $pools, /*array*/ $images) {
		global $user;

		$this->display_top($pools, "Sorting Pool");

		$pool_images = "\n<form action='".make_link("pool/order")."' method='POST' name='checks'>";
		$n = 0;
		foreach($images as $pair) {
			$image = $pair[0];
			$thumb_html = $this->build_thumb_html($image);
			$pool_images .= '<span class="thumb">'."\n".$thumb_html."\n".
				'<br><input name="imgs['.$n.'][]" type="text" style="max-width:50px;" value="'.$image->image_order.'" />'.
				'<input name="imgs['.$n.'][]" type="hidden" value="'.$image->id.'" />'.
				'</span>';
			$n++;
		}

		$pool_images .= "<br>".
			"<input type='submit' name='edit' id='edit_pool_order' value='Order'/>".
			"<input type='hidden' name='pool_id' value='".$pools[0]['id']."'>".
			"</form>";

		$page->add_block(new Block("Sorting Posts", $pool_images, "main", 30));
	}


	/*
	 * HERE WE DISPLAY THE POOL EDITOR
	 * WE LIST ALL IMAGES ON POOL WITHOUT PAGINATION AND WITH
	 * A CHECKBOX TO SELECT WHICH IMAGE WE WANT TO REMOVE
	 */
	public function edit_pool(Page $page, /*array*/ $pools, /*array*/ $images) {
		global $user;

		$this->display_top($pools, "Editing Pool", true);

		$pool_images = "\n<form action='".make_link("pool/remove_posts")."' method='POST' name='checks'>";

		foreach($images as $pair) {
			$image = $pair[0];

			$thumb_html = $this->build_thumb_html($image);

			$pool_images .= '<span class="thumb">'."\n".$thumb_html."\n".
				'<br><input name="check[]" type="checkbox" value="'.$image->id.'" />'.
				'</span>';
		}

		$pool_images .= "<br>".
			"<input type='submit' name='edit' id='edit_pool_remove_sel' value='Remove Selected'/>".
			"<input type='hidden' name='pool_id' value='".$pools[0]['id']."'>".
			"</form>";

		$page->add_block(new Block("Editing Posts", $pool_images, "main", 30));
	}


	/*
	 * HERE WE DISPLAY THE HISTORY LIST
	 */
	public function show_history($histories, /*int*/ $pageNumber, /*int*/ $totalPages) {
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

		$n = 0;
		foreach($histories as $history) {
			$pool_link = "<a href='".make_link("pool/view/".$history['pool_id'])."'>".html_escape($history['title'])."</a>";
			$user_link = "<a href='".make_link("user/".url_escape($history['user_name']))."'>".html_escape($history['user_name'])."</a>";
			$revert_link = "<a href='".make_link("pool/revert/".$history['id'])."'>Revert</a>";

			if ($history['action'] == 1) {
				$prefix = "+";
			} elseif ($history['action'] == 0) {
				$prefix = "-";
			}

			$images = trim($history['images']);
			$images = explode(" ", $images);

			$image_link = "";
			foreach ($images as $image) {		
				$image_link .= "<a href='".make_link("post/view/".$image)."'>".$prefix.$image." </a>";
			}

			$html .= "<tr>".
				"<td class='left'>".$pool_link."</td>".
				"<td>".$history['count']."</td>".
				"<td>".$image_link."</td>".
				"<td>".$user_link."</td>".
				"<td>".$history['date']."</td>".
				"<td>".$revert_link."</td>".
				"</tr>";
		}

		$html .= "</tbody></table>";

		$page->set_title("Recent Changes");
		$page->set_heading("Recent Changes");
		$page->add_block(new Block("Recent Changes", $html, "main", 10));

		$this->display_paginator($page, "pool/updated", null, $pageNumber, $totalPages);
	}
}
?>
