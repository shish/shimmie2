<?php
/**
 * A class to turn a Page data structure into a blob of HTML
 */
class Layout {
	/**
	 * turns the Page into HTML
	 */
	public function display_page(Page $page) {
		global $config;

		$theme_name = $config->get_string('theme', 'material');
		$site_name = $config->get_string('title');
		$data_href = get_base_href();
		$main_page = $config->get_string('main_page');
		$contact_link = $config->get_string('contact_link');
    $site_link = make_link();

		$header_html = "";
		ksort($page->html_headers);
		foreach($page->html_headers as $line) {
			$header_html .= "\t\t$line\n";
		}

		$left_block_html = "";
		$main_block_html = "";
		$head_block_html = "";
		$sub_block_html = "";
		$drawer_block_html = ""; //use exampled in user.theme.php & view.theme.php
		$toolbar_block_html = ""; // not used at this point
		$subtoolbar_block_html = ""; // use exampled in user.theme.php
    $navigation = "";

    $h_search = "
      <div class='mdl-textfield mdl-js-textfield mdl-textfield--expandable
                  mdl-textfield--floating-label mdl-textfield--align-right'>
        <form action='".make_link()."' method='GET'>
          <label class='mdl-button mdl-js-button mdl-button--icon'
                 for='waterfall-exp'>
            <i class='material-icons'>search</i>
          </label>
          <div class='mdl-textfield__expandable-holder'>
            <input id='waterfall-exp' class='autocomplete_tags mdl-textfield__input' name='search' type='text' placeholder='Search' value='' />
            <input type='hidden' name='q' value='/post/list'>
            <input type='submit' value='Find' style='display: none;' />
          </div>
        </form>
      </div>
    ";

		foreach($page->blocks as $block) {
			switch($block->section) {
				case "toolbar":
					$toolbar_block_html .= $this->get_html($block, "toolbar");
					break;
				case "subtoolbar":
					$subtoolbar_block_html .= $this->get_html($block, "subtoolbar");
  				break;
				case "left":
          if($block->header == "Navigation"){
            $subtoolbar_block_html = $this->rework_navigation($block);
            break;
          }
					// $left_block_html .= $block->get_html(true);
          $left_block_html .= $this->get_html($block, "full", true, "left-blocks nav-card mdl-cell--4-col-tablet");
					break;
				case "head":
          $head_block_html .= $this->get_html($block, "third", true, "nav-card head-blocks");
					break;
				case "drawer":
          $drawer_block_html .= $this->get_html($block, "full", true, "nav-card drawer-blocks");
					break;
				case "main":
					// $main_block_html .= $block->get_html(false);
          $main_block_html .= $this->get_html($block, "main", true, "");
					break;
				case "subheading":
					// $sub_block_html .= $block->body; // $this->block_to_html($block, true);
          $sub_block_html .= $this->get_html($block, "third", true, "nav-card");
					break;
				default:
					print "<p>error: {$block->header} using an unknown section ({$block->section})";
					break;
			}
		}

		$debug = get_debug_info();

		$contact = empty($contact_link) ? "" : "<br><a href='mailto:$contact_link'>Contact</a>";
		/*$subheading = empty($page->subheading) ? "" : "<div id='subtitle'>{$page->subheading}</div>";

		$wrapper = "";
		if(strlen($page->heading) > 100) {
			$wrapper = ' style="height: 3em; overflow: auto;"';
		}
		*/

    $flash = $page->get_cookie("flash_message");
		$flash_html = "";
		if($flash) {
			$flash_html = "<b id='flash'>".nl2br(html_escape($flash))." <a href='#' onclick=\"\$('#flash').hide(); return false;\">[X]</a></b>";
		}

		print <<<EOD
<!doctype html>
<!--[if lt IE 7]> <html class="no-js lt-ie9 lt-ie8 lt-ie7" lang="en"> <![endif]-->
<!--[if IE 7]>    <html class="no-js lt-ie9 lt-ie8" lang="en"> <![endif]-->
<!--[if IE 8]>    <html class="no-js lt-ie9" lang="en"> <![endif]-->
<!--[if gt IE 8]><!--> <html class="no-js" lang="en"> <!--<![endif]-->
	<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
		<title>{$page->title}</title>
    <link rel="stylesheet" href="//fonts.googleapis.com/css?family=Roboto:regular,bold,italic,thin,light,bolditalic,black,medium&amp;lang=en">
    <link rel="stylesheet" href="//fonts.googleapis.com/icon?family=Material+Icons"  rel="stylesheet">
    <link rel="stylesheet" href="{$data_href}/themes/{$theme_name}/material.min.css?v1.0.5"  rel="stylesheet">
    $header_html
		<script type="text/javascript" src="{$data_href}/themes/{$theme_name}/material.min.js?v1.0.5"></script>
		<script type="text/javascript" src="{$data_href}/themes/{$theme_name}/script0.js?v1"></script>
    <!-- having conflicts this ensures the screens will not remain hidden \while the layout is adjusted -->
	</head>

	<body>

    <div class="mdl-layout mdl-js-layout mdl-layout--fixed-header">
      <header class="mdl-layout__header mdl-layout__header--waterfall">
        <!-- Top row, always visible -->
        <div class="mdl-layout__header-row ">
          <!-- Title -->
          <span class="mdl-layout-title">
            <a class="mdl-logo" href="{$site_link}">{$site_name}</a>
          </span>
          <div class="mdl-layout-spacer"></div>
          $h_search
          {$toolbar_block_html}
          <button id="menu-left-col-menu"
                  class="mdl-button mdl-js-button mdl-button--icon">
            <i class="material-icons">more_vert</i>
          </button>
        </div>
        <!-- Bottom row, not visible on scroll -->
        <div class="mdl-layout__header-row">
          <div class="mdl-layout-spacer"></div>
          <!-- Navigation -->
          {$subtoolbar_block_html}
        </div>
      </header>
      <div class="mdl-layout__drawer">
        <span class="mdl-layout-title">Drawer</span>
          <div class="mdl-grid">
            $drawer_block_html
          </div>
        <nav class="mdl-navigation">
          <a class="mdl-navigation__link" href="http://code.shishnet.org/shimmie2/">Shimmie  &copy;</a>
        </nav>
      </div>
      <main class="mdl-layout__content">
        <div class="mdl-grid">
          <div class="mdl-cell mdl-cell--12-col mdl-grid ">
            $head_block_html
            $sub_block_html
          </div>
        </div>
        <div id="main-grid" class="mdl-grid">
          <div id="left-block" class="mdl-cell mdl-cell--4-col mdl-cell--8-col-tablet mdl-cell--4-col-phone mdl-color--grey-200">
            <script>
              document.getElementById("left-block").style.display="none";
            </script>
            <div id="left-blk-content" class="mdl-cell mdl-cell--12-col mdl-grid">
              <!-- Start Left Block -->
              $left_block_html
            </div>
          </div>
          <div id="main-block" class="mdl-cell mdl-shadow--2dp mdl-cell--8-col mdl-cell--8-col-tablet mdl-color--grey-200 mdl-grid">
              <script>
                document.getElementById("main-block").style.display="none";
              </script>
              <!-- Start art Block -->
              <article class="mdl-cell mdl-cell--12-col mdl-cell--top">
              $flash_html
              $main_block_html
              </article>
          </div>
        </div>
          <footer class="mdl-mini-footer">
              $debug
              $contact
          </footer>
      </main>
    </div>

    <ul class="mdl-menu mdl-menu--bottom-right mdl-js-menu mdl-js-ripple-effect"
        for="menu-left-col-menu">
      <li id="layout-top" class="mdl-menu__item">Layout Top</li>
      <li id="layout-right" class="mdl-menu__item">Layout Right</li>
      <li id="layout-bottom" class="mdl-menu__item">Layout Bottom</li>
      <li id="layout-left" class="mdl-menu__item">Layout Left</li>
    </ul>
	</body>
</html>
EOD;
	}

  public function rework_navigation(Block $block){
    $h = $block->header;
    $b = $block->body;
    $i = $block->id;

    $dom = new DomDocument();
    $dom->loadHTML($b);
    $output = array();
    $html = "<section id='$i'>\n<nav class='mdl-navigation'>\n";
    foreach ($dom->getElementsByTagName('a') as $item) {
       $item->setAttribute('class', 'mdl-navigation__link');
       $html .= $dom->saveHTML($item);
      //  $output[] = array (
      //     ,'str' => $dom->saveHTML($item)
      //     // ,'href' => $item->getAttribute('href')
      //     // ,'anchorText' => $item->nodeValue
      //  );
    }
    $html .= "</nav>\n</section>\n";
    return $html;
  }

  /**
   * Get the HTML for this block. from core
   *
   * @param bool $hidable
   * @return string
   */
  public function get_html(Block $block, $section="main", $hidable=false, $extra_class="") {
    $h = $block->header;
    $b = $block->body;
    $i = $block->id;
    $i = preg_replace('/[^\w-]/', '', $i);//blotter extention id has `!`

    if($section == "toolbar"){
      $html = "<section id='$i'>\n<nav class='mdl-navigation'>\n";
      if(!empty($b)) $html .= "$b";
      $html .= "</nav>\n</section>\n";
      return $html;
    }
    if($section == "subtoolbar"){
      $html = "<section id='$i'>\n<nav class='mdl-navigation'>\n";
      if(!empty($b)) $html .= "$b";
      $html .= "</nav>\n</section>\n";
      return $html;
    }
    if($section == "full"){
      $html = "<div class='mdl-card mdl-shadow--4dp mdl-cell mdl-cell--12-col $extra_class'><section id='$i'>";
      $h_toggler = $hidable ? " shm-toggler" : "";
      if(!empty($h)) $html .="<div class='mdl-card__title'><h3 data-toggle-sel='#$i' class='mdl-card__title-text $h_toggler'>$h</h3></div>";
      if(!empty($b)) $html .="<div class='mdl-card__supporting-text blockbody'>$b</div>";
      $html .= "</section></div>\n";
      return $html;
    }
    if($section == "third"){
      $html = "<div class='mdl-card mdl-shadow--4dp mdl-cell mdl-cell--4-col $extra_class'><section id='$i'>";
      $h_toggler = $hidable ? " shm-toggler" : "";
      if(!empty($h)) $html .="<div class='mdl-card__title'><h3 data-toggle-sel='#$i' class='mdl-card__title-text $h_toggler'>$h</h3></div>";
      if(!empty($b)) $html .="<div class='mdl-card__supporting-text blockbody'>$b</div>";
      $html .= "</section></div>\n";
      return $html;
    }
    $html = "<section id='$i'>";
    $h_toggler = $hidable ? " shm-toggler" : "";
    if(!empty($h)) $html .= "<h3 data-toggle-sel='#$i' class='$h_toggler'>$h</h3>";
    if(!empty($b)) $html .= "<div class='blockbody'>$b</div>";
    $html .= "</section>\n";
    return $html;
  }

}


//@todo fix ext/blotter id tag
//@todo fix table row error for ext/ip_ban
//@todo fix table row error for ext/image_hash_ban
//@todo fix table row error for ext/untag
//@todo fix ext private-messages gives Uncaught TypeError: Cannot read property 'href' of null when no messages are there..
