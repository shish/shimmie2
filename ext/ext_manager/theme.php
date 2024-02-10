<?php

declare(strict_types=1);

namespace Shimmie2;

use function MicroHTML\LABEL;
use function MicroHTML\A;
use function MicroHTML\B;
use function MicroHTML\BR;
use function MicroHTML\IMG;
use function MicroHTML\TABLE;
use function MicroHTML\THEAD;
use function MicroHTML\TFOOT;
use function MicroHTML\TBODY;
use function MicroHTML\TH;
use function MicroHTML\TR;
use function MicroHTML\TD;
use function MicroHTML\INPUT;
use function MicroHTML\DIV;
use function MicroHTML\P;
use function MicroHTML\emptyHTML;
use function MicroHTML\rawHTML;

class ExtManagerTheme extends Themelet
{
    /**
     * @param ExtensionInfo[] $extensions
     */
    public function display_table(Page $page, array $extensions, bool $editable): void
    {
        $tbody = TBODY();

        $form = SHM_SIMPLE_FORM(
            "ext_manager/set",
            TABLE(
                ["id" => 'extensions', "class" => 'zebra'],
                THEAD(TR(
                    $editable ? TH("Enabled") : null,
                    TH("Name"),
                    TH("Docs"),
                    TH("Description")
                )),
                $tbody,
                $editable ? TFOOT(TR(TD(["colspan" => '5'], INPUT(["type" => 'submit', "value" => 'Set Extensions'])))) : null
            )
        );

        $categories = [];
        $last_cat = null;
        foreach ($extensions as $extension) {
            if (
                (!$editable && $extension->visibility === ExtensionVisibility::ADMIN)
                || $extension->visibility === ExtensionVisibility::HIDDEN
            ) {
                continue;
            }

            if($extension->category !== $last_cat) {
                $last_cat = $extension->category;
                $categories[] = $last_cat;
                $tbody->appendChild(
                    TR(
                        ["class" => 'category', "id" => $extension->category->value],
                        TH(["colspan" => '5'], BR(), $last_cat->value)
                    )
                );
            }

            $tbody->appendChild(TR(
                ["data-ext" => $extension->name],
                $editable ? TD(INPUT([
                    "type" => 'checkbox',
                    "name" => "ext_{$extension->key}",
                    "id" => "ext_{$extension->key}",
                    "checked" => ($extension->is_enabled() === true),
                    "disabled" => ($extension->is_supported() === false || $extension->core === true)
                ])) : null,
                TD(LABEL(
                    ["for" => "ext_{$extension->key}"],
                    (
                        ($extension->beta === true ? "[BETA] " : "").
                        (empty($extension->name) ? $extension->key : $extension->name)
                    )
                )),
                TD(
                    // TODO: A proper "docs" symbol would be preferred here.
                    $extension->documentation ?
                        A(
                            ["href" => make_link("ext_doc/" . url_escape($extension->key))],
                            IMG(["src" => 'ext/ext_manager/baseline_open_in_new_black_18dp.png'])
                        ) :
                        null
                ),
                TD(
                    ["style" => 'text-align: left;'],
                    $extension->description,
                    " ",
                    B(["style" => 'color:red'], $extension->get_support_info())
                ),
            ));
        }

        if($editable) {
            foreach ($extensions as $extension) {
                if ($extension->visibility === ExtensionVisibility::HIDDEN && !$extension->core) {
                    $form->appendChild(INPUT([
                        "type" => 'hidden',
                        "name" => "ext_{$extension->key}",
                        "value" => ($extension->is_enabled() === true) ? "on" : "off"
                    ]));
                }
            }
        }

        $cat_html = [
            A(["href" => make_link()], "Index"),
            BR(),
        ];
        foreach ($categories as $cat) {
            $cat_html[] = A(["href" => "#".$cat->value], $cat->value);
        }

        $page->set_title("Extensions");
        $page->set_heading("Extensions");
        $page->add_block(new Block("Navigation", \MicroHTML\joinHTML(BR(), $cat_html), "left", 0));
        $page->add_block(new Block("Extension Manager", $form));
    }

    public function display_doc(Page $page, ExtensionInfo $info): void
    {
        $author = emptyHTML();
        if (count($info->authors) > 0) {
            $author->appendChild(BR());
            $author->appendChild(B(count($info->authors) > 1 ? "Authors: " : "Author: "));
            foreach ($info->authors as $auth => $email) {
                if (!empty($email)) {
                    $author->appendChild(A(["href" => "mailto:$email"], $auth));
                } else {
                    $author->appendChild($auth);
                }
                $author->appendChild(BR());
            }
        }

        $html = DIV(
            ["style" => 'margin: auto; text-align: left; width: 512px;'],
            $author,
            ($info->link ? emptyHTML(BR(), B("Home Page"), A(["href" => $info->link], "Link")) : null),
            P(rawHTML($info->documentation ?? "(This extension has no documentation)")),
            // <hr>,
            P(A(["href" => make_link("ext_manager")], "Back to the list"))
        );

        $page->set_title("Documentation for " . html_escape($info->name));
        $page->set_heading(html_escape($info->name));
        $page->add_block(new NavBlock());
        $page->add_block(new Block("Documentation", $html));
    }
}
