<?php
class SimpleSCoreTestTheme extends Themelet {
}

/** @private */
class SCoreWebReporter extends HtmlReporter {
	var $current_html = "";
	var $clear_modules = array();
	var $page;
	var $fails;
	var $exceptions;

	public function __construct(Page $page) {
		$this->page = $page;
		$this->fails = 0;
		$this->exceptions = 0;
	}

	function paintHeader($test_name) {
		// nowt
		//parent::paintHeader($test_name);
	}

	function paintFooter($test_name) {
		global $page;
		
		//parent::paintFooter($test_name);
		if(($this->fails + $this->exceptions) > 0) {
			$style = "background: red;";
		}
		else {
			$style = "background: green;";
		}
		$html = "<div style=\"padding: 4px; $style\">".
			$this->getPassCount() . " passes, " .
			$this->fails . " failures, " .
			$this->exceptions . " exceptions" .
			"<br>Passed modules: " . implode(", ", $this->clear_modules) .
			"</div>";
			$page->add_block(new Block("Results", $html, "main", 40));
	}

	function paintGroupStart($name, $size) {
		parent::paintGroupStart($name, $size);
		$this->current_html = "";
	}

	function paintGroupEnd($name) {
		global $page;
            
		$matches = array();
		if(preg_match("#ext/(.*)/test.php#", $name, $matches)) {
			$name = $matches[1];
			$link = "<a href=\"".make_link("test/$name")."\">Test only this extension</a>";
		}
		parent::paintGroupEnd($name);
		if($this->current_html == "") {
			$this->clear_modules[] = $name;
		}
		else {
			$this->current_html .= "<p>$link";
			$page->add_block(new Block($name, $this->current_html, "main", 50));
			$this->current_html = "";
		}
	}

	function paintFail($message) {
		//parent::paintFail($message);
		$this->fails++; // manually do the grandparent behaviour

		$message = str_replace(getcwd(), "...", $message);
		$this->current_html .= "<p style='text-align: left;'><b>Fail</b>: $message";
	}

	function paintException($message) {
		//parent::paintFail($message);
		$this->exceptions++; // manually do the grandparent behaviour

		$message = str_replace(getcwd(), "...", $message);
		$this->current_html .= "<p style='text-align: left;'><b>Exception</b>: $message";
	}
}

/** @private */
class SCoreCLIReporter extends TextReporter {
}

