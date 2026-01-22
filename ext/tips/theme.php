<?php

declare(strict_types=1);

namespace Shimmie2;

use function MicroHTML\{A, DIV, IMG, INPUT, OPTION, SELECT, TABLE, TBODY, TD, TEXTAREA, TH, THEAD, TR};

/**
 * @phpstan-type Tip array{id: int, image: string, text: string, enable: bool}
 */
class TipsTheme extends Themelet
{
    /**
     * @param string[] $images
     */
    public function manageTips(array $images): void
    {
        $select = SELECT(
            ["name" => "image"],
            OPTION(["value" => ""], "- Select Image -")
        );
        foreach ($images as $image) {
            $select->appendChild(
                OPTION(["value" => $image], $image)
            );
        }

        $html = SHM_SIMPLE_FORM(
            make_link("tips/save"),
            TABLE(
                ["class" => "form"],
                TR(
                    TH("Enable"),
                    TD(INPUT(["name" => "enable", "type" => "checkbox", "checked" => true]))
                ),
                TR(
                    TH("Image"),
                    TD($select)
                ),
                TR(
                    TH("Message"),
                    TD(TEXTAREA(["name" => "text"]))
                ),
                TR(
                    TD(["colspan" => 2], SHM_SUBMIT("Submit"))
                )
            )
        );
        Ctx::$page->set_title("Tips List");
        Ctx::$page->add_block(new Block("Add Tip", $html, "main", 10));
    }

    /**
     * @param Tip $tip
     */
    public function showTip(array $tip): void
    {
        $url = Url::base()."/ext/tips/images/";
        $html = DIV(
            ["id" => "tips"],
            empty($tip['image']) ? null : IMG(["src" => $url.url_escape($tip['image'])]),
            " ",
            $tip["text"]
        );
        Ctx::$page->add_block(new Block(null, $html, "subheading", 10));
    }

    /**
     * @param Tip[] $tips
     */
    public function showAll(array $tips): void
    {
        $url = Url::base()."/ext/tips/images/";
        $tbody = TBODY();
        foreach ($tips as $tip) {
            $tbody->appendChild(TR(
                TD(A(["href" => make_link("tips/status/".$tip['id'])], $tip['enable'] ? "Yes" : "No")),
                TD(
                    empty($tip['image']) ?
                        null :
                        IMG(["src" => $url.$tip['image']])
                ),
                TD($tip['text']),
                Ctx::$user->can(TipsPermission::ADMIN) ? TD(A(["href" => make_link("tips/delete/".$tip['id'])], "Delete")) : null
            ));
        }

        $html = TABLE(
            ["class" => "zebra"],
            THEAD(
                TR(
                    TH("Enabled"),
                    TH("Image"),
                    TH("Message"),
                    Ctx::$user->can(TipsPermission::ADMIN) ? TH("Action") : null
                )
            ),
            $tbody
        );

        Ctx::$page->add_block(new Block("All Tips", $html, "main", 20));
    }
}
