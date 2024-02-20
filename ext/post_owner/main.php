<?php

declare(strict_types=1);

namespace Shimmie2;

class OwnerSetEvent extends Event
{
    public Image $image;
    public User $owner;

    public function __construct(Image $image, User $owner)
    {
        parent::__construct();
        $this->image = $image;
        $this->owner = $owner;
    }
}

class PostOwner extends Extension
{
    /** @var PostOwnerTheme */
    protected Themelet $theme;

    public function onImageInfoSet(ImageInfoSetEvent $event): void
    {
        global $page, $user;
        $owner = $event->get_param('owner');
        if ($user->can(Permissions::EDIT_IMAGE_OWNER) && !is_null($owner)) {
            $owner_ob = User::by_name($owner);
            if (!is_null($owner_ob)) {
                send_event(new OwnerSetEvent($event->image, $owner_ob));
            } else {
                throw new UserNotFound("Error: No user with that name was found.");
            }
        }
    }

    public function onOwnerSet(OwnerSetEvent $event): void
    {
        global $user;
        if ($user->can(Permissions::EDIT_IMAGE_OWNER)) {
            $event->image->set_owner($event->owner);
        }
    }

    public function onImageInfoBoxBuilding(ImageInfoBoxBuildingEvent $event): void
    {
        $event->add_part($this->theme->get_owner_editor_html($event->image), 39);
    }
}
