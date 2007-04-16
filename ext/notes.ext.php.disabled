<?php

class Notes extends Extension {
	public function receive_event($event) {
		if(is_a($event, 'InitExtEvent')) {
			global $config;
			if($config->get_int("ext_notes_version") < 1) {
				$this->install();
			}
		}

		if(is_a($event, 'DisplayingImageEvent')) {
			global $page;
			$page->add_main_block(new Block(null, $this->make_notes($event->image->id)));
		}
	}

	protected function install() {
		global $database;
		global $config;
		$database->db->Execute("CREATE TABLE `image_notes` (
			`id` int(11) NOT NULL auto_increment,
			`image_id` int(11) NOT NULL,
			`user_id` int(11) NOT NULL,
			`owner_ip` char(15) NOT NULL,
			`created_at` datetime NOT NULL,
			`updated_at` datetime NOT NULL,
			`version` int(11) DEFAULT 1 NOT NULL,
			`is_active` enum('Y', 'N') DEFAULT 'Y' NOT NULL,
			`x` int(11) NOT NULL,
			`y` int(11) NOT NULL,
			`w` int(11) NOT NULL,
			`h` int(11) NOT NULL,
			`body` text NOT NULL,
			PRIMARY KEY  (`id`)
		)");
		$config->set_int("ext_notes_version", 1);
	}

	private function make_notes($image_id) {
		global $database;
		$notes = $database->db->GetAll("SELECT * FROM image_notes WHERE image_id = ?", array($image_id));
		
		return <<<EOD
<script type="text/javascript">
img = byId("main_image");
</script>
EOD;
	}
}
add_event_listener(new Notes());
?>
