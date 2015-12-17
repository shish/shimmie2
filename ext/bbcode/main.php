<?php
/**
 * Name: BBCode
 * Author: Shish <webmaster@shishnet.org>
 * Link: http://code.shishnet.org/shimmie2/
 * License: GPLv2
 * Description: Turns BBCode into HTML
 * Documentation:
 *  Supported tags:
 *  <ul>
 *    <li>[img]url[/img]
 *    <li>[url]<a href="http://code.shishnet.org/shimmie2/">http://code.shishnet.org/</a>[/url]
 *    <li>[email]<a href="mailto:webmaster@shishnet.org">webmaster@shishnet.org</a>[/email]
 *    <li>[b]<b>bold</b>[/b]
 *    <li>[i]<i>italic</i>[/i]
 *    <li>[u]<u>underline</u>[/u]
 *    <li>[s]<s>strikethrough</s>[/s]
 *    <li>[sup]<sup>superscript</sup>[/sup]
 *    <li>[sub]<sub>subscript</sub>[/sub]
 *    <li>[[wiki article]]
 *    <li>[[wiki article|with some text]]
 *    <li>[quote]text[/quote]
 *    <li>[quote=Username]text[/quote]
 *    <li>&gt;&gt;123 (link to image #123)
 *  </ul>
 */

class BBCode extends FormatterExtension {
	/**
	 * @param string $text
	 * @return string
	 */
	public function format(/*string*/ $text) {
		$text = $this->extract_code($text);
		foreach(array(
			"b", "i", "u", "s", "sup", "sub", "h1", "h2", "h3", "h4",
		) as $el) {
			$text = preg_replace("!\[$el\](.*?)\[/$el\]!s", "<$el>$1</$el>", $text);
		}
		$text = preg_replace('!^&gt;&gt;([^\d].+)!', '<blockquote><small>$1</small></blockquote>', $text);
		$text = preg_replace('!&gt;&gt;(\d+)(#c?\d+)?!s', '<a class="shm-clink" data-clink-sel="$2" href="'.make_link('post/view/$1$2').'">&gt;&gt;$1$2</a>', $text);
		$text = preg_replace('!\[anchor=(.*?)\](.*?)\[/anchor\]!s', '<span class="anchor">$2 <a class="alink" href="#bb-$1" name="bb-$1" title="link to this anchor"> ¶ </a></span>', $text);  // add "bb-" to avoid clashing with eg #top
		$text = preg_replace('!\[url=site://(.*?)(#c\d+)?\](.*?)\[/url\]!s', '<a class="shm-clink" data-clink-sel="$2" href="'.make_link('$1$2').'">$3</a>', $text);
		$text = preg_replace('!\[url\]site://(.*?)(#c\d+)?\[/url\]!s', '<a class="shm-clink" data-clink-sel="$2" href="'.make_link('$1$2').'">$1$2</a>', $text);
		$text = preg_replace('!\[url=((?:https?|ftp|irc|mailto)://.*?)\](.*?)\[/url\]!s', '<a href="$1">$2</a>', $text);
		$text = preg_replace('!\[url\]((?:https?|ftp|irc|mailto)://.*?)\[/url\]!s', '<a href="$1">$1</a>', $text);
		$text = preg_replace('!\[email\](.*?)\[/email\]!s', '<a href="mailto:$1">$1</a>', $text);
		$text = preg_replace('!\[img\](https?:\/\/.*?)\[/img\]!s', '<img src="$1">', $text);
		$text = preg_replace('!\[\[([^\|\]]+)\|([^\]]+)\]\]!s', '<a href="'.make_link('wiki/$1').'">$2</a>', $text);
		$text = preg_replace('!\[\[([^\]]+)\]\]!s', '<a href="'.make_link('wiki/$1').'">$1</a>', $text);
		$text = preg_replace("!\n\s*\n!", "\n\n", $text);
		$text = str_replace("\n", "\n<br>", $text);
		$text = preg_replace("/\[quote\](.*?)\[\/quote\]/s", "<blockquote><small>\\1</small></blockquote>", $text);
		$text = preg_replace("/\[quote=(.*?)\](.*?)\[\/quote\]/s", "<blockquote><em>\\1 said:</em><br><small>\\2</small></blockquote>", $text);
		while(preg_match("/\[list\](.*?)\[\/list\]/s", $text))
			$text = preg_replace("/\[list\](.*?)\[\/list\]/s", "<ul>\\1</ul>", $text);
		while(preg_match("/\[ul\](.*?)\[\/ul\]/s", $text))
			$text = preg_replace("/\[ul\](.*?)\[\/ul\]/s", "<ul>\\1</ul>", $text);
		while(preg_match("/\[ol\](.*?)\[\/ol\]/s", $text))
			$text = preg_replace("/\[ol\](.*?)\[\/ol\]/s", "<ol>\\1</ol>", $text);
		$text = preg_replace("/\[li\](.*?)\[\/li\]/s", "<li>\\1</li>", $text);
		$text = preg_replace("#\[\*\]#s", "<li>", $text);
		$text = preg_replace("#<br><(li|ul|ol|/ul|/ol)>#s", "<\\1>", $text);
		$text = preg_replace("#\[align=(left|center|right)\](.*?)\[\/align\]#s", "<div style='text-align:\\1;'>\\2</div>", $text);
		$text = $this->filter_spoiler($text);
		$text = $this->insert_code($text);
		return $text;
	}

	/**
	 * @param string $text
	 * @return string
	 */
	public function strip(/*string*/ $text) {
		foreach(array(
			"b", "i", "u", "s", "sup", "sub", "h1", "h2", "h3", "h4",
			"code", "url", "email", "li",
		) as $el) {
			$text = preg_replace("!\[$el\](.*?)\[/$el\]!s", '$1', $text);
		}
		$text = preg_replace("!\[anchor=(.*?)\](.*?)\[/anchor\]!s", '$2', $text);
		$text = preg_replace("!\[url=(.*?)\](.*?)\[/url\]!s", '$2', $text);
		$text = preg_replace("!\[img\](.*?)\[/img\]!s", "", $text);
		$text = preg_replace("!\[\[([^\|\]]+)\|([^\]]+)\]\]!s", '$2', $text);
		$text = preg_replace("!\[\[([^\]]+)\]\]!s", '$1', $text);
		$text = preg_replace("!\[quote\](.*?)\[/quote\]!s", "", $text);
		$text = preg_replace("!\[quote=(.*?)\](.*?)\[/quote\]!s", "", $text);
		$text = preg_replace("!\[/?(list|ul|ol)\]!", "", $text);
		$text = preg_replace("!\[\*\](.*?)!s", '$1', $text);
		$text = $this->strip_spoiler($text);
		return $text;
	}

	/**
	 * @param string $text
	 * @return string
	 */
	private function filter_spoiler(/*string*/ $text) {
		return str_replace(
			array("[spoiler]","[/spoiler]"),
			array("<span style=\"background-color:#000; color:#000;\">","</span>"),
			$text);
	}

	/**
	 * @param string $text
	 * @return string
	 */
	private function strip_spoiler(/*string*/ $text) {
		$l1 = strlen("[spoiler]");
		$l2 = strlen("[/spoiler]");
		while(true) {
			$start = strpos($text, "[spoiler]");
			if($start === false) break;

			$end = strpos($text, "[/spoiler]");
			if($end === false) break;

			if($end < $start) break;

			$beginning = substr($text, 0, $start);
			$middle = str_rot13(substr($text, $start+$l1, ($end-$start-$l1)));
			$ending = substr($text, $end + $l2, (strlen($text)-$end+$l2));

			$text = $beginning . $middle . $ending;
		}
		return $text;
	}

	/**
	 * @param string $text
	 * @return string
	 */
	private function extract_code(/*string*/ $text) {
		# at the end of this function, the only code! blocks should be
		# the ones we've added -- others may contain malicious content,
		# which would only appear after decoding
		$text = str_replace("[code!]", "[code]", $text);
		$text = str_replace("[/code!]", "[/code]", $text);

		$l1 = strlen("[code]");
		$l2 = strlen("[/code]");
		while(true) {
			$start = strpos($text, "[code]");
			if($start === false) break;

			$end = strpos($text, "[/code]", $start);
			if($end === false) break;

			if($end < $start) break;

			$beginning = substr($text, 0, $start);
			$middle = base64_encode(substr($text, $start+$l1, ($end-$start-$l1)));
			$ending = substr($text, $end + $l2, (strlen($text)-$end+$l2));

			$text = $beginning . "[code!]" . $middle . "[/code!]" . $ending;
		}
		return $text;
	}

	/**
	 * @param string $text
	 * @return string
	 */
	private function insert_code(/*string*/ $text) {
		$l1 = strlen("[code!]");
		$l2 = strlen("[/code!]");
		while(true) {
			$start = strpos($text, "[code!]");
			if($start === false) break;

			$end = strpos($text, "[/code!]");
			if($end === false) break;

			$beginning = substr($text, 0, $start);
			$middle = base64_decode(substr($text, $start+$l1, ($end-$start-$l1)));
			$ending = substr($text, $end + $l2, (strlen($text)-$end+$l2));

			$text = $beginning . "<pre>" . $middle . "</pre>" . $ending;
		}
		return $text;
	}
}

