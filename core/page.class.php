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
	var $type = "text/html; charset=utf-8";

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
	var $html_headers = array();
	var $http_headers = array();
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
	public function add_html_header($line, $position=50) {
		while(isset($this->html_headers[$position])) $position++;
		$this->html_headers[$position] = $line;
	}
	
	/**
	 * Add a http header to be sent to the client.
	 */
	public function add_http_header($line, $position=50) {
		while(isset($this->http_headers[$position])) $position++;
		$this->http_headers[$position] = $line;
	}
	
	/**
	 * Get all the HTML headers that are currently set and return as a string.
	 */
	public function get_all_html_headers() {
		$data = '';
		foreach ($this->html_headers as $line) {
			$data .= $line . "\n";
		}
		return $data;
	}
	
	/**
	 * Removes all currently set HTML headers. (Be careful..)
	 */
	public function delete_all_html_headers() {
		$this->html_headers = array();
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
		global $page, $user;
		
		header("Content-type: ".$this->type);
		header("X-Powered-By: SCore-".SCORE_VERSION);

		if (!headers_sent()) {
			foreach($this->http_headers as $head){ header($head); }
		} else {
			print "Error: Headers have already been sent to the client.";
		}

		switch($this->mode) {
			case "page":
				if(CACHE_HTTP) {
					header("Vary: Cookie, Accept-Encoding");
					if($user->is_anonymous() && $_SERVER["REQUEST_METHOD"] == "GET") {
						header("Cache-control: public, max-age=600");
						header('Expires: ' . gmdate('D, d M Y H:i:s', time() + 600) . ' GMT');
					}
					else {
						#header("Cache-control: private, max-age=0");
						header("Cache-control: no-cache");
						header('Expires: ' . gmdate('D, d M Y H:i:s', time() - 600) . ' GMT');
					}
				}
				#else {
				#	header("Cache-control: no-cache");
				#	header('Expires: ' . gmdate('D, d M Y H:i:s', time() - 600) . ' GMT');
				#}
				usort($this->blocks, "blockcmp");
				$this->add_auto_html_headers();
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
				header('Location: '.$this->redirect);
				print 'You should be redirected to <a href="'.$this->redirect.'">'.$this->redirect.'</a>';
				break;
			default:
				print "Invalid page mode";
				break;
		}
	}
	
	protected function add_auto_html_headers() {
		global $config;

		$data_href = get_base_href();
		$theme_name = $config->get_string('theme', 'default');

		$this->add_html_header("<script type='text/javascript'>base_href = '$data_href';</script>");

		# 404/static handler will map these to themes/foo/bar.ico or lib/static/bar.ico
		$this->add_html_header("<link rel='icon' type='image/x-icon' href='$data_href/favicon.ico'>");
		$this->add_html_header("<link rel='apple-touch-icon' href='$data_href/apple-touch-icon.png'>");

		if(!file_exists("data/cache")) {
			mkdir("data/cache");
		}
		
		$css_files = array();
		$css_latest = 0;
		foreach(array_merge(zglob("lib/*.css"), zglob("ext/*/style.css"), zglob("themes/$theme_name/style.css")) as $css) {
			$css_files[] = $css;
			$css_latest = max($css_latest, filemtime($css));
		}
		$css_cache_file = "data/cache/style.$css_latest.css";
		if(!file_exists($css_cache_file)) {
			$css_data = "";
			foreach($css_files as $file) {
				$file_data = file_get_contents($file);
				$pattern = '/url[\s]*\([\s]*["\']?([^"\'\)]+)["\']?[\s]*\)/';
				$replace = 'url("../../'.dirname($file).'/$1")';
				$file_data = preg_replace($pattern, $replace, $file_data);
				$css_data .= $file_data . "\n";
			}
			file_put_contents($css_cache_file, $css_data);
		}
		$this->add_html_header("<link rel='stylesheet' href='$data_href/$css_cache_file' type='text/css'>");

		$js_files = array();
		$js_latest = 0;
		foreach(array_merge(zglob("lib/*.js"), zglob("ext/*/style.js"), zglob("themes/$theme_name/style.js")) as $js) {
			$js_files[] = $js;
			$js_latest = max($js_latest, filemtime($js));
		}
		$js_cache_file = "data/cache/script.$js_latest.js";
		if(!file_exists($js_cache_file)) {
			$js_data = "";
			foreach($js_files as $file) {
				$js_data .= file_get_contents($file) . "\n";
			}
			file_put_contents($js_cache_file, $js_data);
		}
		$this->add_html_header("<script src='$data_href/$js_cache_file' type='text/javascript'></script>");
	}
}
?>
