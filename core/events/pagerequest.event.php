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
	var $page_name;
	var $args;
	var $page;
	var $user;

	public function PageRequestEvent($page_name, $args, $page, $user) {
		$this->page_name = $page_name;
		$this->args = $args;
		$this->page = $page;
		$this->user = $user;
	}

	public function get_arg($n) {
		return isset($this->args[$n]) ? $this->args[$n] : null;
	}

	public function count_args() {
		return isset($this->args) ? count($this->args) : 0;
	}
}
?>
