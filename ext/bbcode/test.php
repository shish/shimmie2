<?php

declare(strict_types=1);

namespace Shimmie2;

final class BBCodeTest extends ShimmiePHPUnitTestCase
{
    public function testBasics(): void
    {
        self::assertEquals(
            "<b>bold</b><i>italic</i>",
            $this->filter("[b]bold[/b][i]italic[/i]")
        );
    }

    public function testStacking(): void
    {
        self::assertEquals(
            "<b>B</b><i>I</i><b>B</b>",
            $this->filter("[b]B[/b][i]I[/i][b]B[/b]")
        );
        self::assertEquals(
            "<b>bold<i>bolditalic</i>bold</b>",
            $this->filter("[b]bold[i]bolditalic[/i]bold[/b]")
        );
    }

    public function testFailure(): void
    {
        self::assertEquals(
            "[b]bold[i]italic",
            $this->filter("[b]bold[i]italic")
        );
    }

    public function testCode(): void
    {
        self::assertEquals(
            "<pre><code>[b]bold[/b]</code></pre>",
            $this->filter("[code][b]bold[/b][/code]")
        );
    }

    public function testNestedList(): void
    {
        self::assertEquals(
            "<ul><li>a<ul><li>a<li>b</ul><li>b</ul>",
            $this->filter("[list][*]a[list][*]a[*]b[/list][*]b[/list]")
        );
        self::assertEquals(
            "<ul><li>a<ol><li>a<li>b</ol><li>b</ul>",
            $this->filter("[ul][*]a[ol][*]a[*]b[/ol][*]b[/ul]")
        );
    }

    public function testSpoiler(): void
    {
        self::assertEquals(
            "<span style=\"background-color:#000; color:#000;\">ShishNet</span>",
            $this->filter("[spoiler]ShishNet[/spoiler]")
        );
        self::assertEquals(
            "FuvfuArg",
            $this->strip("[spoiler]ShishNet[/spoiler]")
        );
        #self::assertEquals(
        #	$this->filter("[spoiler]ShishNet"),
        #	"[spoiler]ShishNet");
    }

    public function testURL(): void
    {
        self::assertEquals(
            "<a href=\"https://shishnet.org\">https://shishnet.org</a>",
            $this->filter("[url]https://shishnet.org[/url]")
        );
        self::assertEquals(
            "<a href=\"https://shishnet.org\">ShishNet</a>",
            $this->filter("[url=https://shishnet.org]ShishNet[/url]")
        );
        self::assertEquals(
            "[url=javascript:alert(\"owned\")]click to fail[/url]",
            $this->filter("[url=javascript:alert(\"owned\")]click to fail[/url]")
        );
    }

    public function testEmailURL(): void
    {
        self::assertEquals(
            "<a href=\"mailto:spam@shishnet.org\">spam@shishnet.org</a>",
            $this->filter("[email]spam@shishnet.org[/email]")
        );
    }

    public function testAnchor(): void
    {
        self::assertEquals(
            '<span class="anchor">Rules <a class="alink" href="#bb-rules" name="bb-rules" title="link to this anchor"> ¶ </a></span>',
            $this->filter("[anchor=rules]Rules[/anchor]")
        );
    }

    private function filter(string $in): string
    {
        $bb = new BBCode();
        return $bb->_format($in);
    }

    private function strip(string $in): string
    {
        $bb = new BBCode();
        return $bb->strip($in);
    }

    public function testSiteLinks(): void
    {
        self::assertEquals(
            '<a class="shm-clink" data-clink-sel="" href="/test/post/view/123">&gt;&gt;123</a>',
            $this->filter("&gt;&gt;123")
        );
        self::assertEquals(
            '<a class="shm-clink" data-clink-sel="#c456" href="/test/post/view/123#c456">&gt;&gt;123#c456</a>',
            $this->filter("&gt;&gt;123#c456")
        );
        self::assertEquals(
            '<a class="shm-clink" data-clink-sel="" href="/test/foo/bar">foo/bar</a>',
            $this->filter("[url]site://foo/bar[/url]")
        );
        self::assertEquals(
            '<a class="shm-clink" data-clink-sel="#c123" href="/test/foo/bar#c123">foo/bar#c123</a>',
            $this->filter("[url]site://foo/bar#c123[/url]")
        );
        self::assertEquals(
            '<a class="shm-clink" data-clink-sel="" href="/test/foo/bar">look at my post</a>',
            $this->filter("[url=site://foo/bar]look at my post[/url]")
        );
        self::assertEquals(
            '<a class="shm-clink" data-clink-sel="#c123" href="/test/foo/bar#c123">look at my comment</a>',
            $this->filter("[url=site://foo/bar#c123]look at my comment[/url]")
        );
    }
}
