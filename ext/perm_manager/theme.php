<?php

declare(strict_types=1);

namespace Shimmie2;

use function MicroHTML\LABEL;
use function MicroHTML\A;
use function MicroHTML\B;
use function MicroHTML\TABLE;
use function MicroHTML\THEAD;
use function MicroHTML\TFOOT;
use function MicroHTML\TBODY;
use function MicroHTML\TH;
use function MicroHTML\TR;
use function MicroHTML\TD;
use function MicroHTML\INPUT;

class PermManagerTheme extends Themelet
{
    /**
     * @param String[] $parent_options
     * @param String[] $permissions
     */
    public function display_table(Page $page, bool $is_parent, array $parent_options, array $permissions, UserClass $class): void
    {
        $editable = !is_null($class->parent);

        $parent_form = $editable ? SHM_SIMPLE_FORM(
            "perm_manager/{$class->name}/set_parent",
            LABEL(["for" => "parent"], "Parent class: "),
            SHM_SELECT("parent", $parent_options, required: true, selected_options: $class->parent ? [$class->parent->name] : ["base"]),
            INPUT(["type" => 'submit', "value" => 'Set Parent'])
        ) : null;

        $tbody = TBODY();

        $perm_form = SHM_SIMPLE_FORM(
            "perm_manager/{$class->name}/set_perms",
            $is_parent ? B("WARNING: This class is a parent to another class. Modifying this class will do the same for any child classes.") : null,
            TABLE(
                ["id" => 'permissions', "class" => 'zebra'],
                THEAD(TR(
                    TH("Enabled"),
                    TH("Name"),
                )),
                $tbody,
                $editable ? TFOOT(TR(TD(["colspan" => '5'], INPUT(["type" => 'submit', "value" => 'Set Permissions'])))) : null
            )
        );

        foreach ($permissions as $permission) {
            $tbody->appendChild(TR(
                ["data-ext" => $permission],
                TD(INPUT([
                    "type" => 'checkbox',
                    "name" => "perm_{$permission}",
                    "id" => "perm_{$permission}",
                    "checked" => $class->can($permission),
                    "disabled" => !$editable || ($class->parent && $class->parent->can($permission))
                ])),
                TD(LABEL(
                    ["for" => "perm_{$permission}"],
                    (
                        $permission
                    )
                )),
            ));
        }

        $delete_form = $editable && !$is_parent && !$class->core ? SHM_SIMPLE_FORM(
            "perm_manager/{$class->name}/delete",
            TABLE(
                ["id" => 'delete_class'],
                TBODY(
                    TR(TD(
                        LABEL(["for" => "name"], "Type class name to confirm: "),
                        INPUT(["type" => 'text', "name" => "name", "placeholder" => $class->name, "required" => ""]),
                    )),
                    TR(TD(
                        INPUT(["type" => 'submit', "value" => 'Delete class'])
                    ))
                )
            )
        ) : null;

        $title = ($editable ? "Edit" : "View") . " Class - " . $class->name;

        $page->set_title($title);
        $page->set_heading("Permissions");
        $page->add_block(new Block($title, ""));
        if ($editable) {
            $page->add_block(new Block("Parent", $parent_form));
        }
        $page->add_block(new Block("Permissions", $perm_form));
        if ($editable && !$is_parent && !$class->core) {
            $page->add_block(new Block("Delete class", $delete_form));
        }
    }

    /**
     * @param UserClass[] $classes
     * @param string[] $parent_options
     */
    public function display_list(Page $page, array $classes, array $parent_options): void
    {
        $tbody = TBODY();

        $table = TABLE(
            ["id" => 'permissions', "class" => 'zebra'],
            THEAD(TR(
                TH("Name"),
                TH("Details"),
            )),
            $tbody
        );
        foreach ($classes as $class) {
            $tbody->appendChild(TR(
                TD($class->name),
                TD(
                    A(["href" => "perm_manager/{$class->name}"], $class->parent ? "Edit" : "View")
                ),
            ));
        }

        $new_form = SHM_SIMPLE_FORM(
            "perm_manager/new",
            TABLE(
                ["id" => 'new_class'],
                TBODY(
                    TR(TD(
                        LABEL(["for" => "new_name"], "Class name: "),
                        INPUT(["type" => 'text', "name" => "new_name", "required" => ""]),
                    )),
                    TR(TD(
                        LABEL(["for" => "new_parent"], "Parent class: "),
                        SHM_SELECT("new_parent", $parent_options, required: true, selected_options: ["base"]),
                    )),
                    TR(TD(
                        INPUT(["type" => 'submit', "value" => 'Add class'])
                    ))
                )
            )
        );

        $page->set_title("User Classes");
        $page->set_heading("User Classes");
        $page->add_block(new Block("User Classes", $table));
        $page->add_block(new Block("New Class", $new_form));
    }
}
