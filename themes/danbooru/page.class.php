<?php

class Page extends GenericPage {
	var $left_enabled = true;
	public function disable_left() {
		$this->left_enabled = false;
	}
}
?>
