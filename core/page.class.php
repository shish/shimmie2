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
		$data_href = get_base_href();

		$this->add_html_header("<script>base_href = '$data_href';</script>");
		
		/* Attempt to cache the CSS & JavaScript files */
		if ($this->add_cached_auto_html_headers() === FALSE) {
			// caching failed, add all files to html_headers.
			
			foreach(glob("lib/*.css") as $css) {
				$this->add_html_header('<link rel="stylesheet" href="'.$data_href.'/'.$css.'" type="text/css">');
			}
			$css_files = glob("ext/*/style.css");
			if($css_files) {
				foreach($css_files as $css_file) {
					$this->add_html_header('<link rel="stylesheet" href="'.$data_href.'/'.$css_file.'" type="text/css">');
				}
			}
			
			foreach(glob("lib/*.js") as $js) {
				$this->add_html_header('<script src="'.$data_href.'/'.$js.'" type="text/javascript"></script>');
			}
			$js_files = glob("ext/*/script.js");
			if($js_files) {
				foreach($js_files as $js_file) {
					$this->add_html_header('<script src="'.$data_href.'/'.$js_file.'" type="text/javascript"></script>');
				}
			}
		}
	}

	/**
	 * Automatic caching of CSS and Javascript files
	 *
	 * Allows site admins to have Shimmie automatically cache and minify all CSS and JS files.
	 * This is done to reduce the number of HTTP requests (recommended by the Yahoo high-performance
	 * guidelines). It combines all of the CSS and JavaScript files into one for each type, and
	 * stores them in cached files to serve the client. Changes to the CSS or JavaScript files are
	 * caught by taking the md5sum of the concatenated files.
	 *
	 * Note: This can be somewhat problematic, as it edits the links to your CSS files (as well
	 * as the links to images inside them).
	 * Also, the directory cache directory needs to be writeable by the php/webserver user.
	 * PLEASE: Ensure that you test your site out throughly after enabling this module!
	 * Either that, or don't use this module unless you are sure of what it is doing.
	 *
	 * TODO: Add support for minify-ing CSS and Javascript files. (similar to Minify. See: http://code.google.com/p/minify/ or https://github.com/mrclay/minify)
	 * TODO: For performance reasons: Before performing the regex's, compute the md5 of the CSS & JS files and store somewhere to check later.
	 *
	 * @return
	 *	This function returns FALSE if it failed to cache the files,
	 *	and returns TRUE if it was successful.
	 */
	private function add_cached_auto_html_headers()
	{
		global $config;
		
		if (!$config->get_bool("autocache_css") && !$config->get_bool("autocache_js")) {
			return false;	// caching disabled
		}
		
		$cache_location = $config->get_string("autocache_location", 'data/cache');
		// Detect is there is a trailing slash, and add one if not.
		$cache_location = ((strrpos($cache_location, '/') + 1) == strlen($cache_location)) ? $cache_location : $cache_location.'/'; 

		// Create directory if needed.
		if(!file_exists($cache_location)) {
			if (!mkdir($cache_location, 0750, true)) {
				return false; // failed to create directory
			}
		}

		$data_href = get_base_href();

		/* ----- CSS Files ----- */
		if ($config->get_bool("autocache_css"))
		{
			// First get all the CSS from the lib directory
			$contents_from_lib = '';
			$css_files = glob("lib/*.css");
			if($css_files) {
				foreach($css_files as $css_file) {
					$contents_from_lib .= file_get_contents($css_file);
				}
				//	Can't directly cache the CSS files, as they might have relative locations to images, etc. in them.
				//	We have to adjust the URLs accordingly before saving the cached file.
				$pattern = '/url[\s]*\([\s]*["\']?([^"\'\)]+)["\']?[\s]*\)/';
				$replace = 'url("../../lib/${1}")';
				$contents_from_lib = preg_replace($pattern, $replace, $contents_from_lib);
			}
			// Next get all the CSS from the extensions
			$contents_from_extensions = '';
			$css_files = glob("ext/*/style.css");
			if($css_files) {
				foreach($css_files as $css_file) {
					$contents_from_extensions .= file_get_contents($css_file);
				}
				//	Can't directly cache the CSS files, as they might have relative locations to images, etc. in them.
				//	We have to adjust the URLs accordingly before saving the cached file.
				$pattern = '/url[\s]*\([\s]*["\']?([^"\'\)]+)["\']?[\s]*\)/';
				$replace = 'url("../../${1}")';
				$contents_from_extensions = preg_replace($pattern, $replace, $contents_from_extensions);
			}
			// Combine the two
			$data = $contents_from_lib .' '. $contents_from_extensions;
			
			// Minify the CSS if enabled.
			if ($config->get_bool("autocache_min_css")){
				// not supported yet.
				// TODO: add support for Minifying CSS files.
			}

			// compute the MD5 sum of the concatenated CSS files
			$md5sum = md5($data);
			
			if (!file_exists($cache_location.$md5sum.'.css')) {
				// remove any old cached CSS files.
				$mask = '*.css';
				array_map( 'unlink', glob( $mask ) );
			
				// output the combined file
				if (file_put_contents($cache_location.$md5sum.'.css', $data, LOCK_EX) === FALSE) {
					return false; // failed to write the file
				}
			}
			// tell the client where to get the css cache file
			$this->add_html_header('<link rel="stylesheet" href="'.$data_href.'/'.$cache_location.$md5sum.'.css" type="text/css">');
		} else {
			// Caching of CSS disabled.
			foreach(glob("lib/*.css") as $css) {
				$this->add_html_header('<link rel="stylesheet" href="'.$data_href.'/'.$css.'" type="text/css">');
			}
			$css_files = glob("ext/*/style.css");
			if($css_files) {
				foreach($css_files as $css_file) {
					$this->add_html_header('<link rel="stylesheet" href="'.$data_href.'/'.$css_file.'" type="text/css">');
				}
			}	
		}
		
		
		/* ----- JavaScript Files ----- */
		if ($config->get_bool("autocache_js"))
		{
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
			// Minify the JS if enabled.
			if ($config->get_bool("autocache_min_js")){
				// not supported yet.
				// TODO: add support for Minifying CSS files.
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
			$this->add_html_header('<script src="'.$data_href.'/'.$cache_location.$md5sum.'.js" type="text/javascript"></script>');
		} else {
			// Caching of Javascript disabled.
			foreach(glob("lib/*.js") as $js) {
				$this->add_html_header('<script src="'.$data_href.'/'.$js.'" type="text/javascript"></script>');
			}
			$js_files = glob("ext/*/script.js");
			if($js_files) {
				foreach($js_files as $js_file) {
					$this->add_html_header('<script src="'.$data_href.'/'.$js_file.'" type="text/javascript"></script>');
				}
			}
		}
		
		return true;
	}

}
?>
