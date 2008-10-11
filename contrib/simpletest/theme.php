<?php
class SimpleSCoreTestTheme extends Themelet {
}

class SCoreReporter extends HtmlReporter {
	var $current_html = "";
	var $clear_modules = "";
	var $page;

	public function SCoreReporter($page) {
		$this->page = $page;
	}

	function paintHeader($test_name) {
		// nowt
		//parent::paintHeader($test_name);
	}

	function paintFooter($test_name) {
		//parent::paintFooter($test_name);
		$html = "".
			$this->getPassCount() . " passes, " .
			$this->getFailCount() . " failures" .
			"<br>Passed modules: " . $this->clear_modules;
		$this->page->add_block(new Block("Results", $html, "main", 40));
	}
	
	function paintGroupStart($name, $size) {
		parent::paintGroupStart($name, $size);
		$this->current_html = "";
	}

	function paintGroupEnd($name) {
		$name = substr($name, 4, strlen($name)-13);
		parent::paintGroupEnd($name);
		if($this->current_html == "") {
			$this->clear_modules .= "$name, ";
		}
		else {
			$this->page->add_block(new Block($name, $this->current_html, "main", 50));
		}
	}

	function paintFail($message) {
		//parent::paintFail($message);
		$this->_fails++; // manually do the grandparent behaviour

		$message = str_replace(getcwd(), "...", $message);
		$this->current_html .= "<p style='text-align: left;'><b>Fail</b>: $message";
	}
}
?>
