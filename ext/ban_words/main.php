<?php

declare(strict_types=1);

namespace Shimmie2;

class BanWords extends Extension
{
    public function onInitExt(InitExtEvent $event): void
    {
        global $config;
        $config->set_default_string(BanWordsConfig::BANNED_WORDS, "
a href=
anal
blowjob
/buy-.*-online/
casino
cialis
doors.txt
fuck
hot video
kaboodle.com
lesbian
nexium
penis
/pokerst.*/
pornhub
porno
purchase
sex
sex tape
spinnenwerk.de
thx for all
TRAMADOL
ultram
very nice site
viagra
xanax
");
    }

    public function onCommentPosting(CommentPostingEvent $event): void
    {
        global $user;
        if (!$user->can(Permissions::BYPASS_COMMENT_CHECKS)) {
            $this->test_text($event->comment, new CommentPostingException("Comment contains banned terms"));
        }
    }

    public function onSourceSet(SourceSetEvent $event): void
    {
        $this->test_text($event->source, new UserError("Source contains banned terms"));
    }

    public function onTagSet(TagSetEvent $event): void
    {
        $this->test_text(Tag::implode($event->new_tags), new UserError("Tags contain banned terms"));
    }

    /**
     * Throws if the comment contains banned words.
     */
    private function test_text(string $comment, SCoreException $ex): void
    {
        $comment = strtolower($comment);

        foreach ($this->get_words() as $word) {
            if ($word[0] == '/') {
                // lines that start with slash are regex
                if (\Safe\preg_match($word, $comment)) {
                    throw $ex;
                }
            } else {
                // other words are literal
                if (str_contains($comment, $word)) {
                    throw $ex;
                }
            }
        }
    }

    /**
     * @return string[]
     */
    public static function get_words(): array
    {
        global $config;
        $words = [];

        $banned = $config->get_string(BanWordsConfig::BANNED_WORDS);
        foreach (explode("\n", $banned) as $word) {
            $word = trim(strtolower($word));
            if (strlen($word) == 0) {
                // line is blank
                continue;
            }
            $words[] = $word;
        }

        return $words;
    }

    public function get_priority(): int
    {
        return 30;
    }
}
