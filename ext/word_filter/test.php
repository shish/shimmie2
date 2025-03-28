<?php

declare(strict_types=1);

namespace Shimmie2;

final class WordFilterTest extends ShimmiePHPUnitTestCase
{
    public function setUp(): void
    {
        parent::setUp();
        Ctx::$config->set("word_filter", "whore,nice lady\na duck,a kitten\n white ,\tspace\ninvalid");
    }

    public function _doThings(string $in, string $out): void
    {
        self::log_in_as_user();
        $image_id = $this->post_image("tests/pbx_screenshot.jpg", "pbx computer screenshot");
        send_event(new CommentPostingEvent($image_id, Ctx::$user, $in));
        self::get_page("post/view/$image_id");
        self::assert_text($out);
    }

    public function testRegular(): void
    {
        $this->_doThings(
            "posted by a whore",
            "posted by a nice lady"
        );
    }

    public function testReplaceAll(): void
    {
        $this->_doThings(
            "a whore is a whore is a whore",
            "a nice lady is a nice lady is a nice lady"
        );
    }

    public function testMixedCase(): void
    {
        $this->_doThings(
            "monkey WhorE",
            "monkey nice lady"
        );
    }

    public function testOnlyWholeWords(): void
    {
        $this->_doThings(
            "my name is whoretta",
            "my name is whoretta"
        );
    }

    public function testMultipleWords(): void
    {
        $this->_doThings(
            "I would like a duck",
            "I would like a kitten"
        );
    }

    public function testWhitespace(): void
    {
        $this->_doThings(
            "A colour is white",
            "A colour is space"
        );
    }

    public function testIgnoreInvalid(): void
    {
        $this->_doThings(
            "The word was invalid",
            "The word was invalid"
        );
    }
}
