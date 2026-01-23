<?php

declare(strict_types=1);

namespace Shimmie2;

final class LockSetEvent extends Event
{
    public function __construct(
        public Image $image,
        public bool $locked
    ) {
        parent::__construct();
    }
}

/** @extends Extension<PostLockTheme> */
final class PostLock extends Extension
{
    public const KEY = "post_lock";

    #[EventListener]
    public function onImageInfoSet(ImageInfoSetEvent $event): void
    {
        if ($event->image->is_locked() && !Ctx::$user->can(PostLockPermission::EDIT_IMAGE_LOCK)) {
            throw new PermissionDenied("Error: This image is locked and cannot be edited.");
        }
        if (Ctx::$user->can(PostLockPermission::EDIT_IMAGE_LOCK)) {
            $locked = $event->get_param('locked') === "on";
            send_event(new LockSetEvent($event->image, $locked));
        }
    }

    #[EventListener]
    public function onLockSet(LockSetEvent $event): void
    {
        if (Ctx::$user->can(PostLockPermission::EDIT_IMAGE_LOCK)) {
            $event->image->set_locked($event->locked);
        }
    }

    #[EventListener]
    public function onImageInfoBoxBuilding(ImageInfoBoxBuildingEvent $event): void
    {
        $event->add_part($this->theme->get_lock_editor_html($event->image), 42);
    }
}
