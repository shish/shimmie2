<?php

declare(strict_types=1);

namespace Shimmie2;

use function MicroHTML\{A, B, BR, DIV, IMG, INPUT, LABEL, P, TABLE, TBODY, TD, TFOOT, TR, emptyHTML};

class ExtManagerTheme extends Themelet
{
    /**
     * @param ExtensionInfo[] $extensions
     */
    public function display_table(array $extensions, bool $editable): void
    {
        $tbody = TBODY();

        $form = SHM_SIMPLE_FORM(
            make_link("ext_manager/set"),
            TABLE(
                ["id" => 'extensions', "class" => 'zebra form'],
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

            if ($extension->category !== $last_cat) {
                $last_cat = $extension->category;
                $categories[] = $last_cat;
                $tbody->appendChild(
                    TR(
                        ["class" => 'category', "id" => $extension->category->value],
                        TD(),
                        TD(["colspan" => '5'], BR(), B($last_cat->value))
                    )
                );
            }

            $tbody->appendChild(TR(
                ["data-ext" => $extension->name],
                $editable ? TD(INPUT([
                    "type" => 'checkbox',
                    "name" => "extensions[]",
                    "id" => "ext_" . $extension::KEY,
                    "value" => $extension::KEY,
                    "checked" => ($extension::is_enabled() === true),
                    "disabled" => ($extension->is_supported() === false || $extension->core === true)
                ])) : null,
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

        if ($editable) {
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
        }

        $cat_html = [
            " ",
        ];
        foreach ($categories as $cat) {
            $cat_html[] = A(["href" => "#".$cat->value], $cat->value);
        }

        Ctx::$page->set_title("Extensions");
        $this->display_navigation(extra: \MicroHTML\joinHTML(BR(), $cat_html));
        Ctx::$page->add_block(new Block(null, $form));
    }

    public function display_doc(ExtensionInfo $info): void
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
            P(\MicroHTML\rawHTML($info->documentation ?? "(This extension has no documentation)")),
            // <hr>,
            P(A(["href" => make_link("ext_manager")], "Back to the list"))
        );

        Ctx::$page->set_title("Documentation for {$info->name}");
        Ctx::$page->set_heading($info->name);
        $this->display_navigation();
        Ctx::$page->add_block(new Block(null, $html));
    }
}
