<?php

declare(strict_types=1);

namespace Shimmie2;

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
    protected Themelet $theme;

    public const NAME = "Relationships";

    public function onInitExt(InitExtEvent $event): void
    {
        Image::$prop_types["parent_id"] = ImagePropType::INT;
        Image::$prop_types["has_children"] = ImagePropType::BOOL;
    }

    public function onDatabaseUpgrade(DatabaseUpgradeEvent $event): void
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

    public function onImageInfoSet(ImageInfoSetEvent $event): void
    {
        global $user;
        if ($user->can(Permissions::EDIT_IMAGE_RELATIONSHIPS)) {
            if (isset($event->params['tags']) ? !preg_match('/parent[=|:]/', $event->params["tags"]) : true) { //Ignore parent if tags contain parent metatag
                if (isset($event->params["parent"]) ? ctype_digit($event->params["parent"]) : false) {
                    send_event(new ImageRelationshipSetEvent($event->image->id, (int) $event->params["parent"]));
                } else {
                    $this->remove_parent($event->image->id);
                }
            }
        }
    }

    public function onDisplayingImage(DisplayingImageEvent $event): void
    {
        $this->theme->relationship_info($event->image);
    }

    public function onSearchTermParse(SearchTermParseEvent $event): void
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
                $event->add_querylet(new Querylet("images.parent_id = :pid", ["pid" => $parentID]));
            }
        } elseif (preg_match("/^child[=|:](any|none)$/", $event->term, $matches)) {
            $not = ($matches[1] == "any" ? "=" : "!=");
            $event->add_querylet(new Querylet("images.has_children $not :true", ["true" => true]));
        }
    }

    public function onHelpPageBuilding(HelpPageBuildingEvent $event): void
    {
        if ($event->key === HelpPages::SEARCH) {
            $block = new Block();
            $block->header = "Relationships";
            $block->body = $this->theme->get_help_html();
            $event->add_block($block);
        }
    }

    public function onTagTermCheck(TagTermCheckEvent $event): void
    {
        if (preg_match("/^(parent|child)[=|:](.*)$/i", $event->term)) {
            $event->metatag = true;
        }
    }

    public function onTagTermParse(TagTermParseEvent $event): void
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

    public function onImageInfoBoxBuilding(ImageInfoBoxBuildingEvent $event): void
    {
        $event->add_part($this->theme->get_parent_editor_html($event->image), 45);
    }

    public function onImageDeletion(ImageDeletionEvent $event): void
    {
        global $database;

        if (bool_escape($event->image['has_children'])) {
            $database->execute("UPDATE images SET parent_id = NULL WHERE parent_id = :iid", ["iid" => $event->image->id]);
        }

        if ($event->image['parent_id'] !== null) {
            $this->set_has_children($event->image['parent_id']);
        }
    }

    public function onImageRelationshipSet(ImageRelationshipSetEvent $event): void
    {
        global $database;

        $old_parent = $database->get_one("SELECT parent_id FROM images WHERE id = :cid", ["cid" => $event->child_id]);
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
        $database->execute("UPDATE images SET has_children = :true WHERE id = :pid", ["pid" => $event->parent_id, "true" => true]);

        if ($old_parent != null) {
            $this->set_has_children($old_parent);
        }
    }

    /**
     * @return Image[]
     */
    public static function get_children(int $image_id): array
    {
        global $database;
        $child_ids = $database->get_col("SELECT id FROM images WHERE parent_id = :pid ", ["pid" => $image_id]);

        return Search::get_images($child_ids);
    }

    private function remove_parent(int $imageID): void
    {
        global $database;
        $parentID = $database->get_one("SELECT parent_id FROM images WHERE id = :iid", ["iid" => $imageID]);

        if ($parentID) {
            $database->execute("UPDATE images SET parent_id = NULL WHERE id = :iid", ["iid" => $imageID]);
            $this->set_has_children((int)$parentID);
        }
    }

    private function set_has_children(int $parent_id): void
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
            ["pid" => $parent_id]
        );
        $database->execute(
            "UPDATE images SET has_children = :has_children WHERE id = :pid",
            ["has_children" => $children > 0, "pid" => $parent_id]
        );
    }

    public static function has_siblings(int $image_id): bool
    {
        global $database;

        $image = Image::by_id_ex($image_id);

        $count = $database->get_one(
            "SELECT COUNT(*) FROM images WHERE id!=:id AND parent_id=:pid",
            ["id" => $image_id, "pid" => $image['parent_id']]
        );

        return $count > 0;
    }

    /**
     * @return Image[]
     */
    public static function get_siblings(int $image_id): array
    {
        global $database;

        $image = Image::by_id_ex($image_id);

        $sibling_ids = $database->get_col(
            "SELECT id FROM images WHERE id!=:id AND parent_id=:pid",
            ["id" => $image_id, "pid" => $image['parent_id']]
        );
        $siblings = Search::get_images($sibling_ids);

        return $siblings;
    }
}
