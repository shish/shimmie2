<?php

declare(strict_types=1);

namespace Shimmie2;

use function MicroHTML\{A, BR, DIV, INPUT, LABEL, SMALL, SUP, TABLE, TBODY, TD, TEXTAREA, TH, THEAD, TR, emptyHTML};

use MicroHTML\HTMLElement;

/**
 * @phpstan-type Thread array{id:int,title:string,sticky:bool,user_name:string,uptodate:string,response_count:int}
 * @phpstan-type Post array{id:int,user_name:string,user_class:string,date:string,message:string}
 */
class ForumTheme extends Themelet
{
    /**
     * @param Thread[] $threads
     */
    public function display_thread_list(array $threads, bool $showAdminOptions, int $pageNumber, int $totalPages): void
    {
        if (count($threads) === 0) {
            $html = emptyHTML("There are no threads to show.");
        } else {
            $html = $this->make_thread_list($threads, $showAdminOptions);
        }

        Ctx::$page->set_title("Forum");
        Ctx::$page->add_block(new Block("Forum", $html, "main", 10));
        $this->display_paginator("forum/index", null, $pageNumber, $totalPages);
    }

    public function display_new_thread_composer(?string $threadText = null, ?string $threadTitle = null): void
    {
        $max_characters = Ctx::$config->get(ForumConfig::MAX_CHARS_PER_POST);

        $html = SHM_SIMPLE_FORM(
            make_link("forum/create"),
            TABLE(
                ["class" => "form"],
                TR(
                    TH("Title"),
                    TD(INPUT(["type" => "text", "name" => "title", "value" => $threadTitle]))
                ),
                TR(
                    TH("Message"),
                    TD(TEXTAREA(
                        ["id" => "message", "name" => "message"],
                        $threadText
                    ))
                ),
                TR(
                    TD(),
                    TD(SMALL("Max characters allowed: $max_characters."))
                ),
                Ctx::$user->can(ForumPermission::FORUM_ADMIN) ? TR(
                    TD(),
                    TD(
                        LABEL(["for" => "sticky"], "Sticky:"),
                        INPUT(["name" => "sticky", "id" => "sticky", "type" => "checkbox", "value" => "Y"])
                    )
                ) : null,
                TR(
                    TD(
                        ["colspan" => 2],
                        SHM_SUBMIT("Create"),
                    )
                )
            )
        );

        $blockTitle = "Write a new thread";
        Ctx::$page->set_title($blockTitle);
        Ctx::$page->add_block(new Block($blockTitle, $html, "main", 120));
    }

    public function display_new_post_composer(int $threadID): void
    {
        $max_characters = Ctx::$config->get(ForumConfig::MAX_CHARS_PER_POST);

        $html = SHM_SIMPLE_FORM(
            make_link("forum/answer"),
            INPUT(["type" => "hidden", "name" => "threadID", "value" => $threadID]),
            TABLE(
                ["class" => "form"],
                TR(
                    TH("Message"),
                    TD(TEXTAREA(["id" => "message", "name" => "message"]))
                ),
                TR(
                    TD(),
                    TD(SMALL("Max characters allowed: $max_characters."))
                ),
                TR(
                    TD(
                        ["colspan" => 2],
                        SHM_SUBMIT("Reply"),
                    )
                )
            )
        );

        Ctx::$page->add_block(new Block("Answer to this thread", $html, "main", 130));
    }


    /**
     * @param array<Post> $posts
     */
    public function display_thread(array $posts, bool $showAdminOptions, string $threadTitle, int $threadID, int $pageNumber, int $totalPages): void
    {
        $posts_per_page = Ctx::$config->get(ForumConfig::POSTS_PER_PAGE);

        $current_post = 0;

        $tbody = TBODY();
        foreach ($posts as $post) {
            /** @var BuildAvatarEvent $avatar_e */
            $avatar_e = send_event(new BuildAvatarEvent(User::by_name($post["user_name"])));
            $avatar = $avatar_e->html;

            $current_post++;

            $post_number = (($pageNumber - 1) * $posts_per_page) + $current_post;
            $tbody->appendChild(
                emptyHTML(
                    TR(
                        ["class" => "postHead"],
                        TD(["class" => "forumSupuser"]),
                        TD(
                            ["class" => "forumSupmessage"],
                            DIV(
                                ["class" => "deleteLink"],
                                $showAdminOptions
                                    ? SHM_SIMPLE_FORM(
                                        make_link("forum/delete/$threadID/" . $post['id']),
                                        SHM_SUBMIT("Delete"),
                                    )
                                    : null
                            )
                        )
                    ),
                    TR(
                        ["class" => "posBody"],
                        TD(
                            ["class" => "forumUser"],
                            A(["href" => make_link("user/".$post["user_name"])], $post["user_name"]),
                            BR(),
                            SUP(["class" => "user_rank"], $post["user_class"]),
                            BR(),
                            $avatar,
                            BR()
                        ),
                        TD(
                            ["class" => "forumMessage"],
                            DIV(["class" => "postDate"], SMALL(SHM_DATE($post['date']))),
                            DIV(["class" => "postNumber"], " #".$post_number),
                            BR(),
                            DIV(["class" => "postMessage"], format_text($post["message"]))
                        )
                    ),
                    TR(
                        ["class" => "postFoot"],
                        TD(["class" => "forumSubuser"]),
                        TD(["class" => "forumSubmessage"])
                    )
                )
            );
        }

        $html = emptyHTML(
            DIV(
                ["id" => "returnLink"],
                A(["href" => make_link("forum/index")], "Return")
            ),
            BR(),
            BR(),
            TABLE(
                ["id" => "threadPosts", "class" => "zebra"],
                THEAD(
                    TR(
                        TH(["id" => "threadHeadUser"], "User"),
                        TH("Message")
                    )
                ),
                $tbody
            )
        );

        $this->display_paginator("forum/view/".$threadID, null, $pageNumber, $totalPages);
        Ctx::$page->set_title($threadTitle);
        Ctx::$page->add_block(new Block($threadTitle, $html, "main", 20));
    }

    public function add_actions_block(int $threadID): void
    {
        $html = SHM_SIMPLE_FORM(
            make_link("forum/nuke/".$threadID),
            SHM_SUBMIT("Delete Thread"),
        );
        Ctx::$page->add_block(new Block("Admin Actions", $html, "left"));
    }

    /**
     * @param Thread[] $threads
     */
    private function make_thread_list(array $threads, bool $showAdminOptions): HTMLElement
    {
        $tbody = TBODY();
        $html = TABLE(
            ["id" => "threadList", "class" => "zebra"],
            THEAD(
                TR(
                    TH("Title"),
                    TH("Author"),
                    TH("Updated"),
                    TH("Responses"),
                    $showAdminOptions ? TH("Actions") : null
                )
            ),
            $tbody
        );

        foreach ($threads as $thread) {
            $titleSubString = Ctx::$config->get(ForumConfig::TITLE_SUBSTRING);
            $title = truncate($thread["title"], $titleSubString);

            $tbody->appendChild(
                TR(
                    TD(
                        ["class" => "left"],
                        $thread["sticky"] ? "Sticky: " : "",
                        A(["href" => make_link("forum/view/".$thread["id"])], $title)
                    ),
                    TD(
                        A(["href" => make_link("user/".$thread["user_name"])], $thread["user_name"])
                    ),
                    TD(SHM_DATE($thread["uptodate"])),
                    TD($thread["response_count"]),
                    $showAdminOptions ? TD(
                        SHM_SIMPLE_FORM(
                            make_link("forum/nuke/".$thread["id"]),
                            SHM_SUBMIT("Delete"),
                        )
                    ) : null
                )
            );
        }

        return $html;
    }
}
