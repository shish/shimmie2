<?php

class TagListTheme extends Themelet {
	/** @var string  */
	public $heading = "";
	/** @var string|string[]  */
	public $list = "";

	public $navigation;

	/**
	 * @param string $text
	 */
	public function set_heading($text) {
		$this->heading = $text;
	}

	/**
	 * @param string|string[] $list
	 */
	public function set_tag_list($list) {
		$this->list = $list;
	}

	public function set_navigation($nav) {
		$this->navigation = $nav;
	}

	public function display_page(Page $page) {
		$page->set_title("Tag List");
		$page->set_heading($this->heading);
		$page->add_block(new Block("Tags", $this->list));
		$page->add_block(new Block("Navigation", $this->navigation, "left", 0));
	}

	// =======================================================================

	/*
	 * $tag_infos = array(
	 *                 array('tag' => $tag, 'count' => $number_of_uses),
	 *                 ...
	 *              )
	 */
	public function display_split_related_block(Page $page, $tag_infos) {
		global $config;

		if($config->get_string('tag_list_related_sort') == 'alphabetical') asort($tag_infos);

		if(class_exists('TagCategories')) {
			$this->tagcategories = new TagCategories;
			$tag_category_dict = $this->tagcategories->getKeyedDict();
		}
		else {
			$tag_category_dict = array();
		}
		$tag_categories_html = array();
		$tag_categories_count = array();

		foreach($tag_infos as $row) {
			$split = self::return_tag($row, $tag_category_dict);
			$category = $split[0];
			$tag_html = $split[1];
			if(!isset($tag_categories_html[$category])) {
				$tag_categories_html[$category] = '';
			}
			$tag_categories_html[$category] .= $tag_html . '<br />';

			if(!isset($tag_categories_count[$category])) {
				$tag_categories_count[$category] = 0;
			}
			$tag_categories_count[$category] += 1;
		}

		asort($tag_categories_html);
		if(isset($tag_categories_html[' '])) $main_html = $tag_categories_html[' ']; else $main_html = null;
		unset($tag_categories_html[' ']);

		foreach(array_keys($tag_categories_html) as $category) {
			if($tag_categories_count[$category] < 2) {
				$category_display_name = html_escape($tag_category_dict[$category]['display_singular']);
			}
			else{
				$category_display_name = html_escape($tag_category_dict[$category]['display_multiple']);
			}
			$page->add_block(new Block($category_display_name, $tag_categories_html[$category], "left", 9));
		}

		if($config->get_string('tag_list_image_type')=="tags") {
			$page->add_block(new Block("Tags", $main_html, "left", 10));
		}
		else {
			$page->add_block(new Block("Related Tags", $main_html, "left", 10));
		}
	}

	/*
	 * $tag_infos = array(
	 *                 array('tag' => $tag, 'count' => $number_of_uses),
	 *                 ...
	 *              )
	 */
	public function display_related_block(Page $page, $tag_infos) {
		global $config;

		if($config->get_string('tag_list_related_sort') == 'alphabetical') asort($tag_infos);

		if(class_exists('TagCategories')) {
			$this->tagcategories = new TagCategories;
			$tag_category_dict = $this->tagcategories->getKeyedDict();
		}
		else {
			$tag_category_dict = array();
		}
		$main_html = '';

		foreach($tag_infos as $row) {
			$split = $this->return_tag($row, $tag_category_dict);
			//$category = $split[0];
			$tag_html = $split[1];
			$main_html .= $tag_html . '<br />';
		}

		if($config->get_string('tag_list_image_type')=="tags") {
			$page->add_block(new Block("Tags", $main_html, "left", 10));
		}
		else {
			$page->add_block(new Block("Related Tags", $main_html, "left", 10));
		}
	}


	/*
	 * $tag_infos = array(
	 *                 array('tag' => $tag, 'count' => $number_of_uses),
	 *                 ...
	 *              )
	 */
	public function display_popular_block(Page $page, $tag_infos) {
		global $config;

		if($config->get_string('tag_list_popular_sort') == 'alphabetical') asort($tag_infos);

		if(class_exists('TagCategories')) {
			$this->tagcategories = new TagCategories;
			$tag_category_dict = $this->tagcategories->getKeyedDict();
		}
		else {
			$tag_category_dict = array();
		}
		$main_html = '';

		foreach($tag_infos as $row) {
			$split = self::return_tag($row, $tag_category_dict);
			//$category = $split[0];
			$tag_html = $split[1];
			$main_html .= $tag_html . '<br />';
		}

		$main_html .= "&nbsp;<br><a class='more' href='".make_link("tags")."'>Full List</a>\n";
		$page->add_block(new Block("Popular Tags", $main_html, "left", 60));
	}

	/*
	 * $tag_infos = array(
	 *                 array('tag' => $tag),
	 *                 ...
	 *              )
	 * $search = the current array of tags being searched for
	 */
	public function display_refine_block(Page $page, $tag_infos, $search) {
		global $config;

		if($config->get_string('tag_list_popular_sort') == 'alphabetical') asort($tag_infos);

		if(class_exists('TagCategories')) {
			$this->tagcategories = new TagCategories;
			$tag_category_dict = $this->tagcategories->getKeyedDict();
		}
		else {
			$tag_category_dict = array();
		}
		$main_html = '';

		foreach($tag_infos as $row) {
			$split = self::return_tag($row, $tag_category_dict);
			//$category = $split[0];
			$tag_html = $split[1];
			$main_html .= $tag_html . '<br />';
		}

		$main_html .= "&nbsp;<br><a class='more' href='".make_link("tags")."'>Full List</a>\n";
		$page->add_block(new Block("refine Search", $main_html, "left", 60));
	}

	public function return_tag($row, $tag_category_dict) {
		global $config;

		$display_html = '';
		$tag = $row['tag'];
		$h_tag = html_escape($tag);
		
		$tag_category_css = '';
		$tag_category_style = '';
		$h_tag_split = explode(':', html_escape($tag), 2);
		$category = ' ';

		// we found a tag, see if it's valid!
		if((count($h_tag_split) > 1) and array_key_exists($h_tag_split[0], $tag_category_dict)) {
			$category = $h_tag_split[0];
			$h_tag = $h_tag_split[1];
			$tag_category_css .= ' tag_category_'.$category;
			$tag_category_style .= 'style="color:'.html_escape($tag_category_dict[$category]['color']).';" ';
		}

		$h_tag_no_underscores = str_replace("_", " ", $h_tag);
		$count = $row['calc_count'];
		// if($n++) $display_html .= "\n<br/>";
		if(!is_null($config->get_string('info_link'))) {
			$link = str_replace('$tag', $tag, $config->get_string('info_link'));
			$display_html .= ' <a class="tag_info_link'.$tag_category_css.'" '.$tag_category_style.'href="'.$link.'">?</a>';
		}
		$link = $this->tag_link($row['tag']);
		$display_html .= ' <a class="tag_name'.$tag_category_css.'" '.$tag_category_style.'href="'.$link.'">'.$h_tag_no_underscores.'</a>';

		if($config->get_bool("tag_list_numbers")) {
			$display_html .= " <span class='tag_count'>$count</span>";
		}

		return array($category, $display_html);
	}

	/**
	 * @param string $tag
	 * @param string[] $tags
	 * @return string
	 */
	protected function ars(/*string*/ $tag, /*array(string)*/ $tags) {
		assert(is_array($tags));

		// FIXME: a better fix would be to make sure the inputs are correct
		$tag = strtolower($tag);
		$tags = array_map("strtolower", $tags);
		$html = "";
		$html .= " <span class='ars'>(";
		$html .= $this->get_add_link($tags, $tag);
		$html .= $this->get_remove_link($tags, $tag);
		$html .= $this->get_subtract_link($tags, $tag);
		$html .= ")</span>";
		return $html;
	}

	/**
	 * @param array $tags
	 * @param string $tag
	 * @return string
	 */
	protected function get_remove_link($tags, $tag) {
		if(!in_array($tag, $tags) && !in_array("-$tag", $tags)) {
			return "";
		}
		else {
			$tags = array_remove($tags, $tag);
			$tags = array_remove($tags, "-$tag");
			return "<a href='".$this->tag_link(join(' ', $tags))."' title='Remove' rel='nofollow'>R</a>";
		}
	}

	/**
	 * @param array $tags
	 * @param string $tag
	 * @return string
	 */
	protected function get_add_link($tags, $tag) {
		if(in_array($tag, $tags)) {
			return "";
		}
		else {
			$tags = array_remove($tags, "-$tag");
			$tags = array_add($tags, $tag);
			return "<a href='".$this->tag_link(join(' ', $tags))."' title='Add' rel='nofollow'>A</a>";
		}
	}

	/**
	 * @param array $tags
	 * @param string $tag
	 * @return string
	 */
	protected function get_subtract_link($tags, $tag) {
		if(in_array("-$tag", $tags)) {
			return "";
		}
		else {
			$tags = array_remove($tags, $tag);
			$tags = array_add($tags, "-$tag");
			return "<a href='".$this->tag_link(join(' ', $tags))."' title='Subtract' rel='nofollow'>S</a>";
		}
	}

	/**
	 * @param string $tag
	 * @return string
	 */
	protected function tag_link($tag) {
		$u_tag = url_escape($tag);
		return make_link("post/list/$u_tag/1");
	}
}
