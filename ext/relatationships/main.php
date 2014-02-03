<?php
/**
 * Name: Post Relationships
 * Author: Angus Johnston <admin@codeanimu.net>
 * License: GPLv2
 * Description: Allow posts to have relationships (parent/child).
 */

class Relationships extends Extension {
	public function onInitExt(InitExtEvent $event) {
		global $config, $database;

		// Create the database tables
		if ($config->get_int("ext_relationships_version") < 1){
			$database->Execute("ALTER TABLE images ADD parent_id INT NULL, ADD INDEX (parent_id);");
			$database->Execute("ALTER TABLE images ADD has_children BOOL DEFAULT FALSE NOT NULL;");

			$config->set_int("ext_relationships_version", 1);
			log_info("relationships", "extension installed");
		}
	}

	public function onImageInfoSet(ImageInfoSetEvent $event) {
        global $user;
		if (isset($_POST["tag_edit__parent"]) ? ctype_digit($_POST["tag_edit__parent"]) : FALSE) {
			$this->set_parent($event->image->id, (int) $_POST["tag_edit__parent"]);
		}
	}

	public function onDisplayingImage(DisplayingImageEvent $event) {
		$this->theme->relationship_info($event->image);
	}

	public function onSearchTermParse(SearchTermParseEvent $event) {
		$matches = array();
		if(preg_match("/^parent[=|:]([0-9]+|any|none)$/", $event->term, $matches)) {
			$parentID = $matches[1];

			if(preg_match("/^(any|none)$/", $parentID)){
				$not = ($parentID == "any" ? "NOT" : "");
				$event->add_querylet(new Querylet("images.parent_id IS $not NULL"));
			}else{
				$event->add_querylet(new Querylet("images.parent_id = :pid", array("pid"=>$parentID)));
			}
		}
		else if(preg_match("/^child[=|:](any|none)$/", $event->term, $matches)) {
			$not = ($matches[1] == "any" ? "=" : "!=");
			$event->add_querylet(new Querylet("images.has_children $not TRUE"));
		}
	}

	public function onTagTermParse(TagTermParseEvent $event) {
		$matches = array();

		if(preg_match("/^parent[=|:]([0-9]+|none)$/", $event->term, $matches)) {
			$parentID = $matches[1];

			if($parentID == "none" || $parentID == "0"){
				$this->remove_parent($event->id);
			}else{
				$this->set_parent($event->id, $parentID);
			}
		}
		else if(preg_match("/^child[=|:]([0-9]+)$/", $event->term, $matches)) {
			$childID = $matches[1];

			$this->set_child($event->id, $childID);
		}

		if(!empty($matches)) $event->metatag = true;
	}

	public function onImageInfoBoxBuilding(ImageInfoBoxBuildingEvent $event) {
		$event->add_part($this->theme->get_parent_editor_html($event->image), 45);
	}

	public function onImageDeletion(ImageDeletionEvent $event) {
		global $database;

		if($event->image->has_children){
			$database->execute("UPDATE images SET parent_id = NULL WHERE parent_id = :iid", array("iid"=>$event->image->id));
		}

		if($event->image->parent_id !== NULL){
			$database->execute("UPDATE images SET has_children = (SELECT * FROM (SELECT CASE WHEN (COUNT(*) - 1) > 0 THEN 1 ELSE 0 END FROM images WHERE parent_id = :pid) AS sub)
								WHERE id = :pid", array("pid"=>$event->image->parent_id));
		}
	}

	private function set_parent(/*int*/ $imageID, /*int*/ $parentID){
		global $database;

		if($database->get_row("SELECT 1 FROM images WHERE id = :pid", array("pid"=>$parentID))){
			$database->execute("UPDATE images SET parent_id = :pid WHERE id = :iid", array("pid"=>$parentID, "iid"=>$imageID));
			$database->execute("UPDATE images SET has_children = TRUE WHERE id = :pid", array("pid"=>$parentID));
		}
	}

	private function set_child(/*int*/ $parentID, /*int*/ $childID){
		global $database;

		if($database->get_row("SELECT 1 FROM images WHERE id = :cid", array("cid"=>$childID))){
			$database->execute("UPDATE images SET parent_id = :pid WHERE id = :cid", array("cid"=>$childID, "pid"=>$parentID));
			$database->execute("UPDATE images SET has_children = TRUE WHERE id = :pid", array("pid"=>$parentID));
		}
	}

	private function remove_parent(/*int*/ $imageID){
		global $database;
		$parentID = $database->get_one("SELECT parent_id FROM images WHERE id = :iid", array("iid"=>$imageID));

		if($parentID){
			$database->execute("UPDATE images SET parent_id = NULL WHERE id = :iid", array("iid"=>$imageID));
			$database->execute("UPDATE images SET has_children = (SELECT * FROM (SELECT CASE WHEN COUNT(*) > 0 THEN 1 ELSE 0 END FROM images WHERE parent_id = :pid) AS sub)
								WHERE id = :pid", array("pid"=>$parentID));
		}
	}
}
?>
