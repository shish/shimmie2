<?php
class SimpleSCoreTestTheme extends Themelet {
}

class SCoreReporter extends HtmlReporter {
	var $current_html = "";
	var $clear_modules = "";
	var $page;

	public function SCoreReporter($page) {
		$this->page = $page;
		$this->_fails = 0;
	}

	function paintHeader($test_name) {
		// nowt
		//parent::paintHeader($test_name);
	}

	function paintFooter($test_name) {
		//parent::paintFooter($test_name);
		$fail = $this->getFailCount() > 0;
		if($fail) {
			$style = "background: red;";
		}
		else {
			$style = "background: green;";
		}
		$html = "<div style=\"padding: 4px; $style\">".
			$this->getPassCount() . " passes, " .
			$this->getFailCount() . " failures" .
			"<br>Passed modules: " . $this->clear_modules .
			"</div>";
		$this->page->add_block(new Block("Results", $html, "main", 40));
	}
	
	function paintGroupStart($name, $size) {
		parent::paintGroupStart($name, $size);
		$this->current_html = "";
	}

	function paintGroupEnd($name) {
		$matches = array();
		if(preg_match("#ext/(.*)/test.php#", $name, $matches)) {
			$name = $matches[1];
			$link = "<a href=\"".make_link("test/$name")."\"></a>";
		}
		parent::paintGroupEnd($name);
		if($this->current_html == "") {
			$this->clear_modules .= "$name, ";
		}
		else {
			$this->current_html .= "<p>$link";
			$this->page->add_block(new Block($name, $this->current_html, "main", 50));
			$this->current_html = "";
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
