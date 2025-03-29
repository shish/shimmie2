<?php

declare(strict_types=1);

namespace Shimmie2;

use function MicroHTML\FORM;
use function MicroHTML\TABLE;
use function MicroHTML\TBODY;
use function MicroHTML\TD;
use function MicroHTML\TFOOT;
use function MicroHTML\TH;
use function MicroHTML\THEAD;
use function MicroHTML\TR;
use function MicroHTML\SPAN;
use function MicroHTML\emptyHTML;
use function MicroHTML\INPUT;
use function MicroHTML\BUTTON;

class PermManagerTheme extends Themelet
{
    /**
     * @param UserClass[] $classes
     * @param array<string, int> $counts
     */
    public function display_add_remove(array $classes, array $counts): void
    {
        $tbody = TBODY();
        foreach ($classes as $class) {
            if ($class->get_parent() === null) {
                continue;
            }
            $actions = emptyHTML();

            // every visible class can be edited, (base and admin
            // can't be edited, but they aren't visible)
            $actions->appendChild(FORM(
                ["action" => make_link("user_class/{$class->name}"), "method" => "GET"],
                BUTTON("Edit"),
            ));

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
                if ($counts[$class->name] == 0) {
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
                TD($class->name),
                TD($class->get_parent()->name),
                TD($counts[$class->name] ?? 0),
                TD($class->description),
                TD($actions)
            ));
        }

        $class_list = [];
        foreach ($classes as $class) {
            $class_list[$class->name] = $class->name;
        }
        $html = TABLE(
            ["class" => "zebra form"],
            THEAD(TR(
                TH("Class"),
                TH("Base"),
                TH("Count"),
                TH("Description"),
                TH("Actions"),
            )),
            $tbody,
            TFOOT(SHM_SIMPLE_FORM(
                make_link("user_classes"),
                TR(
                    TD(INPUT(["type" => "text", "name" => "name"])),
                    TD(SHM_SELECT("parent", $class_list)),
                    TD(),
                    TD(INPUT(["type" => "text", "name" => "description"])),
                    TD(SHM_SUBMIT("Create"))
                )
            ))
        );
        Ctx::$page->add_block(new Block("Summary", $html, "main", 10));
    }

    /**
     * @param UserClass[] $classes
     * @param array<string,PermissionMeta> $permissions
     */
    public function display_user_classes(array $classes, array $permissions): void
    {
        $table = TABLE(["class" => "zebra", "id" => "permission_table"]);

        $row = TR();
        $row->appendChild(TH("Permission"));
        foreach ($classes as $class) {
            $n = $class->name;
            if ($class->get_parent()) {
                $n .= " ({$class->get_parent()->name})";
            }
            $row->appendChild(TH($n));
        }
        $row->appendChild(TH("Description"));
        $table->appendChild($row);

        foreach ($permissions as $name => $meta) {
            $row = TR();
            $row->appendChild(TH($meta->label));

            foreach ($classes as $class) {
                $inherited = $class->has_own_permission($name) ? "" : "inherited";
                if ($class->can($name)) {
                    $cell = SPAN(["class" => "allowed $inherited;"], "✔");
                } else {
                    $cell = SPAN(["class" => "denied $inherited"], "✘");
                }
                $row->appendChild(TD($cell));
            }

            $row->appendChild(TD(["style" => "text-align: left;"], $meta->help));
            $table->appendChild($row);
        }

        Ctx::$page->set_title("User Classes");
        $this->display_navigation();
        Ctx::$page->add_block(new Block("Classes", $table, "main", 20));
    }
}
