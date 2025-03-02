<?php

declare(strict_types=1);

namespace Shimmie2;

use PHPUnit\Framework\TestCase;

class UtilTest extends TestCase
{
    public function test_get_theme(): void
    {
        $this->assertEquals("default", get_theme());
    }

    public function test_get_memory_limit(): void
    {
        $this->assertGreaterThan(0, get_memory_limit());
    }

    public function test_check_gd_version(): void
    {
        $this->assertGreaterThanOrEqual(0, check_gd_version());
    }

    public function test_check_im_version(): void
    {
        $this->assertGreaterThanOrEqual(0, check_im_version());
    }

    public function test_human_filesize(): void
    {
        $this->assertEquals("123.00B", human_filesize(123));
        $this->assertEquals("123B", human_filesize(123, 0));
        $this->assertEquals("120.56KB", human_filesize(123456));
    }

    public function test_generate_key(): void
    {
        $this->assertEquals(20, strlen(generate_key()));
    }

    public function test_contact_link(): void
    {
        $this->assertEquals(
            "mailto:asdf@example.com",
            contact_link("asdf@example.com")
        );
        $this->assertEquals(
            "http://example.com",
            contact_link("http://example.com")
        );
        $this->assertEquals(
            "https://foo.com/bar",
            contact_link("foo.com/bar")
        );
        $this->assertEquals(
            "john",
            contact_link("john")
        );
    }

    public function test_get_user(): void
    {
        // TODO: HTTP_AUTHORIZATION
        // TODO: cookie user + session
        // fallback to anonymous
        $this->assertEquals(
            "Anonymous",
            _get_user()->name
        );
    }
}
