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

    #[EventListener]
    public function onImageInfoSet(ImageInfoSetEvent $event): void
    {
        $owner = $event->get_param('owner');
        if (Ctx::$user->can(PostOwnerPermission::EDIT_IMAGE_OWNER) && !is_null($owner)) {
            $owner_ob = User::by_name($owner);
            send_event(new OwnerSetEvent($event->image, $owner_ob));
        }
    }

    #[EventListener]
    public function onOwnerSet(OwnerSetEvent $event): void
    {
        if (Ctx::$user->can(PostOwnerPermission::EDIT_IMAGE_OWNER)) {
            $event->image->set_owner($event->owner);
        }
    }

    #[EventListener]
    public function onImageInfoBoxBuilding(ImageInfoBoxBuildingEvent $event): void
    {
        $event->add_part($this->theme->get_owner_editor_html($event->image), 39);

        // Add avatar to sidebar
        /** @var BuildAvatarEvent $bae */
        $bae = send_event(new BuildAvatarEvent($event->image->get_owner()));
        if ($bae->html) {
            $event->add_sidebar_part($bae->html, 10);
        }
    }
}
