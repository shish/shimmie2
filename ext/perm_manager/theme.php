<?php

declare(strict_types=1);

namespace Shimmie2;

use function MicroHTML\TABLE;
use function MicroHTML\TD;
use function MicroHTML\TH;
use function MicroHTML\TR;
use function MicroHTML\SPAN;

class PermManagerTheme extends Themelet
{
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
        Ctx::$page->add_block(new Block("Classes", $table, "main", 10));
    }
}
