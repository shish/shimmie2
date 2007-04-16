<?php
/*
 * PageRequestEvent:
 *   $page
 *   $args
 *   get_arg(int)
 *   count_args()
 *	
 * User requests /view/42 -> an event is generated with
 * $page="view" and $args=array("42");
 *
 * Used for initial page generation triggers
 */
class PageRequestEvent extends Event {
	var $page;
	var $args;

	public function PageRequestEvent($page, $args) {
		$this->page = $page;
		$this->args = $args;
	}

	public function get_arg($n) {
		return isset($this->args[$n]) ? $this->args[$n] : null;
	}

	public function count_args() {
		return isset($this->args) ? count($this->args) : 0;
	}
}
?>
