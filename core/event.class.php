<?php
/*
 * Event:
 * generic parent class
 */
class Event {
	var $_live = true;

	public function veto() {
		$this->_live = false;
	}
}
?>
