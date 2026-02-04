<?php

declare(strict_types=1);

namespace Shimmie2;

use function MicroHTML\{A, B, BR, DIV, IMG, INPUT, LABEL, P, TABLE, TBODY, TD, TR, emptyHTML, joinHTML};

class ExtManagerTheme extends Themelet
{
    /**
     * @param ExtensionInfo[] $extensions
     */
    public function display_table(array $extensions): void
    {
        $form = SHM_FORM(
            make_link("ext_manager/set"),
            id: "extensions",
            children: [INPUT(["class" => "setupsubmit", "type" => 'submit', "value" => 'Set Extensions'])],
        );
        foreach ($extensions as $extension) {
            if (
                $extension->visibility === ExtensionVisibility::HIDDEN
                && !$extension->core
                && $extension::is_enabled()
            ) {
                $form->appendChild(INPUT([
                    "type" => 'hidden',
                    "name" => "extensions[]",
                    "value" => $extension::KEY
                ]));
            }
        }
        Ctx::$page->add_block(new Block(null, $form, position: 99));

        $groups = [];
        foreach ($extensions as $extension) {
            if ($extension->visibility === ExtensionVisibility::HIDDEN) {
                continue;
            }
            $groups[$extension->category->value][] = $extension;
        }
        ksort($groups);

        foreach ($groups as $cat => $exts) {
            $tbody = TBODY();
            foreach ($exts as $extension) {
                $tbody->appendChild(TR(
                    ["data-ext" => $extension->name],
                    TD(INPUT([
                        "type" => 'checkbox',
                        "name" => "extensions[]",
                        "form" => "extensions",
                        "id" => "ext_" . $extension::KEY,
                        "value" => $extension::KEY,
                        "checked" => ($extension::is_enabled() === true),
                        "disabled" => ($extension->is_supported() === false || $extension->core === true)
                    ])),
                    TD(LABEL(
                        ["for" => "ext_" . $extension::KEY],
                        (
                            ($extension->beta === true ? "[BETA] " : "").
                            (empty($extension->name) ? $extension::KEY : $extension->name)
                        )
                    )),
                    TD(
                        // TODO: A proper "docs" symbol would be preferred here.
                        $extension->documentation ?
                            A(
                                ["href" => make_link("ext_doc/" . $extension::KEY)],
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
            $form = TABLE(["class" => 'zebra form ext-list'], $tbody);
            Ctx::$page->add_block(new Block($cat, $form, position: 10, id: $cat));
        }

        $cat_html = [
            " ",
        ];
        foreach ($groups as $cat => $group) {
            $cat_html[] = A(["href" => "#".str_replace(" ", "_", $cat)], $cat);
        }

        Ctx::$page->set_title("Extensions");
        Ctx::$page->add_to_navigation(joinHTML(BR(), $cat_html));
    }

    public function display_doc(ExtensionInfo $info): void
    {
        $author = emptyHTML();
        if (count($info->authors) > 0) {
            $author->appendChild(BR());
            $author->appendChild(B(count($info->authors) > 1 ? "Authors: " : "Author: "));
            foreach ($info->authors as $auth => $contact) {
                if (!empty($contact)) {
                    $author->appendChild(A(["href" => $contact], $auth));
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
            P(\MicroHTML\rawHTML($info->documentation ?? "(This extension has no documentation)")),
            // <hr>,
            P(A(["href" => make_link("ext_manager")], "Back to the list"))
        );

        Ctx::$page->set_title("Documentation for {$info->name}");
        Ctx::$page->set_heading($info->name);
        Ctx::$page->add_block(new Block(null, $html));
    }
}
