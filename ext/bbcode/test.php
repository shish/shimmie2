<?php

declare(strict_types=1);

namespace Shimmie2;

final class BBCodeTest extends ShimmiePHPUnitTestCase
{
    public function testBasics(): void
    {
        self::assertSame(
            "<b>bold</b><i>italic</i>",
            $this->filter("[b]bold[/b][i]italic[/i]")
        );
    }

    public function testStacking(): void
    {
        self::assertSame(
            "<b>B</b><i>I</i><b>B</b>",
            $this->filter("[b]B[/b][i]I[/i][b]B[/b]")
        );
        self::assertSame(
            "<b>bold<i>bolditalic</i>bold</b>",
            $this->filter("[b]bold[i]bolditalic[/i]bold[/b]")
        );
    }

    public function testFailure(): void
    {
        self::assertSame(
            "[b]bold[i]italic",
            $this->filter("[b]bold[i]italic")
        );
    }

    public function testCode(): void
    {
        self::assertSame(
            "<pre><code>[b]bold[/b]</code></pre>",
            $this->filter("[code][b]bold[/b][/code]")
        );
    }

    public function testNestedList(): void
    {
        self::assertSame(
            "<ul><li>a<ul><li>a<li>b</ul><li>b</ul>",
            $this->filter("[list][*]a[list][*]a[*]b[/list][*]b[/list]")
        );
        self::assertSame(
            "<ul><li>a<ol><li>a<li>b</ol><li>b</ul>",
            $this->filter("[ul][*]a[ol][*]a[*]b[/ol][*]b[/ul]")
        );
    }

    public function testSpoiler(): void
    {
        self::assertSame(
            "<span style=\"background-color:#000; color:#000;\">ShishNet</span>",
            $this->filter("[spoiler]ShishNet[/spoiler]")
        );
        self::assertSame(
            "FuvfuArg",
            $this->strip("[spoiler]ShishNet[/spoiler]")
        );
        self::assertSame(
            "[spoiler]ShishNet",
            $this->filter("[spoiler]ShishNet")
        );
        self::assertSame(
            '<span style="background-color:#000; color:#000;">a<span style="background-color:#000; color:#000;">b</span>c</span>',
            $this->filter("[spoiler]a[spoiler]b[/spoiler]c[/spoiler]")
        );
    }

    public function testURL(): void
    {
        self::assertSame(
            "<a href=\"https://shishnet.org\">https://shishnet.org</a>",
            $this->filter("[url]https://shishnet.org[/url]")
        );
        self::assertSame(
            "<a href=\"https://shishnet.org\">ShishNet</a>",
            $this->filter("[url=https://shishnet.org]ShishNet[/url]")
        );
        self::assertSame(
            "[url=javascript:alert(\"owned\")]click to fail[/url]",
            $this->filter("[url=javascript:alert(\"owned\")]click to fail[/url]")
        );
    }

    public function testEmailURL(): void
    {
        self::assertSame(
            "<a href=\"mailto:spam@shishnet.org\">spam@shishnet.org</a>",
            $this->filter("[email]spam@shishnet.org[/email]")
        );
    }

    public function testAnchor(): void
    {
        self::assertSame(
            '<span class="anchor">Rules <a class="alink" href="#bb-rules" name="bb-rules" title="link to this anchor"> ¶ </a></span>',
            $this->filter("[anchor=rules]Rules[/anchor]")
        );
    }

    public function testFormattingInsideLinks(): void
    {
        self::assertSame(
            '<a href="https://shishnet.org"><b>Shish</b>Net</a>',
            $this->filter("[url=https://shishnet.org][b]Shish[/b]Net[/url]")
        );
        self::assertSame(
            '<a href="https://shishnet.org"><img alt="user image" src="https://shishnet.org/a.png"></a>',
            $this->filter("[url=https://shishnet.org][img]https://shishnet.org/a.png[/img][/url]")
        );
    }

    public function testCodeIsNotFormatted(): void
    {
        self::assertSame(
            "<pre><code>a\n[url=https://x]b[/url]\n&gt;&gt;1</code></pre>",
            $this->filter("[code]a\n[url=https://x]b[/url]\n&gt;&gt;1[/code]")
        );
        self::assertSame(
            '<a href="https://x"><pre><code>[b]b[/b]</code></pre></a>',
            $this->filter("[url=https://x][code][b]b[/b][/code][/url]")
        );
        // code can't be smuggled into an attribute
        self::assertSame(
            '[img]https://x<pre><code>a</code></pre>[/img]',
            $this->filter("[img]https://x[code]a[/code][/img]")
        );
    }

    public function testUglySiteLinks(): void
    {
        Ctx::$config->set(SetupConfig::NICE_URLS, false);
        try {
            self::assertSame(
                '<a class="shm-clink" data-clink-sel="#c456" href="/test/index.php?q=post%2Fview%2F123#c456">&gt;&gt;123#c456</a>',
                $this->filter("&gt;&gt;123#c456")
            );
            self::assertSame(
                '<a class="shm-clink" data-clink-sel="#c123" href="/test/index.php?q=foo%2Fbar#c123">look</a>',
                $this->filter("[url=site://foo/bar#c123]look[/url]")
            );
        } finally {
            Ctx::$config->set(SetupConfig::NICE_URLS, true);
        }
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
        self::assertSame(
            '<a class="shm-clink" data-clink-sel="" href="/test/post/view/123">&gt;&gt;123</a>',
            $this->filter("&gt;&gt;123")
        );
        self::assertSame(
            '<a class="shm-clink" data-clink-sel="#c456" href="/test/post/view/123#c456">&gt;&gt;123#c456</a>',
            $this->filter("&gt;&gt;123#c456")
        );
        self::assertSame(
            '<a class="shm-clink" data-clink-sel="" href="/test/foo/bar">foo/bar</a>',
            $this->filter("[url]site://foo/bar[/url]")
        );
        self::assertSame(
            '<a class="shm-clink" data-clink-sel="#c123" href="/test/foo/bar#c123">foo/bar#c123</a>',
            $this->filter("[url]site://foo/bar#c123[/url]")
        );
        self::assertSame(
            '<a class="shm-clink" data-clink-sel="" href="/test/foo/bar">look at my post</a>',
            $this->filter("[url=site://foo/bar]look at my post[/url]")
        );
        self::assertSame(
            '<a class="shm-clink" data-clink-sel="#c123" href="/test/foo/bar#c123">look at my comment</a>',
            $this->filter("[url=site://foo/bar#c123]look at my comment[/url]")
        );
    }
}
