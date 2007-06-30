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
	var $page_object;

	public function PageRequestEvent($page, $args, $page_object) {
		$this->page = $page;
		$this->args = $args;
		$this->page_object = $page_object;
	}

	public function get_arg($n) {
		return isset($this->args[$n]) ? $this->args[$n] : null;
	}

	public function count_args() {
		return isset($this->args) ? count($this->args) : 0;
	}
}
?>
