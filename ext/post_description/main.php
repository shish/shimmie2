<?php

declare(strict_types=1);

namespace Shimmie2;

final class PostDescriptionSetEvent extends Event
{
    public function __construct(
        public int $image_id,
        public string $description
    ) {
        parent::__construct();
    }
}

/** @extends Extension<PostDescriptionTheme> */
final class PostDescription extends Extension
{
    public const KEY = "post_description";

    public function onDatabaseUpgrade(DatabaseUpgradeEvent $event): void
    {
        global $database;

        if ($this->get_version() < 1) {
            $database->create_table("image_descriptions", "
                image_id INTEGER NOT NULL,
                description TEXT,
                UNIQUE(image_id),
                FOREIGN KEY (image_id) REFERENCES images(id) ON DELETE CASCADE,
            ");
            $this->set_version(1);
        }
    }

    public function onImageInfoSet(ImageInfoSetEvent $event): void
    {
        $description = $event->get_param("description");
        if (Ctx::$user->can(PostDescriptionPermission::EDIT_IMAGE_DESCRIPTIONS) && $description) {
            send_event(new PostDescriptionSetEvent($event->image->id, $description));
        }
    }

    public function onPostDescriptionSet(PostDescriptionSetEvent $event): void
    {
        global $database;

        $database->execute("
            DELETE
            FROM image_descriptions
            WHERE image_id=:id
        ", ["id" => $event->image_id]);
        $database->execute("
            INSERT
            INTO image_descriptions
            VALUES (:id, :description)
        ", ["id" => $event->image_id, "description" => $event->description]);
    }

    public function onImageInfoBoxBuilding(ImageInfoBoxBuildingEvent $event): void
    {
        global $database;

        $description = (string) $database->get_one(
            "SELECT description FROM image_descriptions WHERE image_id = :id",
            ["id" => $event->image->id]
        ) ?: "None";
        $event->add_part($this->theme->get_description_editor_html($description), 35);
    }
}
