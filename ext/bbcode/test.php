<?php

declare(strict_types=1);

namespace Shimmie2;

class BBCodeTest extends ShimmiePHPUnitTestCase
{
    public function testBasics(): void
    {
        $this->assertEquals(
            "<b>bold</b><i>italic</i>",
            $this->filter("[b]bold[/b][i]italic[/i]")
        );
    }

    public function testStacking(): void
    {
        $this->assertEquals(
            "<b>B</b><i>I</i><b>B</b>",
            $this->filter("[b]B[/b][i]I[/i][b]B[/b]")
        );
        $this->assertEquals(
            "<b>bold<i>bolditalic</i>bold</b>",
            $this->filter("[b]bold[i]bolditalic[/i]bold[/b]")
        );
    }

    public function testFailure(): void
    {
        $this->assertEquals(
            "[b]bold[i]italic",
            $this->filter("[b]bold[i]italic")
        );
    }

    public function testCode(): void
    {
        $this->assertEquals(
            "<pre class='code'>[b]bold[/b]</pre>",
            $this->filter("[code][b]bold[/b][/code]")
        );
    }

    public function testNestedList(): void
    {
        $this->assertEquals(
            "<ul><li>a<ul><li>a<li>b</ul><li>b</ul>",
            $this->filter("[list][*]a[list][*]a[*]b[/list][*]b[/list]")
        );
        $this->assertEquals(
            "<ul><li>a<ol><li>a<li>b</ol><li>b</ul>",
            $this->filter("[ul][*]a[ol][*]a[*]b[/ol][*]b[/ul]")
        );
    }

    public function testSpoiler(): void
    {
        $this->assertEquals(
            "<span style=\"background-color:#000; color:#000;\">ShishNet</span>",
            $this->filter("[spoiler]ShishNet[/spoiler]")
        );
        $this->assertEquals(
            "FuvfuArg",
            $this->strip("[spoiler]ShishNet[/spoiler]")
        );
        #$this->assertEquals(
        #	$this->filter("[spoiler]ShishNet"),
        #	"[spoiler]ShishNet");
    }

    public function testURL(): void
    {
        $this->assertEquals(
            "<a href=\"https://shishnet.org\">https://shishnet.org</a>",
            $this->filter("[url]https://shishnet.org[/url]")
        );
        $this->assertEquals(
            "<a href=\"https://shishnet.org\">ShishNet</a>",
            $this->filter("[url=https://shishnet.org]ShishNet[/url]")
        );
        $this->assertEquals(
            "[url=javascript:alert(\"owned\")]click to fail[/url]",
            $this->filter("[url=javascript:alert(\"owned\")]click to fail[/url]")
        );
    }

    public function testEmailURL(): void
    {
        $this->assertEquals(
            "<a href=\"mailto:spam@shishnet.org\">spam@shishnet.org</a>",
            $this->filter("[email]spam@shishnet.org[/email]")
        );
    }

    public function testAnchor(): void
    {
        $this->assertEquals(
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
        $this->assertEquals(
            '<a class="shm-clink" data-clink-sel="" href="/test/post/view/123">&gt;&gt;123</a>',
            $this->filter("&gt;&gt;123")
        );
        $this->assertEquals(
            '<a class="shm-clink" data-clink-sel="#c456" href="/test/post/view/123#c456">&gt;&gt;123#c456</a>',
            $this->filter("&gt;&gt;123#c456")
        );
        $this->assertEquals(
            '<a class="shm-clink" data-clink-sel="" href="/test/foo/bar">foo/bar</a>',
            $this->filter("[url]site://foo/bar[/url]")
        );
        $this->assertEquals(
            '<a class="shm-clink" data-clink-sel="#c123" href="/test/foo/bar#c123">foo/bar#c123</a>',
            $this->filter("[url]site://foo/bar#c123[/url]")
        );
        $this->assertEquals(
            '<a class="shm-clink" data-clink-sel="" href="/test/foo/bar">look at my post</a>',
            $this->filter("[url=site://foo/bar]look at my post[/url]")
        );
        $this->assertEquals(
            '<a class="shm-clink" data-clink-sel="#c123" href="/test/foo/bar#c123">look at my comment</a>',
            $this->filter("[url=site://foo/bar#c123]look at my comment[/url]")
        );
    }
}
