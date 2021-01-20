<?php declare(strict_types=1);

class BanWords extends Extension
{
    public function onInitExt(InitExtEvent $event)
    {
        global $config;
        $config->set_default_string('banned_words', "
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

    public function onCommentPosting(CommentPostingEvent $event)
    {
        global $user;
        if (!$user->can(Permissions::BYPASS_COMMENT_CHECKS)) {
            $this->test_text($event->comment, new CommentPostingException("Comment contains banned terms"));
        }
    }

    public function onSourceSet(SourceSetEvent $event)
    {
        $this->test_text($event->source, new SCoreException("Source contains banned terms"));
    }

    public function onTagSet(TagSetEvent $event)
    {
        $this->test_text(Tag::implode($event->tags), new SCoreException("Tags contain banned terms"));
    }

    public function onSetupBuilding(SetupBuildingEvent $event)
    {
        $sb = $event->panel->create_new_block("Banned Phrases");
        $sb->add_label("One per line, lines that start with slashes are treated as regex<br/>");
        $sb->add_longtext_option("banned_words");
        $failed = [];
        foreach ($this->get_words() as $word) {
            if ($word[0] == '/') {
                if (preg_match($word, "") === false) {
                    $failed[] = $word;
                }
            }
        }
        if ($failed) {
            $sb->add_label("Failed regexes: ".join(", ", $failed));
        }
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
                if (preg_match($word, $comment) === 1) {
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

    private function get_words(): array
    {
        global $config;
        $words = [];

        $banned = $config->get_string("banned_words");
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
