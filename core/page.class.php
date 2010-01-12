<?php
/**
 * \page themes Themes
 * 
 * Each extension has a theme with a specific name -- eg. the extension Setup
 * which is stored in ext/setup/main.php will have a theme called SetupTheme
 * stored in ext/setup/theme.php. If you want to customise it, create a class
 * in the file themes/mytheme/setup.theme.php called CustomSetupTheme which
 * extends SetupTheme and overrides some of its methods.
 * 
 * Generally an extension should only deal with processing data; whenever it
 * wants to display something, it should pass the data to be displayed to the
 * theme object, and the theme will add the data into the global $page
 * structure.
 *
 * A page should make sure that all the data it outputs is free from dangerous
 * data by using html_escape(), url_escape(), or int_escape() as appropriate.
 *
 * Because some HTML can be placed anywhere according to the theme, coming up
 * with the correct way to link to a page can be hard -- thus we have the
 * make_link() function, which will take a path like "post/list" and turn it
 * into a full and correct link, eg /myboard/post/list, /foo/index.php?q=post/list,
 * etc depending on how things are set up. This should always be used to link
 * to pages rather than hardcoding a path.
 *
 * Various other common functions are available as part of the Themelet class.
 */


/**
 * A data structure for holding all the bits of data that make up a page.
 *
 * The various extensions all add whatever they want to this structure,
 * then Layout turns it into HTML
 */
class Page {
	/** @name Overall */
	//@{
	/** @private */
	var $mode = "page";
	/** @private */
	var $type = "text/html";

	/**
	 * Set what this page should do; "page", "data", or "redirect".
	 */
	public function set_mode($mode) {
		$this->mode = $mode;
	}

	/**
	 * Set the page's MIME type
	 */
	public function set_type($type) {
		$this->type = $type;
	}


	//@}
	// ==============================================
	/** @name "data" mode */
	//@{

	/** @private */
	var $data = "";
	/** @private */
	var $filename = null;

	/**
	 * Set the raw data to be sent
	 */
	public function set_data($data) {
		$this->data = $data;
	}

	/**
	 * Set the recommended download filename
	 */
	public function set_filename($filename) {
		$this->filename = $filename;
	}


	//@}
	// ==============================================
	/** @name "redirect" mode */
	//@{

	/** @private */
	var $redirect = "";

	/**
	 * Set the URL to redirect to (remember to use make_link() if linking
	 * to a page in the same site)
	 */
	public function set_redirect($redirect) {
		$this->redirect = $redirect;
	}


	//@}
	// ==============================================
	/** @name "page" mode */
	//@{

	/** @privatesection */
	var $title = "";
	var $heading = "";
	var $subheading = "";
	var $quicknav = "";
	var $headers = array();
	var $blocks = array();
	/** @publicsection */

	/**
	 * Set the window title
	 */
	public function set_title($title) {
		$this->title = $title;
	}

	/**
	 * Set the main heading
	 */
	public function set_heading($heading) {
		$this->heading = $heading;
	}

	/**
	 * Set the sub heading
	 */
	public function set_subheading($subheading) {
		$this->subheading = $subheading;
	}

	/**
	 * Add a line to the HTML head section
	 */
	public function add_header($line, $position=50) {
		while(isset($this->headers[$position])) $position++;
		$this->headers[$position] = $line;
	}

	/**
	 * Add a Block of data
	 */
	public function add_block(Block $block) {
		$this->blocks[] = $block;
	}


	//@}
	// ==============================================

	/**
	 * Display the page according to the mode and data given
	 */
	public function display() {
		global $page;

		header("Content-type: {$this->type}");
		header("X-Powered-By: SCore-".SCORE_VERSION);

		switch($this->mode) {
			case "page":
				header("Cache-control: no-cache");
				usort($this->blocks, "blockcmp");
				$this->add_auto_headers();
				$layout = new Layout();
				$layout->display_page($page);
				break;
			case "data":
				header("Content-Length: ".strlen($this->data));
				if(!is_null($this->filename)) {
					header('Content-Disposition: attachment; filename='.$this->filename);
				}
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

	protected function add_auto_headers() {
		$data_href = get_base_href();
		foreach(glob("lib/*.js") as $js) {
			$this->add_header("<script src='$data_href/$js' type='text/javascript'></script>");
		}

		$css_files = glob("ext/*/style.css");
		if($css_files) {
			foreach($css_files as $css_file) {
				$this->add_header("<link rel='stylesheet' href='$data_href/$css_file' type='text/css'>");
			}
		}

		$js_files = glob("ext/*/script.js");
		if($js_files) {
			foreach($js_files as $js_file) {
				$this->add_header("<script src='$data_href/$js_file' type='text/javascript'></script>");
			}
		}
	}
}
?>
