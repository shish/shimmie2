<?php

declare(strict_types=1);

namespace Shimmie2;

use function MicroHTML\TABLE;
use function MicroHTML\TD;
use function MicroHTML\TH;
use function MicroHTML\TR;

class PermManagerTheme extends Themelet
{
    /**
     * @param UserClass[] $classes
     * @param array<string,PermissionMeta> $permissions
     */
    public function display_user_classes(array $classes, array $permissions): void
    {
        global $page;
        $table = TABLE(["class" => "zebra"]);

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
                $opacity = $class->has_own_permission($name) ? 1 : 0.2;
                if ($class->can($name)) {
                    $cell = TD(["style" => "color: green; opacity: $opacity;"], "✔");
                } else {
                    $cell = TD(["style" => "color: red; opacity: $opacity;"], "✘");
                }
                $row->appendChild($cell);
            }

            $row->appendChild(TD(["style" => "text-align: left;"], $meta->help));
            $table->appendChild($row);
        }

        $page->set_title("User Classes");
        $this->display_navigation();
        $page->add_block(new Block("Classes", $table, "main", 10));
    }
}
