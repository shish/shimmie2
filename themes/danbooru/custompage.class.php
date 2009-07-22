<?php

class CustomPage extends Page {
	var $left_enabled = true;
	public function disable_left() {
		$this->left_enabled = false;
	}
}
?>
