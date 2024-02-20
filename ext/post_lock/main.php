<?php

declare(strict_types=1);

namespace Shimmie2;

class LockSetEvent extends Event
{
    public Image $image;
    public bool $locked;

    public function __construct(Image $image, bool $locked)
    {
        parent::__construct();
        $this->image = $image;
        $this->locked = $locked;
    }
}

class PostLock extends Extension
{
    /** @var PostLockTheme */
    protected Themelet $theme;

    public function onImageInfoSet(ImageInfoSetEvent $event): void
    {
        global $page, $user;
        if ($event->image->is_locked() && !$user->can(Permissions::EDIT_IMAGE_LOCK)) {
            throw new PermissionDenied("Error: This image is locked and cannot be edited.");
        }
        if ($user->can(Permissions::EDIT_IMAGE_LOCK)) {
            $locked = $event->get_param('locked') == "on";
            send_event(new LockSetEvent($event->image, $locked));
        }
    }

    public function onLockSet(LockSetEvent $event): void
    {
        global $user;
        if ($user->can(Permissions::EDIT_IMAGE_LOCK)) {
            $event->image->set_locked($event->locked);
        }
    }

    public function onImageInfoBoxBuilding(ImageInfoBoxBuildingEvent $event): void
    {
        $event->add_part($this->theme->get_lock_editor_html($event->image), 42);
    }
}
