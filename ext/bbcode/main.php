<?php

class BBCode extends FormatterExtension {
	public function format($text) {
		$text = wordwrap($text, 80, " ", true);
		$text = $this->extract_code($text);
		$text = preg_replace("/\[b\](.*?)\[\/b\]/s", "<b>\\1</b>", $text);
		$text = preg_replace("/\[i\](.*?)\[\/i\]/s", "<i>\\1</i>", $text);
		$text = preg_replace("/\[u\](.*?)\[\/u\]/s", "<u>\\1</u>", $text);
		$text = preg_replace("/\[s\](.*?)\[\/s\]/s", "<s>\\1</s>", $text);
		$text = preg_replace("/&gt;&gt;(\d+)/s", "<a href=\"".make_link("post/view/\\1")."\">&gt;&gt;\\1</a>", $text);
		$text = preg_replace("/&gt;&gt;([^\d].+)/", "<blockquote><small>\\1</small></blockquote>", $text);
		$text = preg_replace("/\[url=((?:https?|ftp|irc|mailto):\/\/.*?)\](.*?)\[\/url\]/s", "<a href=\"\\1\">\\2</a>", $text);
		$text = preg_replace("/\[url\]((?:https?|ftp|irc|mailto):\/\/.*?)\[\/url\]/s", "<a href=\"\\1\">\\1</a>", $text);
		$text = preg_replace("/\[\[([^\|\]]+)\|([^\]]+)\]\]/s", "<a href=\"".make_link("wiki/\\1")."\">\\2</a>", $text);
		$text = preg_replace("/\[\[([^\]]+)\]\]/s", "<a href=\"".make_link("wiki/\\1")."\">\\1</a>", $text);
		$text = preg_replace("/\n\s*\n/", "\n\n", $text);
		$text = str_replace("\n", "\n<br>", $text);
		$text = preg_replace("/\[quote\](.*?)\[\/quote\]/s", "<blockquote><small>\\1</small></blockquote>", $text);
		$text = preg_replace("/\[quote=(.*?)\](.*?)\[\/quote\]/s", "<small><small>Quoting \\1</small></small><blockquote><small>\\2</small></blockquote>", $text);
		$text = preg_replace("/\[h1\](.*?)\[\/h1\]/s", "<h1>\\1</h1>", $text);
		$text = preg_replace("/\[h2\](.*?)\[\/h2\]/s", "<h2>\\1</h2>", $text);
		$text = preg_replace("/\[h3\](.*?)\[\/h3\]/s", "<h3>\\1</h3>", $text);
		$text = preg_replace("/\[h4\](.*?)\[\/h4\]/s", "<h4>\\1</h4>", $text);
		while(preg_match("/\[list\](.*?)\[\/list\]/s", $text))
			$text = preg_replace("/\[list\](.*?)\[\/list\]/s", "<ul>\\1</ul>", $text);
		while(preg_match("/\[ul\](.*?)\[\/ul\]/s", $text))
			$text = preg_replace("/\[ul\](.*?)\[\/ul\]/s", "<ul>\\1</ul>", $text);
		while(preg_match("/\[ol\](.*?)\[\/ol\]/s", $text))
			$text = preg_replace("/\[ol\](.*?)\[\/ol\]/s", "<ol>\\1</ol>", $text);
		$text = preg_replace("/\[li\](.*?)\[\/li\]/s", "<li>\\1</li>", $text);
		$text = preg_replace("#\[\*\]#s", "<li>", $text);
		$text = preg_replace("#<br><(li|ul|ol|/ul|/ol)>#s", "<\\1>", $text);
		$text = $this->filter_spoiler($text);
		$text = $this->insert_code($text);
		return $text;
	}

	public function strip($text) {
		$text = wordwrap($text, 80, " ", true);
		$text = preg_replace("/\[b\](.*?)\[\/b\]/s", "\\1", $text);
		$text = preg_replace("/\[i\](.*?)\[\/i\]/s", "\\1", $text);
		$text = preg_replace("/\[u\](.*?)\[\/u\]/s", "\\1", $text);
		$text = preg_replace("/\[s\](.*?)\[\/s\]/s", "\\1", $text);
		$text = preg_replace("/\[code\](.*?)\[\/code\]/s", "\\1", $text);
		$text = preg_replace("/\[url=(.*?)\](.*?)\[\/url\]/s", "\\2", $text);
		$text = preg_replace("/\[url\](.*?)\[\/url\]/s", "\\1", $text);
		$text = preg_replace("/\[\[([^\|\]]+)\|([^\]]+)\]\]/s", "\\2", $text);
		$text = preg_replace("/\[\[([^\]]+)\]\]/s", "\\1", $text);
		$text = preg_replace("/\[quote\](.*?)\[\/quote\]/s", "", $text);
		$text = preg_replace("/\[quote=(.*?)\](.*?)\[\/quote\]/s", "", $text);
		$text = preg_replace("/\[h1\](.*?)\[\/h1\]/s", "\\1", $text);
		$text = preg_replace("/\[h2\](.*?)\[\/h2\]/s", "\\1", $text);
		$text = preg_replace("/\[h3\](.*?)\[\/h3\]/s", "\\1", $text);
		$text = preg_replace("/\[h4\](.*?)\[\/h4\]/s", "\\1", $text);
		$text = preg_replace("/\[\/?(list|ul|ol)\]/", "", $text);
		$text = preg_replace("/\[li\](.*?)\[\/li\]/s", "\\1", $text);
		$text = preg_replace("/\[\*\](.*?)/s", "\\1", $text);
		$text = $this->strip_spoiler($text);
		return $text;
	}


	private function filter_spoiler($text) {
		return str_replace(
			array("[spoiler]","[/spoiler]"),
			array("<span style=\"background-color:#000; color:#000;\">","</span>"),
			$text);
	}

	private function strip_spoiler($text) {
		$l1 = strlen("[spoiler]");
		$l2 = strlen("[/spoiler]");
		while(true) {
			$start = strpos($text, "[spoiler]");
			if($start === false) break;

			$end = strpos($text, "[/spoiler]");
			if($end === false) break;

			$beginning = substr($text, 0, $start);
			$middle = str_rot13(substr($text, $start+$l1, ($end-$start-$l1)));
			$ending = substr($text, $end + $l2, (strlen($text)-$end+$l2));

			$text = $beginning . $middle . $ending;
		}
		return $text;
	}

	private function extract_code($text) {
		# at the end of this function, the only code! blocks should be
		# the ones we've added -- others may contain malicious content,
		# which would only appear after decoding
		$text = preg_replace("/\[code!\](.*?)\[\/code!\]/s", "[code]\\1[/code]", $text);

		$l1 = strlen("[code]");
		$l2 = strlen("[/code]");
		while(true) {
			$start = strpos($text, "[code]");
			if($start === false) break;

			$end = strpos($text, "[/code]");
			if($end === false) break;

			$beginning = substr($text, 0, $start);
			$middle = base64_encode(substr($text, $start+$l1, ($end-$start-$l1)));
			$ending = substr($text, $end + $l2, (strlen($text)-$end+$l2));

			$text = $beginning . "[code!]" . $middle . "[/code!]" . $ending;
		}
		return $text;
	}

	private function insert_code($text) {
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
add_event_listener(new BBCode());
?>
