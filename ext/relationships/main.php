<?php declare(strict_types=1);

class ImageRelationshipSetEvent extends Event
{
    public int $child_id;
    public int $parent_id;

    public function __construct(int $child_id, int $parent_id)
    {
        parent::__construct();
        $this->child_id = $child_id;
        $this->parent_id = $parent_id;
    }
}


class Relationships extends Extension
{
    /** @var RelationshipsTheme */
    protected ?Themelet $theme;

    public const NAME = "Relationships";

    public function onInitExt(InitExtEvent $event)
    {
        Image::$bool_props[] = "has_children";
        Image::$int_props[] = "parent_id";
    }

    public function onDatabaseUpgrade(DatabaseUpgradeEvent $event)
    {
        global $database;

        if ($this->get_version("ext_relationships_version") < 1) {
            $database->execute("ALTER TABLE images ADD parent_id INT");
            $database->execute("ALTER TABLE images ADD has_children BOOLEAN DEFAULT FALSE NOT NULL");
            $database->execute("CREATE INDEX images__parent_id ON images(parent_id)");
            $database->execute("CREATE INDEX images__has_children ON images(has_children)");
            $this->set_version("ext_relationships_version", 3);
        }
        if ($this->get_version("ext_relationships_version") < 2) {
            $database->execute("CREATE INDEX images__has_children ON images(has_children)");
            $this->set_version("ext_relationships_version", 2);
        }
        if ($this->get_version("ext_relationships_version") < 3) {
            $database->standardise_boolean("images", "has_children");
            $this->set_version("ext_relationships_version", 3);
        }
    }

    public function onImageInfoSet(ImageInfoSetEvent $event)
    {
        global $user;
        if ($user->can(Permissions::EDIT_IMAGE_RELATIONSHIPS)) {
            if (isset($_POST['tag_edit__tags']) ? !preg_match('/parent[=|:]/', $_POST["tag_edit__tags"]) : true) { //Ignore tag_edit__parent if tags contain parent metatag
                if (isset($_POST["tag_edit__parent"]) ? ctype_digit($_POST["tag_edit__parent"]) : false) {
                    send_event(new ImageRelationshipSetEvent($event->image->id, (int) $_POST["tag_edit__parent"]));
                } else {
                    $this->remove_parent($event->image->id);
                }
            }
        }
    }

    public function onDisplayingImage(DisplayingImageEvent $event)
    {
        $this->theme->relationship_info($event->image);
    }

    public function onSearchTermParse(SearchTermParseEvent $event)
    {
        if (is_null($event->term)) {
            return;
        }

        $matches = [];
        if (preg_match("/^parent[=|:]([0-9]+|any|none)$/", $event->term, $matches)) {
            $parentID = $matches[1];

            if (preg_match("/^(any|none)$/", $parentID)) {
                $not = ($parentID == "any" ? "NOT" : "");
                $event->add_querylet(new Querylet("images.parent_id IS $not NULL"));
            } else {
                $event->add_querylet(new Querylet("images.parent_id = :pid", ["pid"=>$parentID]));
            }
        } elseif (preg_match("/^child[=|:](any|none)$/", $event->term, $matches)) {
            $not = ($matches[1] == "any" ? "=" : "!=");
            $event->add_querylet(new Querylet("images.has_children $not :true", ["true"=>true]));
        }
    }

    public function onHelpPageBuilding(HelpPageBuildingEvent $event)
    {
        if ($event->key===HelpPages::SEARCH) {
            $block = new Block();
            $block->header = "Relationships";
            $block->body = $this->theme->get_help_html();
            $event->add_block($block);
        }
    }

    public function onTagTermCheck(TagTermCheckEvent $event)
    {
        if (preg_match("/^(parent|child)[=|:](.*)$/i", $event->term)) {
            $event->metatag = true;
        }
    }

    public function onTagTermParse(TagTermParseEvent $event)
    {
        $matches = [];

        if (preg_match("/^parent[=|:]([0-9]+|none)$/", $event->term, $matches)) {
            $parentID = $matches[1];
            if ($parentID == "none" || $parentID == "0") {
                $this->remove_parent($event->image_id);
            } else {
                send_event(new ImageRelationshipSetEvent($event->image_id, (int)$parentID));
            }
        } elseif (preg_match("/^child[=|:]([0-9]+)$/", $event->term, $matches)) {
            $childID = $matches[1];
            send_event(new ImageRelationshipSetEvent((int)$childID, $event->image_id));
        }
    }

    public function onImageInfoBoxBuilding(ImageInfoBoxBuildingEvent $event)
    {
        $event->add_part($this->theme->get_parent_editor_html($event->image), 45);
    }

    public function onImageDeletion(ImageDeletionEvent $event)
    {
        global $database;

        if (bool_escape($event->image->has_children)) {
            $database->execute("UPDATE images SET parent_id = NULL WHERE parent_id = :iid", ["iid"=>$event->image->id]);
        }

        if ($event->image->parent_id !== null) {
            $this->set_has_children($event->image->parent_id);
        }
    }

    public function onImageRelationshipSet(ImageRelationshipSetEvent $event)
    {
        global $database;

        $old_parent = $database->get_one("SELECT parent_id FROM images WHERE id = :cid", ["cid"=>$event->child_id]);
        if (!is_null($old_parent)) {
            $old_parent = (int)$old_parent;
        }

        if ($old_parent == $event->parent_id) {
            return;  // no change
        }
        if (!Image::by_id($event->parent_id) || !Image::by_id($event->child_id)) {
            return;  // one of the images doesn't exist
        }

        $database->execute("UPDATE images SET parent_id = :pid WHERE id = :cid", ["pid" => $event->parent_id, "cid" => $event->child_id]);
        $database->execute("UPDATE images SET has_children = :true WHERE id = :pid", ["pid" => $event->parent_id, "true"=>true]);

        if ($old_parent!=null) {
            $this->set_has_children($old_parent);
        }
    }

    public static function get_children(Image $image, int $omit = null): array
    {
        global $database;
        $results = $database->get_all_iterable("SELECT * FROM images WHERE parent_id = :pid ", ["pid"=>$image->id]);
        $output = [];
        foreach ($results as $result) {
            if ($result["id"]==$omit) {
                continue;
            }
            $output[] = new Image($result);
        }
        return $output;
    }

    private function remove_parent(int $imageID)
    {
        global $database;
        $parentID = $database->get_one("SELECT parent_id FROM images WHERE id = :iid", ["iid"=>$imageID]);

        if ($parentID) {
            $database->execute("UPDATE images SET parent_id = NULL WHERE id = :iid", ["iid"=>$imageID]);
            $this->set_has_children((int)$parentID);
        }
    }

    private function set_has_children(int $parent_id)
    {
        global $database;

        // Doesn't work on pgsql
        // $database->execute("
        //     UPDATE images
        //     SET has_children = (SELECT * FROM (SELECT CASE WHEN COUNT(*) > 0 THEN 1 ELSE 0 END FROM images WHERE parent_id = :pid) AS sub)
        //	   WHERE id = :pid
        // ", ["pid"=>$parentID]);

        $children = $database->get_one(
            "SELECT COUNT(*) FROM images WHERE parent_id=:pid",
            ["pid"=>$parent_id]
        );
        $database->execute(
            "UPDATE images SET has_children = :has_children WHERE id = :pid",
            ["has_children"=>$children>0, "pid"=>$parent_id]
        );
    }
}
