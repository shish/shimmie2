<?php
/*
 * Event:
 * generic parent class
 */
class Event {
	var $vetoed = false;
	var $veto_reason;

	public function veto($reason="") {
		$this->vetoed = true;
		$this->veto_reason = $reason;
	}
}
?>
