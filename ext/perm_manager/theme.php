<?php

declare(strict_types=1);

namespace Shimmie2;

use function MicroHTML\{BR, BUTTON, FORM, INPUT, SPAN, TABLE, TBODY, TD, TEXTAREA, TFOOT, TH, THEAD, TR, emptyHTML};

class PermManagerTheme extends Themelet
{
    /**
     * @param UserClass[] $classes
     * @param array<string, int> $counts
     */
    public function display_create_delete(array $classes, array $counts): void
    {
        $tbody = TBODY();
        foreach ($classes as $class) {
            if ($class->get_parent() === null) {
                continue;
            }
            $actions = emptyHTML();

            // every visible class can be edited, (base and admin
            // can't be edited, but they aren't visible)
            if (in_array(UserClassSource::DATABASE, $class->sources)) {
                $actions->appendChild(FORM(
                    ["action" => make_link("user_class/{$class->name}"), "method" => "GET"],
                    BUTTON("Edit"),
                ));
            } else {
                $actions->appendChild(SHM_SIMPLE_FORM(
                    make_link("user_class/{$class->name}/migrate"),
                    BUTTON("Edit"),
                ));

            }

            // - If a database class overrides a default or file class, it can
            //   be reverted (ie delete the database and fall back to file)
            // - If a database class is unused, it can be deleted
            // - Default and file-based classes can't be deleted
            $form = SHM_FORM(action: make_link("user_class/{$class->name}/delete"));
            if (in_array(UserClassSource::DATABASE, $class->sources) && in_array(UserClassSource::FILE, $class->sources)) {
                $form->appendChild(SHM_SUBMIT("Revert to File"));
            } elseif (in_array(UserClassSource::DATABASE, $class->sources) && in_array(UserClassSource::DEFAULT, $class->sources)) {
                $form->appendChild(SHM_SUBMIT("Revert to Default"));
            } elseif (in_array(UserClassSource::DATABASE, $class->sources)) {
                if ($counts[$class->name] === 0) {
                    $form->appendChild(SHM_SUBMIT("Delete"));
                } else {
                    $form->appendChild(BUTTON(["disabled" => true], "Can't delete in-use classes"));
                }
            } elseif (in_array(UserClassSource::FILE, $class->sources)) {
                $form->appendChild(BUTTON(["disabled" => true], "Can't delete classes from user-classes.conf.php"));
            } elseif (in_array(UserClassSource::DEFAULT, $class->sources)) {
                $form->appendChild(BUTTON(["disabled" => true], "Can't delete default classes"));
            } else {
                $form->appendChild("Confused by" . implode(", ", array_map(fn ($source) => $source->name, $class->sources)));
            }
            $actions->appendChild($form);

            $tbody->appendChild(TR(
                TH($class->name, BR(), "(" . $class->get_parent()->name . ")"),
                TD($counts[$class->name] ?? 0),
                TD($class->description),
                TD($actions)
            ));
        }

        $html = TABLE(
            ["class" => "zebra"],
            THEAD(TR(
                TH("Class", BR(), "(Parent)"),
                TH("Count"),
                TH("Description"),
                TH("Actions"),
            )),
            $tbody,
            TFOOT(SHM_SIMPLE_FORM(
                make_link("user_class"),
                TR(
                    TD(
                        ["colspan" => "2"],
                        INPUT(["type" => "text", "name" => "name"]),
                        BR(),
                        SHM_SELECT("parent", $this->class_list()),
                    ),
                    TD(TEXTAREA(["name" => "description"])),
                    TD(SHM_SUBMIT("Create"))
                )
            ))
        );
        Ctx::$page->add_block(new Block("User Classes", $html, "main", 10));
    }

    /**
     * @return array<string,string>
     */
    protected function class_list(?string $exclude = null): array
    {
        $class_list = [];
        foreach (UserClass::$known_classes as $class) {
            if ($class->name === $exclude) {
                continue;
            }
            $class_list[$class->name] = $class->name;
        }
        return $class_list;
    }

    public function display_edit_class(UserClass $class): void
    {
        $parent = $class->get_parent();
        assert($parent !== null, "Can't edit a class without a parent");
        $parent_name = $parent->name;

        $class_edit = SHM_SIMPLE_FORM(make_link("user_class/{$class->name}"), TABLE(
            ["class" => "form"],
            TR(TH("Name"), TD(INPUT(["type" => "text", "name" => "name", "value" => $class->name, "disabled" => true]))),
            TR(TH("Parent"), TD(SHM_SELECT("parent", $this->class_list(exclude: $class->name), [$parent_name]))),
            TR(TH("Descr."), TD(TEXTAREA(["name" => "description"], $class->description))),
            TR(TD(["colspan" => 2], SHM_SUBMIT("Save")))
        ));
        Ctx::$page->add_block(new Block($class->name, $class_edit, "main", 20));
    }

    /**
     * @param array<string,array<string,PermissionMeta>> $metas
     */
    public function display_edit_permissions(UserClass $class, array $metas): void
    {
        $parent = $class->get_parent();
        assert($parent !== null, "Can't edit permissions without a parent");
        $parent_name = $parent->name;

        $tbody = TBODY();
        foreach ($metas as $ext => $group) {
            foreach ($group as $permission => $meta) {
                $tbody->appendChild(TR(
                    TD($ext),
                    TD(["title" => $permission], $meta->label),
                    TD($parent->can($permission)
                        ? SPAN(["class" => "allowed"], "✔")
                        : SPAN(["class" => "denied"], "✘")),
                    TD($class->can($permission)
                        ? SPAN(["class" => "allowed"], "✔")
                        : SPAN(["class" => "denied"], "✘")),
                    TD(SHM_SELECT(
                        "permissions[$permission]",
                        [
                            "null" => $parent->can($permission) ? "✔ Inherit" : "✘ Inherit",
                            "true" => "✔ Allow",
                            "false" => "✘ Deny"
                        ],
                        [$class->has_own_permission($permission)
                            ? $class->can($permission)
                                ? "true"
                                : "false"
                            : "null"
                        ]
                    ))
                ));
            }
        }
        $perms_edit = SHM_SIMPLE_FORM(make_link("user_class/{$class->name}/permissions"), TABLE(
            ["class" => "zebra", "id" => "permission_table"],
            THEAD(TR(TH("Extension"), TH("Permission"), TH("Parent ($parent_name)"), TH("Current"), TH("Setting"))),
            $tbody,
            TFOOT(TR(TD(["colspan" => 5], SHM_SUBMIT("Save Permissions"))))
        ));

        Ctx::$page->add_block(new Block("Edit Permissions", $perms_edit, "main", 30));
    }

    /**
     * @param UserClass[] $classes
     * @param array<string,array<string,PermissionMeta>> $metas
     */
    public function display_user_classes(array $classes, array $metas): void
    {
        $row = TR();
        $row->appendChild(TH("Extension"));
        $row->appendChild(TH("Permission"));
        foreach ($classes as $class) {
            $n = $class->name;
            if ($class->get_parent()) {
                $n .= " ({$class->get_parent()->name})";
            }
            $row->appendChild(TH($n));
        }
        $row->appendChild(TH("Description"));
        $thead = THEAD($row);

        $tbody = TBODY();
        foreach ($metas as $ext => $group) {
            foreach ($group as $permission => $meta) {
                $row = TR();
                $row->appendChild(TH($ext));
                $row->appendChild(TH(["title" => $permission], $meta->label));

                foreach ($classes as $class) {
                    $inherited = $class->has_own_permission($permission) ? "" : "inherited";
                    if ($class->can($permission)) {
                        $cell = SPAN(["class" => "allowed $inherited"], "✔");
                    } else {
                        $cell = SPAN(["class" => "denied $inherited"], "✘");
                    }
                    $row->appendChild(TD($cell));
                }

                $row->appendChild(TD(["style" => "text-align: left;"], $meta->help));
                $tbody->appendChild($row);
            }
        }

        $table = TABLE(
            ["class" => "zebra", "id" => "permission_table"],
            $thead,
            $tbody
        );

        Ctx::$page->add_block(new Block("Permissions Comparison", $table, "main", 20));
    }
}
