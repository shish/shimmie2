<?php
class PoolsTheme extends Themelet {

	/*
	* HERE WE ADD THE POOL INFO ON IMAGE
	*/
	public function pool_info($linksPools){
		global $config, $page;
		$editor = 'This post belongs to the '.$linksPools.' pool.';
		
		if($config->get_bool("poolsInfoOnViewImage")){
			if($linksPools <> " "){
				$page->add_block(new Block("Pool Info", $editor, "main", 1));
			}
		}
	}



	/*
	* HERE WE SHOWS THE LIST OF POOLS
	*/
	public function list_pools(Page $page, $pools, $pageNumber, $totalPages)
    {
		global $user;
        
		$html = '<table id="poolsList" class="zebra">'.
			"<thead><tr>".
            "<th>Name</th>".
			"<th>Creator</th>".
            "<th>Posts</th>".
			"<th>Public</th>";
		
		if($user->is_admin()){
		$html .= "<th>Action</th>";
		}
		$html .= "</tr></thead>";
			
		$n = 0;
        foreach ($pools as $pool)
        {
		$oe = ($n++ % 2 == 0) ? "even" : "odd";
		
		$pool_link = '<a href="'.make_link("pool/view/".$pool['id']).'">'.$pool['title']."</a>";
		$user_link = '<a href="'.make_link("user/".$pool['user_name']).'">'.$pool['user_name']."</a>";
		$del_link = '<a href="'.make_link("pool/nuke/".$pool['id']).'">Delete</a>';
		
		if($pool['public'] == "Y"){
		$public = "Yes";
		} elseif($pool['public'] == "N"){
		$public = "No";
		}
		
		$html .= "<tr class='$oe'>".
                "<td class='left'>".$pool_link."</td>".
                "<td>".$user_link."</td>".
				"<td>".$pool['posts']."</td>".
                "<td>".$public."</td>";
				
		if($user->is_admin()){
		$html .= "<td>".$del_link."</td>";
		}
		
		$html .= "</tr>";
		
		}
		
		$html .= "</tbody></table>";

        $blockTitle = "Pools";
		$page->set_title(html_escape($blockTitle));
		$page->set_heading(html_escape($blockTitle));
        $page->add_block(new Block($blockTitle, $html, "main", 10));
		
		$this->display_paginator($page, "pool/list", null, $pageNumber, $totalPages);
    }



	/*
	* HERE WE DISPLAY THE NEW POOL COMPOSER
	*/
	public function new_pool_composer(Page $page)
    {
        $html = "<form action=".make_link("pool/create")." method='POST'>
				<table>
					<tr><td>Title:</td><td><input type='text' name='title'></td></tr>
					<tr><td>Public?</td><td><input name='public' type='checkbox' value='Y' checked='checked'/></td></tr>
					<tr><td>Description:</td><td><textarea name='description'></textarea></td></tr>
					<tr><td colspan='2'><input type='submit' value='Submit' /></td></tr>
				</table>
			";

        $blockTitle = "Create Pool";
		$page->set_title(html_escape($blockTitle));
		$page->set_heading(html_escape($blockTitle));
        $page->add_block(new Block($blockTitle, $html, "main", 10));
    }
	
	
	
	/*
	* HERE WE DISPLAY THE POOL WITH TITLE DESCRIPTION AND IMAGES WITH PAGINATION
	*/
	public function view_pool($pools, $images, $pageNumber, $totalPages)
        {
		global $user, $page;
		
                $pool_info = "<table id='poolsList' class='zebra'>".
			"<thead><tr>".
                        "<th class='left'>Title</th>".
			"<th class='left'>Description</th>".
			"</tr></thead>";
		
		$n = 0;
		foreach ($pools as $pool)
                {
                    $oe = ($n++ % 2 == 0) ? "even" : "odd";

                    $pool_info .= "<tr class='$oe'>".
                    "<td class='left'>".$pool['title']."</td>".
                    "<td class='left'>".$pool['description']."</td>".
                                    "</tr>";

                    // this will make disasters if more than one pool comes in the parameter
                    if($pool['public'] == "Y" || $user->is_admin()){// IF THE POOL IS PUBLIC OR IS ADMIN SHOW EDIT PANEL
                            if(!$user->is_anonymous()){// IF THE USER IS REGISTERED AND LOGGED IN SHOW EDIT PANEL
                                    $this->sidebar_options($page, $pool);
                            }
                    }
                    $this->display_paginator($page, "pool/view/".$pool['id']."", null, $pageNumber, $totalPages);
		}

		$pool_info .= "</tbody></table>";

		$page->set_title("Viewing Pool");
		$page->set_heading("Viewing Pool");
                $page->add_block(new Block("Viewing Pool", $pool_info, "main", 10));
				            
		$pool_images = '';
		foreach($images as $pair) {
			$image = $pair[0];

			$thumb_html = $this->build_thumb_html($image);

			$pool_images .= '<span class="thumb">'.
					   		'<a href="$image_link">'.$thumb_html.'</a>'.
					   		'</span>';
		}
			
			//$pool_images .= print_r($images);
		$page->add_block(new Block("Viewing Posts", $pool_images, "main", 30));		
    }
	
	
	
	/*
	* HERE WE DISPLAY THE POOL OPTIONS ON SIDEBAR BUT WE HIDE REMOVE OPTION IF THE USER IS NOT THE OWNER OR ADMIN
	*/
	public function sidebar_options(Page $page, $pool){
		global $user;
		
		$editor = " <form action='".make_link("pool/import")."' method='POST'>
						<input type='text' name='pool_tag' id='edit' value='Please enter a tag' onclick='this.value=\"\";'/>
						<input type='submit' name='edit' id='edit' value='Import'/>
						<input type='hidden' name='pool_id' value='".$pool['id']."'>
					</form>
					
					<form id='form1' name='form1' method='post' action='".make_link("pool/edit_pool")."'>
						<input type='submit' name='edit' id='edit' value='Edit Pool'/>
						<input type='hidden' name='pool_id' value='".$pool['id']."'>
					</form>
					
					<form id='form1' name='form1' method='post' action='".make_link("pool/edit_order")."'>
						<input type='submit' name='edit' id='edit' value='Order Pool'/>
						<input type='hidden' name='pool_id' value='".$pool['id']."'>
					</form>
					";
		
		if($user->id == $pool['user_id'] || $user->is_admin()){
			$editor .= "
					<script type='text/javascript'>
						function confirm_action() {
							return confirm('Are you sure that you want to delete this pool?');
						}
					</script>
					
					<form action='".make_link("pool/nuke_pool")."' method='POST'>
					  	<input type='submit' name='delete' id='delete' value='Delete Pool' onclick='return confirm_action()' />
						<input type='hidden' name='pool_id' value='".$pool['id']."'>
					</form>
					";
		}
		$page->add_block(new Block("Manage Pool", $editor, "left", 10));
	}
	
	
	
	/*
	* HERE WE DISPLAY THE RESULT OF THE SEARCH ON IMPORT
	*/
	public function pool_result(Page $page, $images, $pool_id){

		$pool_images = "
		<script language='JavaScript' type='text/javascript'>
			
		function checkAll()
		{
		var a=new Array();
			a=document.getElementsByName('check[]');
			var p=0;
			for(i=0;i<a.length;i++){
				a[i].checked = true ;
			}
		}
				
		function uncheckAll()
		{
		var a=new Array();
			a=document.getElementsByName('check[]');
			var p=0;
			for(i=0;i<a.length;i++){
				a[i].checked = false ;
			}
		}
		</script>
		
		<script type='text/javascript'>
			function confirm_action() {
				return confirm('Are you sure you want to add selected posts to this pool?');
			}
		</script>
		
		";
		
		$pool_images .= "<form action='".make_link("pool/add_posts")."' method='POST' name='checks'>";
		
		foreach($images as $image) {
										
				$thumb_html = $this->build_thumb_html($image);
				
				$pool_images .= '<span class="thumb">'.
					   		'<a href="$image_link">'.$thumb_html.'</a>'.
							'<br>'.
							'<input name="check[]" type="checkbox" value="'.$image->id.'" />'.
					   '</span>';
		}
		$pool_images .= "<br>".
						"<input type='submit' name='edit' id='edit' value='Add Selected' onclick='return confirm_action()'/>".
						"<input type='hidden' name='pool_id' value='".$pool_id."'>".
						"</form>";
		
		$page->add_block(new Block("Import", $pool_images, "main", 10));
		
			$editor = "
						<input type='button' name='CheckAll' value='Check All' onClick='checkAll()'>
						<input type='button' name='UnCheckAll' value='Uncheck All' onClick='uncheckAll()'>
						";
		
		$page->add_block(new Block("Manage Pool", $editor, "left", 10));
	}
		
	
	
	/*
	* HERE WE DISPLAY THE POOL ORDERER
	* WE LIST ALL IMAGES ON POOL WITHOUT PAGINATION AND WITH A TEXT INPUT TO SET A NUMBER AND CHANGE THE ORDER
	*/
	public function edit_order(Page $page, $pools, $images)
    {
		global $user;
		
        $pool_info = "<table id='poolsList' class='zebra'>".
			"<thead><tr>".
            "<th class='left'>Title</th>".
			"<th class='left'>Description</th>".
			"</tr></thead>";
		
		$n = 0;
		
		foreach ($pools as $pool)
        {
		$oe = ($n++ % 2 == 0) ? "even" : "odd";
						
		$pool_info .= "<tr class='$oe'>".
                "<td class='left'>".$pool['title']."</td>".
                "<td class='left'>".$pool['description']."</td>".
				"</tr>";
		
		}
		$pool_info .= "</tbody></table>";

		$page->set_title("Sorting Pool");
		$page->set_heading("Sorting Pool");
        $page->add_block(new Block("Sorting Pool", $pool_info, "main", 10));
				
		$pool_images = "<form action='".make_link("pool/order_posts")."' method='POST' name='checks'>";
		$n = 0;
		foreach($images as $pair) {
			$image = $pair[0];

			$thumb_html = $this->build_thumb_html($image);

			$pool_images .= '<span class="thumb">'.
					   		'<a href="$image_link">'.$thumb_html.'</a>';
										

					$pool_images .= '<br><input name="imgs['.$n.'][]" type="text" width="50px" value="'.$image->image_order.'" />'.
									'<input name="imgs['.$n.'][]" type="hidden" value="'.$image->id.'" />';
		$n = $n+1;
			
			$pool_images .= '</span>';
		}
		
			$pool_images .= "<br>".
						"<input type='submit' name='edit' id='edit' value='Order'/>".
						"<input type='hidden' name='pool_id' value='".$pool['id']."'>".
						"</form>";
		
		$page->add_block(new Block("Sorting Posts", $pool_images, "main", 30));
	}
	
	
	
	/*
	* HERE WE DISPLAY THE POOL EDITOR
	* WE LIST ALL IMAGES ON POOL WITHOUT PAGINATION AND WITH A CHECKBOX TO SELECT WHICH IMAGE WE WANT REMOVE
	*/
	public function edit_pool(Page $page, $pools, $images)
    {
		global $user;
		
        $pool_info = "<table id='poolsList' class='zebra'>".
			"<thead><tr>".
            "<th class='left'>Title</th>".
			"<th class='left'>Description</th>".
			"</tr></thead>";
		
		$n = 0;
		
		foreach ($pools as $pool)
        {
		$oe = ($n++ % 2 == 0) ? "even" : "odd";
						
		$pool_info .= "<tr class='$oe'>".
                "<td class='left'>".$pool['title']."</td>".
                "<td class='left'>".$pool['description']."</td>".
				"</tr>";
		
		}
		$pool_info .= "</tbody></table>";

		$page->set_title("Editing Pool");
		$page->set_heading("Editing Pool");
        $page->add_block(new Block("Editing Pool", $pool_info, "main", 10));
		
		
		$pool_images = "
		<script language='JavaScript' type='text/javascript'>
			
		function checkAll()
		{
		var a=new Array();
			a=document.getElementsByName('check[]');
			var p=0;
			for(i=0;i<a.length;i++){
				a[i].checked = true ;
			}
		}
				
		function uncheckAll()
		{
		var a=new Array();
			a=document.getElementsByName('check[]');
			var p=0;
			for(i=0;i<a.length;i++){
				a[i].checked = false ;
			}
		}
		</script>
		
		";
		
		$pool_images .= "<form action='".make_link("pool/remove_posts")."' method='POST' name='checks'>";
		
		foreach($images as $pair) {
			$image = $pair[0];

			$thumb_html = $this->build_thumb_html($image);

			$pool_images .= '<span class="thumb">'.
					   		'<a href="$image_link">'.$thumb_html.'</a>';
										

					$pool_images .= '<br><input name="check[]" type="checkbox" value="'.$image->id.'" />';

			
			$pool_images .= '</span>';
		}
		
			$pool_images .= "<br>".
						"<input type='submit' name='edit' id='edit' value='Remove Selected'/>".
						"<input type='hidden' name='pool_id' value='".$pool['id']."'>".
						"</form>";
		
		$page->add_block(new Block("Editing Posts", $pool_images, "main", 30));
		
		$editor = "
					<input type='button' name='CheckAll' value='Check All' onClick='checkAll()'>
					<input type='button' name='UnCheckAll' value='Uncheck All' onClick='uncheckAll()'>";
				
		$page->add_block(new Block("Manage Pool", $editor, "left", 10));
    }
	
	
	
	/*
	* HERE WE DISPLAY THE HISTORY LIST
	*/
	public function show_history($histories, $pageNumber, $totalPages){
		global $page;
		$html = "<table id='poolsList' class='zebra'>".
			"<thead><tr>".
            "<th>Pool</th>".
			"<th>Post Count</th>".
            "<th>Changes</th>".
			"<th>Updater</th>".
			"<th>Date</th>".
			"<th>Action</th>".
			"</tr></thead>";
			
		$n = 0;
        foreach ($histories as $history)
        {
		$oe = ($n++ % 2 == 0) ? "even" : "odd";
		
		$pool_link = "<a href='".make_link("pool/view/".$history['pool_id'])."'>".$history['title']."</a>";
		$user_link = "<a href='".make_link("user/".$history['user_name'])."'>".$history['user_name']."</a>";
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
				
		$html .= "<tr class='$oe'>".
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
	
	
	
	/*
	* HERE WE DISPLAY THE ERROR
	*/
	public function display_error($errMessage){
		global $page;
		
		$page->set_title("Error");
		$page->set_heading("Error");
		$page->add_block(new Block("Error", $errMessage, "main", 10));
	}
	
}
?>