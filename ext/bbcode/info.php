<?php

declare(strict_types=1);

namespace Shimmie2;

class BBCodeInfo extends ExtensionInfo
{
    public const KEY = "bbcode";

    public string $key = self::KEY;
    public string $name = "BBCode";
    public string $url = self::SHIMMIE_URL;
    public array $authors = self::SHISH_AUTHOR;
    public string $license = self::LICENSE_GPLV2;
    public bool $core = true;
    public string $description = "Turns BBCode into HTML";
    public ?string $documentation =
"  Basic formatting tags:
   <ul>
     <li>[b]<b>bold</b>[/b]
     <li>[i]<i>italic</i>[/i]
     <li>[u]<u>underline</u>[/u]
     <li>[s]<s>strikethrough</s>[/s]
     <li>[sup]<sup>superscript</sup>[/sup]
     <li>[sub]<sub>subscript</sub>[/sub]
     <li>[h1]Heading 1[/h1]
     <li>[h2]Heading 2[/h2]
     <li>[h3]Heading 3[/h3]
     <li>[h4]Heading 4[/h4]
     <li>[align=left|center|right]Aligned Text[/align]
   </ul>
   <br>
   Link tags:
   <ul>
     <li>[img]url[/img]
     <li>[url]<a href=\"{self::SHIMMIE_URL}\">https://code.shishnet.org/</a>[/url]
     <li>[url=<a href=\"{self::SHIMMIE_URL}\">https://code.shishnet.org/</a>]some text[/url]
     <li>[url]site://ext_doc/bbcode[/url]
     <li>[url=site://ext_doc/bbcode]Link to BBCode docs[/url]
     <li>[email]<a href=\"mailto:{self::SHISH_EMAIL}\">webmaster@shishnet.org</a>[/email]
     <li>[[wiki article]]
     <li>[[wiki article|with some text]]
     <li>&gt;&gt;123 (link to post #123)
     <li>[anchor=target]Scroll to #bb-target[/anchor]
   </ul>
   <br>
   More format Tags:
   <ul>
     <li>[list]Unordered list[/list]
     <li>[ul]Unordered list[/ul]
     <li>[ol]Ordered list[/ol]
     <li>[li]List Item[/li]
     <li>[code]<pre>print(\"Hello World!\");</pre>[/code]
     <li>[spoiler]<span style=\"background-color:#000; color:#000;\">Voldemort is bad</span>[/spoiler]
     <li>[quote]<blockquote><small>To be or not to be...</small></blockquote>[/quote]
     <li>[quote=Shakespeare]<blockquote><em>Shakespeare said:</em><br><small>... That is the question</small></blockquote>[/quote]
   </ul>";
}
