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
 * Class Page
 *
 * A data structure for holding all the bits of data that make up a page.
 *
 * The various extensions all add whatever they want to this structure,
 * then Layout turns it into HTML.
 */
class Page {
	/** @name Overall */
	//@{
	/** @var string */
	public $mode = "page";
	/** @var string */
	public $type = "text/html; charset=utf-8";

	/**
	 * Set what this page should do; "page", "data", or "redirect".
	 * @param string $mode
	 */
	public function set_mode($mode) {
		$this->mode = $mode;
	}

	/**
	 * Set the page's MIME type.
	 * @param string $type
	 */
	public function set_type($type) {
		$this->type = $type;
	}


	//@}
	// ==============================================
	/** @name "data" mode */
	//@{

	/** @var string; public only for unit test */
	public $data = "";

	/** @var string; public only for unit test */
	public $filename = null;

	/**
	 * Set the raw data to be sent.
	 * @param string $data
	 */
	public function set_data($data) {
		$this->data = $data;
	}

	/**
	 * Set the recommended download filename.
	 * @param string $filename
	 */
	public function set_filename($filename) {
		$this->filename = $filename;
	}


	//@}
	// ==============================================
	/** @name "redirect" mode */
	//@{

	/** @var string */
	private $redirect = "";

	/**
	 * Set the URL to redirect to (remember to use make_link() if linking
	 * to a page in the same site).
	 * @param string $redirect
	 */
	public function set_redirect($redirect) {
		$this->redirect = $redirect;
	}


	//@}
	// ==============================================
	/** @name "page" mode */
	//@{

	/** @var int */
	public $code = 200;

	/** @var string */
	public $title = "";

	/** @var string */
	public $heading = "";

	/** @var string */
	public $subheading = "";

	/** @var string */
	public $quicknav = "";

	/** @var string[] */
	public $html_headers = array();

	/** @var string[] */
	public $http_headers = array();

	/** @var string[][] */
	public $cookies = array();

	/** @var Block[] */
	public $blocks = array();

	/**
	 * Set the HTTP status code
	 * @param int $code
	 */
	public function set_code($code) {
		$this->code = $code;
	}

	/**
	 * Set the window title.
	 * @param string $title
	 */
	public function set_title($title) {
		$this->title = $title;
	}

	/**
	 * Set the main heading.
	 * @param string $heading
	 */
	public function set_heading($heading) {
		$this->heading = $heading;
	}

	/**
	 * Set the sub heading.
	 * @param string $subheading
	 */
	public function set_subheading($subheading) {
		$this->subheading = $subheading;
	}

	/**
	 * Add a line to the HTML head section.
	 * @param string $line
	 * @param int $position
	 */
	public function add_html_header($line, $position=50) {
		while(isset($this->html_headers[$position])) $position++;
		$this->html_headers[$position] = $line;
	}

	/**
	 * Add a http header to be sent to the client.
	 * @param string $line
	 * @param int $position
	 */
	public function add_http_header($line, $position=50) {
		while(isset($this->http_headers[$position])) $position++;
		$this->http_headers[$position] = $line;
	}

	/**
	 * The counterpart for get_cookie, this works like php's
	 * setcookie method, but prepends the site-wide cookie prefix to
	 * the $name argument before doing anything.
	 *
	 * @param string $name
	 * @param string $value
	 * @param int $time
	 * @param string $path
	 */
	public function add_cookie($name, $value, $time, $path) {
		$full_name = COOKIE_PREFIX."_".$name;
		$this->cookies[] = array($full_name, $value, $time, $path);
	}

	/**
	 * @param string $name
	 * @return string|null
	 */
	public function get_cookie(/*string*/ $name) {
		$full_name = COOKIE_PREFIX."_".$name;
		if(isset($_COOKIE[$full_name])) {
			return $_COOKIE[$full_name];
		}
		else {
			return null;
		}
	}

	/**
	 * Get all the HTML headers that are currently set and return as a string.
	 * @return string
	 */
	public function get_all_html_headers() {
		$data = '';
		foreach ($this->html_headers as $line) {
			$data .= $line . "\n";
		}
		return $data;
	}

	/**
	 * Removes all currently set HTML headers (Be careful..).
	 */
	public function delete_all_html_headers() {
		$this->html_headers = array();
	}

	/**
	 * Add a Block of data to the page.
	 * @param Block $block
	 */
	public function add_block(Block $block) {
		$this->blocks[] = $block;
	}


	//@}
	// ==============================================

	/**
	 * Display the page according to the mode and data given.
	 */
	public function display() {
		global $page, $user;

		header("HTTP/1.0 {$this->code} Shimmie");
		header("Content-type: ".$this->type);
		header("X-Powered-By: SCore-".SCORE_VERSION);

		if (!headers_sent()) {
			foreach($this->http_headers as $head) {
				header($head);
			}
			foreach($this->cookies as $c) {
				setcookie($c[0], $c[1], $c[2], $c[3]);
			}
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
				if($this->get_cookie("flash_message")) {
					$this->add_cookie("flash_message", "", -1, "/");
				}
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

	/**
	 * This function grabs all the CSS and JavaScript files sprinkled throughout Shimmie's folders,
	 * concatenates them together into two large files (one for CSS and one for JS) and then stores
	 * them in the /cache/ directory for serving to the user.
	 *
	 * Why do this? Two reasons:
	 *  1. Reduces the number of files the user's browser needs to download.
	 *  2. Allows these cached files to be compressed/minified by the admin.
	 *
	 * TODO: This should really be configurable somehow...
	 */
	public function add_auto_html_headers() {
		global $config;

		$data_href = get_base_href();
		$theme_name = $config->get_string('theme', 'default');

		$this->add_html_header("<script type='text/javascript'>base_href = '$data_href';</script>", 40);

		# 404/static handler will map these to themes/foo/bar.ico or lib/static/bar.ico
		$this->add_html_header("<link rel='icon' type='image/x-icon' href='$data_href/favicon.ico'>", 41);
		$this->add_html_header("<link rel='apple-touch-icon' href='$data_href/apple-touch-icon.png'>", 42);

		$config_latest = 0;
		foreach(zglob("data/config/*") as $conf) {
			$config_latest = max($config_latest, filemtime($conf));
		}

		$css_files = array();
		$css_latest = $config_latest;
		foreach(array_merge(zglob("lib/*.css"), zglob("ext/*/style.css"), zglob("themes/$theme_name/style.css")) as $css) {
			$css_files[] = $css;
			$css_latest = max($css_latest, filemtime($css));
		}
		$css_cache_file = data_path("cache/style.$theme_name.$css_latest.css");
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
		$this->add_html_header("<link rel='stylesheet' href='$data_href/$css_cache_file' type='text/css'>", 43);

		$js_files = array();
		$js_latest = $config_latest;
		foreach(array_merge(zglob("lib/*.js"), zglob("ext/*/script.js"), zglob("themes/$theme_name/script.js")) as $js) {
			$js_files[] = $js;
			$js_latest = max($js_latest, filemtime($js));
		}
		$js_cache_file = data_path("cache/script.$theme_name.$js_latest.js");
		if(!file_exists($js_cache_file)) {
			$js_data = "";
			foreach($js_files as $file) {
				$js_data .= file_get_contents($file) . "\n";
			}
			file_put_contents($js_cache_file, $js_data);
		}
		$this->add_html_header("<script src='$data_href/$js_cache_file' type='text/javascript'></script>", 44);
	}
}

class MockPage extends Page {
}
