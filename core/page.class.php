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
		
		header("Content-type: ".$this->type);
		header("X-Powered-By: SCore-".SCORE_VERSION);

		if (!headers_sent()) {
			foreach($this->http_headers as $head){ header($head); }
		} else {
			print "Error: Headers have already been sent to the client.";
		}

		switch($this->mode) {
			case "page":
				header("Cache-control: no-cache");
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
				header("Location: {$this->redirect}");
				print "You should be redirected to <a href='{$this->redirect}'>{$this->redirect}</a>";
				break;
			default:
				print "Invalid page mode";
				break;
		}
	}
	
	protected function add_auto_html_headers() {
		$data_href = get_base_href();
		
		/* Attempt to cache the CSS & JavaScript files */
		if ($this->add_cached_auto_html_headers() === FALSE) {
			// caching failed, add all files to html_headers.
			
			foreach(glob("lib/*.css") as $css) {
				$this->add_html_header("<link rel='stylesheet' href='$data_href/$css' type='text/css'>");
			}
			$css_files = glob("ext/*/style.css");
			if($css_files) {
				foreach($css_files as $css_file) {
					$this->add_html_header("<link rel='stylesheet' href='$data_href/$css_file' type='text/css'>");
				}
			}
			
			foreach(glob("lib/*.js") as $js) {
				$this->add_html_header("<script src='$data_href/$js' type='text/javascript'></script>");
			}
			$js_files = glob("ext/*/script.js");
			if($js_files) {
				foreach($js_files as $js_file) {
					$this->add_html_header("<script src='$data_href/$js_file' type='text/javascript'></script>");
				}
			}
		}
	}

	/*
		This function caches the CSS and JavaScript files.
		This is done to reduce the number of HTTP requests (recommended by
		the Yahoo high-performance guidelines). It combines all of the CSS
		and JavaScript files into one for each type, and stores them in 
		cached files to serve the client. Changes to the CSS or JavaScript
		files are caught by taking the md5sum of the concatenated files.
	*/
	private function add_cached_auto_html_headers()
	{
		$cache_location = 'data/cache/';
		$data_href = get_base_href();
		
		if(!file_exists($cache_location)) {
			if (!mkdir($cache_location, 0750, true)) {
				return false; // failed to create directory
			}
		}

		/* ----- CSS Files ----- */
		// First get all the CSS from the lib directory
		$data_1 = '';
		$css_files = glob("lib/*.css");
		if($css_files) {
			foreach($css_files as $css_file) {
				$data_1 .= file_get_contents($css_file);
			}
			//	Can't directly cache the CSS files, as they might have relative locations to images, etc. in them.
			//	We have to adjust the URLs accordingly before saving the cached file.
			$pattern = '/url[\s]*\([\s]*["\']?([^"\'\)]+)["\']?[\s]*\)/';
			$replace = 'url("../../lib/${1}")';
			$data_1 = preg_replace($pattern, $replace, $data_1);
		}
		// Next get all the CSS from the extensions
		$data_2 = '';
		$css_files = glob("ext/*/style.css");
		if($css_files) {
			foreach($css_files as $css_file) {
				$data_2 .= file_get_contents($css_file);
			}
			//	Can't directly cache the CSS files, as they might have relative locations to images, etc. in them.
			//	We have to adjust the URLs accordingly before saving the cached file.
			$pattern = '/url[\s]*\([\s]*["\']?([^"\'\)]+)["\']?[\s]*\)/';
			$replace = 'url("../../${1}")';
			$data_2 = preg_replace($pattern, $replace, $data_2);
		}
		// Combine the two
		$data = $data_1 . $data_2;
		// compute the MD5 sum of the concatenated CSS files
		$md5sum = md5($data);
		
		if (!file_exists($cache_location.$md5sum.'.css')) {
			// remove any old cached CSS files.
			$mask = '*.css';
			array_map( 'unlink', glob( $mask ) );
		
			// output the combined file
			if (file_put_contents($cache_location.$md5sum.'.css', $data, LOCK_EX) === FALSE) {
				return false;
			}
		}
		// tell the client where to get the css cache file
		$this->add_html_header('<link rel="stylesheet" href="'.$data_href.'/'.$cache_location.$md5sum.'.css'.'" type="text/css">');

		
		/* ----- JavaScript Files ----- */
		$data = '';
		$js_files = glob("lib/*.js");
		if($js_files) {
			foreach($js_files as $js_file) {
				$data .= file_get_contents($js_file);
			}
		}
		$js_files = glob("ext/*/script.js");
		if($js_files) {
			foreach($js_files as $js_file) {
				$data .= file_get_contents($js_file);
			}
		}
		// compute the MD5 sum of the concatenated JavaScript files
		$md5sum = md5($data);
		
		if (!file_exists($cache_location.$md5sum.'.js')) {
			// remove any old cached js files.
			$mask = '*.js';
			array_map( 'unlink', glob( $mask ) );
			// output the combined file
			if (file_put_contents($cache_location.$md5sum.'.js', $data, LOCK_EX) === FALSE) {
				return false;
			}
		}
		// tell the client where to get the js cache file
		$this->add_html_header('<script src="'.$data_href.'/'.$cache_location.$md5sum.'.js'.'" type="text/javascript"></script>');
		
		return true;
	}

}
?>
