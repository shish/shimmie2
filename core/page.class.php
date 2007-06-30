<?php
class GenericPage {
	var $mode = "page";
	var $type = "text/html";

	public function set_mode($mode) {
		$this->mode = $mode;
	}
	
	public function set_type($type) {
		$this->type = $type;
	}

	
	// ==============================================

	// data
	var $data = "";

	public function set_data($data) {
		$this->data = $data;
	}


	// ==============================================

	// redirect
	var $redirect = "";

	public function set_redirect($redirect) {
		$this->redirect = $redirect;
	}


	// ==============================================

	// page
	var $title = "";
	var $heading = "";
	var $subheading = "";
	var $quicknav = "";
	var $headers = array();
	var $blocks = array();

	public function set_title($title) {
		$this->title = $title;
	}

	public function set_heading($heading) {
		$this->heading = $heading;
	}

	public function set_subheading($subheading) {
		$this->subheading = $subheading;
	}

	public function add_header($line, $position=50) {
		while(isset($this->headers[$position])) $position++;
		$this->headers[$position] = $line;
	}

	public function add_block($block) {
		$this->blocks[] = $block;
	}

	// ==============================================
	
	public function display() {
		global $config;

		header("Content-type: {$this->type}");

		switch($this->mode) {
			case "page":
				header("Cache-control: no-cache");
				usort($this->blocks, "blockcmp");
				$layout = new Layout();
				$layout->display_page($this);
				break;
			case "data":
				print $this->data;
				break;
			case "redirect":
				header("Location: {$this->redirect}");
				print "You should be redirected to <a href='{$this->redirect}'>{$this->redirect}</a>";
				break;
			default:
				print "Invalid page mode";
				break;
		}
	}
}
?>
