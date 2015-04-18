<?php

class RelationshipsTheme extends Themelet {
	/**
	 * @param \Image $image
	 */
	public function relationship_info($image) {
		global $page, $database;

		if($image->parent_id !== NULL){
			$a = "<a href='".make_link("post/view/".$image->parent_id)."'>parent post</a>";
			$page->add_block(new Block(null, "This post belongs to a $a.", "main", 5));
		}

		if($image->has_children == TRUE){
			$ids = $database->get_col("SELECT id FROM images WHERE parent_id = :iid", array("iid"=>$image->id));

			$html = "This post has <a href='".make_link('post/list/parent='.$image->id.'/1')."'>".(count($ids) > 1 ? "child posts" : "a child post")."</a>";
			$html .= " (post ";
			foreach($ids as $id){
				$html .= "#<a href='".make_link('post/view/'.$id)."'>{$id}</a>, ";
			}
			$html = rtrim($html, ", ").").";

			$page->add_block(new Block(null, $html, "main", 6));
		}
	}

	public function get_parent_editor_html(Image $image) {
		global $user;

		$h_parent_id = $image->parent_id;
		$s_parent_id = $h_parent_id ?: "None.";

		$html = "<tr>\n".
		        "	<th>Parent</th>\n".
		        "	<td>\n".
		        (!$user->is_anonymous() ?
		            "		<span class='view' style='overflow: hidden; white-space: nowrap;'>{$s_parent_id}</span>\n".
		            "		<input class='edit' type='number' name='tag_edit__parent' type='number' value='{$h_parent_id}'>\n"
		        :
		            $s_parent_id
		        ).
		        "	<td>\n".
		        "</tr>\n";
		return $html;
	}
}

