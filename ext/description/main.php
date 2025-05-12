<?php

declare(strict_types=1);

namespace Shimmie2;

final class ImageDescriptionSetEvent extends Event
{
    public function __construct(
        public int $image_id,
        public string $description
    ) {
        parent::__construct();
    }
}

final class ImageDescription extends Extension
{
    public const KEY = "image_description";

    /** @var ImageDescriptionsTheme */
    protected Themelet $theme;
    
    public function onInitExt(InitExtEvent $event): void
    {
        Image::$prop_types["description"] = ImagePropType::STRING;
    }

    public function onDatabaseUpgrade(DatabaseUpgradeEvent $event): void
    {
        global $database;

        if ($this->get_version() < 1) {
            $database->execute("ALTER TABLE images ADD description TEXT");
            $this->set_version(1);
        }
    }

    public function onImageInfoSet(ImageInfoSetEvent $event): void
    {
        $description = $event->get_param("description");
        if ($description) {
            send_event(new ImageDescriptionSetEvent($event->image->id, $description));
        }
    }

    public function onImageDescriptionSet(ImageDescriptionSetEvent $event): void
    {
        global $database;

        $database->execute("
            UPDATE images 
            SET description = :description 
            WHERE id = :pid
        ", ["pid" => $event->image_id, "description" => $event->description]);
    }

    public function onImageInfoBoxBuilding(ImageInfoBoxBuildingEvent $event): void
    {
        $event->add_part($this->theme->get_description_editor_html($event->image), 35);
    }
}
