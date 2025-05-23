<?php

declare(strict_types=1);

namespace Shimmie2;

final class OwnerSetEvent extends Event
{
    public function __construct(
        public Image $image,
        public User $owner
    ) {
        parent::__construct();
    }
}

/** @extends Extension<PostOwnerTheme> */
final class PostOwner extends Extension
{
    public const KEY = "post_owner";

    public function onImageInfoSet(ImageInfoSetEvent $event): void
    {
        $owner = $event->get_param('owner');
        if (Ctx::$user->can(PostOwnerPermission::EDIT_IMAGE_OWNER) && !is_null($owner)) {
            $owner_ob = User::by_name($owner);
            send_event(new OwnerSetEvent($event->image, $owner_ob));
        }
    }

    public function onOwnerSet(OwnerSetEvent $event): void
    {
        if (Ctx::$user->can(PostOwnerPermission::EDIT_IMAGE_OWNER)) {
            $event->image->set_owner($event->owner);
        }
    }

    public function onImageInfoBoxBuilding(ImageInfoBoxBuildingEvent $event): void
    {
        $event->add_part($this->theme->get_owner_editor_html($event->image), 39);
    }
}
