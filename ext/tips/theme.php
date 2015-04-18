<?php
class TipsTheme extends Themelet {
	public function manageTips($url, $images) {
		global $page, $user;
		$select = "<select name='image'><option value=''>- Select Image -</option>";

		foreach($images as $image){
			$select .= "<option style='background-image:url(".$url.$image."); background-repeat:no-repeat; padding-left:20px;'  value=\"".$image."\">".$image."</option>\n";
		}

		$select .= "</select>";

		$html = "
".make_form("tips/save", "POST", array(), TRUE)."
<table>
  <tr>
    <td>Enable:</td>
    <td><input name='enable' type='checkbox' value='Y' checked/></td>
  </tr>
  <tr>
    <td>Image:</td>
    <td>{$select}</td>
  </tr>
  <tr>
    <td>Message:</td>
    <td><textarea name='text'></textarea></td>
  </tr>
  <tr>
    <td colspan='2'><input type='submit' value='Submit' /></td>
  </tr>
</table>
</form>
";

		$page->set_title("Tips List");
		$page->set_heading("Tips List");
		$page->add_block(new NavBlock());
		$page->add_block(new Block("Add Tip", $html, "main", 10));
	}

	public function showTip($url, $tip) {
		global $page;

		$img = "";
		if(!empty($tip['image'])) {
			$img = "<img src=".$url.$tip['image']." /> ";
		}
		$html = "<div id='tips'>".$img.$tip['text']."</div>";
		$page->add_block(new Block(null, $html, "subheading", 10));
	}

	public function showAll($url, $tips){
		global $user, $page;

		$html = "<table id='poolsList' class='zebra'>".
			"<thead><tr>".
			"<th>ID</th>".
			"<th>Enabled</th>".
			"<th>Image</th>".
			"<th>Text</th>";

		if($user->is_admin()){
			$html .= "<th>Action</th>";
		}	

		$html .= "</tr></thead>";

		foreach ($tips as $tip) {
			$tip_enable = ($tip['enable'] == "Y") ? "Yes" : "No";
			$set_link = "<a href='".make_link("tips/status/".$tip['id'])."'>".$tip_enable."</a>";

			$html .= "<tr>".
				"<td>".$tip['id']."</td>".
				"<td>".$set_link."</td>".
				(
				empty($tip['image']) ?
					"<td></td>" :
					"<td><img src=".$url.$tip['image']." /></td>"
				).
				"<td class='left'>".$tip['text']."</td>";

			$del_link = "<a href='".make_link("tips/delete/".$tip['id'])."'>Delete</a>";

			if($user->is_admin()){
				$html .= "<td>".$del_link."</td>";
			}

			$html .= "</tr>";
		}
		$html .= "</tbody></table>";

		$page->add_block(new Block("All Tips", $html, "main", 20));
	}
}

